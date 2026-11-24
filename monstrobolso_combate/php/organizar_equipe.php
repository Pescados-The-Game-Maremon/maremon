<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/proteger.php';
function retEquipe(array $dados,int $status=200): never {
 http_response_code($status);echo json_encode($dados,JSON_UNESCAPED_UNICODE);exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') retEquipe(['sucesso'=>false,'erro'=>'Método não permitido.'],405);
$dados=json_decode(file_get_contents('php://input'),true);
$token=$dados['csrf_token']??null;
if (!is_string($token) || !is_string($_SESSION['csrf_equipe']??null)
 || !hash_equals($_SESSION['csrf_equipe'],$token)) retEquipe(['sucesso'=>false,'erro'=>'Solicitação não autorizada.'],403);
$pescadoId=filter_var($dados['pescado_id']??null,FILTER_VALIDATE_INT);
$slot=filter_var($dados['slot']??null,FILTER_VALIDATE_INT);
if (!$pescadoId || $slot===false || $slot===null || $slot<0 || $slot>3)
 retEquipe(['sucesso'=>false,'erro'=>'Pescado ou posição inválida.'],400);
if ($_SESSION['combate']['ativo']??false) retEquipe(['sucesso'=>false,'erro'=>'Finalize o combate antes de organizar.'],409);
try {
 $pdo->beginTransaction();
 $s=$pdo->prepare('SELECT id FROM public.pescador WHERE id=:id FOR UPDATE');
 $s->execute(['id'=>$pescadorId]);if (!$s->fetch()) throw new RuntimeException('Conta não encontrada.');
 $s=$pdo->prepare('SELECT selecionado,equipe_slot FROM public.pescador_pescado
   WHERE pescador_id=:id AND pescado_id=:p FOR UPDATE');
 $s->execute(['id'=>$pescadorId,'p'=>$pescadoId]);$registro=$s->fetch(PDO::FETCH_ASSOC);
 if (!$registro) { $pdo->rollBack(); retEquipe(['sucesso'=>false,'erro'=>'Pescado não pertence à conta.'],403); }
 if ($slot===0 && in_array($registro['selecionado'],[true,'t','1',1],true)) {
    $pdo->rollBack(); retEquipe(['sucesso'=>false,'erro'=>'Selecione outro Pescado ativo antes de remover este da equipe.'],400);
 }
 if ($slot!==0) {
    // Libera a posição ocupada por outro Pescado; não altera a escolha ativa.
    $s=$pdo->prepare('UPDATE public.pescador_pescado SET equipe_slot=NULL
       WHERE pescador_id=:id AND equipe_slot=:slot');
    $s->execute(['id'=>$pescadorId,'slot'=>$slot]);
 }
 $s=$pdo->prepare('UPDATE public.pescador_pescado SET equipe_slot=:slot
   WHERE pescador_id=:id AND pescado_id=:p');
 $s->execute(['id'=>$pescadorId,'p'=>$pescadoId,'slot'=>$slot===0?null:$slot]);
 $pdo->commit();
 retEquipe(['sucesso'=>true,'mensagem'=>$slot===0?'Pescado removido da equipe.':'Pescado colocado na posição '.$slot.'.']);
} catch (Throwable $e) {
 if ($pdo->inTransaction()) $pdo->rollBack();error_log((string)$e);
 retEquipe(['sucesso'=>false,'erro'=>'Não foi possível organizar a equipe.'],500);
}
