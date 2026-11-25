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
    <title>Enciclopédia — Maremon</title>
    <link rel="stylesheet" href="css/maremon-ui.css?v=<?= filemtime(__DIR__ . '/css/maremon-ui.css') ?>">
    <link rel="stylesheet" href="css/registros.css?v=<?= filemtime(__DIR__ . '/css/registros.css') ?>">
</head>
<body class="maremon-page pagina_registros pagina_pokedex">
<main class="maremon-main">
    <header class="registros_cabecalho">
        <a class="registros_voltar" href="index.php" onclick="return voltarMaremon(event)"><span aria-hidden="true">‹</span> Voltar</a>
        <div class="registros_titulo">
            <h1>Enciclopédia</h1>
            <span>Catálogo de Pescados</span>
        </div>
        <span class="registros_marca">MAREMON</span>
    </header>
    <section class="registros_intro pokedex_intro" aria-labelledby="titulo_pokedex">
        <span class="registros_icone" aria-hidden="true">◇</span>
        <div><h2 id="titulo_pokedex">Enciclopédia dos Pescados</h2><p>Espécies, tipos e atributos base. Os níveis individuais continuam em Minha equipe.</p></div>
    </section>
    <section class="pokedex_tipos" aria-labelledby="titulo_tipos">
        <div><h2 id="titulo_tipos">Vantagens entre tipos</h2><p>Consulte a relação de vantagens e fraquezas dos Pescados.</p></div>
        <a href="assets/tabela_tipos.png" target="_blank" rel="noopener" aria-label="Abrir tabela de vantagens entre tipos em tamanho original (nova aba)"><img src="assets/tabela_tipos.png" alt="Relação de vantagens entre tipos"></a>
    </section>
    <section aria-labelledby="titulo_catalogo">
        <div class="registros_rotulo"><h2 id="titulo_catalogo">Espécies cadastradas</h2><p id="aviso_pokedex" role="status">Carregando catálogo...</p></div>
        <div id="lista_equipe" class="registros_grade" aria-busy="true"></div>
    </section>
</main>
<template id="modelo_especie">
    <article class="registro_card especie_card">
        <div class="especie_sprite"><img alt="" loading="lazy" decoding="async"></div>
        <div class="especie_info">
            <div class="registro_topo"><h3 class="especie_nome"></h3><span class="especie_tipo"></span></div>
            <p class="especie_descricao"></p>
            <dl class="registro_stats especie_stats">
                <div><dt>HP</dt><dd data-stat="hp"></dd></div>
                <div><dt>ATK</dt><dd data-stat="atk"></dd></div>
                <div><dt>DEF</dt><dd data-stat="def"></dd></div>
                <div><dt>VEL</dt><dd data-stat="spe"></dd></div>
            </dl>
        </div>
    </article>
</template>
<template id="modelo_vazio"><p class="registros_vazio"></p></template>
<script src="js/monstros.js?v=<?= filemtime(__DIR__ . '/js/monstros.js') ?>"></script>
<script src="js/pokedex.js?v=<?= filemtime(__DIR__ . '/js/pokedex.js') ?>"></script>
<script>
function voltarMaremon(event) {
    event.preventDefault();
    window.location.assign('index.php');
    return false;
}
</script>
</body>
</html>
