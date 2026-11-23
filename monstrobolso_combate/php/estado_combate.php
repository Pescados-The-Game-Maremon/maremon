<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, private");
header("Vary: Cookie");

require_once __DIR__ . "/proteger.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$combate = $_SESSION["combate"] ?? null;

if (
    !is_array($combate)
    || !($combate["ativo"] ?? false)
    || (int) ($combate["pescador_id"] ?? 0) !== $pescadorId
) {
    http_response_code(409);
    echo json_encode([
        "sucesso" => false,
        "erro" => "O combate não está mais ativo. Inicie outro encontro."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$tokenAcao = $combate["csrf_acao"] ?? null;
$tokenCaptura = $combate["csrf_captura"] ?? null;

if (!is_string($tokenAcao) || !is_string($tokenCaptura)) {
    http_response_code(409);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Sessão antiga de combate. Inicie outro encontro."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    "sucesso" => true,
    "combate_id" => (int) $combate["log_id"],
    "csrf_acao" => $tokenAcao,
    "csrf_captura" => $tokenCaptura,
    "captura_tentada" => (bool) ($combate["captura_tentada"] ?? false)
], JSON_UNESCAPED_UNICODE);
