<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, private");

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/experiencia.php";
require_once __DIR__ . "/log_combate.php";
require_once __DIR__ . "/token_combate.php";
require_once __DIR__ . "/../app/bootstrap.php";

use Maremon\Controllers\CombateController;
use Maremon\Exceptions\JogoException;

function responderTurno(array $dados, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responderTurno([
        "sucesso" => false,
        "erro" => "Utilize POST."
    ], 405);
}

$dados = json_decode(file_get_contents("php://input"), true);

if (!is_array($dados)) {
    responderTurno([
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
    responderTurno([
        "sucesso" => false,
        "erro" => "Combate inválido ou encerrado. Inicie outro encontro."
    ], 409);
}

exigirTokenCombate($combate, $dados, "csrf_acao");

$ataqueId = filter_var($dados["ataque_id"] ?? null, FILTER_VALIDATE_INT);

if (!$ataqueId || $ataqueId < 1) {
    responderTurno([
        "sucesso" => false,
        "erro" => "Ataque inválido."
    ], 400);
}

try {
    $controller = new CombateController($pdo);
    $resultado = $controller->atacar($pescadorId, $combate, (int) $ataqueId);

    // Gravar o estado na sessão somente depois do COMMIT do controlador.
    $_SESSION["combate"] = $combate;
    responderTurno($resultado);

} catch (JogoException $e) {
    responderTurno([
        "sucesso" => false,
        "erro" => $e->getMessage()
    ], 400);

} catch (Throwable $e) {
    error_log((string) $e);
    responderTurno([
        "sucesso" => false,
        "erro" => "Não foi possível executar o turno."
    ], 500);
}
