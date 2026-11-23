<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/proteger.php';
if ($_SERVER['REQUEST_METHOD']!=='GET') {http_response_code(405);echo json_encode(['sucesso'=>false,'erro'=>'Método inválido.']);exit;}
try {
 $s=$pdo->prepare('SELECT i.id,i.nome,i.descricao,i.categoria,i.efeito,i.valor_efeito,pi.quantidade
     FROM public.pescador_itens pi JOIN public.loja_itens i ON i.id=pi.item_id
     WHERE pi.pescador_id=:id AND pi.quantidade>0 ORDER BY i.categoria,i.nome');
 $s->execute(['id'=>$pescadorId]);$itens=$s->fetchAll(PDO::FETCH_ASSOC);
 $s=$pdo->prepare('SELECT pescado_id AS id,nome,tipo,nivel,exp,status,status_turnos,
     estagio_evolucao,COALESCE(hp_atual,hp_max) AS hp_atual,hp_max,atk,def,spe,selecionado,equipe_slot
     FROM public.vw_equipe_maremon WHERE jogador_id=:id ORDER BY selecionado DESC,nome');
 $s->execute(['id'=>$pescadorId]);$pescados=$s->fetchAll(PDO::FETCH_ASSOC);
 if (empty($_SESSION['csrf_inventario'])) $_SESSION['csrf_inventario']=bin2hex(random_bytes(32));
 echo json_encode(['sucesso'=>true,'itens'=>$itens,'pescados'=>$pescados,'csrf_token'=>$_SESSION['csrf_inventario']],JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {error_log((string)$e);http_response_code(500);echo json_encode(['sucesso'=>false,'erro'=>'Falha ao listar inventário.']);}
