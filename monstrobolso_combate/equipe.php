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
    <link rel="stylesheet"
          href="css/maremon-ui.css?v=<?= filemtime(__DIR__ . '/css/maremon-ui.css') ?>">
    <link rel="stylesheet"
          href="css/equipe_melhorada.css?v=<?= filemtime(__DIR__ . '/css/equipe_melhorada.css') ?>">
</head>
<body class="pagina_equipe maremon-page">
<main class="maremon-main">

    <header class="maremon-page-header">
        <span class="maremon-eyebrow">MAREMON · GERENCIAMENTO DE EQUIPE</span>
        <h1>Minha equipe</h1>
        <p>Organize até três Pescados para enfrentar o oceano. Escolha os titulares e o integrante ativo.</p>
    </header>

    <h2 class="maremon-section-title">Formação de batalha</h2>
    <div id="slots_equipe"></div>

    <h2 class="maremon-section-title">Meus Pescados</h2>
    <div id="lista_equipe"></div>

    <p id="mensagem_equipe" class="maremon-feedback" role="status" aria-live="polite"></p>

    <nav class="maremon-acoes-pagina" aria-label="Ações da equipe">
        <a href="inventario.php">Abrir mochila</a>
        <a href="index.php">Voltar ao jogo</a>
    </nav>

</main>

<script src="js/monstros.js?v=<?= filemtime(__DIR__ . '/js/monstros.js') ?>"></script>
<script src="js/equipe.js?v=<?= filemtime(__DIR__ . '/js/equipe.js') ?>"></script>
</body>
</html>
