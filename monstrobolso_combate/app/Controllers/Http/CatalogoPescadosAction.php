<?php
namespace Maremon\Controllers\Http;

use Throwable;

final class CatalogoPescadosAction {
    public function executar(): void {
        header('Content-Type: application/json; charset=utf-8');
        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        if ($_SERVER['REQUEST_METHOD']!=='GET') {http_response_code(405);echo json_encode(['sucesso'=>false,'erro'=>'Método não permitido.']);exit;}
        try {
         echo json_encode((new \Maremon\Controllers\PokedexController($pdo))->listar(),JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {error_log((string)$e);http_response_code(500);echo json_encode(['sucesso'=>false,'erro'=>'Falha ao carregar catálogo.']);}
    }
}
