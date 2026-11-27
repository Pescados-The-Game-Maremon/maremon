<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/proteger.php';
require_once __DIR__.'/../app/bootstrap.php';
use Maremon\Services\ItemService;
use Maremon\Services\EvolucaoService;
use Maremon\Exceptions\JogoException;
function saidaInventario(array $retorno,int $status=200): never {
    http_response_code($status);
    echo json_encode($retorno,JSON_UNESCAPED_UNICODE);exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') saidaInventario(['sucesso'=>false,'erro'=>'Utilize POST.'],405);
$dados=json_decode(file_get_contents('php://input'),true);
if (!is_array($dados)) saidaInventario(['sucesso'=>false,'erro'=>'JSON inválido.'],400);
$token=$dados['csrf_token']??null;
if (!is_string($token) || !is_string($_SESSION['csrf_inventario']??null)
    || !hash_equals($_SESSION['csrf_inventario'],$token)) {
    saidaInventario(['sucesso'=>false,'erro'=>'Solicitação não autorizada.'],403);
}
$itemId=filter_var($dados['item_id']??null,FILTER_VALIDATE_INT);
$pescadoId=filter_var($dados['pescado_id']??null,FILTER_VALIDATE_INT);
if (!$itemId || !$pescadoId) saidaInventario(['sucesso'=>false,'erro'=>'Seleção inválida.'],400);
if ($_SESSION['combate']['ativo']??false) {
    saidaInventario(['sucesso'=>false,'erro'=>'Use a mochila do combate ou finalize a batalha.'],409);
}
try {
    $pdo->beginTransaction();
    // Lock da conta: mesma ordem da compra, combate e equipe.
    $s=$pdo->prepare('SELECT id FROM public.pescador WHERE id=:id FOR UPDATE');
    $s->execute(['id'=>$pescadorId]);
    if (!$s->fetch()) throw new JogoException('Jogador não encontrado.');
    $s=$pdo->prepare('SELECT i.nome,i.efeito,i.valor_efeito,pi.quantidade FROM public.pescador_itens pi
        JOIN public.loja_itens i ON i.id=pi.item_id
        WHERE pi.pescador_id=:id AND pi.item_id=:item FOR UPDATE OF pi');
    $s->execute(['id'=>$pescadorId,'item'=>$itemId]);
    $item=$s->fetch(PDO::FETCH_ASSOC);
    if (!$item || (int)$item['quantidade']<1) throw new JogoException('Você não possui esse item.');
    $s=$pdo->prepare('SELECT pp.hp_atual AS hp,pp.nivel,pp.status,pp.status_turnos,pp.estagio_evolucao,
        s.hp+2*GREATEST(pp.nivel-5,0)+10*pp.bonus_evolucao_legado AS hp_max
        FROM public.pescador_pescado pp JOIN public.stats s ON s.pescado_id=pp.pescado_id
        WHERE pp.pescador_id=:id AND pp.pescado_id=:pescado FOR UPDATE OF pp');
    $s->execute(['id'=>$pescadorId,'pescado'=>$pescadoId]);
    $alvo=$s->fetch(PDO::FETCH_ASSOC);
    if (!$alvo) throw new JogoException('Você não possui esse Pescado.');
    if ($alvo['hp']===null) $alvo['hp']=$alvo['hp_max'];
    if (in_array($item['efeito'],['evoluir','dna_boss'],true)) {
        $evolucaoId=filter_var($dados['evolucao_id']??null,FILTER_VALIDATE_INT);
        if (!$evolucaoId || $evolucaoId<1) throw new JogoException('Escolha uma evolução compatível.');
        $retorno=EvolucaoService::evoluir($pdo,$pescadorId,$pescadoId,$itemId,$evolucaoId,$item,$alvo);
        $pdo->commit();
        saidaInventario($retorno);
    }
    $resultado=(new ItemService())->aplicar($item,$alvo,false);
    $s=$pdo->prepare('UPDATE public.pescador_pescado SET hp_atual=:hp,status=:status,
        status_turnos=:turnos,estagio_evolucao=:estagio
        WHERE pescador_id=:id AND pescado_id=:pescado');
    $s->execute(['hp'=>(int)$resultado['hp'],'status'=>$resultado['status'],
        'turnos'=>(int)$resultado['status_turnos'],
        'estagio'=>(int)($resultado['estagio_evolucao']??$alvo['estagio_evolucao']),
        'id'=>$pescadorId,'pescado'=>$pescadoId]);
    $s=$pdo->prepare('UPDATE public.pescador_itens SET quantidade=quantidade-1
        WHERE pescador_id=:id AND item_id=:item AND quantidade>0');
    $s->execute(['id'=>$pescadorId,'item'=>$itemId]);
    if ($s->rowCount()!==1) throw new JogoException('Falha ao consumir o item.');
    $s=$pdo->prepare('DELETE FROM public.pescador_itens WHERE pescador_id=:id AND item_id=:item AND quantidade=0');
    $s->execute(['id'=>$pescadorId,'item'=>$itemId]);
    $pdo->commit();
    saidaInventario(['sucesso'=>true,'mensagem'=>$item['nome'].' utilizado com sucesso!',
        'hp_anterior'=>(int)$alvo['hp'],'hp_atual'=>(int)$resultado['hp'],'hp_max'=>(int)$resultado['hp_max'],
        'status_atual'=>$resultado['status'],'estagio_evolucao'=>(int)($resultado['estagio_evolucao']??$alvo['estagio_evolucao']),
        'quantidade_restante'=>(int)$item['quantidade']-1]);
} catch (JogoException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    saidaInventario(['sucesso'=>false,'erro'=>$e->getMessage()],400);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log((string)$e);
    saidaInventario(['sucesso'=>false,'erro'=>'Não foi possível utilizar o item.'],500);
}
