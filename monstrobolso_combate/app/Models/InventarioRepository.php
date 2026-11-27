<?php
namespace Maremon\Models;
use PDO;
use PDOStatement;

final class InventarioRepository {
    public function __construct(private PDO $pdo) {}

    public function listarEstoque(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('SELECT i.id,i.nome,i.descricao,i.categoria,i.efeito,i.valor_efeito,pi.quantidade
     FROM public.pescador_itens pi JOIN public.loja_itens i ON i.id=pi.item_id
     WHERE pi.pescador_id=:id AND pi.quantidade>0 ORDER BY i.categoria,i.nome');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function listarAlvos(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('SELECT pescado_id AS id,nome,tipo,nivel,exp,status,status_turnos,
     estagio_evolucao,COALESCE(hp_atual,hp_max) AS hp_atual,hp_max,atk,def,spe,selecionado,equipe_slot
     FROM public.vw_equipe_maremon WHERE jogador_id=:id ORDER BY selecionado DESC,nome');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function listarItensBatalha(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
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
        $stmt->execute($parametros);
        return $stmt;
    }

    public function listarAlvosBatalha(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
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
        $stmt->execute($parametros);
        return $stmt;
    }

    public function bloquearContaUso(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('SELECT id FROM public.pescador WHERE id=:id FOR UPDATE');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function bloquearItem(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('SELECT i.nome,i.efeito,i.valor_efeito,pi.quantidade FROM public.pescador_itens pi
        JOIN public.loja_itens i ON i.id=pi.item_id
        WHERE pi.pescador_id=:id AND pi.item_id=:item FOR UPDATE OF pi');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function bloquearAlvo(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('SELECT pp.hp_atual AS hp,pp.nivel,pp.status,pp.status_turnos,pp.estagio_evolucao,
        s.hp+2*GREATEST(pp.nivel-5,0)+10*pp.bonus_evolucao_legado AS hp_max
        FROM public.pescador_pescado pp JOIN public.stats s ON s.pescado_id=pp.pescado_id
        WHERE pp.pescador_id=:id AND pp.pescado_id=:pescado FOR UPDATE OF pp');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function salvarCura(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('UPDATE public.pescador_pescado SET hp_atual=:hp,status=:status,
        status_turnos=:turnos,estagio_evolucao=:estagio
        WHERE pescador_id=:id AND pescado_id=:pescado');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function consumirItem(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('UPDATE public.pescador_itens SET quantidade=quantidade-1
        WHERE pescador_id=:id AND item_id=:item AND quantidade>0');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function removerEstoqueVazio(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('DELETE FROM public.pescador_itens WHERE pescador_id=:id AND item_id=:item AND quantidade=0');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function bloquearContaBatalha(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT id
        FROM public.pescador
        WHERE id = :id
        FOR UPDATE
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function bloquearItemBatalha(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT
            i.nome,
            i.efeito,
            i.valor_efeito,
            pi.quantidade
        FROM public.pescador_itens pi
        JOIN public.loja_itens i ON i.id = pi.item_id
        WHERE pi.pescador_id = :id
          AND pi.item_id = :item
        FOR UPDATE OF pi
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function bloquearAlvoBatalha(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT
            pp.hp_atual AS hp,
            pp.status,
            pp.status_turnos,
            pp.estagio_evolucao,
            s.hp + 2 * GREATEST(pp.nivel - 5, 0)
                + 10 * pp.bonus_evolucao_legado AS hp_max
        FROM public.pescador_pescado pp
        JOIN public.stats s ON s.pescado_id = pp.pescado_id
        WHERE pp.pescador_id = :id
          AND pp.pescado_id = :pescado
        FOR UPDATE OF pp
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function salvarCuraBatalha(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        UPDATE public.pescador_pescado
        SET hp_atual = :hp,
            status = :status,
            status_turnos = :turnos
        WHERE pescador_id = :id
          AND pescado_id = :pescado
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function consumirItemBatalha(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        UPDATE public.pescador_itens
        SET quantidade = quantidade - 1
        WHERE pescador_id = :id
          AND item_id = :item
          AND quantidade > 0
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function removerEstoqueVazioBatalha(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        DELETE FROM public.pescador_itens
        WHERE pescador_id = :id
          AND item_id = :item
          AND quantidade = 0
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function bloquearProgressaoBatalha(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
            SELECT nivel, COALESCE(exp, 0) AS exp
            FROM public.pescador_pescado
            WHERE pescador_id = :id
              AND pescado_id = :pescado
            FOR UPDATE
        ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function salvarProgressaoBatalha(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
            UPDATE public.pescador_pescado
            SET nivel = :nivel, exp = :exp
            WHERE pescador_id = :id
              AND pescado_id = :pescado
        ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function creditarMoedasBatalha(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
            UPDATE public.pescador
            SET dinheiro = COALESCE(dinheiro, 0) + :moedas
            WHERE id = :id
        ");
        $stmt->execute($parametros);
        return $stmt;
    }
}
