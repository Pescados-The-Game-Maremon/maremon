<?php

require_once __DIR__ . "/php/sessao.php";

if (!isset($_SESSION["pescador_id"])) {
    header("Location: login.html");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Minha Equipe - Maremon</title>
</head>
<body>

    <h1>Minha equipe</h1>

    <p>Escolha o Pescado que entrará nas batalhas.</p>

    <div id="lista_equipe"></div>

    <p id="mensagem_equipe"></p>

    <a href="index.php">Voltar ao jogo</a>

    <script src="js/equipe.js"></script>

</body>
</html>
