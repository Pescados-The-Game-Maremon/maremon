
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/proteger.php";

function responderCura($dados, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (empty($_SESSION["csrf_cura"])) {
        $_SESSION["csrf_cura"] = bin2hex(random_bytes(32));
    }

    responderCura([
        "sucesso" => true,
        "csrf_token" => $_SESSION["csrf_cura"]
    ]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responderCura([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ], 405);
}

$dados = json_decode(file_get_contents("php://input"), true);

$token = is_array($dados)
    ? ($dados["csrf_token"] ?? null)
    : null;

$tokenSessao = $_SESSION["csrf_cura"] ?? null;

if (
    !is_string($token) ||
    !is_string($tokenSessao) ||
    !hash_equals($tokenSessao, $token)
) {
    responderCura([
        "sucesso" => false,
        "erro" => "Solicitação não autorizada."
    ], 403);
}

if (
    ($_SESSION["combate"]["ativo"] ?? false) &&
    (int) ($_SESSION["combate"]["pescador_id"] ?? 0) === $pescadorId
) {
    responderCura([
        "sucesso" => false,
        "erro" => "Finalize o combate antes de curar."
    ], 409);
}

try {
    $stmt = $pdo->prepare("
        UPDATE public.pescador_pescado pp
        SET hp_atual = GREATEST(
            1,
            s.hp + 2 * GREATEST(pp.nivel - 5, 0)
        )
        FROM public.stats s
        WHERE s.pescado_id = pp.pescado_id
          AND pp.pescador_id = :pescador_id
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId
    ]);

    responderCura([
        "sucesso" => true,
        "mensagem" => "Seus Pescados foram curados!",
        "quantidade" => $stmt->rowCount()
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());

    responderCura([
        "sucesso" => false,
        "erro" => "Erro ao curar os Pescados."
    ], 500);
}
