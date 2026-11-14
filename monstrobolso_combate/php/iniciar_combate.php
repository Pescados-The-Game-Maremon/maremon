
<?php

header("Content-Type: application/json; charset=utf-8");

session_start();

require_once __DIR__ . "/conexao.php";

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
            COALESCE(pp.hp_atual, s.hp) AS hp,
            s.hp AS hp_max,
            s.atk,
            s.def,
            s.spe
        FROM public.pescador_pescado pp
        JOIN public.pescado p
            ON p.id = pp.pescado_id
        JOIN public.stats s
            ON s.pescado_id = p.id
        WHERE COALESCE(pp.hp_atual, s.hp) > 0
        ORDER BY pp.pescador_id, p.id
        LIMIT 1
    ";

    $jogador = $pdo->query($sqlJogador)->fetch();

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

    // Guardar o combate na sessão PHP
    $_SESSION["combate"] = [
        "jogador" => $jogador,
        "inimigo" => $inimigo,
        "turno" => 1,
        "ativo" => true
    ];

    echo json_encode([
        "sucesso" => true,
        "jogador" => $jogador,
        "inimigo" => $inimigo,
        "turno" => 1,
        "mensagem" => "Um Pescado selvagem apareceu!"
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    error_log($e->getMessage());

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível iniciar o combate."
    ], JSON_UNESCAPED_UNICODE);
}
