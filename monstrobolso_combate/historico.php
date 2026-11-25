<?php
require_once __DIR__.'/php/sessao.php';
if (empty($_SESSION['pescador_id'])) {header('Location: login.html');exit;}
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Histórico de batalhas — Maremon</title><link rel="stylesheet" href="css/equipe_melhorada.css"></head>
<body class="pagina_equipe"><main><h1>Histórico das últimas 100 batalhas</h1>
<p id="aviso_historico">Carregando...</p><div id="lista_equipe"></div>
<p><a href="index.php">Voltar ao jogo</a></p></main><script src="js/elementos.js?v=<?= filemtime(__DIR__ . '/js/elementos.js') ?>"></script>
<script src="js/historico.js?v=<?= filemtime(__DIR__ . '/js/historico.js') ?>"></script></body></html>
