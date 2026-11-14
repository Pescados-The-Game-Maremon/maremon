<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/conexao.php";

try {
    $sql = "
        SELECT
            p.id,
            p.nome,
            p.tipo,
            p.\"desc\" AS descricao,
            s.hp,
            s.atk,
            s.def,
            s.spe
        FROM public.pescado p
        LEFT JOIN public.stats s
            ON s.pescado_id = p.id
        ORDER BY p.id
    ";

    $stmt = $pdo->query($sql);

    echo json_encode(
        $stmt->fetchAll(),
        JSON_UNESCAPED_UNICODE
    );

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "erro" => "Erro ao buscar Pescados."
    ]);
}
