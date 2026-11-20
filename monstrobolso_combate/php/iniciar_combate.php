<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/proteger.php";

try {
    // Buscar um Pescado do jogador para o combate
    // Temporário: utiliza o primeiro disponível
   
$sqlJogador = "
    SELECT
        pp.pescador_id,
        p.id,
        p.nome,
        p.tipo,
        pp.nivel,       
    COALESCE(
        pp.hp_atual,
        s.hp + 2 * GREATEST(pp.nivel - 5, 0)
    ) AS hp,
    s.hp + 2 * GREATEST(pp.nivel - 5, 0) AS hp_max,
    s.atk + GREATEST(pp.nivel - 5, 0) AS atk,
    s.def + GREATEST(pp.nivel - 5, 0) AS def,
    s.spe + GREATEST(pp.nivel - 5, 0) AS spe

    FROM public.pescador_pescado pp
    JOIN public.pescado p
        ON p.id = pp.pescado_id
    JOIN public.stats s
        ON s.pescado_id = p.id
    WHERE pp.pescador_id = :pescador_id
    AND pp.selecionado = TRUE
     AND COALESCE(pp.hp_atual, s.hp) > 0
    ORDER BY p.id
    LIMIT 1
";

$stmtJogador = $pdo->prepare($sqlJogador);

$stmtJogador->execute([
    "pescador_id" => $pescadorId
]);

$jogador = $stmtJogador->fetch();


    if (!$jogador) {
        throw new Exception(
            "Nenhum Pescado disponível para o jogador."
        );
    }

    // Selecionar qualquer espécie selvagem
    // Inclusive espécies já capturadas
    $sqlInimigo = "
        SELECT
            p.id,
            p.nome,
            p.tipo,
            5 AS nivel,
            s.hp,
            s.hp AS hp_max,
            s.atk,
            s.def,
            s.spe
        FROM public.pescado p
        JOIN public.stats s
            ON s.pescado_id = p.id
        WHERE s.hp > 0
        ORDER BY RANDOM()
        LIMIT 1
    ";

    $inimigo = $pdo->query($sqlInimigo)->fetch();

    if (!$inimigo) {
        throw new Exception(
            "Nenhum Pescado selvagem disponível."
        );
    }



// Encerrar um registro anterior caso o jogador
// tenha iniciado outro encontro sem finalizar o primeiro.
$pdo->beginTransaction();

$stmt = $pdo->prepare("
    UPDATE public.combates_log
    SET resultado = 'interrompido',
        finalizado_em = CURRENT_TIMESTAMP
    WHERE pescador_id = :pescador_id
      AND resultado = 'ativo'
");

$stmt->execute([
    "pescador_id" => $pescadorId
]);

// Registrar o novo combate.
$stmt = $pdo->prepare("
    INSERT INTO public.combates_log (
        pescador_id,
        jogador_pescado_id,
        inimigo_pescado_id
    )
    VALUES (
        :pescador_id,
        :jogador_id,
        :inimigo_id
    )
    RETURNING id
");


$stmt->execute([
    "pescador_id" => $pescadorId,
    "jogador_id" => (int) $jogador["id"],
    "inimigo_id" => (int) $inimigo["id"]
]);

$combateLogId = (int) $stmt->fetchColumn();

$pdo->commit();



    // Guardar o combate na sessão PHP
    $_SESSION["combate"] = [
    "pescador_id" => $pescadorId,
    "jogador" => $jogador,
    "inimigo" => $inimigo,
    "turno" => 1,
    "ativo" => true,
    "captura_tentada" => false,
    "log_id" => $combateLogId,
    "csrf_captura" => bin2hex(random_bytes(32))
    ];

   
echo json_encode([
    "sucesso" => true,
    "jogador" => $jogador,
    "inimigo" => $inimigo,
    "turno" => 1,
    "mensagem" => "Um Pescado selvagem apareceu!",

    // Informações necessárias para a captura
    "csrf_captura" => $_SESSION["combate"]["csrf_captura"],
    "captura_tentada" => false

], JSON_UNESCAPED_UNICODE);


if ($pdo->inTransaction()) {
    $pdo->rollBack();
}
} catch (Throwable $e) {

    error_log($e->getMessage());

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível iniciar o combate."
    ], JSON_UNESCAPED_UNICODE);
}
