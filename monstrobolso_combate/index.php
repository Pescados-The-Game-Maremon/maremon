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
        WHERE pescador_id = :pescador_id
    )
");

$stmt->execute([
    "pescador_id" => $_SESSION["pescador_id"]
]);

$possuiPescado = $stmt->fetchColumn();

if (
    $possuiPescado === false ||
    $possuiPescado === "f" ||
    $possuiPescado === "0"
) {
    header("Location: escolher_inicial.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Maremon</title>

    <link rel="stylesheet"
          href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
    <link rel="stylesheet"
          href="css/fundos.css?v=<?= filemtime(__DIR__ . '/css/fundos.css') ?>">
    <link rel="stylesheet"
          href="css/buttons.css?v=<?= filemtime(__DIR__ . '/css/buttons.css') ?>">
    <link rel="stylesheet"
          href="css/combate.css?v=<?= filemtime(__DIR__ . '/css/combate.css') ?>">
    <link rel="stylesheet"
          href="css/centro_cura.css?v=<?= filemtime(__DIR__ . '/css/centro_cura.css') ?>">
    <link rel="stylesheet"
          href="css/loja.css?v=<?= filemtime(__DIR__ . '/css/loja.css') ?>">
    <link rel="stylesheet"
          href="css/equipe_melhorada.css?v=<?= filemtime(__DIR__ . '/css/equipe_melhorada.css') ?>">
    <link rel="stylesheet"
          href="css/iscas.css?v=<?= filemtime(__DIR__ . '/css/iscas.css') ?>">
    <link rel="stylesheet"
          href="css/hud_mapa.css?v=<?= filemtime(__DIR__ . '/css/hud_mapa.css') ?>">

    <!-- Versões automáticas dos fundos: evita imagens antigas no cache. -->
    <style>
        .casa_inicial {
            background-image: url("assets/casa.png?v=<?= filemtime(__DIR__ . '/assets/casa.png') ?>");
        }

        .fundo_combate {
            background-image: url("assets/fundo_combate.png?v=<?= filemtime(__DIR__ . '/assets/fundo_combate.png') ?>");
        }

        .aqua_dentro {
            background-image: url("assets/Aqua_dentro.png?v=<?= filemtime(__DIR__ . '/assets/Aqua_dentro.png') ?>");
        }
    </style>
</head>
<body>

    <div id="tela_jogo" class="casa_inicial"></div>

    <template id="navegacao_jogo">
        <nav class="hud_jogo" aria-label="Menu do jogador">
            <button type="button" class="hud_acao" data-destino="inventario.php">Inventário</button>
            <button type="button" class="hud_acao" data-destino="historico.php">Histórico</button>
            <button type="button" class="hud_acao" data-destino="equipe.php">Equipe</button>
            <button type="button" class="hud_acao hud_sair" data-sair>Sair da conta</button>
        </nav>
    </template>

    <template id="cena_casa">
        <a id="ponto_inventario" class="ponto_mapa ponto_objeto" href="inventario.php"
           aria-label="Abrir inventário" data-rotulo="Inventário"></a>
        <a id="ponto_historico" class="ponto_mapa ponto_objeto" href="historico.php"
           aria-label="Abrir histórico" data-rotulo="Histórico"></a>
        <a id="ponto_equipe" class="ponto_mapa ponto_objeto" href="equipe.php"
           aria-label="Abrir equipe" data-rotulo="Equipe"></a>
        <button type="button" class="hud_acao casa_sair" data-sair>Sair da conta</button>
        <button type="button" id="botao_varanda" class="ponto_mapa" data-rotulo="Varanda"
                aria-label="Ir para a varanda"
                onclick="trocarfundo('varanda'); goTo('cena_varanda'); carregarIscas()"></button>
        <button type="button" id="botao_porta" class="ponto_mapa" data-rotulo="Destinos"
                aria-label="Escolher destino"
                onclick="goTo('escolher_destino')"></button>
    </template>

    <template id="cena_varanda">

        <button type="button" class="botao_voltar"
                onclick="trocarfundo('casa_inicial'); goTo('cena_casa')">
            Voltar
        </button>
        <button type="button" id="botao_boia" class="ponto_mapa" data-rotulo="Pescar"
                aria-label="Procurar Pescado selvagem"
                onclick="abrirCombate()"></button>

        <div id="aviso_pesca" role="status" aria-live="polite" hidden></div>
        <aside id="painel_iscas" aria-label="Escolha da isca">
            <h2>ISCAS DE CHEFE</h2>
            <p>Escolha uma isca antes de pescar. Chance de chefe: 5%.</p>
            <div id="lista_iscas"></div>
            <p id="mensagem_iscas" role="status" aria-live="polite"></p>
        </aside>
    </template>

    <template id="cena_combate">
        <div id="interface_combate">
            <img id="sprite_inimigo"
                 class="sprite_pescado"
                 alt="Pescado inimigo">

            <img id="sprite_jogador"
                 class="sprite_pescado"
                 alt="Pescado do jogador">

            <div id="painel_inimigo" class="painel_pescado" data-tipo="">
                <span class="faixa_tipo_pescado" aria-hidden="true"></span>
                <span class="marca_maremon" aria-hidden="true">
                    <svg viewBox="0 0 100 100" focusable="false">
                        <path d="M85 46 C81 19 48 9 26 25 C6 40 9 67 28 77 C49 88 67 70 62 54 C57 38 42 36 34 45 C26 54 32 66 43 66 C54 66 55 54 48 51" />
                    </svg>
                </span>
                <div class="painel_conteudo">
                    <div class="painel_identidade">
                        <h3 id="nome_inimigo">Inimigo</h3>
                        <span id="tipo_inimigo" class="tag_tipo" aria-label="Tipo"></span>
                        <span id="status_inimigo" class="tag_status" hidden></span>
                        <span class="nivel_pescado">Nv. <span id="nivel_inimigo">—</span></span>
                    </div>
                    <div class="painel_vida">
                        <span class="rotulo_hp">HP</span>
                        <div class="barra_hp">
                            <div id="barra_inimigo" class="vida"></div>
                        </div>
                        <p id="hp_inimigo">—</p>
                    </div>
                </div>
            </div>

            <div id="painel_jogador" class="painel_pescado" data-tipo="">
                <span class="faixa_tipo_pescado" aria-hidden="true"></span>
                <span class="marca_maremon" aria-hidden="true">
                    <svg viewBox="0 0 100 100" focusable="false">
                        <path d="M85 46 C81 19 48 9 26 25 C6 40 9 67 28 77 C49 88 67 70 62 54 C57 38 42 36 34 45 C26 54 32 66 43 66 C54 66 55 54 48 51" />
                    </svg>
                </span>
                <div class="painel_conteudo">
                    <div class="painel_identidade">
                        <h3 id="nome_jogador">Maremon</h3>
                        <span id="tipo_jogador" class="tag_tipo" aria-label="Tipo"></span>
                        <span id="status_jogador" class="tag_status" hidden></span>
                        <span class="nivel_pescado">Nv. <span id="nivel_jogador">—</span></span>
                    </div>
                    <div class="painel_vida">
                        <span class="rotulo_hp">HP</span>
                        <div class="barra_hp">
                            <div id="barra_jogador" class="vida"></div>
                        </div>
                        <p id="hp_jogador">—</p>
                    </div>
                </div>
            </div>

            <div id="menu_combate">
                <div id="registro_combate">

                    <div id="caixa_dialogo_combate">
                        <div id="aviso_boss" class="faixa_boss" hidden>BOSS · DRK · HP ×5</div>
                        <p id="mensagem_combate" role="status" aria-live="polite">
                            Preparando combate...
                        </p>
                    </div>
                </div>

                <div id="comandos_combate">
                    <div id="menu_principal_combate" class="menu_acoes">
                        <button type="button" id="botao_lutar"
                                class="comando_acao comando_lutar"
                                onclick="mostrarAtaquesCombate()">
                            <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                <path d="M6 4l7 7-2.5 3.5 6 6-3.5 3.5-6-6L3.5 21l-1-7z"/>
                                <path d="M26 4l-7 7 2.5 3.5-6 6 3.5 3.5 6-6 3.5 3z"/>
                                <path d="M12 24l-3 4M20 24l3 4"/>
                            </svg>
                            <span>LUTAR</span>
                        </button>

                        <button type="button" id="botao_itens_combate"
                                class="comando_acao comando_bolsa"
                                onclick="abrirItensCombate()">
                            <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                <path d="M6 11h20l2 15H4z"/>
                                <path d="M12 11V8a4 4 0 0 1 8 0v3"/>
                                <path d="M15 16v7M11.5 19.5h7"/>
                            </svg>
                            <span>BOLSA</span>
                        </button>

                        <button type="button" id="botao_trocar_combate"
                                class="comando_acao comando_pescados"
                                onclick="abrirTrocaCombate()">
                            <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                <path d="M3 16c7-10 16-10 23 0-7 10-16 10-23 0z"/>
                                <path d="M4 16L1 11v10z"/>
                                <circle cx="20" cy="14" r="1.5"/>
                                <path d="M10 19l5 3"/>
                            </svg>
                            <span>PESCADOS</span>
                        </button>

                        <button type="button" id="botao_fugir"
                                class="comando_acao comando_fugir"
                                onclick="fugirCombate()">
                            <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                <circle cx="18" cy="5" r="3"/>
                                <path d="M14 12l-5 6 5 3-4 8M14 12l6 3 6-1M14 12l-2 9 10 4 3 5"/>
                            </svg>
                            <span>FUGIR</span>
                        </button>
                        <button type="button" id="botao_capturar"
                                aria-label="Capturar Pescado"
                                title="Capturar Pescado"
                                onclick="capturarPescado()">
                            <img src="assets/ui/captura.png" alt="" aria-hidden="true">
                        </button>
                    </div>

                    <div id="menu_ataques_combate" class="combate_painel_aux" hidden>
                        <div class="cabecalho_submenu">
                            <strong>ESCOLHA UM ATAQUE</strong>
                            <button type="button" onclick="fecharSubmenuCombate()">Voltar ×</button>
                        </div>
                        <div id="ataques_combate"></div>
                    </div>

                    <div id="troca_combate" class="combate_painel_aux" hidden>
                        <div class="cabecalho_submenu">
                            <strong>ESCOLHA UM PESCADO</strong>
                            <button type="button" onclick="fecharTrocaCombate()">Voltar ×</button>
                        </div>
                        <p>Trocar voluntariamente consome seu turno.</p>
                        <div id="opcoes_troca_combate"></div>
                    </div>

                    <div id="itens_combate" class="combate_painel_aux" hidden>
                        <div class="cabecalho_submenu">
                            <strong>MOCHILA DE COMBATE</strong>
                            <button type="button" onclick="fecharItensCombate()">Voltar ×</button>
                        </div>
                        <div class="campos_itens">
                            <label for="item_batalha">
                                Item
                                <select id="item_batalha"></select>
                            </label>
                            <label for="alvo_batalha">
                                Pescado
                                <select id="alvo_batalha"></select>
                            </label>
                        </div>
                        <div class="acoes_itens">
                            <button type="button" id="botao_usar_combate"
                                    onclick="usarItemNoCombate()">
                                Usar item (consome turno)
                            </button>
                        </div>
                    </div>

                    <div id="painel_tipos" class="combate_painel_aux" hidden>
                        <div class="cabecalho_submenu">
                            <strong>VANTAGENS DE TIPO (1,3×)</strong>
                            <button type="button" onclick="mostrarTipos()">Voltar ×</button>
                        </div>
                        <ul>
                            <li>BLU → RED → YEL → PUR → GRN → BLU</li>
                            <li>LGT vence DRK.</li>
                            <li>DRK vence RED, BLU, YEL, GRN e PUR.</li>
                            <li>Demais confrontos: dano normal (1×).</li>
                        </ul>
                        <small>O tipo é o do ataque contra o tipo do defensor.</small>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template id="cena_aqua_fora">
        <button type="button" class="botao_voltar"
                onclick="trocarfundo('casa_inicial'); goTo('cena_casa')">
            Voltar
        </button>
        <button type="button" id="botao_entrar_loja" class="ponto_mapa" data-rotulo="Loja Maremon"
                aria-label="Entrar na Loja Maremon"
                onclick="abrirLoja()"></button>
        <button type="button" id="botao_entrar_centro" class="ponto_mapa" data-rotulo="Centro Maremon"
                aria-label="Entrar no Centro Maremon"
                onclick="trocarfundo('aqua_dentro'); goTo('cena_aqua_dentro')"></button>
    </template>

    <template id="cena_aqua_dentro">
        <div class="centro_cura">
            <div class="centro_cura_emblema" aria-hidden="true">✦</div>
            <span class="centro_cura_sobretitulo">MAREMON · CENTRO DE RECUPERAÇÃO</span>
            <h2>Centro de Cura</h2>
            <p>Uma pausa no oceano para restaurar seus Pescados. Recupere a equipe inteira e volte à aventura!</p>
            <div class="centro_cura_acoes">
                <button type="button" class="centro_cura_confirmar" onclick="curarEquipe()">
                    ✦ Curar meus Pescados
                </button>
                <button type="button" class="centro_cura_voltar"
                        onclick="trocarfundo('aqua_fora'); goTo('cena_aqua_fora')">
                    Voltar
                </button>
            </div>
            <p id="mensagem_cura" role="status" aria-live="polite"></p>
            <span class="centro_cura_rodape">◆ SUA EQUIPE MERECE DESCANSAR ◆</span>
        </div>
    </template>

    <template id="cena_loja">
        <div class="loja_container">
            <header class="loja_cabecalho">
                <button type="button" class="loja_voltar" onclick="voltarDaLoja()">
                    <span aria-hidden="true">‹</span> Voltar
                </button>
                <div class="loja_titulo">
                    <h1>Loja Maremon</h1>
                    <span>Artigos de pesca</span>
                </div>
                <div class="loja_saldo">
                    <span class="loja_moeda" aria-hidden="true">$</span>
                    <div><span>Suas moedas</span><strong id="saldo_loja">—</strong></div>
                </div>
            </header>
            <div class="loja_intro">
                <span class="loja_intro_icone" aria-hidden="true">◆</span>
                <div><h2>Suprimentos</h2><p>Escolha os itens e a quantidade para sua próxima aventura.</p></div>
            </div>
            <p id="mensagem_loja" role="status" aria-live="polite"></p>
            <div class="loja_rotulo"><h2>Produtos disponíveis</h2><span id="resumo_loja"></span></div>
            <div id="produtos_loja"></div>
        </div>
    </template>

    <template id="modelo_produto_loja">
        <article class="produto_loja">
            <div class="produto_loja_info">
                <span class="loja_placeholder" aria-hidden="true"></span>
                <div class="produto_loja_texto">
                    <h3></h3><p></p>
                    <span class="preco_loja"></span>
                </div>
            </div>
            <div class="controles_loja">
                <label class="loja_quantidade_rotulo">Qtd. <input type="number" min="1" max="99" step="1" value="1" class="quantidade_loja"></label>
                <span class="loja_total"></span>
                <button type="button" class="comprar_loja">Comprar</button>
            </div>
        </article>
    </template>

    <template id="escolher_destino">
        <button type="button" class="botao_voltar"
                onclick="trocarfundo('casa_inicial'); goTo('cena_casa')">
            Voltar
        </button>

        <div id="locais_viagem">
            <button type="button" class="local_viagem"
                    onclick="trocarfundo('aqua_fora'); goTo('cena_aqua_fora')">
                Centro Maremon
            </button>
            <button type="button" class="local_viagem" onclick="abrirLoja()">
                Loja Maremon
            </button>
            <button type="button" class="local_viagem"
                    onclick="window.location.href='pokedex.php'">
                Enciclopédia
            </button>
            <button type="button" class="local_viagem">????????</button>
        </div>
    </template>

<script src="js/elementos.js?v=<?= filemtime(__DIR__ . '/js/elementos.js') ?>"></script>
    <script src="js/game.js?v=<?= filemtime(__DIR__ . '/js/game.js') ?>"></script>
    <script src="js/monstros.js?v=<?= filemtime(__DIR__ . '/js/monstros.js') ?>"></script>
    <script src="js/combate.js?v=<?= filemtime(__DIR__ . '/js/combate.js') ?>"></script>
    <script src="js/cura.js?v=<?= filemtime(__DIR__ . '/js/cura.js') ?>"></script>
    <script src="js/loja.js?v=<?= filemtime(__DIR__ . '/js/loja.js') ?>"></script>
    <script src="js/iscas.js?v=<?= filemtime(__DIR__ . '/js/iscas.js') ?>"></script>

</body>
</html>
