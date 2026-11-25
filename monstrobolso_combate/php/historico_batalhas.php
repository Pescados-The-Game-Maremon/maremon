<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/proteger.php';
if ($_SERVER['REQUEST_METHOD']!=='GET') {http_response_code(405);echo json_encode(['sucesso'=>false,'erro'=>'Método inválido.']);exit;}
try {
 $s=$pdo->prepare('SELECT id,resultado,turnos,dinheiro_ganho,pescado_jogador,
     pescado_inimigo,iniciado_em,finalizado_em
     FROM public.vw_historico_maremon WHERE pescador_id=:id ORDER BY id DESC LIMIT 100');
 $s->execute(['id'=>$pescadorId]);
 echo json_encode(['sucesso'=>true,'batalhas'=>$s->fetchAll(PDO::FETCH_ASSOC)],JSON_UNESCAPED_UNICODE);
} catch(Throwable $e){error_log((string)$e);http_response_code(500);echo json_encode(['sucesso'=>false,'erro'=>'Erro ao consultar histórico.']);}
