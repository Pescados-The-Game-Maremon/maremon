<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/proteger.php';
require_once __DIR__.'/../app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']!=='GET') {http_response_code(405);echo json_encode(['sucesso'=>false,'erro'=>'Método não permitido.']);exit;}
try {
 echo json_encode((new Maremon\Controllers\PokedexController($pdo))->listar(),JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {error_log((string)$e);http_response_code(500);echo json_encode(['sucesso'=>false,'erro'=>'Falha ao carregar catálogo.']);}
