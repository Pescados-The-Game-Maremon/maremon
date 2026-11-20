
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/log_combate.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ]);
    exit;
}

$combate = $_SESSION["combate"] ?? null;

if (
    !is_array($combate) ||
    (int) ($combate["pescador_id"] ?? 0) !== $pescadorId
) {
    http_response_code(403);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Combate inválido."
    ]);
    exit;
}

// Encerrar o combate na sessão PHP

if (!($combate["ativo"] ?? false)) {
    http_response_code(409);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Este combate já foi encerrado."
    ]);

    exit;
}

try {
    $pdo->beginTransaction();

    atualizarLogCombate(
        $pdo,
        (int) $combate["log_id"],
        $pescadorId,
        "fuga",
        max(0, (int) $combate["turno"] - 1),
        0
    );

    $pdo->commit();

    // Encerrar a batalha somente após salvar.
    $_SESSION["combate"]["ativo"] = false;

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Você fugiu do combate."
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível registrar a fuga."
    ], JSON_UNESCAPED_UNICODE);
}

