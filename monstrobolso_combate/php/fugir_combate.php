
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/proteger.php";

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
$_SESSION["combate"]["ativo"] = false;

echo json_encode([
    "sucesso" => true,
    "mensagem" => "Você fugiu do combate."
]);
