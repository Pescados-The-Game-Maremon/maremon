<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/proteger.php';
require_once __DIR__.'/../app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']!=='GET') {http_response_code(405);echo json_encode(['sucesso'=>false,'erro'=>'Método inválido.']);exit;}
try {
 $dados=(new Maremon\Controllers\EquipeController($pdo))->listar($pescadorId);
 if (empty($_SESSION['csrf_equipe'])) $_SESSION['csrf_equipe']=bin2hex(random_bytes(32));
 $dados['csrf_token']=$_SESSION['csrf_equipe'];
 $dados['em_combate']=(bool)($_SESSION['combate']['ativo']??false);
 echo json_encode($dados,JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {error_log((string)$e);http_response_code(500);echo json_encode(['sucesso'=>false,'erro'=>'Erro ao carregar equipe.']);}
