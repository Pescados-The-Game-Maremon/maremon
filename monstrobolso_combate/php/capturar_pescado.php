<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/log_combate.php";

function responder($dados, $codigo = 200) {
    http_response_code($codigo);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder([
        "sucesso" => false,
        "erro" => "Utilize POST."
    ], 405);
}

$entrada = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($entrada)) {
    responder([
        "sucesso" => false,
        "erro" => "Dados inválidos."
    ], 400);
}

$combate = $_SESSION["combate"] ?? null;

if (
    !is_array($combate) ||
    !($combate["ativo"] ?? false) ||
    (int) ($combate["pescador_id"] ?? 0) !== $pescadorId
) {
    responder([
        "sucesso" => false,
        "erro" => "Combate inválido ou encerrado."
    ], 403);
}

$tokenRecebido = $entrada["csrf_captura"] ?? null;
$tokenEsperado = $combate["csrf_captura"] ?? null;

if (
    !is_string($tokenRecebido) ||
    !is_string($tokenEsperado) ||
    !hash_equals($tokenEsperado, $tokenRecebido)
) {
    responder([
        "sucesso" => false,
        "erro" => "Solicitação não autorizada."
    ], 403);
}

if ($combate["captura_tentada"] ?? false) {
    responder([
        "sucesso" => false,
        "erro" => "Você já tentou capturar neste combate."
    ], 409);
}

$jogador = $combate["jogador"];
$inimigo = $combate["inimigo"];

if (
    (int) ($jogador["pescador_id"] ?? 0) !== $pescadorId ||
    (int) $jogador["hp"] <= 0 ||
    (int) $inimigo["hp"] <= 0
) {
    responder([
        "sucesso" => false,
        "erro" => "Não é possível capturar agora."
    ], 400);
}


try {
    $pdo->beginTransaction();

    // Bloquear o jogador durante a operação.
    $stmt = $pdo->prepare("
        SELECT id
        FROM public.pescador
        WHERE id = :id
        FOR UPDATE
    ");

    $stmt->execute([
        "id" => $pescadorId
    ]);

    if (!$stmt->fetch()) {
        throw new Exception("Jogador não encontrado.");
    }

    // Confirmar que o Pescado ativo pertence ao jogador.
    $stmt = $pdo->prepare("
        SELECT 1
        FROM public.pescador_pescado
        WHERE pescador_id = :pescador_id
          AND pescado_id = :pescado_id
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId,
        "pescado_id" => (int) $jogador["id"]
    ]);

    if (!$stmt->fetchColumn()) {
        $pdo->rollBack();

        responder([
            "sucesso" => false,
            "erro" => "Seu Pescado não foi encontrado."
        ], 403);
    }

    // Verificar se já possui a espécie selvagem.
    $stmt->execute([
        "pescador_id" => $pescadorId,
        "pescado_id" => (int) $inimigo["id"]
    ]);

    if ($stmt->fetchColumn()) {
        $pdo->rollBack();

        responder([
            "sucesso" => false,
            "erro" => "Você já possui esta espécie."
        ], 409);
    }


    $hpAtual = (int) $inimigo["hp"];
    $hpMaximo = max(1, (int) $inimigo["hp_max"]);

    // Chance entre aproximadamente 25% e 75%.
    $chance = 25 + 50 * (
        1 - $hpAtual / $hpMaximo
    );

    $sorteio = random_int(1, 100);

    $capturou = $sorteio <= $chance;

    if (!$capturou) {
        $pdo->rollBack();

        $_SESSION["combate"]["captura_tentada"] = true;

        responder([
            "sucesso" => true,
            "capturou" => false,
            "captura_tentada" => true,
            "mensagem" => "O Pescado escapou! Continue o combate."
        ]);
    }


    // Salvar o Pescado na coleção.
    $stmt = $pdo->prepare("
        INSERT INTO public.pescador_pescado
            (
                pescador_id,
                pescado_id,
                nivel,
                exp,
                hp_atual,
                selecionado
            )
        VALUES
            (
                :pescador_id,
                :pescado_id,
                :nivel,
                0,
                :hp_atual,
                FALSE
            )
        ON CONFLICT (pescador_id, pescado_id)
        DO NOTHING
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId,
        "pescado_id" => (int) $inimigo["id"],
        "nivel" => (int) $inimigo["nivel"],
        "hp_atual" => $hpAtual
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception(
            "A espécie já pertence ao jogador."
        );
    }

    // Confirmar o HP do Pescado que participou.
    $stmt = $pdo->prepare("
        UPDATE public.pescador_pescado
        SET hp_atual = :hp
        WHERE pescador_id = :pescador_id
          AND pescado_id = :pescado_id
    ");

    $stmt->execute([
        "hp" => (int) $jogador["hp"],
        "pescador_id" => $pescadorId,
        "pescado_id" => (int) $jogador["id"]
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception(
            "Não foi possível atualizar o HP."
        );
    }

    atualizarLogCombate(
    $pdo,
    (int) $combate["log_id"],
    $pescadorId,
    "captura",
    max(0, (int) $combate["turno"] - 1),
    0
);

    $pdo->commit();

    // Encerrar o combate após salvar.
    $_SESSION["combate"]["ativo"] = false;
    $_SESSION["combate"]["captura_tentada"] = true;

    responder([
        "sucesso" => true,
        "capturou" => true,
        "pescado" => $inimigo["nome"],
        "mensagem" => $inimigo["nome"]
            . " foi capturado com sucesso!"
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    responder([
        "sucesso" => false,
        "erro" => "Não foi possível concluir a captura."
    ], 500);
}
