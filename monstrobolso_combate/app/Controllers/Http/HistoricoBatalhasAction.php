<?php
namespace Maremon\Controllers\Http;

use PDO;
use Throwable;

final class HistoricoBatalhasAction {
    public function executar(): void {
        header('Content-Type: application/json; charset=utf-8');
        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();
        if ($_SERVER['REQUEST_METHOD']!=='GET') {http_response_code(405);echo json_encode(['sucesso'=>false,'erro'=>'Método inválido.']);exit;}
        try {
         $s = (new \Maremon\Models\CombateDadosRepository($pdo))->listarHistorico(['id'=>$pescadorId]);
         echo json_encode(['sucesso'=>true,'batalhas'=>$s->fetchAll(PDO::FETCH_ASSOC)],JSON_UNESCAPED_UNICODE);
        } catch(Throwable $e){error_log((string)$e);http_response_code(500);echo json_encode(['sucesso'=>false,'erro'=>'Erro ao consultar histórico.']);}
    }
}
