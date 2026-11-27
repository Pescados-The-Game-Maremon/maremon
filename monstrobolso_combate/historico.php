<?php
require_once __DIR__ . '/php/sessao.php';
if (empty($_SESSION['pescador_id'])) {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico — Maremon</title>
    <link rel="stylesheet" href="css/maremon-ui.css?v=<?= filemtime(__DIR__ . '/css/maremon-ui.css') ?>">
    <link rel="stylesheet" href="css/registros.css?v=<?= filemtime(__DIR__ . '/css/registros.css') ?>">
</head>
<body class="maremon-page pagina_registros pagina_historico">
<main class="maremon-main">
    <header class="registros_cabecalho">
        <a class="registros_voltar" href="index.php" onclick="return voltarMaremon(event)"><span aria-hidden="true">‹</span> Voltar</a>
        <div class="registros_titulo">
            <h1>Histórico</h1>
            <span>Registro de batalhas</span>
        </div>
        <span class="registros_marca">MAREMON</span>
    </header>
    <section class="registros_intro" aria-labelledby="titulo_historico">
        <span class="registros_icone" aria-hidden="true">≡</span>
        <div><h2 id="titulo_historico">Histórico de batalhas</h2><p>Consulte suas últimas 100 batalhas, resultados, turnos e moedas recebidas.</p></div>
    </section>
    <section aria-labelledby="titulo_lista">
        <div class="registros_rotulo"><h2 id="titulo_lista">Batalhas registradas</h2><p id="aviso_historico" role="status">Carregando...</p></div>
        <div id="lista_equipe" class="registros_grade" aria-busy="true"></div>
    </section>
</main>
<template id="modelo_batalha">
    <article class="registro_card batalha_card">
        <div class="registro_topo"><span class="batalha_numero"></span><span class="batalha_resultado"></span></div>
        <div class="batalha_confronto"><strong class="batalha_jogador"></strong><span class="batalha_vs">vs</span><strong class="batalha_inimigo"></strong></div>
        <dl class="registro_stats">
            <div><dt>Turnos</dt><dd class="batalha_turnos"></dd></div>
            <div><dt>Moedas recebidas</dt><dd class="batalha_moedas"></dd></div>
        </dl>
        <p class="batalha_hora"></p>
    </article>
</template>
<template id="modelo_vazio"><p class="registros_vazio"></p></template>
<script src="js/elementos.js?v=<?= filemtime(__DIR__ . '/js/elementos.js') ?>"></script>
<script src="js/historico.js?v=<?= filemtime(__DIR__ . '/js/historico.js') ?>"></script>
<script>
function voltarMaremon(event) {
    event.preventDefault();
    window.location.assign('index.php');
    return false;
}
</script>
</body>
</html>
