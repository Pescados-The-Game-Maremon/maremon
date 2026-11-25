<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, private");

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/../app/bootstrap.php";

use Maremon\Services\BossService;

function responderIsca(array $dados, int $codigo = 200): never
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responderIsca(["sucesso" => false, "erro" => "Use POST."], 405);
}

$dados = json_decode(file_get_contents("php://input"), true);
$recebido = is_array($dados) ? ($dados["csrf_token"] ?? null) : null;
$esperado = $_SESSION["csrf_isca"] ?? null;

if (!is_string($recebido) || !is_string($esperado) || !hash_equals($esperado, $recebido)) {
    responderIsca(["sucesso" => false, "erro" => "Solicitação não autorizada."], 403);
}

if ($_SESSION["combate"]["ativo"] ?? false) {
    responderIsca(["sucesso" => false, "erro" => "Finalize a batalha para trocar a isca."], 409);
}

$ordem = filter_var($dados["ordem"] ?? null, FILTER_VALIDATE_INT);
if ($ordem === false || $ordem === null || $ordem < 1 || $ordem > 8) {
    responderIsca(["sucesso" => false, "erro" => "Isca inválida."], 400);
}

try {
    BossService::escolherIsca($pdo, $pescadorId, $ordem);
    responderIsca([
        "sucesso" => true,
        "isca_atual" => $ordem,
        "mensagem" => "Isca equipada! O chefe desse mapa tem 5% de chance de aparecer."
    ]);
} catch (RuntimeException $e) {
    responderIsca(["sucesso" => false, "erro" => $e->getMessage()], 403);
} catch (Throwable $e) {
    error_log((string) $e);
    responderIsca(["sucesso" => false, "erro" => "Não foi possível selecionar a isca."], 500);
}
