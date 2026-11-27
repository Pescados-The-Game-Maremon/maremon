<?php
namespace Maremon\Models;
use PDO;
use PDOStatement;

final class EvolucaoRepository {
    public function __construct(private PDO $pdo) {}

    public function listarOpcoes(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('SELECT e.id, e.pescado_origem_id, e.pescado_destino_id,
            e.item_necessario_id, e.quantidade_item, d.nome AS destino, d.tipo,
            COALESCE(pi.quantidade,0) AS quantidade_possuida,
            CASE WHEN outro.pescado_id IS NULL THEN 0 ELSE 1 END AS ja_possui
            FROM public.pescador_pescado pp
            JOIN public.evolucoes e ON e.pescado_origem_id=pp.pescado_id
            JOIN public.pescado d ON d.id=e.pescado_destino_id
            LEFT JOIN public.pescador_itens pi ON pi.pescador_id=pp.pescador_id AND pi.item_id=e.item_necessario_id
            LEFT JOIN public.pescador_pescado outro ON outro.pescador_id=pp.pescador_id AND outro.pescado_id=e.pescado_destino_id
            WHERE pp.pescador_id=:id ORDER BY d.nome');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function buscarRota(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('SELECT e.pescado_destino_id, e.quantidade_item, e.estagio_destino,
            p.nome AS destino, p.tipo, s.hp AS hp_base
            FROM public.evolucoes e JOIN public.pescado p ON p.id=e.pescado_destino_id
            JOIN public.stats s ON s.pescado_id=p.id
            WHERE e.id=:evolucao AND e.pescado_origem_id=:origem AND e.item_necessario_id=:item
            FOR SHARE OF e,p,s');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function verificarEspecieDestino(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('SELECT 1 FROM public.pescador_pescado WHERE pescador_id=:id AND pescado_id=:destino');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function transformarPescado(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('UPDATE public.pescador_pescado SET pescado_id=:destino,
            hp_atual=:hp, estagio_evolucao=:estagio, bonus_evolucao_legado=0
            WHERE pescador_id=:id AND pescado_id=:origem');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function consumirMaterial(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('UPDATE public.pescador_itens SET quantidade=quantidade-:consumo
            WHERE pescador_id=:id AND item_id=:item AND quantidade>=:minimo');
        $stmt->execute($parametros);
        return $stmt;
    }

    public function limparMaterial(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare('DELETE FROM public.pescador_itens WHERE pescador_id=:id AND item_id=:item AND quantidade=0');
        $stmt->execute($parametros);
        return $stmt;
    }
}
