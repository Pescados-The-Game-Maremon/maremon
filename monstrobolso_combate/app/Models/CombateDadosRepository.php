<?php
namespace Maremon\Models;
use PDO;
use PDOStatement;

final class CombateDadosRepository {
    public function __construct(private PDO $pdo) {}

    public function bloquearContaCaptura(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT id
        FROM public.pescador
        WHERE id = :id
        FOR UPDATE
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function verificarCapturaExistente(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT 1
        FROM public.pescador_pescado
        WHERE pescador_id = :pescador_id
          AND pescado_id = :pescado_id
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function registrarCaptura(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        INSERT INTO public.pescador_pescado
            (
                pescador_id,
                pescado_id,
                nivel,
                exp,
                hp_atual,
                status,
                status_turnos,
                selecionado
            )
        VALUES
            (
                :pescador_id,
                :pescado_id,
                :nivel,
                0,
                :hp_atual,
                :status,
                :status_turnos,
                FALSE
            )
        ON CONFLICT (pescador_id, pescado_id)
        DO NOTHING
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function atualizarPescadoAposCaptura(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        UPDATE public.pescador_pescado
        SET hp_atual = :hp, status = :status, status_turnos = :status_turnos
        WHERE pescador_id = :pescador_id
          AND pescado_id = :pescado_id
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function listarHistorico(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('SELECT id,resultado,turnos,dinheiro_ganho,pescado_jogador,
     pescado_inimigo,iniciado_em,finalizado_em
     FROM public.vw_historico_maremon WHERE pescador_id=:id ORDER BY id DESC LIMIT 100');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function listarFormacaoInicial(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
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
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function buscarBoss(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
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
        $stmt->execute($parametros);
        return $stmt;
    }

    public function sortearSelvagem(): PDOStatement {
        return $this->pdo->query("
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
    ");
    }

    public function encerrarRegistroAnterior(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        UPDATE public.combates_log
        SET resultado = 'interrompido',
            finalizado_em = CURRENT_TIMESTAMP
        WHERE pescador_id = :pescador_id
          AND resultado = 'ativo'
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function abrirRegistro(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
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
        $stmt->execute($parametros);
        return $stmt;
    }

    public function bloquearProgressaoTroca(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
            SELECT nivel, COALESCE(exp, 0) AS exp
            FROM public.pescador_pescado
            WHERE pescador_id = :jogador AND pescado_id = :pescado
            FOR UPDATE
        ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function salvarProgressaoTroca(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
            UPDATE public.pescador_pescado
            SET nivel = :nivel, exp = :exp
            WHERE pescador_id = :jogador AND pescado_id = :pescado
        ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function creditarMoedasTroca(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
            UPDATE public.pescador SET dinheiro = COALESCE(dinheiro, 0) + :moedas
            WHERE id = :id
        ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function bloquearProgressaoTurno(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
                SELECT nivel, COALESCE(exp, 0) AS exp
                FROM public.pescador_pescado
                WHERE pescador_id = :jogador
                  AND pescado_id = :pescado
                FOR UPDATE
            ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function salvarProgressaoTurno(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
                    UPDATE public.pescador_pescado
                    SET nivel = :nivel, exp = :exp
                    WHERE pescador_id = :jogador AND pescado_id = :pescado
                ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function creditarMoedasTurno(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
                    UPDATE public.pescador
                    SET dinheiro = COALESCE(dinheiro, 0) + :moedas
                    WHERE id = :pescador_id
                ");
        $stmt->execute($parametros);
        return $stmt;
    }
}
