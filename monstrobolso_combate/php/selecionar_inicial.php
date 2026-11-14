<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/proteger.php";

function responder($sucesso, $mensagem, $codigo = 200) {
    http_response_code($codigo);

    echo json_encode([
        "sucesso" => $sucesso,
        $sucesso ? "mensagem" : "erro" => $mensagem
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método não permitido.", 405);
}

$dados = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($dados)) {
    responder(false, "Dados inválidos.", 400);
}

$tokenRecebido = $dados["csrf_token"] ?? null;
$tokenSessao = $_SESSION["csrf_token"] ?? null;

if (
    !is_string($tokenRecebido) ||
    !is_string($tokenSessao) ||
    !hash_equals($tokenSessao, $tokenRecebido)
) {
    responder(false, "Solicitação não autorizada.", 403);
}

$pescadoEscolhido = $dados["pescado"] ?? null;

$iniciaisPermitidos = [
    "Trilobita",
    "Anomalocaris",
    "Hallucigenia"
];

if (
    !is_string($pescadoEscolhido) ||
    !in_array($pescadoEscolhido, $iniciaisPermitidos, true)
) {
    responder(false, "Pescado inicial inválido.", 400);
}


try {
    $pdo->beginTransaction();

    // Bloquear a linha da conta durante a escolha.
    // Evita duas escolhas simultâneas.
    $stmt = $pdo->prepare("
        SELECT id
        FROM public.pescador
        WHERE id = :pescador_id
        FOR UPDATE
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId
    ]);

    if (!$stmt->fetch()) {
        throw new Exception("Jogador não encontrado.");
    }

    // Conferir se o jogador já possui um Pescado.
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM public.pescador_pescado
        WHERE pescador_id = :pescador_id
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId
    ]);

    if ((int) $stmt->fetchColumn() > 0) {
        $pdo->rollBack();

        responder(
            false,
            "Você já possui um Pescado inicial.",
            409
        );
    }

    // Procurar a espécie e seus atributos.
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            s.hp
        FROM public.pescado p
        JOIN public.stats s
            ON s.pescado_id = p.id
        WHERE p.nome = :nome
          AND s.hp > 0
    ");

    $stmt->execute([
        "nome" => $pescadoEscolhido
    ]);

    $especiesEncontradas = $stmt->fetchAll();

    if (count($especiesEncontradas) !== 1) {
        throw new Exception(
            "Espécie não encontrada ou cadastrada em duplicidade."
        );
    }

    $pescado = $especiesEncontradas[0];


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
        :exp,
        :hp_atual,
        TRUE
    )
    ");

    $stmt->execute([
        "pescador_id" => $pescadorId,
        "pescado_id" => (int) $pescado["id"],
        "nivel" => 5,
        "exp" => 0,
        "hp_atual" => (int) $pescado["hp"]
    ]);

    // Confirmar o salvamento no PostgreSQL.
    $pdo->commit();

    // Impedir reutilização do token da escolha.
    unset($_SESSION["csrf_token"]);

    responder(
        true,
        "Você escolheu " . $pescadoEscolhido . "!"
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    responder(
        false,
        "Não foi possível salvar seu Pescado inicial.",
        500
    );
}
