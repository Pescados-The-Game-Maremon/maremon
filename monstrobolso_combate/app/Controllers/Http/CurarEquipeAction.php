<?php
namespace Maremon\Controllers\Http;

use Throwable;

final class CurarEquipeAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");

        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        if ($_SERVER["REQUEST_METHOD"] === "GET") {
            if (empty($_SESSION["csrf_cura"])) {
                $_SESSION["csrf_cura"] = bin2hex(random_bytes(32));
            }

            $this->responderCura([
                "sucesso" => true,
                "csrf_token" => $_SESSION["csrf_cura"]
            ]);
        }

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->responderCura([
                "sucesso" => false,
                "erro" => "Método não permitido."
            ], 405);
        }

        $dados = json_decode(file_get_contents("php://input"), true);

        $token = is_array($dados)
            ? ($dados["csrf_token"] ?? null)
            : null;

        $tokenSessao = $_SESSION["csrf_cura"] ?? null;

        if (
            !is_string($token) ||
            !is_string($tokenSessao) ||
            !hash_equals($tokenSessao, $token)
        ) {
            $this->responderCura([
                "sucesso" => false,
                "erro" => "Solicitação não autorizada."
            ], 403);
        }

        if (
            ($_SESSION["combate"]["ativo"] ?? false) &&
            (int) ($_SESSION["combate"]["pescador_id"] ?? 0) === $pescadorId
        ) {
            $this->responderCura([
                "sucesso" => false,
                "erro" => "Finalize o combate antes de curar."
            ], 409);
        }

        try {
            $stmt = (new \Maremon\Models\EquipeDadosRepository($pdo))->curarTodos([
                "pescador_id" => $pescadorId
            ]);

            $this->responderCura([
                "sucesso" => true,
                "mensagem" => "Seus Pescados foram curados!",
                "quantidade" => $stmt->rowCount()
            ]);

        } catch (Throwable $e) {
            error_log($e->getMessage());

            $this->responderCura([
                "sucesso" => false,
                "erro" => "Erro ao curar os Pescados."
            ], 500);
        }
    }

    private function responderCura($dados, $codigo = 200) {
        http_response_code($codigo);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
