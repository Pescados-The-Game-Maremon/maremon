<?php
namespace Maremon\Models;
use PDO;
use PDOStatement;

final class EquipeDadosRepository {
    public function __construct(private PDO $pdo) {}

    public function curarTodos(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        UPDATE public.pescador_pescado pp
        SET status='normal', status_turnos=0, hp_atual = GREATEST(
            1,
            s.hp + 2 * GREATEST(pp.nivel - 5, 0) + 10 * pp.bonus_evolucao_legado
        )
        FROM public.stats s
        WHERE s.pescado_id = pp.pescado_id
          AND pp.pescador_id = :pescador_id
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function bloquearContaFormacao(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('SELECT id FROM public.pescador WHERE id=:id FOR UPDATE');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function bloquearIntegrantes(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('SELECT pp.pescado_id, pp.equipe_slot, COALESCE(pp.hp_atual,s.hp) AS hp
        FROM public.pescador_pescado pp JOIN public.stats s ON s.pescado_id=pp.pescado_id
        WHERE pp.pescador_id=:id FOR UPDATE OF pp');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function limparPosicoes(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('UPDATE public.pescador_pescado SET equipe_slot=NULL,selecionado=FALSE WHERE pescador_id=:id');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function atribuirPosicoes(int $pescadorId, array $formacao): void {
        $stmt = $this->pdo->prepare('UPDATE public.pescador_pescado SET equipe_slot=:slot WHERE pescador_id=:id AND pescado_id=:p');
        foreach ($formacao as $posicao => $id) {
            $stmt->execute(['slot'=>$posicao, 'id'=>$pescadorId, 'p'=>$id]);
        }
    }

    public function marcarAtivo(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('UPDATE public.pescador_pescado SET selecionado=TRUE WHERE pescador_id=:id AND pescado_id=:p');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function bloquearContaInicial(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT id
        FROM public.pescador
        WHERE id = :pescador_id
        FOR UPDATE
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function contarPescados(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT COUNT(*)
        FROM public.pescador_pescado
        WHERE pescador_id = :pescador_id
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function buscarEspecieInicial(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT
            p.id,
            s.hp
        FROM public.pescado p
        JOIN public.stats s
            ON s.pescado_id = p.id
        WHERE p.nome = :nome
          AND s.hp > 0
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function inserirInicial(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        INSERT INTO public.pescador_pescado
    (
        pescador_id,
        pescado_id,
        nivel,
        exp,
        hp_atual,
        selecionado,
        equipe_slot
    )
        VALUES
    (
        :pescador_id,
        :pescado_id,
        :nivel,
        :exp,
        :hp_atual,
        TRUE,
        1
    )
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function listarEquipe(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('SELECT pescado_id AS id,nome,tipo,(SELECT m.sprite FROM public.maremon_especies m WHERE m.pescado_id=vw_equipe_maremon.pescado_id) AS sprite,nivel,exp,status,status_turnos,
          estagio_evolucao,COALESCE(hp_atual,hp_max) AS hp_atual,hp_max,atk,def,spe,
          selecionado,equipe_slot
          FROM public.vw_equipe_maremon WHERE jogador_id=:id
          ORDER BY selecionado DESC,equipe_slot NULLS LAST,nome');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function salvarEstadoCombate(int $pescadorId, array $equipe): void {
        $stmt = $this->pdo->prepare("
            UPDATE public.pescador_pescado
            SET hp_atual = :hp,
                status = :status,
                status_turnos = :turnos
            WHERE pescador_id = :pescador
              AND pescado_id = :pescado
        ");
        foreach ($equipe as $pescado) {
            $stmt->execute([
                'hp' => (int) $pescado['hp'],
                'status' => (string) ($pescado['status'] ?? 'normal'),
                'turnos' => (int) ($pescado['status_turnos'] ?? 0),
                'pescador' => $pescadorId,
                'pescado' => (int) $pescado['id']
            ]);
        }
    }
}
