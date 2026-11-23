<?php

header("Content-Type: application/json; charset=utf-8");

ini_set("session.use_strict_mode", "1");
require_once __DIR__ . "/sessao.php";

if (!isset($_SESSION["pescador_id"])) {
    http_response_code(401);

    echo json_encode([
        "autenticado" => false
    ]);

    exit;
}

require_once __DIR__ . "/conexao.php";

$stmt = $pdo->prepare("
    SELECT id, nome, usuario
    FROM public.pescador
    WHERE id = :id
      AND usuario IS NOT NULL
      AND senha_hash IS NOT NULL
");

$stmt->execute([
    "id" => $_SESSION["pescador_id"]
]);

$jogador = $stmt->fetch();

if (!$jogador) {
    unset($_SESSION["pescador_id"], $_SESSION["usuario"]);

    http_response_code(401);
    echo json_encode(["autenticado" => false]);
    exit;
}

echo json_encode([
    "autenticado" => true,
    "jogador" => $jogador
], JSON_UNESCAPED_UNICODE);
