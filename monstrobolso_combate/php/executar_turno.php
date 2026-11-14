<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/experiencia.php";

function responder($dados, $codigo = 200) {
    http_response_code($codigo);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

function calcularDano($atacante, $defensor, $poder) {
    $atk = max(1, (int) $atacante["atk"]);
    $def = max(1, (int) $defensor["def"]);

    return max(
        1,
        (int) floor(($atk * $poder) / ($def * 5))
    );
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder([
        "sucesso" => false,
        "erro" => "Utilize uma requisição POST."
    ], 405);
}

if (
    !isset($_SESSION["combate"]) ||
    (int) ($_SESSION["combate"]["pescador_id"] ?? 0)
        !== $pescadorId ||
    !($_SESSION["combate"]["ativo"] ?? false)
) {
    responder([
        "sucesso" => false,
        "erro" => "Você não possui um combate ativo."
    ], 403);
}

$entrada = json_decode(file_get_contents("php://input"), true);
$ataqueId = filter_var(
    $entrada["ataque_id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$ataqueId || $ataqueId < 1) {
    responder([
        "sucesso" => false,
        "erro" => "Ataque inválido."
    ], 400);
}

try {
    $combate = &$_SESSION["combate"];

    $jogador = &$combate["jogador"];
    $inimigo = &$combate["inimigo"];

    // Buscar os ataques disponíveis para cada espécie.
    $sql = "
        SELECT
            a.id,
            a.nome,
            a.poder,
            a.precisao
        FROM public.pescado_ataques pa
        JOIN public.ataques a
            ON a.id = pa.ataque_id
        WHERE pa.pescado_id = :pescado_id
        ORDER BY a.id
        LIMIT 4
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        "pescado_id" => $jogador["id"]
    ]);

    $ataquesJogador = $stmt->fetchAll();

    $stmt->execute([
        "pescado_id" => $inimigo["id"]
    ]);

    $ataquesInimigo = $stmt->fetchAll();

    $ataqueEscolhido = null;

    foreach ($ataquesJogador as $ataque) {
        if ((int) $ataque["id"] === $ataqueId) {
            $ataqueEscolhido = $ataque;
            break;
        }
    }

    if (!$ataqueEscolhido) {
        responder([
            "sucesso" => false,
            "erro" => "Este ataque não está disponível para seu Pescado."
        ], 400);
    }

    if (count($ataquesInimigo) === 0) {
        responder([
            "sucesso" => false,
            "erro" => "O inimigo não possui ataques cadastrados."
        ], 400);
    }

    $ataqueInimigo = $ataquesInimigo[
        random_int(0, count($ataquesInimigo) - 1)
    ];

    $jogadorPrimeiro =
        (int) $jogador["spe"] >= (int) $inimigo["spe"];

    $ordem = $jogadorPrimeiro
        ? [
            ["jogador", $ataqueEscolhido],
            ["inimigo", $ataqueInimigo]
        ]
        : [
            ["inimigo", $ataqueInimigo],
            ["jogador", $ataqueEscolhido]
        ];

    $eventos = [];

    foreach ($ordem as [$lado, $ataque]) {
        if ($jogador["hp"] <= 0 || $inimigo["hp"] <= 0) {
            break;
        }

        $atacante = $lado === "jogador"
            ? $jogador
            : $inimigo;

        $defensor = &$inimigo;

        if ($lado === "inimigo") {
            $defensor = &$jogador;
        }

        $acertou = random_int(1, 100)
            <= (int) $ataque["precisao"];

        $dano = 0;

        if ($acertou) {
            $dano = calcularDano(
                $atacante,
                $defensor,
                (int) $ataque["poder"]
            );

            $defensor["hp"] = max(
                0,
                (int) $defensor["hp"] - $dano
            );
        }

        $eventos[] = [
            "atacante" => $atacante["nome"],
            "ataque" => $ataque["nome"],
            "acertou" => $acertou,
            "dano" => $dano
        ];

        unset($defensor);
    }

    

// Verificar o resultado da batalha.
if ((int) $inimigo["hp"] <= 0) {
    $resultado = "vitoria";
} elseif ((int) $jogador["hp"] <= 0) {
    $resultado = "derrota";
} else {
    $resultado = "continuar";
}

// Experiência ganha neste turno.
$expGanha = $resultado === "vitoria" ? 10 : 0;

$pdo->beginTransaction();

// Bloquear o registro do Pescado enquanto salvamos.
$stmt = $pdo->prepare("
    SELECT nivel, COALESCE(exp, 0) AS exp
    FROM public.pescador_pescado
    WHERE pescador_id = :pescador_id
      AND pescado_id = :pescado_id
    FOR UPDATE
");

$stmt->execute([
    "pescador_id" => $pescadorId,
    "pescado_id" => (int) $jogador["id"]
]);

$progresso = $stmt->fetch();

if (!$progresso) {
    throw new Exception("Pescado não encontrado.");
}

// Calcular a experiência e o novo nível.
$nivelAnterior = (int) $progresso["nivel"];

$expTotal = (int) $progresso["exp"] + $expGanha;

$nivelNovo = calcularNivel(
    $nivelAnterior,
    $expTotal
);

$niveisGanhos = $nivelNovo - $nivelAnterior;

// Atualizar os atributos do Pescado na batalha.
if ($niveisGanhos > 0) {

    $jogador["nivel"] = $nivelNovo;

    $jogador["hp_max"] += $niveisGanhos * 2;

    $jogador["atk"] += $niveisGanhos;

    $jogador["def"] += $niveisGanhos;

    $jogador["spe"] += $niveisGanhos;

    // Recuperar apenas o HP adicional do nível.
    $jogador["hp"] = min(
        (int) $jogador["hp_max"],
        (int) $jogador["hp"] + $niveisGanhos * 2
    );
}

// Salvar HP, nível e experiência.
$sqlSalvar = "
    UPDATE public.pescador_pescado
    SET
        hp_atual = :hp,
        nivel = :nivel,
        exp = :exp
    WHERE pescador_id = :pescador_id
      AND pescado_id = :pescado_id
";

$stmtSalvar = $pdo->prepare($sqlSalvar);

$stmtSalvar->execute([
    "hp" => (int) $jogador["hp"],
    "nivel" => $nivelNovo,
    "exp" => $expTotal,
    "pescador_id" => $pescadorId,
    "pescado_id" => (int) $jogador["id"]
]);

if ($stmtSalvar->rowCount() !== 1) {
    throw new Exception("Não foi possível salvar o progresso.");
}

// Confirmar as alterações no PostgreSQL.
$pdo->commit();

// Atualizar o estado do combate após salvar.
if ($resultado !== "continuar") {
    $combate["ativo"] = false;
}

$combate["turno"]++;

$_SESSION["combate"] = $combate;

   responder([
    "sucesso" => true,
    "eventos" => $eventos,
    "jogador" => $jogador,
    "inimigo" => $inimigo,
    "resultado" => $resultado,
    "exp_ganha" => $expGanha,
    "exp_total" => $expTotal,
    "nivel_anterior" => $nivelAnterior,
    "nivel_novo" => $nivelNovo,
    "niveis_ganhos" => $niveisGanhos,
    "turno" => $combate["turno"]
]);

} catch (Throwable $e) {
    error_log($e->getMessage());
    if ($pdo->inTransaction()) {
    $pdo->rollBack();
    }
    responder([
        "sucesso" => false,
        "erro" => "Não foi possível executar o turno."
    ], 500);
}
