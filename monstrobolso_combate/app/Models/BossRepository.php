<?php
namespace Maremon\Models;
use PDO;
use PDOStatement;

final class BossRepository {
    public function __construct(private PDO $pdo) {}

    public function listarIscas(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
            SELECT b.ordem, b.nome, b.isca, b.dna_nome, b.sprite,
                (v.pescador_id IS NOT NULL) AS derrotado,
                (b.ordem = 1 OR anterior.pescador_id IS NOT NULL) AS liberado
            FROM public.bosses b
            LEFT JOIN public.boss_vitorias v
                ON v.boss_ordem = b.ordem AND v.pescador_id = :pescador
            LEFT JOIN public.boss_vitorias anterior
                ON anterior.boss_ordem = b.ordem - 1
                AND anterior.pescador_id = :pescador_anterior
            ORDER BY b.ordem
        ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function consultarIsca(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("SELECT isca_atual FROM public.pescador WHERE id = :id");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function selecionarIsca(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
            UPDATE public.pescador SET isca_atual = :ordem WHERE id = :pescador
        ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function registrarVitoria(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
            INSERT INTO public.boss_vitorias (pescador_id, boss_ordem)
            VALUES (:pescador, :ordem)
            ON CONFLICT (pescador_id, boss_ordem) DO NOTHING
        ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function buscarChefe(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
            SELECT nome, dna_nome FROM public.bosses WHERE ordem = :ordem
        ");
        $stmt->execute($parametros);
        return $stmt;
    }
}
