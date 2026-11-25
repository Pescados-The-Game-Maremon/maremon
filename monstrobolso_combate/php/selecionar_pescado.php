
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/proteger.php";

function responder($dados, $codigo = 200) {
    http_response_code($codigo);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ], 405);
}

$dados = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($dados)) {
    responder([
        "sucesso" => false,
        "erro" => "Dados inválidos."
    ], 400);
}

$token = $dados["csrf_token"] ?? null;
$tokenSessao = $_SESSION["csrf_equipe"] ?? null;

if (
    !is_string($token) ||
    !is_string($tokenSessao) ||
    !hash_equals($tokenSessao, $token)
) {
    responder([
        "sucesso" => false,
        "erro" => "Solicitação não autorizada."
    ], 403);
}

$pescadoId = filter_var(
    $dados["pescado_id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$pescadoId || $pescadoId < 1) {
    responder([
        "sucesso" => false,
        "erro" => "Pescado inválido."
    ], 400);
}

// Não permitir trocar o ativo durante o combate.
if ($_SESSION["combate"]["ativo"] ?? false) {
    responder([
        "sucesso" => false,
        "erro" => "Finalize o combate antes de trocar."
    ], 409);
}


try {
    $pdo->beginTransaction();

    // Serializar alterações da equipe deste jogador.
    $stmt = $pdo->prepare("
        SELECT id
        FROM public.pescador
        WHERE id = :pescador_id
        FOR UPDATE
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId
    ]);

    if (!$stmt->fetch()) {
        throw new Exception("Jogador não encontrado.");
    }

    // Verificar posse e HP do Pescado.
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(pp.hp_atual, s.hp) AS hp
        FROM public.pescador_pescado pp
        JOIN public.stats s
            ON s.pescado_id = pp.pescado_id
        WHERE pp.pescador_id = :pescador_id
          AND pp.pescado_id = :pescado_id
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId,
        "pescado_id" => $pescadoId
    ]);

    $pescado = $stmt->fetch();

    if (!$pescado) {
        $pdo->rollBack();

        responder([
            "sucesso" => false,
            "erro" => "Você não possui este Pescado."
        ], 403);
    }

    if ((int) $pescado["hp"] <= 0) {
        $pdo->rollBack();

        responder([
            "sucesso" => false,
            "erro" => "Este Pescado está sem HP."
        ], 400);
    }


    // Primeiro, desmarcar o Pescado anterior.
    $stmt = $pdo->prepare("
        UPDATE public.pescador_pescado
        SET selecionado = FALSE
        WHERE pescador_id = :pescador_id
          AND selecionado = TRUE
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId
    ]);

    // Selecionar o novo Pescado.
    $stmt = $pdo->prepare("
        UPDATE public.pescador_pescado
        SET selecionado = TRUE
        WHERE pescador_id = :pescador_id
          AND pescado_id = :pescado_id
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId,
        "pescado_id" => $pescadoId
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception("Falha ao selecionar Pescado.");
    }

    $pdo->commit();

    responder([
        "sucesso" => true,
        "mensagem" => "Pescado selecionado com sucesso!"
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    responder([
        "sucesso" => false,
        "erro" => "Não foi possível selecionar o Pescado."
    ], 500);
}
