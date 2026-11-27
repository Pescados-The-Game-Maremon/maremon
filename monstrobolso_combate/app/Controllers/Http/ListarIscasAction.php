<?php
namespace Maremon\Controllers\Http;

use Throwable;
use Maremon\Services\BossService;

final class ListarIscasAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store, private");

        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            http_response_code(405);
            echo json_encode(["sucesso" => false, "erro" => "Use GET."]);
            exit;
        }

        try {
            if (empty($_SESSION["csrf_isca"])) {
                $_SESSION["csrf_isca"] = bin2hex(random_bytes(32));
            }

            echo json_encode([
                "sucesso" => true,
                "iscas" => BossService::iscas($pdo, $pescadorId),
                "isca_atual" => BossService::iscaAtual($pdo, $pescadorId),
                "csrf_token" => $_SESSION["csrf_isca"],
                "chance_boss" => 5
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log((string) $e);
            http_response_code(500);
            echo json_encode([
                "sucesso" => false,
                "erro" => "Não foi possível carregar as iscas. Execute a migração SQL dos chefes."
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
