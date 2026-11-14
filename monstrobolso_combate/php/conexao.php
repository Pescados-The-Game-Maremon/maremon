<?php

$host = "127.0.0.1";
$porta = "5432";
$banco = "Maremon";
$usuario = "postgres";
$senha = "";

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
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit("Erro ao conectar ao banco de dados.");
}