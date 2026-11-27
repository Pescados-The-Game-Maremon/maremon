<?php
namespace Maremon\Controllers\Http;

use Throwable;

final class BuscarAtaquesAction {
    public function executar(): void {
        header('Content-Type: application/json; charset=utf-8');
        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        $combate=$_SESSION['combate']??null;
        if (!is_array($combate) || !($combate['ativo']??false) || (int)($combate['pescador_id']??0)!==$pescadorId) {
            http_response_code(403); echo json_encode(['sucesso'=>false,'erro'=>'Combate inválido.']); exit;
        }
        try {
            $repo=new \Maremon\Models\CombateRepository($pdo);
            $ataques=$repo->ataques((int)$combate['jogador']['id'],(int)$combate['jogador']['nivel']);
            foreach ($ataques as &$a) {
                $a['pp_atual']=(int)($combate['pp_equipe'][(int)$combate['jogador']['id']][$a['id']]??$a['pp_max']);
            }
            unset($a);
            echo json_encode(['sucesso'=>true,'ataques'=>$ataques],JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log((string)$e);http_response_code(500);
            echo json_encode(['sucesso'=>false,'erro'=>'Erro ao carregar ataques.']);
        }
    }
}
