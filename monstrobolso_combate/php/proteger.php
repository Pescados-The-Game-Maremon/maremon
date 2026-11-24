<?php

require_once __DIR__ . "/sessao.php";
require_once __DIR__ . "/conexao.php";

// Verificar se existe um jogador autenticado.
if (!isset($_SESSION["pescador_id"])) {

    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Você precisa fazer login."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Consultar o jogador autenticado.
$sql = "
    SELECT id, nome, usuario
    FROM public.pescador
    WHERE id = :id
      AND usuario IS NOT NULL
      AND senha_hash IS NOT NULL
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    "id" => $_SESSION["pescador_id"]
]);

$jogadorAutenticado = $stmt->fetch();

if (!$jogadorAutenticado) {

    unset(
        $_SESSION["pescador_id"],
        $_SESSION["usuario"],
        $_SESSION["combate"]
    );

    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Sessão inválida."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// ID confiável do jogador conectado.
$pescadorId = (int) $jogadorAutenticado["id"];
