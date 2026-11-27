<?php
namespace Maremon\Controllers\Http;

use PDO;
use Throwable;

final class ListarInventarioAction {
    public function executar(): void {
        header('Content-Type: application/json; charset=utf-8');
        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        if ($_SERVER['REQUEST_METHOD']!=='GET') {http_response_code(405);echo json_encode(['sucesso'=>false,'erro'=>'Método inválido.']);exit;}
        try {
         $s = (new \Maremon\Models\InventarioRepository($pdo))->listarEstoque(['id'=>$pescadorId]);$itens=$s->fetchAll(PDO::FETCH_ASSOC);
         $s = (new \Maremon\Models\InventarioRepository($pdo))->listarAlvos(['id'=>$pescadorId]);$pescados=$s->fetchAll(PDO::FETCH_ASSOC);
         if (empty($_SESSION['csrf_inventario'])) $_SESSION['csrf_inventario']=bin2hex(random_bytes(32));
         echo json_encode(['sucesso'=>true,'itens'=>$itens,'pescados'=>$pescados,'evolucoes'=>\Maremon\Services\EvolucaoService::opcoes($pdo,$pescadorId),'em_combate'=>(bool)($_SESSION['combate']['ativo']??false),'csrf_token'=>$_SESSION['csrf_inventario']],JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {error_log((string)$e);http_response_code(500);echo json_encode(['sucesso'=>false,'erro'=>'Falha ao listar inventário.']);}
    }
}
