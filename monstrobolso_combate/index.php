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

if ($possuiPescado === false || $possuiPescado === "f" || $possuiPescado === "0") {
    header("Location: escolher_inicial.php");
    exit;
}

?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Maremon</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/fundos.css">
    <link rel="stylesheet" href="css/buttons.css?v=2">
    <link rel="stylesheet" href="css/combate.css">
</head>
<body>

    <div id="tela_jogo" class="casa_inicial"></div>


<section id="cena_casa">
    <button onclick="sairDaConta()">Sair da conta</button>
    <button onclick="window.location.href='equipe.php'">Minha equipe</button>
    <button id=botao_varanda onclick="trocarfundo('varanda'); goTo('cena_varanda')"></button>
    <button id=botao_porta onclick="goTo('escolher_destino')"></button>
</section>

<section id="cena_varanda">
    <button class="botao_voltar" onclick="trocarfundo('casa_inicial'); goTo('cena_casa')">voltar</button>
    <button id="botao_boia" onclick="abrirCombate()"></button>
</section>






<section id="cena_combate">
    <div id="interface_combate">
        <img
          id="sprite_inimigo"
          class="sprite_pescado"
          alt="Pescado inimigo"
         >

        <img
        id="sprite_jogador"
        class="sprite_pescado"
        alt="Pescado do jogador"
        >   

        <div id="painel_inimigo" class="painel_pescado">
            <h3 id="nome_inimigo"></h3>
            <p id="hp_inimigo"></p>
            <div class="barra_hp">
                <div id="barra_inimigo" class="vida"></div>
            </div>
        </div>

        <div id="painel_jogador" class="painel_pescado">
            <h3 id="nome_jogador"></h3>
            <p id="hp_jogador"></p>
            <div class="barra_hp">
                <div id="barra_jogador" class="vida"></div>
            </div>
        </div>

        <div id="menu_combate">
            <p id="mensagem_combate">
                Preparando combate...
            </p>

            <div id="ataques_combate"></div>
            <button id="botao_capturar" onclick="capturarPescado()">Capturar</button>
            <button id="botao_fugir"
                    onclick="fugirCombate()">
                Fugir
            </button>
        </div>

    </div>
</section>



<section id="cena_aqua_fora">
    <button class="botao_voltar"
        onclick="trocarfundo('casa_inicial'); goTo('cena_casa')">
        Voltar
    </button>

    <button id="botao_entrar_centro" onclick="trocarfundo('aqua_dentro'); goTo('cena_aqua_dentro')"></button>
</section>


<section id="cena_aqua_dentro">

    <div class="centro_cura">

        <h2>Centro de Cura</h2>

        <p>
            Bem-vindo! Aqui podemos recuperar
            a energia dos seus Pescados.
        </p>

        <button onclick="curarEquipe()">
            Curar meus Pescados
        </button>

        <p id="mensagem_cura"></p>

    <button onclick="trocarfundo('aqua_fora'); goTo('cena_aqua_fora')">Voltar</button>

    </div>
</section>



<section id="escolher_destino">
    <button class="botao_voltar" onclick="trocarfundo('casa_inicial'); goTo('cena_casa')">voltar</button>


    <div id="locais_viagem">

        <button class="local_viagem" onclick="trocarfundo('aqua_fora'); goTo('cena_aqua_fora')">Centro Maremon</button>

        <button class="local_viagem">????????</button>

        <button class="local_viagem">????????</button>

        <button class="local_viagem">????????</button>

    </div>

</section>



<script src="js/game.js"></script>
<script src="js/monstros.js"></script>
<script src="js/combate.js"></script>
<script src="js/cura.js"></script>

</body>
</html>