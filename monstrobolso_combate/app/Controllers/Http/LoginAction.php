<?php
namespace Maremon\Controllers\Http;

use Throwable;

final class LoginAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405); echo json_encode(["sucesso"=>false,"erro"=>"Método não permitido."]); return;
        }

        \Maremon\Core\Sessao::iniciar();
        $pdo = \Maremon\Core\Conexao::abrir();

        $dados = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($dados)) {
            http_response_code(400);
            echo json_encode([
                "sucesso" => false,
                "erro" => "Dados inválidos."
            ]);
            exit;
        }

        $usuario = $dados["usuario"] ?? "";
        $senha = $dados["senha"] ?? "";

        if (
            !is_string($usuario) ||
            !is_string($senha) ||
            strlen($usuario) > 30 ||
            strlen($senha) > 1024 ||
            $usuario === "" ||
            $senha === ""
        ) {
            http_response_code(400);
            echo json_encode([
                "sucesso" => false,
                "erro" => "Usuário ou senha inválidos."
            ]);
            exit;
        }

        try {
            $stmt = (new \Maremon\Models\ContaRepository($pdo))->buscarPorUsuario([
                "usuario" => trim($usuario)
            ]);

            $jogador = $stmt->fetch();

            if (
                !$jogador ||
                empty($jogador["senha_hash"]) ||
                !password_verify($senha, $jogador["senha_hash"])
            ) {
                http_response_code(401);

                echo json_encode([
                    "sucesso" => false,
                    "erro" => "Usuário ou senha incorretos."
                ]);
                exit;
            }

            // Renovar o identificador da sessão após o login
            session_regenerate_id(true);

            $_SESSION = [];
            // Guardar apenas os dados necessários
            $_SESSION["pescador_id"] = (int) $jogador["id"];
            $_SESSION["usuario"] = $jogador["usuario"];

            // Limpar qualquer combate antigo
            unset($_SESSION["combate"]);

            echo json_encode([
                "sucesso" => true,
                "mensagem" => "Login realizado com sucesso!",
                "jogador" => [
                    "id" => (int) $jogador["id"],
                    "nome" => $jogador["nome"],
                    "usuario" => $jogador["usuario"]
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            error_log($e->getMessage());

            http_response_code(500);

            echo json_encode([
                "sucesso" => false,
                "erro" => "Erro ao realizar login."
            ]);
        }
    }
}
