<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventário - Maremon</title>
    <link rel="stylesheet"
          href="css/maremon-ui.css?v=<?= filemtime(MAREMON_ROOT . '/css/maremon-ui.css') ?>">
    <link rel="stylesheet"
          href="css/inventario.css?v=<?= filemtime(MAREMON_ROOT . '/css/inventario.css') ?>">
</head>
<body class="pagina_inventario maremon-page">
<main class="maremon-main">
    <header class="inventario_cabecalho">
        <a class="inventario_voltar" href="index.php" onclick="return voltarMaremon(event)"><span aria-hidden="true">‹</span> Voltar</a>
        <div class="inventario_titulo">
            <h1>Inventário</h1>
            <span>Minha mochila</span>
        </div>
        <span class="inventario_marca">MAREMON</span>
    </header>

    <div class="inventario_abas" role="tablist" aria-label="Categorias de itens">
        <button type="button" id="aba_cura" class="inventario_aba" role="tab" aria-selected="true" aria-controls="painel_cura" tabindex="0">
            <span class="aba_simbolo" aria-hidden="true">+</span> Itens de cura
            <span class="aba_contagem" id="contagem_cura" aria-hidden="true">0</span>
        </button>
        <button type="button" id="aba_evolucao" class="inventario_aba" role="tab" aria-selected="false" aria-controls="painel_itens_evolucao" tabindex="-1">
            <span class="aba_simbolo" aria-hidden="true">◆</span> Itens de evolução
            <span class="aba_contagem" id="contagem_evolucao" aria-hidden="true">0</span>
        </button>
    </div>

    <div id="lista_itens" class="inventario_catalogo" aria-busy="true">
        <section id="painel_cura" role="tabpanel" aria-labelledby="aba_cura">
            <div class="inventario_intro">
                <span class="inventario_intro_icone" aria-hidden="true">+</span>
                <div><h2>Itens de cura</h2><p>Recupere HP, reviva Pescados e remova condições como sono, veneno e paralisia.</p></div>
            </div>
            <div class="inventario_rotulo"><h3>Na mochila</h3><span id="resumo_cura" aria-live="polite">Carregando...</span></div>
            <div id="itens_cura" class="inventario_grade"><p class="inventario_vazio">Carregando itens...</p></div>
        </section>
        <section id="painel_itens_evolucao" role="tabpanel" aria-labelledby="aba_evolucao" hidden>
            <div class="inventario_intro">
                <span class="inventario_intro_icone" aria-hidden="true">◆</span>
                <div><h2>Itens de evolução</h2><p>Use pedras evolutivas e DNAs para transformar Pescados em espécies compatíveis.</p></div>
            </div>
            <div class="inventario_rotulo"><h3>Na mochila</h3><span id="resumo_evolucao" aria-live="polite">Carregando...</span></div>
            <div id="itens_evolucao" class="inventario_grade"><p class="inventario_vazio">Carregando itens...</p></div>
        </section>
    </div>

    <section class="inventario_alvo" aria-labelledby="titulo_pescados">
        <div class="inventario_rotulo inventario_rotulo_alvo"><h2 id="titulo_pescados">Escolha um Pescado</h2><span>Somente Pescados compatíveis podem receber o item</span></div>
        <div id="lista_pescados"></div>
        <section id="painel_evolucao" hidden>
            <h3>Evolução disponível</h3>
            <label for="escolher_evolucao">Nova espécie:</label>
            <select id="escolher_evolucao" aria-label="Escolher evolução"></select>
            <p id="detalhe_evolucao" aria-live="polite"></p>
        </section>
        <div class="inventario_rodape_acao">
            <p id="resumo_selecao" aria-live="polite">Selecione um item e um Pescado.</p>
            <button type="button" id="botao_usar" class="maremon-botao-principal" disabled>✦ Usar item</button>
        </div>
        <p id="mensagem_inventario" class="maremon-feedback" role="status" aria-live="polite"></p>
    </section>

    <nav class="maremon-acoes-pagina" aria-label="Voltar da mochila">
        <a href="equipe.php">Minha equipe</a>
        <a href="index.php" onclick="return voltarMaremon(event)">Voltar ao jogo</a>
    </nav>
</main>
<script src="js/elementos.js?v=<?= filemtime(MAREMON_ROOT . '/js/elementos.js') ?>"></script>
<script src="js/inventario.js?v=<?= filemtime(MAREMON_ROOT . '/js/inventario.js') ?>"></script>
<script>
function voltarMaremon(event) {
    event.preventDefault();
    window.location.assign('index.php');
    return false;
}
</script>
</body>
</html>
