<?php
namespace Maremon\Core;
use PDO;
use PDOException;

final class Conexao {
    public static function abrir(): PDO {
        $host = getenv("MAREMON_DB_HOST") ?: "127.0.0.1";
        $porta = getenv("MAREMON_DB_PORT") ?: "5432";
        $banco = getenv("MAREMON_DB_NAME") ?: "Maremon";
        $usuario = getenv("MAREMON_DB_USER") ?: "postgres";
        $senha = getenv("MAREMON_DB_PASSWORD") ?: "";

        try {
            $pdo = new PDO(
                "pgsql:host=$host;port=$porta;dbname=$banco",
                $usuario,
                $senha,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/php/')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['sucesso'=>false,'erro'=>'Erro ao conectar ao banco de dados.']);
                exit;
            }
            exit("Erro ao conectar ao banco de dados.");
        }
    }
}
