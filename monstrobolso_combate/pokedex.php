<?php
require_once __DIR__.'/php/sessao.php';
if (empty($_SESSION['pescador_id'])) {header('Location: login.html');exit;}
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Enciclopédia — Maremon</title><link rel="stylesheet" href="css/equipe_melhorada.css"></head>
<body class="pagina_equipe"><main><h1>Enciclopédia dos Pescados</h1>
<p>Espécies, tipos e atributos base. Os níveis individuais continuam em Minha equipe.</p>
<img style="max-width:280px;width:100%;height:auto" src="assets/tabela_tipos.png" alt="Relação de vantagens entre tipos">
<p id="aviso_pokedex">Carregando catálogo...</p><div id="lista_equipe"></div>
<p><a href="index.php">Voltar ao jogo</a></p></main>
<script src="js/monstros.js"></script><script src="js/pokedex.js"></script></body></html>
