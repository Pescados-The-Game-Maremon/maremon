
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
    <link rel="stylesheet"
          href="css/maremon-ui.css?v=<?= filemtime(__DIR__ . '/css/maremon-ui.css') ?>">
    <link rel="stylesheet"
          href="css/inventario.css?v=<?= filemtime(__DIR__ . '/css/inventario.css') ?>">
</head>
<body class="pagina_inventario maremon-page">

<main class="maremon-main">

    <header class="maremon-page-header">
        <span class="maremon-eyebrow">MAREMON · ITENS E RECUPERAÇÃO</span>
        <h1>Minha mochila</h1>
        <p>Escolha um item e um Pescado para recuperar HP, remover condições ou evoluir.</p>
    </header>

    <h2 class="maremon-section-title">Itens disponíveis</h2>
    <div id="lista_itens"></div>

    <h2 class="maremon-section-title">Escolha um Pescado</h2>
    <div id="lista_pescados"></div>

    <button id="botao_usar" class="maremon-botao-principal" disabled>
        ✦ Usar item
    </button>

    <p id="mensagem_inventario" class="maremon-feedback" role="status" aria-live="polite"></p>

    <nav class="maremon-acoes-pagina" aria-label="Voltar da mochila">
        <a href="equipe.php">Minha equipe</a>
        <a href="index.php">Voltar ao jogo</a>
    </nav>

</main>

<script src="js/inventario.js?v=<?= filemtime(__DIR__ . '/js/inventario.js') ?>"></script>
</body>
</html>
