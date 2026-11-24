<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, private");

require_once __DIR__ . "/proteger.php";

$combate = $_SESSION["combate"] ?? null;

if (
    $_SERVER["REQUEST_METHOD"] !== "GET" ||
    !is_array($combate) ||
    !($combate["ativo"] ?? false) ||
    (int) ($combate["pescador_id"] ?? 0) !== $pescadorId
) {
    http_response_code(409);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Combate não disponível."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            i.id,
            i.nome,
            i.descricao,
            i.efeito,
            pi.quantidade
        FROM public.pescador_itens pi
        JOIN public.loja_itens i ON i.id = pi.item_id
        WHERE pi.pescador_id = :id
          AND pi.quantidade > 0
          AND i.efeito NOT IN ('evoluir', 'dna_boss')
        ORDER BY i.categoria, i.nome
    ");

    $stmt->execute(["id" => $pescadorId]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT
            pescado_id AS id,
            nome,
            nivel,
            status,
            hp_max,
            COALESCE(hp_atual, hp_max) AS hp_atual
        FROM public.vw_equipe_maremon
        WHERE jogador_id = :id
        ORDER BY selecionado DESC, nome
    ");

    $stmt->execute(["id" => $pescadorId]);
    $pescados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // A mochila do combate só mostra os integrantes escalados.
    $idsEquipe = array_map("intval", array_keys($combate["equipe"] ?? []));
    $pescados = array_values(array_filter(
        $pescados,
        static fn(array $p): bool => in_array((int) $p["id"], $idsEquipe, true)
    ));

    foreach ($pescados as &$pescado) {
        if (isset($combate["equipe"][(int) $pescado["id"]])) {
            $membro = $combate["equipe"][(int) $pescado["id"]];
            $pescado["hp_atual"] = $membro["hp"];
            $pescado["status"] = $membro["status"] ?? "normal";
        }
        if ((int) $pescado["id"] === (int) $combate["jogador"]["id"]) {
            $pescado["hp_atual"] = $combate["jogador"]["hp"];
            $pescado["status"] = $combate["jogador"]["status"] ?? "normal";
        }
    }
    unset($pescado);

    echo json_encode([
        "sucesso" => true,
        "itens" => $itens,
        "pescados" => $pescados,
        "csrf_token" => $combate["csrf_acao"] ?? null
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Erro ao abrir a mochila."
    ], JSON_UNESCAPED_UNICODE);
}
