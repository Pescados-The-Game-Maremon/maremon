<?php
namespace Maremon\Models;
use PDO;
use PDOStatement;

final class LojaRepository {
    public function __construct(private PDO $pdo) {}

    public function bloquearSaldo(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT dinheiro
        FROM public.pescador
        WHERE id = :pescador_id
        FOR UPDATE
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function buscarProduto(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT id, nome, preco, efeito
        FROM public.loja_itens
        WHERE id = :item_id
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function verificarPermissaoDna(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM public.bosses b
            JOIN public.boss_vitorias v
              ON v.boss_ordem = b.ordem
            WHERE b.dna_nome = :nome
              AND v.pescador_id = :pescador
        ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function debitarSaldo(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        UPDATE public.pescador
        SET dinheiro = dinheiro - :valor_total
        WHERE id = :pescador_id
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function adicionarEstoque(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        INSERT INTO public.pescador_itens
            (pescador_id, item_id, quantidade)
        VALUES
            (:pescador_id, :item_id, :quantidade)
        ON CONFLICT (pescador_id, item_id)
        DO UPDATE SET
            quantidade = public.pescador_itens.quantidade
                       + EXCLUDED.quantidade
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function registrarCompra(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        INSERT INTO public.loja_compras
            (
                pescador_id,
                item_id,
                quantidade,
                preco_unitario,
                valor_total
            )
        VALUES
            (
                :pescador_id,
                :item_id,
                :quantidade,
                :preco,
                :total
            )
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function consultarSaldo(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT dinheiro
        FROM public.pescador
        WHERE id = :pescador_id
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function listarCatalogo(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT
            id,
            nome,
            descricao,
            categoria,
            preco,
            efeito,
            valor_efeito
        FROM public.vw_catalogo_maremon AS i
        WHERE i.efeito <> 'dna_boss'
           OR EXISTS (
                SELECT 1
                FROM public.bosses AS b
                JOIN public.boss_vitorias AS v
                  ON v.boss_ordem = b.ordem
                WHERE b.dna_nome = i.nome
                  AND v.pescador_id = :pescador_id
           )
        ORDER BY i.id
    ");
        $stmt->execute($parametros);
        return $stmt;
    }
}
