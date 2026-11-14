
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/proteger.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ]);
    exit;
}

try {
    $sql = "
        SELECT
            p.id,
            p.nome,
            p.tipo,
            pp.nivel,
            pp.exp,
            COALESCE(pp.hp_atual, s.hp) AS hp_atual,
            s.hp AS hp_max,
            s.atk,
            s.def,
            s.spe,
            pp.selecionado
        FROM public.pescador_pescado pp
        JOIN public.pescado p
            ON p.id = pp.pescado_id
        JOIN public.stats s
            ON s.pescado_id = p.id
        WHERE pp.pescador_id = :pescador_id
        ORDER BY pp.selecionado DESC, p.nome
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        "pescador_id" => $pescadorId
    ]);

    if (empty($_SESSION["csrf_equipe"])) {
        $_SESSION["csrf_equipe"] =
            bin2hex(random_bytes(32));
    }

    echo json_encode([
        "sucesso" => true,
        "pescados" => $stmt->fetchAll(),
        "csrf_token" => $_SESSION["csrf_equipe"]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível carregar a equipe."
    ]);
}
