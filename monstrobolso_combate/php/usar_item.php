<?php

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/proteger.php";

function responderItem(array $dados, int $status = 200): void {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responderItem([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ], 405);
}

$dados = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($dados)) {
    responderItem([
        "sucesso" => false,
        "erro" => "Dados inválidos."
    ], 400);
}

$token = $dados["csrf_token"] ?? null;
$tokenEsperado = $_SESSION["csrf_inventario"] ?? null;

if (
    !is_string($token) ||
    !is_string($tokenEsperado) ||
    !hash_equals($tokenEsperado, $token)
) {
    responderItem([
        "sucesso" => false,
        "erro" => "Solicitação não autorizada."
    ], 403);
}

$itemId = filter_var(
    $dados["item_id"] ?? null,
    FILTER_VALIDATE_INT
);

$pescadoId = filter_var(
    $dados["pescado_id"] ?? null,
    FILTER_VALIDATE_INT
);

if (
    !$itemId || $itemId < 1 ||
    !$pescadoId || $pescadoId < 1
) {
    responderItem([
        "sucesso" => false,
        "erro" => "Item ou Pescado inválido."
    ], 400);
}

// Não permitir utilizar itens fora da batalha
// enquanto um combate estiver ativo.
if (
    ($_SESSION["combate"]["ativo"] ?? false) &&
    (int) ($_SESSION["combate"]["pescador_id"] ?? 0) === $pescadorId
) {
    responderItem([
        "sucesso" => false,
        "erro" => "Finalize o combate antes de usar itens."
    ], 409);
}


try {
    $pdo->beginTransaction();

    // Bloquear a conta durante a operação.
    $stmt = $pdo->prepare("
        SELECT id
        FROM public.pescador
        WHERE id = :id
        FOR UPDATE
    ");

    $stmt->execute(["id" => $pescadorId]);

    if (!$stmt->fetch()) {
        throw new RuntimeException("Jogador não encontrado.");
    }

    // Conferir se possui o item.
    $stmt = $pdo->prepare("
        SELECT
            pi.quantidade,
            i.nome,
            i.efeito,
            i.valor_efeito
        FROM public.pescador_itens pi
        JOIN public.loja_itens i
            ON i.id = pi.item_id
        WHERE pi.pescador_id = :pescador_id
          AND pi.item_id = :item_id
        FOR UPDATE OF pi
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId,
        "item_id" => $itemId
    ]);

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item || (int) $item["quantidade"] <= 0) {
        $pdo->rollBack();

        responderItem([
            "sucesso" => false,
            "erro" => "Você não possui esse item."
        ], 400);
    }

    // Buscar somente Pescados da conta autenticada.
    $stmt = $pdo->prepare("
        SELECT
            pp.hp_atual,
            pp.nivel,
            s.hp + 2 * GREATEST(pp.nivel - 5, 0)
                AS hp_max
        FROM public.pescador_pescado pp
        JOIN public.stats s
            ON s.pescado_id = pp.pescado_id
        WHERE pp.pescador_id = :pescador_id
          AND pp.pescado_id = :pescado_id
        FOR UPDATE OF pp
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId,
        "pescado_id" => $pescadoId
    ]);

    $pescado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pescado) {
        $pdo->rollBack();

        responderItem([
            "sucesso" => false,
            "erro" => "Você não possui esse Pescado."
        ], 403);
    }

    $hpMax = max(1, (int) $pescado["hp_max"]);

    $hpAtual = $pescado["hp_atual"] === null
        ? $hpMax
        : max(0, min($hpMax, (int) $pescado["hp_atual"]));

    $efeito = $item["efeito"];
    $novoHp = $hpAtual;


    switch ($efeito) {

        case "curar_hp":
            if ($hpAtual <= 0 || $hpAtual >= $hpMax) {
                $pdo->rollBack();

                responderItem([
                    "sucesso" => false,
                    "erro" => "Este Pescado não pode receber essa cura."
                ], 400);
            }

            $novoHp = min(
                $hpMax,
                $hpAtual + (int) $item["valor_efeito"]
            );
            break;

        case "curar_total":
            if ($hpAtual <= 0 || $hpAtual >= $hpMax) {
                $pdo->rollBack();

                responderItem([
                    "sucesso" => false,
                    "erro" => "Este Pescado não precisa dessa cura."
                ], 400);
            }

            $novoHp = $hpMax;
            break;

        case "reviver":
            if ($hpAtual > 0) {
                $pdo->rollBack();

                responderItem([
                    "sucesso" => false,
                    "erro" => "Revive só funciona em Pescados sem HP."
                ], 400);
            }

            $novoHp = (int) ceil($hpMax / 2);
            break;

        default:
            $pdo->rollBack();

            responderItem([
                "sucesso" => false,
                "erro" => "O efeito desse item ainda não foi implementado."
            ], 400);
    }

    // Aplicar o HP recuperado.
    $stmt = $pdo->prepare("
        UPDATE public.pescador_pescado
        SET hp_atual = :hp
        WHERE pescador_id = :pescador_id
          AND pescado_id = :pescado_id
    ");

    $stmt->execute([
        "hp" => $novoHp,
        "pescador_id" => $pescadorId,
        "pescado_id" => $pescadoId
    ]);

    // Consumir exatamente uma unidade.
    $stmt = $pdo->prepare("
        UPDATE public.pescador_itens
        SET quantidade = quantidade - 1
        WHERE pescador_id = :pescador_id
          AND item_id = :item_id
          AND quantidade > 0
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId,
        "item_id" => $itemId
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            "Falha ao consumir o item."
        );
    }

    // Remover registros com quantidade zero.
    $stmt = $pdo->prepare("
        DELETE FROM public.pescador_itens
        WHERE pescador_id = :pescador_id
          AND item_id = :item_id
          AND quantidade = 0
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId,
        "item_id" => $itemId
    ]);

    $pdo->commit();

    responderItem([
        "sucesso" => true,
        "mensagem" => $item["nome"] . " utilizado com sucesso!",
        "hp_anterior" => $hpAtual,
        "hp_atual" => $novoHp,
        "hp_max" => $hpMax,
        "quantidade_restante" =>
            (int) $item["quantidade"] - 1
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    responderItem([
        "sucesso" => false,
        "erro" => "Não foi possível utilizar o item."
    ], 500);
}
