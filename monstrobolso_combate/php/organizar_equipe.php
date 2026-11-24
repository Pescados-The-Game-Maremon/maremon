<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/proteger.php';
require_once __DIR__.'/../app/bootstrap.php';
use Maremon\Services\FormacaoEquipe;
function retEquipe(array $dados,int $status=200): never {
    http_response_code($status); echo json_encode($dados,JSON_UNESCAPED_UNICODE); exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') retEquipe(['sucesso'=>false,'erro'=>'Método não permitido.'],405);
$dados=json_decode(file_get_contents('php://input'),true);
$token=$dados['csrf_token']??null;
if (!is_string($token) || !is_string($_SESSION['csrf_equipe']??null)
    || !hash_equals($_SESSION['csrf_equipe'],$token)) retEquipe(['sucesso'=>false,'erro'=>'Solicitação não autorizada.'],403);
$pescadoId=filter_var($dados['pescado_id']??null,FILTER_VALIDATE_INT);
$slot=filter_var($dados['slot']??null,FILTER_VALIDATE_INT);
if (!$pescadoId || $pescadoId<1 || $slot===false || $slot===null || $slot<0 || $slot>3)
    retEquipe(['sucesso'=>false,'erro'=>'Pescado ou posição inválida.'],400);
if ($_SESSION['combate']['ativo']??false) retEquipe(['sucesso'=>false,'erro'=>'Finalize o combate antes de organizar.'],409);
try {
    $pdo->beginTransaction();
    $s=$pdo->prepare('SELECT id FROM public.pescador WHERE id=:id FOR UPDATE');
    $s->execute(['id'=>$pescadorId]);
    if (!$s->fetch()) throw new RuntimeException('Conta não encontrada.');
    $s=$pdo->prepare('SELECT pp.pescado_id, pp.equipe_slot, COALESCE(pp.hp_atual,s.hp) AS hp
        FROM public.pescador_pescado pp JOIN public.stats s ON s.pescado_id=pp.pescado_id
        WHERE pp.pescador_id=:id FOR UPDATE OF pp');
    $s->execute(['id'=>$pescadorId]);
    $registros=$s->fetchAll(PDO::FETCH_ASSOC);
    $formacao=FormacaoEquipe::mover($registros,$pescadoId,$slot);
    // Libera as posições primeiro, evitando conflito com o índice UNIQUE.
    $s=$pdo->prepare('UPDATE public.pescador_pescado SET equipe_slot=NULL,selecionado=FALSE WHERE pescador_id=:id');
    $s->execute(['id'=>$pescadorId]);
    $s=$pdo->prepare('UPDATE public.pescador_pescado SET equipe_slot=:slot WHERE pescador_id=:id AND pescado_id=:p');
    foreach ($formacao as $posicao=>$id) $s->execute(['slot'=>$posicao,'id'=>$pescadorId,'p'=>$id]);
    $ativo=FormacaoEquipe::primeiroVivo($registros,$formacao);
    if ($ativo!==null) {
        $s=$pdo->prepare('UPDATE public.pescador_pescado SET selecionado=TRUE WHERE pescador_id=:id AND pescado_id=:p');
        $s->execute(['id'=>$pescadorId,'p'=>$ativo]);
    }
    $pdo->commit();
    retEquipe(['sucesso'=>true,'mensagem'=>$slot===0?'Pescado enviado à reserva.':'Formação atualizada: posição '.$slot.'.']);
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    retEquipe(['sucesso'=>false,'erro'=>$e->getMessage()],400);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack(); error_log((string)$e);
    retEquipe(['sucesso'=>false,'erro'=>'Não foi possível organizar a equipe.'],500);
}
