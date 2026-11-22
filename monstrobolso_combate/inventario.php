
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

    <title>Inventário - Maremon</title>
    <link rel="stylesheet" href="css/inventario.css">
</head>
<body>

<main class="inventario_container">

    <header>
        <h1>Minha Mochila</h1>
        <p>Escolha um item e um Pescado para utilizá-lo.</p>
    </header>

    <h2>Meus itens</h2>
    <div id="lista_itens"></div>

    <h2>Meus Pescados</h2>
    <div id="lista_pescados"></div>

    <button id="botao_usar" disabled>
        Usar item
    </button>

    <p id="mensagem_inventario"></p>

    <a href="index.php">Voltar ao jogo</a>

</main>

<script src="js/inventario.js"></script>

</body>
</html>
