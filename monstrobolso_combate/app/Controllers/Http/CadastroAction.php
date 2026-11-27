<?php
namespace Maremon\Controllers\Http;

use PDOException;

final class CadastroAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);

            echo json_encode([
                "sucesso" => false,
                "erro" => "Método não permitido."
            ]);

            exit;
        }

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

        $nome = trim((string) ($dados["nome"] ?? ""));
        $usuario = trim((string) ($dados["usuario"] ?? ""));
        $senha = $dados["senha"] ?? "";

        if (
            mb_strlen($nome) < 1 ||
            mb_strlen($nome) > 100 ||
            !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $usuario) ||
            !is_string($senha) ||
            strlen($senha) < 8 ||
            strlen($senha) > 1024
        ) {
            http_response_code(400);

            echo json_encode([
                "sucesso" => false,
                "erro" => "Verifique o nome, usuário e senha. O usuário deve ter de 3 a 30 caracteres (letras, números ou _), e a senha deve ter pelo menos 8 caracteres."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        try {

            // Proteger a senha antes de armazenar
            $senhaHash = password_hash(
                $senha,
                PASSWORD_DEFAULT
            );

            // Cadastrar o jogador
            $stmt = (new \Maremon\Models\ContaRepository($pdo))->cadastrar([
                "nome" => $nome,
                "usuario" => $usuario,
                "senha_hash" => $senhaHash
            ]);

            $id = $stmt->fetchColumn();

            echo json_encode([
                "sucesso" => true,
                "mensagem" => "Conta criada com sucesso!",
                "jogador_id" => (int) $id
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            // 23505 = violação de restrição UNIQUE no PostgreSQL
            if ($e->getCode() === "23505") {
                http_response_code(409);

                echo json_encode([
                    "sucesso" => false,
                    "erro" => "Esse usuário já está cadastrado."
                ], JSON_UNESCAPED_UNICODE);
            } else {
                error_log($e->getMessage());

                http_response_code(500);

                echo json_encode([
                    "sucesso" => false,
                    "erro" => "Erro ao criar a conta."
                ], JSON_UNESCAPED_UNICODE);
            }
        }
    }
}
