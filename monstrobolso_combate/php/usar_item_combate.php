<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, private");

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/log_combate.php";
require_once __DIR__ . "/experiencia.php";
require_once __DIR__ . "/token_combate.php";
require_once __DIR__ . "/../app/bootstrap.php";

use Maremon\Services\ItemService;
use Maremon\Services\CombateMotor;
use Maremon\Services\EquipeCombate;
use Maremon\Services\BossService;
use Maremon\Models\CombateRepository;
use Maremon\Exceptions\JogoException;

function saidaItemBatalha(array $dados, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    saidaItemBatalha([
        "sucesso" => false,
        "erro" => "Utilize POST."
    ], 405);
}

$dados = json_decode(file_get_contents("php://input"), true);

if (!is_array($dados)) {
    saidaItemBatalha([
        "sucesso" => false,
        "erro" => "Dados inválidos."
    ], 400);
}

$combate = $_SESSION["combate"] ?? null;

if (
    !is_array($combate) ||
    !($combate["ativo"] ?? false) ||
    (int) ($combate["pescador_id"] ?? 0) !== $pescadorId
) {
    saidaItemBatalha([
        "sucesso" => false,
        "erro" => "Combate inválido ou encerrado."
    ], 409);
}

exigirTokenCombate($combate, $dados, "csrf_token");

$itemId = filter_var($dados["item_id"] ?? null, FILTER_VALIDATE_INT);
$pescadoId = filter_var($dados["pescado_id"] ?? null, FILTER_VALIDATE_INT);

if (!$itemId || !$pescadoId) {
    saidaItemBatalha([
        "sucesso" => false,
        "erro" => "Escolha inválida."
    ], 400);
}

try {
    if (EquipeCombate::exigeTroca($combate)) {
        throw new JogoException("Troque de Pescado antes de utilizar itens.");
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id
        FROM public.pescador
        WHERE id = :id
        FOR UPDATE
    ");

    $stmt->execute(["id" => $pescadorId]);

    if (!$stmt->fetch()) {
        throw new JogoException("Jogador não encontrado.");
    }

    $stmt = $pdo->prepare("
        SELECT
            i.nome,
            i.efeito,
            i.valor_efeito,
            pi.quantidade
        FROM public.pescador_itens pi
        JOIN public.loja_itens i ON i.id = pi.item_id
        WHERE pi.pescador_id = :id
          AND pi.item_id = :item
        FOR UPDATE OF pi
    ");

    $stmt->execute([
        "id" => $pescadorId,
        "item" => $itemId
    ]);

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item || (int) $item["quantidade"] < 1) {
        throw new JogoException("Este item acabou.");
    }

    $stmt = $pdo->prepare("
        SELECT
            pp.hp_atual AS hp,
            pp.status,
            pp.status_turnos,
            pp.estagio_evolucao,
            s.hp + 2 * GREATEST(pp.nivel - 5, 0)
                + 10 * pp.bonus_evolucao_legado AS hp_max
        FROM public.pescador_pescado pp
        JOIN public.stats s ON s.pescado_id = pp.pescado_id
        WHERE pp.pescador_id = :id
          AND pp.pescado_id = :pescado
        FOR UPDATE OF pp
    ");

    $stmt->execute([
        "id" => $pescadorId,
        "pescado" => $pescadoId
    ]);

    $alvo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$alvo) {
        throw new JogoException("Você não possui este Pescado.");
    }

    // HP da sessão tem prioridade sobre a leitura do banco.
    // Apenas integrantes da equipe atual podem receber itens na batalha.
    EquipeCombate::sincronizar($combate);

    if (!isset($combate["equipe"][$pescadoId])) {
        throw new JogoException("Use itens somente nos integrantes da equipe.");
    }

    $ativo = (int) $combate["jogador"]["id"] === $pescadoId;
    $alvo = array_merge($alvo, $combate["equipe"][$pescadoId]);

    if ($alvo["hp"] === null) {
        $alvo["hp"] = $alvo["hp_max"];
    }

    $hpAnterior = (int) $alvo["hp"];
    $curado = (new ItemService())->aplicar($item, $alvo, true);

    $stmt = $pdo->prepare("
        UPDATE public.pescador_pescado
        SET hp_atual = :hp,
            status = :status,
            status_turnos = :turnos
        WHERE pescador_id = :id
          AND pescado_id = :pescado
    ");

    $stmt->execute([
        "hp" => (int) $curado["hp"],
        "status" => $curado["status"],
        "turnos" => (int) $curado["status_turnos"],
        "id" => $pescadorId,
        "pescado" => $pescadoId
    ]);

    $stmt = $pdo->prepare("
        UPDATE public.pescador_itens
        SET quantidade = quantidade - 1
        WHERE pescador_id = :id
          AND item_id = :item
          AND quantidade > 0
    ");

    $stmt->execute([
        "id" => $pescadorId,
        "item" => $itemId
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new JogoException("Estoque insuficiente.");
    }

    $stmt = $pdo->prepare("
        DELETE FROM public.pescador_itens
        WHERE pescador_id = :id
          AND item_id = :item
          AND quantidade = 0
    ");

    $stmt->execute([
        "id" => $pescadorId,
        "item" => $itemId
    ]);

    foreach (["hp", "status", "status_turnos"] as $chave) {
        $combate["equipe"][$pescadoId][$chave] = $curado[$chave];

        if ($ativo) {
            $combate["jogador"][$chave] = $curado[$chave];
        }
    }

    // Usar um item consome o turno; o inimigo pode responder.
    $repo = new CombateRepository($pdo);
    $ataques = $repo->ataques(
        (int) $combate["inimigo"]["id"],
        (int) $combate["inimigo"]["nivel"]
    );

    if (!$ataques) {
        throw new JogoException("O inimigo não possui ataques.");
    }

    $ataqueInimigo = $ataques[random_int(0, count($ataques) - 1)];
    $motor = new CombateMotor();
    // Um Pescado nocauteado exige troca; usar Revive no reserva não
    // pode fazer um oponente atacar o membro já nocauteado.
    $forcarTroca = EquipeCombate::exigeTroca($combate);
    $eventos = $forcarTroca ? [] : $motor->contraAtaque(
        $combate["jogador"],
        $combate["inimigo"],
        $ataqueInimigo
    );

    EquipeCombate::sincronizar($combate);
    $resultado = EquipeCombate::resultado($combate);

    // Vitória por veneno ou queimadura também concede a recompensa normal.
    $expGanha = $resultado === "vitoria"
        ? (!empty($combate["boss_ordem"]) ? 2500 : 100)
        : 0;
    $moedasGanhas = $resultado === "vitoria" ? 25 : 0;

    if ($expGanha > 0) {
        $stmt = $pdo->prepare("
            SELECT nivel, COALESCE(exp, 0) AS exp
            FROM public.pescador_pescado
            WHERE pescador_id = :id
              AND pescado_id = :pescado
            FOR UPDATE
        ");

        $stmt->execute([
            "id" => $pescadorId,
            "pescado" => (int) $combate["jogador"]["id"]
        ]);

        $progresso = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$progresso) {
            throw new JogoException("Pescado ativo não encontrado.");
        }

        $expTotal = (int) $progresso["exp"] + $expGanha;
        $nivelNovo = calcularNivel((int) $progresso["nivel"], $expTotal);
        $ganhos = $nivelNovo - (int) $progresso["nivel"];

        if ($ganhos > 0) {
            $combate["jogador"]["nivel"] = $nivelNovo;
            $combate["jogador"]["hp_max"] += 2 * $ganhos;

            foreach (["atk", "def", "spe"] as $campo) {
                $combate["jogador"][$campo] += $ganhos;
            }

            $combate["jogador"]["hp"] = min(
                (int) $combate["jogador"]["hp_max"],
                (int) $combate["jogador"]["hp"] + 2 * $ganhos
            );
        }

        $stmt = $pdo->prepare("
            UPDATE public.pescador_pescado
            SET nivel = :nivel, exp = :exp
            WHERE pescador_id = :id
              AND pescado_id = :pescado
        ");

        $stmt->execute([
            "nivel" => $nivelNovo,
            "exp" => $expTotal,
            "id" => $pescadorId,
            "pescado" => (int) $combate["jogador"]["id"]
        ]);

        $stmt = $pdo->prepare("
            UPDATE public.pescador
            SET dinheiro = COALESCE(dinheiro, 0) + :moedas
            WHERE id = :id
        ");

        $stmt->execute([
            "moedas" => $moedasGanhas,
            "id" => $pescadorId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new JogoException("Falha ao creditar moedas.");
        }
    }

    // Salvar os três integrantes com HP e status próprios.
    EquipeCombate::salvar($pdo, $pescadorId, $combate);

    atualizarLogCombate(
        $pdo,
        (int) $combate["log_id"],
        $pescadorId,
        $resultado === "continuar" ? "ativo" : $resultado,
        (int) $combate["turno"],
        $moedasGanhas
    );

    $bossVitoria = $resultado === "vitoria"
        ? BossService::registrarVitoria($pdo, $pescadorId, $combate)
        : null;

    $pdo->commit();

    if ($resultado !== "continuar") {
        $combate["ativo"] = false;
    }

    $combate["turno"]++;
    $_SESSION["combate"] = $combate;

    saidaItemBatalha([
        "sucesso" => true,
        "mensagem" => $item["nome"] . " utilizado! O inimigo respondeu.",
        "hp_anterior" => $hpAnterior,
        "jogador" => $combate["jogador"],
        "inimigo" => $combate["inimigo"],
        "eventos" => $eventos,
        "resultado" => $resultado,
        "turno" => $combate["turno"],
        "dinheiro_ganho" => $moedasGanhas,
        "exp_ganha" => $expGanha,
        "boss_vitoria" => $bossVitoria,
        "boss_ordem" => $combate["boss_ordem"] ?? null,
        "equipe" => EquipeCombate::membros($combate),
        "troca_obrigatoria" => EquipeCombate::exigeTroca($combate)
    ]);

} catch (JogoException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    saidaItemBatalha([
        "sucesso" => false,
        "erro" => $e->getMessage()
    ], 400);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log((string) $e);
    saidaItemBatalha([
        "sucesso" => false,
        "erro" => "Não foi possível usar o item no combate."
    ], 500);
}
