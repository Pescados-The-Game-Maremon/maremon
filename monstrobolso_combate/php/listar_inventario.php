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
    $stmt = $pdo->prepare("
        SELECT
            i.id,
            i.nome,
            i.descricao,
            i.categoria,
            i.efeito,
            i.valor_efeito,
            pi.quantidade
        FROM public.pescador_itens pi
        JOIN public.loja_itens i
            ON i.id = pi.item_id
        WHERE pi.pescador_id = :id
          AND pi.quantidade > 0
        ORDER BY i.categoria, i.nome
    ");

    $stmt->execute(["id" => $pescadorId]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.nome,
            pp.nivel,
            COALESCE(
                pp.hp_atual,
                s.hp + 2 * GREATEST(pp.nivel - 5, 0)
            ) AS hp_atual,
            s.hp + 2 * GREATEST(pp.nivel - 5, 0)
                AS hp_max
        FROM public.pescador_pescado pp
        JOIN public.pescado p
            ON p.id = pp.pescado_id
        JOIN public.stats s
            ON s.pescado_id = p.id
        WHERE pp.pescador_id = :id
        ORDER BY p.nome
    ");

    $stmt->execute(["id" => $pescadorId]);
    $pescados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($_SESSION["csrf_inventario"])) {
        $_SESSION["csrf_inventario"] =
            bin2hex(random_bytes(32));
    }

    echo json_encode([
        "sucesso" => true,
        "itens" => $itens,
        "pescados" => $pescados,
        "csrf_token" => $_SESSION["csrf_inventario"]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível carregar o inventário."
    ], JSON_UNESCAPED_UNICODE);
}
