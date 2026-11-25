<?php

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, private");
header("Vary: Cookie");

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/../app/bootstrap.php";

use Maremon\Services\BossService;
use Maremon\Services\FormacaoEquipe;

try {
    // Escolher até três integrantes que o jogador organizou antes da batalha.
    $sqlEquipe = "
        SELECT
            pp.pescador_id,
            p.id,
            p.nome,
            (SELECT m.sprite FROM public.maremon_especies m WHERE m.pescado_id=p.id) AS sprite,
            p.tipo,
            pp.nivel,
            pp.status,
            pp.status_turnos,
            pp.estagio_evolucao,
            pp.equipe_slot,
            COALESCE(
                pp.hp_atual,
                s.hp + 2 * GREATEST(pp.nivel - 5, 0)
                    + 10 * pp.bonus_evolucao_legado
            ) AS hp,
            s.hp + 2 * GREATEST(pp.nivel - 5, 0)
                + 10 * pp.bonus_evolucao_legado AS hp_max,
            s.atk + GREATEST(pp.nivel - 5, 0)
                + 3 * pp.bonus_evolucao_legado AS atk,
            s.def + GREATEST(pp.nivel - 5, 0)
                + 3 * pp.bonus_evolucao_legado AS def,
            s.spe + GREATEST(pp.nivel - 5, 0)
                + 3 * pp.bonus_evolucao_legado AS spe
        FROM public.pescador_pescado pp
        JOIN public.pescado p ON p.id = pp.pescado_id
        JOIN public.stats s ON s.pescado_id = p.id
        WHERE pp.pescador_id = :pescador_id
          AND pp.equipe_slot BETWEEN 1 AND 3
        ORDER BY pp.equipe_slot
        LIMIT 3
    ";

    $stmtEquipe = $pdo->prepare($sqlEquipe);
    $stmtEquipe->execute(["pescador_id" => $pescadorId]);
    $integrantes = $stmtEquipe->fetchAll(PDO::FETCH_ASSOC);

    if (!$integrantes) {
        throw new RuntimeException("Monte uma equipe de até 3 Pescados antes da batalha.");
    }

    $equipe = [];
    $formacao = [];
    $registrosHp = [];

    foreach ($integrantes as $integrante) {
        $integrante["hp"] = max(0, min(
            (int) $integrante["hp_max"],
            (int) $integrante["hp"]
        ));
        $equipe[(int) $integrante["id"]] = $integrante;

        $formacao[(int)$integrante["equipe_slot"]] = (int)$integrante["id"];
        $registrosHp[] = ["pescado_id" => (int)$integrante["id"], "hp" => $integrante["hp"]];
    }

    $idInicial = FormacaoEquipe::primeiroVivo($registrosHp, $formacao);
    if ($idInicial === null) {
        throw new RuntimeException("Todos os Pescados da equipe estão sem HP. Vá ao Centro de Cura ou escolha um integrante vivo antes de pescar.");
    }

    $jogador = $equipe[$idInicial];

    // Cada novo encontro realiza um sorteio independente de 5% para o
    // chefe correspondente à isca escolhida e desbloqueada pelo jogador.
    $iscaAtual = BossService::iscaAtual($pdo, $pescadorId);
    $bossOrdem = null;
    $inimigo = null;

    if (random_int(1, 100) <= 5) {
        $stmtBoss = $pdo->prepare("
            SELECT
                p.id,
                p.nome,
                'DRK' AS tipo,
                (5 + (b.ordem - 1) * 3) AS nivel,
                'normal' AS status,
                0 AS status_turnos,
                0 AS estagio_evolucao,
                5 * (s.hp + 2 * ((b.ordem - 1) * 3)) AS hp,
                5 * (s.hp + 2 * ((b.ordem - 1) * 3)) AS hp_max,
                s.atk + ((b.ordem - 1) * 3) AS atk,
                s.def + ((b.ordem - 1) * 3) AS def,
                s.spe + ((b.ordem - 1) * 3) AS spe,
                b.ordem AS boss_ordem,
                b.sprite AS boss_sprite
            FROM public.bosses b
            JOIN public.pescado p ON p.id = b.pescado_id
            JOIN public.stats s ON s.pescado_id = p.id
            WHERE b.ordem = :ordem
              AND EXISTS (
                  SELECT 1
                  FROM public.pescado_ataques pa
                  JOIN public.ataques a ON a.id = pa.ataque_id
                  WHERE pa.pescado_id = p.id
                    AND COALESCE(pa.nivel_necessario, 1) <= 5 + (b.ordem - 1) * 3
              )
        ");
        $stmtBoss->execute(["ordem" => $iscaAtual]);
        $inimigo = $stmtBoss->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($inimigo !== null) {
            $bossOrdem = (int) $inimigo["boss_ordem"];
        }
    }

    // Nos 95% de encontros normais, as espécies dos oito chefes
    // não entram no sorteio. O chefe nunca aparece como selvagem comum.
    if ($inimigo === null) {
        $sqlInimigo = "
        SELECT
            p.id,
            p.nome,
            (SELECT m.sprite FROM public.maremon_especies m WHERE m.pescado_id=p.id) AS sprite,
            p.tipo,
            5 AS nivel,
            'normal' AS status,
            0 AS status_turnos,
            0 AS estagio_evolucao,
            s.hp,
            s.hp AS hp_max,
            s.atk,
            s.def,
            s.spe
        FROM public.pescado p
        JOIN public.stats s ON s.pescado_id = p.id
        WHERE s.hp > 0
          AND NOT EXISTS (
              SELECT 1 FROM public.bosses b WHERE b.pescado_id = p.id
          )
          AND NOT EXISTS (
              SELECT 1 FROM public.evolucoes e WHERE e.pescado_destino_id = p.id
          )
          AND EXISTS (
              SELECT 1
              FROM public.pescado_ataques pa
              JOIN public.ataques a ON a.id = pa.ataque_id
              WHERE pa.pescado_id = p.id
                AND COALESCE(pa.nivel_necessario, 1) <= 5
          )
        ORDER BY RANDOM()
        LIMIT 1
    ";

        $inimigo = $pdo->query($sqlInimigo)->fetch();
    }

    if (!$inimigo) {
        throw new RuntimeException("Nenhum Pescado selvagem disponível.");
    }

    $pdo->beginTransaction();

    // Fechar um registro anterior, caso o jogador tenha saído sem encerrar.
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

    $stmt = $pdo->prepare("
        INSERT INTO public.combates_log (
            pescador_id,
            jogador_pescado_id,
            inimigo_pescado_id
        ) VALUES (
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

    // Os dois tokens pertencem a este encontro e à sessão do jogador.
    $_SESSION["combate"] = [
        "pescador_id" => $pescadorId,
        "jogador" => $jogador,
        "equipe" => $equipe,
        "pp_equipe" => [],
        "inimigo" => $inimigo,
        "boss_ordem" => $bossOrdem,
        "isca_atual" => $iscaAtual,
        "turno" => 1,
        "ativo" => true,
        "captura_tentada" => false,
        "log_id" => $combateLogId,
        "pp_jogador" => [],
        "csrf_captura" => bin2hex(random_bytes(32)),
        "csrf_acao" => bin2hex(random_bytes(32))
    ];

    echo json_encode([
        "sucesso" => true,
        "jogador" => $jogador,
        "equipe" => \Maremon\Services\EquipeCombate::membros($_SESSION["combate"]),
        "troca_obrigatoria" => false,
        "inimigo" => $inimigo,
        "boss_ordem" => $bossOrdem,
        "isca_atual" => $iscaAtual,
        "turno" => 1,
        "combate_id" => $combateLogId,
        "mensagem" => $bossOrdem !== null
            ? "BOSS! Um chefe DRK apareceu com cinco vezes a vida normal!"
            : (count($equipe) < 2
                ? "Equipe com um integrante: configure mais Pescados para poder trocar."
                : "Um Pescado selvagem apareceu!"),
        "csrf_captura" => $_SESSION["combate"]["csrf_captura"],
        "captura_tentada" => false,
        "csrf_acao" => $_SESSION["combate"]["csrf_acao"]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => $e instanceof RuntimeException
            ? $e->getMessage()
            : "Não foi possível iniciar o combate."
    ], JSON_UNESCAPED_UNICODE);
}
