<?php
namespace Maremon\Controllers\Http;

use PDO;
use Throwable;
use Maremon\Services\ItemService;
use Maremon\Services\EvolucaoService;
use Maremon\Exceptions\JogoException;

final class UsarItemAction {
    public function executar(): void {
        header('Content-Type: application/json; charset=utf-8');
        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        if ($_SERVER['REQUEST_METHOD']!=='POST') $this->saidaInventario(['sucesso'=>false,'erro'=>'Utilize POST.'],405);
        $dados=json_decode(file_get_contents('php://input'),true);
        if (!is_array($dados)) $this->saidaInventario(['sucesso'=>false,'erro'=>'JSON inválido.'],400);
        $token=$dados['csrf_token']??null;
        if (!is_string($token) || !is_string($_SESSION['csrf_inventario']??null)
            || !hash_equals($_SESSION['csrf_inventario'],$token)) {
            $this->saidaInventario(['sucesso'=>false,'erro'=>'Solicitação não autorizada.'],403);
        }
        $itemId=filter_var($dados['item_id']??null,FILTER_VALIDATE_INT);
        $pescadoId=filter_var($dados['pescado_id']??null,FILTER_VALIDATE_INT);
        if (!$itemId || !$pescadoId) $this->saidaInventario(['sucesso'=>false,'erro'=>'Seleção inválida.'],400);
        if ($_SESSION['combate']['ativo']??false) {
            $this->saidaInventario(['sucesso'=>false,'erro'=>'Use a mochila do combate ou finalize a batalha.'],409);
        }
        try {
            $pdo->beginTransaction();
                                                                      
            $s = (new \Maremon\Models\InventarioRepository($pdo))->bloquearContaUso(['id'=>$pescadorId]);
            if (!$s->fetch()) throw new JogoException('Jogador não encontrado.');
            $s = (new \Maremon\Models\InventarioRepository($pdo))->bloquearItem(['id'=>$pescadorId,'item'=>$itemId]);
            $item=$s->fetch(PDO::FETCH_ASSOC);
            if (!$item || (int)$item['quantidade']<1) throw new JogoException('Você não possui esse item.');
            $s = (new \Maremon\Models\InventarioRepository($pdo))->bloquearAlvo(['id'=>$pescadorId,'pescado'=>$pescadoId]);
            $alvo=$s->fetch(PDO::FETCH_ASSOC);
            if (!$alvo) throw new JogoException('Você não possui esse Pescado.');
            if ($alvo['hp']===null) $alvo['hp']=$alvo['hp_max'];
            if (in_array($item['efeito'],['evoluir','dna_boss'],true)) {
                $evolucaoId=filter_var($dados['evolucao_id']??null,FILTER_VALIDATE_INT);
                if (!$evolucaoId || $evolucaoId<1) throw new JogoException('Escolha uma evolução compatível.');
                $retorno=EvolucaoService::evoluir($pdo,$pescadorId,$pescadoId,$itemId,$evolucaoId,$item,$alvo);
                $pdo->commit();
                $this->saidaInventario($retorno);
            }
            $resultado=(new ItemService())->aplicar($item,$alvo,false);
            $s = (new \Maremon\Models\InventarioRepository($pdo))->salvarCura(['hp'=>(int)$resultado['hp'],'status'=>$resultado['status'],
                'turnos'=>(int)$resultado['status_turnos'],
                'estagio'=>(int)($resultado['estagio_evolucao']??$alvo['estagio_evolucao']),
                'id'=>$pescadorId,'pescado'=>$pescadoId]);
            $s = (new \Maremon\Models\InventarioRepository($pdo))->consumirItem(['id'=>$pescadorId,'item'=>$itemId]);
            if ($s->rowCount()!==1) throw new JogoException('Falha ao consumir o item.');
            $s = (new \Maremon\Models\InventarioRepository($pdo))->removerEstoqueVazio(['id'=>$pescadorId,'item'=>$itemId]);
            $pdo->commit();
            $this->saidaInventario(['sucesso'=>true,'mensagem'=>$item['nome'].' utilizado com sucesso!',
                'hp_anterior'=>(int)$alvo['hp'],'hp_atual'=>(int)$resultado['hp'],'hp_max'=>(int)$resultado['hp_max'],
                'status_atual'=>$resultado['status'],'estagio_evolucao'=>(int)($resultado['estagio_evolucao']??$alvo['estagio_evolucao']),
                'quantidade_restante'=>(int)$item['quantidade']-1]);
        } catch (JogoException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $this->saidaInventario(['sucesso'=>false,'erro'=>$e->getMessage()],400);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log((string)$e);
            $this->saidaInventario(['sucesso'=>false,'erro'=>'Não foi possível utilizar o item.'],500);
        }
    }

    private function saidaInventario(array $retorno,int $status=200): never {
        http_response_code($status);
        echo json_encode($retorno,JSON_UNESCAPED_UNICODE);exit;
    }
}
