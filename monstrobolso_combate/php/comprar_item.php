
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/proteger.php";

function responderCompra(array $dados, int $codigo = 200): void {
    http_response_code($codigo);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responderCompra([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ], 405);
}

$dados = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($dados)) {
    responderCompra([
        "sucesso" => false,
        "erro" => "Dados inválidos."
    ], 400);
}

// Validar token de segurança.
$tokenRecebido = $dados["csrf_token"] ?? null;
$tokenSessao = $_SESSION["csrf_loja"] ?? null;

if (
    !is_string($tokenRecebido) ||
    !is_string($tokenSessao) ||
    !hash_equals($tokenSessao, $tokenRecebido)
) {
    responderCompra([
        "sucesso" => false,
        "erro" => "Solicitação não autorizada."
    ], 403);
}

// Validar ID do item e quantidade.
$itemId = filter_var(
    $dados["item_id"] ?? null,
    FILTER_VALIDATE_INT
);

$quantidade = filter_var(
    $dados["quantidade"] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $itemId === false ||
    $itemId === null ||
    $itemId < 1 ||
    $quantidade === false ||
    $quantidade === null ||
    $quantidade < 1 ||
    $quantidade > 99
) {
    responderCompra([
        "sucesso" => false,
        "erro" => "Item ou quantidade inválida."
    ], 400);
}

// Impedir compras enquanto um combate está ativo.
if ($_SESSION["combate"]["ativo"] ?? false) {
    responderCompra([
        "sucesso" => false,
        "erro" => "Finalize o combate antes de comprar."
    ], 409);
}


try {
    $pdo->beginTransaction();

    // Bloquear o saldo durante a compra.
    $stmt = $pdo->prepare("
        SELECT dinheiro
        FROM public.pescador
        WHERE id = :pescador_id
        FOR UPDATE
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

    $saldo = (int) $saldo;

    // Buscar o preço verdadeiro no PostgreSQL.
    $stmt = $pdo->prepare("
        SELECT id, nome, preco, efeito
        FROM public.loja_itens
        WHERE id = :item_id
    ");

    $stmt->execute([
        "item_id" => $itemId
    ]);

    $item = $stmt->fetch();

    if (!$item) {
        $pdo->rollBack();

        responderCompra([
            "sucesso" => false,
            "erro" => "Item não encontrado."
        ], 404);
    }

    // A interface não basta: validar desbloqueio também ao comprar por POST.
    if ($item["efeito"] === "dna_boss") {
        $stmt = $pdo->prepare("
            SELECT 1
            FROM public.bosses b
            JOIN public.boss_vitorias v
              ON v.boss_ordem = b.ordem
            WHERE b.dna_nome = :nome
              AND v.pescador_id = :pescador
        ");
        $stmt->execute([
            "nome" => $item["nome"],
            "pescador" => $pescadorId
        ]);
        if (!$stmt->fetchColumn()) {
            $pdo->rollBack();
            responderCompra([
                "sucesso" => false,
                "erro" => "Derrote o chefe correspondente antes de comprar este DNA."
            ], 403);
        }
    }

    $preco = (int) $item["preco"];
    $valorTotal = $preco * $quantidade;

    if ($saldo < $valorTotal) {
        $pdo->rollBack();

        responderCompra([
            "sucesso" => false,
            "erro" => "Dinheiro insuficiente.",
            "saldo" => $saldo
        ], 400);

        
    }


    // Descontar o dinheiro.
    $stmt = $pdo->prepare("
        UPDATE public.pescador
        SET dinheiro = dinheiro - :valor_total
        WHERE id = :pescador_id
    ");

    $stmt->execute([
        "valor_total" => $valorTotal,
        "pescador_id" => $pescadorId
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            "Falha ao atualizar o dinheiro."
        );
    }

    // Adicionar o item ao inventário.
    $stmt = $pdo->prepare("
        INSERT INTO public.pescador_itens
            (pescador_id, item_id, quantidade)
        VALUES
            (:pescador_id, :item_id, :quantidade)
        ON CONFLICT (pescador_id, item_id)
        DO UPDATE SET
            quantidade = public.pescador_itens.quantidade
                       + EXCLUDED.quantidade
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId,
        "item_id" => $itemId,
        "quantidade" => $quantidade
    ]);

    // Registrar o histórico da compra.
    $stmt = $pdo->prepare("
        INSERT INTO public.loja_compras
            (
                pescador_id,
                item_id,
                quantidade,
                preco_unitario,
                valor_total
            )
        VALUES
            (
                :pescador_id,
                :item_id,
                :quantidade,
                :preco,
                :total
            )
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId,
        "item_id" => $itemId,
        "quantidade" => $quantidade,
        "preco" => $preco,
        "total" => $valorTotal
    ]);

    // Confirmar todas as operações.
    $pdo->commit();

    responderCompra([
        "sucesso" => true,
        "mensagem" => "Compra realizada com sucesso!",
        "item" => $item["nome"],
        "quantidade" => $quantidade,
        "valor_total" => $valorTotal,
        "dinheiro_restante" => $saldo - $valorTotal
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    responderCompra([
        "sucesso" => false,
        "erro" => "Não foi possível realizar a compra."
    ], 500);
}
