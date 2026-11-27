<?php
namespace Maremon\Core;
use PDO;

final class Autenticacao {
    /** @return array{0: PDO, 1: int} */
    public static function conectarJogador(): array {
        Sessao::iniciar();
        if (empty($_SESSION['pescador_id'])) {
            http_response_code(401);
            echo json_encode(['sucesso'=>false,'erro'=>'Você precisa fazer login.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $pdo = Conexao::abrir();
        return [$pdo, self::exigirJogador($pdo)];
    }

    private static function exigirJogador(PDO $pdo): int {
        // Consultar o jogador autenticado.
        $stmt = (new \Maremon\Models\ContaRepository($pdo))->buscarAutenticado([
            "id" => $_SESSION["pescador_id"]
        ]);

        $jogadorAutenticado = $stmt->fetch();

        if (!$jogadorAutenticado) {

            unset(
                $_SESSION["pescador_id"],
                $_SESSION["usuario"],
                $_SESSION["combate"]
            );

            http_response_code(401);

            echo json_encode([
                "sucesso" => false,
                "erro" => "Sessão inválida."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // ID confiável do jogador conectado.
        return (int) $jogadorAutenticado["id"];
    }
}
