
<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, private");

require_once __DIR__ . "/proteger.php";

// Somente consultas GET.
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ]);
    exit;
}

try {
    // Buscar saldo do jogador autenticado.
    $stmt = $pdo->prepare("
        SELECT dinheiro
        FROM public.pescador
        WHERE id = :pescador_id
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId
    ]);

    $saldo = $stmt->fetchColumn();

    if ($saldo === false) {
        throw new RuntimeException(
            "Jogador não encontrado."
        );
    }

    // Buscar todos os produtos da loja.
    $stmt = $pdo->prepare("
        SELECT
            id,
            nome,
            descricao,
            categoria,
            preco,
            efeito,
            valor_efeito
        FROM public.loja_itens AS i
        WHERE i.efeito <> 'dna_boss'
           OR EXISTS (
                SELECT 1
                FROM public.bosses AS b
                JOIN public.boss_vitorias AS v
                  ON v.boss_ordem = b.ordem
                WHERE b.dna_nome = i.nome
                  AND v.pescador_id = :pescador_id
           )
        ORDER BY i.id
    ");

    $stmt->execute(["pescador_id" => $pescadorId]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Criar token para proteger as compras.
    if (empty($_SESSION["csrf_loja"])) {
        $_SESSION["csrf_loja"] =
            bin2hex(random_bytes(32));
    }

    echo json_encode([
        "sucesso" => true,
        "dinheiro" => (int) $saldo,
        "itens" => $itens,
        "csrf_token" => $_SESSION["csrf_loja"]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível carregar a loja."
    ], JSON_UNESCAPED_UNICODE);
}
