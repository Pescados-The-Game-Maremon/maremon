<?php
namespace Maremon\Controllers\Http;

use PDO;
use Throwable;
use RuntimeException;
use InvalidArgumentException;
use Maremon\Services\FormacaoEquipe;

final class OrganizarEquipeAction {
    public function executar(): void {
        header('Content-Type: application/json; charset=utf-8');
        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        if ($_SERVER['REQUEST_METHOD']!=='POST') $this->retEquipe(['sucesso'=>false,'erro'=>'Método não permitido.'],405);
        $dados=json_decode(file_get_contents('php://input'),true);
        $token=$dados['csrf_token']??null;
        if (!is_string($token) || !is_string($_SESSION['csrf_equipe']??null)
            || !hash_equals($_SESSION['csrf_equipe'],$token)) $this->retEquipe(['sucesso'=>false,'erro'=>'Solicitação não autorizada.'],403);
        $pescadoId=filter_var($dados['pescado_id']??null,FILTER_VALIDATE_INT);
        $slot=filter_var($dados['slot']??null,FILTER_VALIDATE_INT);
        if (!$pescadoId || $pescadoId<1 || $slot===false || $slot===null || $slot<0 || $slot>3)
            $this->retEquipe(['sucesso'=>false,'erro'=>'Pescado ou posição inválida.'],400);
        if ($_SESSION['combate']['ativo']??false) $this->retEquipe(['sucesso'=>false,'erro'=>'Finalize o combate antes de organizar.'],409);
        try {
            $pdo->beginTransaction();
            $s = (new \Maremon\Models\EquipeDadosRepository($pdo))->bloquearContaFormacao(['id'=>$pescadorId]);
            if (!$s->fetch()) throw new RuntimeException('Conta não encontrada.');
            $s = (new \Maremon\Models\EquipeDadosRepository($pdo))->bloquearIntegrantes(['id'=>$pescadorId]);
            $registros=$s->fetchAll(PDO::FETCH_ASSOC);
            $formacao=FormacaoEquipe::mover($registros,$pescadoId,$slot);
                                                                                     
            $s = (new \Maremon\Models\EquipeDadosRepository($pdo))->limparPosicoes(['id'=>$pescadorId]);
            (new \Maremon\Models\EquipeDadosRepository($pdo))->atribuirPosicoes($pescadorId, $formacao);
            $ativo=FormacaoEquipe::primeiroVivo($registros,$formacao);
            if ($ativo!==null) {
                $s = (new \Maremon\Models\EquipeDadosRepository($pdo))->marcarAtivo(['id'=>$pescadorId,'p'=>$ativo]);
            }
            $pdo->commit();
            $this->retEquipe(['sucesso'=>true,'mensagem'=>$slot===0?'Pescado enviado à reserva.':'Formação atualizada: posição '.$slot.'.']);
        } catch (InvalidArgumentException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $this->retEquipe(['sucesso'=>false,'erro'=>$e->getMessage()],400);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack(); error_log((string)$e);
            $this->retEquipe(['sucesso'=>false,'erro'=>'Não foi possível organizar a equipe.'],500);
        }
    }

    private function retEquipe(array $dados,int $status=200): never {
        http_response_code($status); echo json_encode($dados,JSON_UNESCAPED_UNICODE); exit;
    }
}
