<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Minha Equipe - Maremon</title>
    <link rel="stylesheet"
          href="css/maremon-ui.css?v=<?= filemtime(MAREMON_ROOT . '/css/maremon-ui.css') ?>">
    <link rel="stylesheet"
          href="css/equipe_melhorada.css?v=<?= filemtime(MAREMON_ROOT . '/css/equipe_melhorada.css') ?>">
</head>
<body class="pagina_equipe maremon-page">
<main class="maremon-main">

    <header class="equipe_cabecalho">
        <a class="equipe_voltar" href="index.php" onclick="return voltarMaremon(event)"><span aria-hidden="true">‹</span> Voltar</a>
        <div class="equipe_titulo">
            <h1>Minha equipe</h1>
            <span>Gerenciamento de equipe</span>
        </div>
        <span class="equipe_marca">MAREMON</span>
    </header>
    <div class="equipe_intro">
        <p>Organize até três Pescados. A primeira posição com HP entra na batalha; os demais ficam disponíveis para troca.</p>
    </div>

    <h2 class="maremon-section-title">Formação de batalha <small id="resumo_equipe"></small></h2>
    <p id="instrucao_equipe">Escolha uma posição.</p>
    <div id="slots_equipe"></div>

    <p id="aviso_equipe_hp" hidden>Não há integrantes com HP. Cure seus Pescados ou coloque um integrante vivo na formação.</p>
    <h2 class="maremon-section-title">Meus Pescados</h2>
    <input id="filtro_equipe" type="search" placeholder="Buscar por nome ou tipo" aria-label="Buscar Pescados">
    <div id="lista_equipe"></div>

    <p id="mensagem_equipe" class="maremon-feedback" role="status" aria-live="polite"></p>

    <nav class="maremon-acoes-pagina" aria-label="Ações da equipe">
        <a href="inventario.php">Abrir mochila</a>
    </nav>

</main>
<template id="modelo_slot_equipe">
    <article class="formacao_slot">
        <button type="button" class="selecionar_slot" aria-pressed="false">
            <span class="numero_slot"></span>
            <img alt="" hidden>
            <strong class="nome_slot"></strong>
            <span class="rotulo_slot"></span>
            <span class="slot_hp"><progress max="1" value="0" aria-label="HP"></progress><small class="hp_texto"></small></span>
        </button>
        <button type="button" class="remover_slot" hidden>Retirar da formação</button>
    </article>
</template>
<template id="modelo_pescado_equipe">
    <article class="card_equipe">
        <img alt="" loading="lazy">
        <div class="infos_pescado">
            <h3></h3><p class="info_tipo"></p>
            <progress max="1" value="0" aria-label="HP"></progress><small class="hp_texto"></small>
            <p class="info_stats"></p>
            <div class="botoes_equipe">
                <button type="button" class="atribuir_slot"></button>
                <div class="atalhos_slots" aria-label="Mover para posição">
                    <button type="button" data-slot="1" aria-label="Posição 1">1</button>
                    <button type="button" data-slot="2" aria-label="Posição 2">2</button>
                    <button type="button" data-slot="3" aria-label="Posição 3">3</button>
                </div>
            </div>
        </div>
    </article>
</template>

<script src="js/elementos.js?v=<?= filemtime(MAREMON_ROOT . '/js/elementos.js') ?>"></script>
<script src="js/monstros.js?v=<?= filemtime(MAREMON_ROOT . '/js/monstros.js') ?>"></script>
<script src="js/equipe.js?v=<?= filemtime(MAREMON_ROOT . '/js/equipe.js') ?>"></script>
<script>
function voltarMaremon(event) {
    event.preventDefault();
    window.location.assign('index.php');
    return false;
}
</script>
</body>
</html>
