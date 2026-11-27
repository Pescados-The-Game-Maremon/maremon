<?php
namespace Maremon\Services;

use PDO;
use Maremon\Exceptions\JogoException;

/** Transforma um exemplar em outra espécie dentro da transação do inventário. */
final class EvolucaoService
{
    public static function opcoes(PDO $pdo, int $pescadorId): array
    {
        $s = $pdo->prepare('SELECT e.id, e.pescado_origem_id, e.pescado_destino_id,
            e.item_necessario_id, e.quantidade_item, d.nome AS destino, d.tipo,
            COALESCE(pi.quantidade,0) AS quantidade_possuida,
            CASE WHEN outro.pescado_id IS NULL THEN 0 ELSE 1 END AS ja_possui
            FROM public.pescador_pescado pp
            JOIN public.evolucoes e ON e.pescado_origem_id=pp.pescado_id
            JOIN public.pescado d ON d.id=e.pescado_destino_id
            LEFT JOIN public.pescador_itens pi ON pi.pescador_id=pp.pescador_id AND pi.item_id=e.item_necessario_id
            LEFT JOIN public.pescador_pescado outro ON outro.pescador_id=pp.pescador_id AND outro.pescado_id=e.pescado_destino_id
            WHERE pp.pescador_id=:id ORDER BY d.nome');
        $s->execute(['id'=>$pescadorId]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function hpProporcional(int $hp, int $maxAntigo, int $maxNovo): int
    {
        if ($hp <= 0) return 0;
        $maxNovo = max(1, $maxNovo);
        return max(1, min($maxNovo, (int)ceil(min(1, $hp / max(1,$maxAntigo)) * $maxNovo)));
    }

    /** O chamador já bloqueou a conta, o inventário e o exemplar de origem. */
    public static function evoluir(PDO $pdo, int $pescadorId, int $pescadoId,
        int $itemId, int $evolucaoId, array $item, array $alvo): array
    {
        if (!$pdo->inTransaction()) throw new \LogicException('Evolução exige transação.');
        if (!in_array($item['efeito'], ['dna_boss','evoluir'], true)) {
            throw new JogoException('Este item não é evolutivo.');
        }
        $s=$pdo->prepare('SELECT e.pescado_destino_id, e.quantidade_item, e.estagio_destino,
            p.nome AS destino, p.tipo, s.hp AS hp_base
            FROM public.evolucoes e JOIN public.pescado p ON p.id=e.pescado_destino_id
            JOIN public.stats s ON s.pescado_id=p.id
            WHERE e.id=:evolucao AND e.pescado_origem_id=:origem AND e.item_necessario_id=:item
            FOR SHARE OF e,p,s');
        $s->execute(['evolucao'=>$evolucaoId,'origem'=>$pescadoId,'item'=>$itemId]);
        $rotas=$s->fetchAll(PDO::FETCH_ASSOC);
        if (count($rotas)!==1) throw new JogoException('Este item não corresponde a uma evolução válida deste Pescado.');
        $rota=$rotas[0];
        $destino=(int)$rota['pescado_destino_id'];
        $quantidade=(int)$rota['quantidade_item'];
        if ($destino===$pescadoId || $quantidade<1 || (int)$rota['hp_base']<1) {
            throw new JogoException('A evolução está configurada incorretamente.');
        }
        if ((int)$item['quantidade']<$quantidade) throw new JogoException('Quantidade de itens insuficiente.');
        $s=$pdo->prepare('SELECT 1 FROM public.pescador_pescado WHERE pescador_id=:id AND pescado_id=:destino');
        $s->execute(['id'=>$pescadorId,'destino'=>$destino]);
        if ($s->fetchColumn()) throw new JogoException('Você já possui a espécie evoluída.');
        // Sem requisito de nível. Nível, experiência, status e equipe permanecem intactos.
        $hpNovoMax=(int)$rota['hp_base']+2*max(0,(int)$alvo['nivel']-5);
        $hpNovo=self::hpProporcional((int)$alvo['hp'],(int)$alvo['hp_max'],$hpNovoMax);
        $s=$pdo->prepare('UPDATE public.pescador_pescado SET pescado_id=:destino,
            hp_atual=:hp, estagio_evolucao=:estagio, bonus_evolucao_legado=0
            WHERE pescador_id=:id AND pescado_id=:origem');
        $s->execute(['destino'=>$destino,'hp'=>$hpNovo,'estagio'=>(int)$rota['estagio_destino'],
            'id'=>$pescadorId,'origem'=>$pescadoId]);
        if ($s->rowCount()!==1) throw new JogoException('Não foi possível transformar o Pescado.');
        $s=$pdo->prepare('UPDATE public.pescador_itens SET quantidade=quantidade-:consumo
            WHERE pescador_id=:id AND item_id=:item AND quantidade>=:minimo');
        $s->execute(['consumo'=>$quantidade,'id'=>$pescadorId,'item'=>$itemId,'minimo'=>$quantidade]);
        if ($s->rowCount()!==1) throw new JogoException('Falha ao consumir o item evolutivo.');
        $s=$pdo->prepare('DELETE FROM public.pescador_itens WHERE pescador_id=:id AND item_id=:item AND quantidade=0');
        $s->execute(['id'=>$pescadorId,'item'=>$itemId]);
        return ['sucesso'=>true,'mensagem'=>'Seu Pescado evoluiu para '.$rota['destino'].'!',
            'evoluiu'=>true,'pescado_id'=>$destino,'nome'=>$rota['destino'],'tipo'=>$rota['tipo'],
            'hp_anterior'=>(int)$alvo['hp'],'hp_atual'=>$hpNovo,'hp_max'=>$hpNovoMax,
            'status_atual'=>$alvo['status'],'estagio_evolucao'=>(int)$rota['estagio_destino'],
            'quantidade_restante'=>(int)$item['quantidade']-$quantidade];
    }
}
