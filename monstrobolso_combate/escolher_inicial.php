<?php

require_once __DIR__ . "/php/sessao.php";

if (!isset($_SESSION["pescador_id"])) {
    header("Location: login.html");
    exit;
}


require_once __DIR__ . "/php/conexao.php";

$stmt = $pdo->prepare("
    SELECT EXISTS (
        SELECT 1
        FROM public.pescador_pescado
        WHERE pescador_id = :id
    )
");

$stmt->execute([
    "id" => $_SESSION["pescador_id"]
]);

$jaPossui = $stmt->fetchColumn();

if ($jaPossui === true || $jaPossui === "t" || $jaPossui === "1") {
    header("Location: index.php");
    exit;
}

// Código de segurança da escolha
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$token = $_SESSION["csrf_token"];

?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolher Inicial - Maremon</title>
</head>
<body>

    <h1>Escolha seu Pescado inicial</h1>

    <div id="iniciais">
        <button data-pescado="Trilobita">
            Trilobita
        </button>

        <button data-pescado="Anomalocaris">
            Anomalocaris
        </button>

        <button data-pescado="Hallucigenia">
            Hallucigenia
        </button>
    </div>

    <p id="mensagem"></p>

    <script>
        const TOKEN_CSRF =
            <?= json_encode($token) ?>;
    </script>

    <script src="js/escolher_inicial.js"></script>

</body>
</html>
