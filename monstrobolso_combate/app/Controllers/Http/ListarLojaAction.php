<?php
namespace Maremon\Controllers\Http;

use PDO;
use Throwable;
use RuntimeException;

final class ListarLojaAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store, private");

        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

                                 
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            http_response_code(405);
            echo json_encode([
                "sucesso" => false,
                "erro" => "Método não permitido."
            ]);
            exit;
        }

        try {
                                                   
            $stmt = (new \Maremon\Models\LojaRepository($pdo))->consultarSaldo([
                "pescador_id" => $pescadorId
            ]);

            $saldo = $stmt->fetchColumn();

            if ($saldo === false) {
                throw new RuntimeException(
                    "Jogador não encontrado."
                );
            }

                                                
            $stmt = (new \Maremon\Models\LojaRepository($pdo))->listarCatalogo(["pescador_id" => $pescadorId]);
            $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                    
            if (empty($_SESSION["csrf_loja"])) {
                $_SESSION["csrf_loja"] =
                    bin2hex(random_bytes(32));
            }

            echo json_encode([
                "sucesso" => true,
                "dinheiro" => (int) $saldo,
                "itens" => $itens,
                "csrf_token" => $_SESSION["csrf_loja"]
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            error_log($e->getMessage());

            http_response_code(500);

            echo json_encode([
                "sucesso" => false,
                "erro" => "Não foi possível carregar a loja."
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
