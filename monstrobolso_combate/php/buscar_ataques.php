
<?php

header("Content-Type: application/json; charset=utf-8");

session_start();
require_once __DIR__ . "/conexao.php";

try {
    if (!isset($_SESSION["combate"]["jogador"]["id"])) {
        throw new Exception("Nenhum combate iniciado.");
    }

    $pescadoId = $_SESSION["combate"]["jogador"]["id"];

    $sql = "
        SELECT
            a.id,
            a.nome,
            a.tipo,
            a.poder,
            a.precisao
        FROM public.pescado_ataques pa
        JOIN public.ataques a
            ON a.id = pa.ataque_id
        WHERE pa.pescado_id = :pescado_id
        ORDER BY a.id
        LIMIT 4
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "pescado_id" => $pescadoId
    ]);

    echo json_encode([
        "sucesso" => true,
        "ataques" => $stmt->fetchAll()
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log($e->getMessage());

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível buscar os ataques."
    ]);
}
