<?php
namespace Maremon\Services;

use PDO;
use Maremon\Exceptions\JogoException;

/** Transforma um exemplar em outra espécie dentro da transação do inventário. */
final class EvolucaoService
{
    public static function opcoes(PDO $pdo, int $pescadorId): array
    {
        $s = (new \Maremon\Models\EvolucaoRepository($pdo))->listarOpcoes(['id'=>$pescadorId]);
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
        $s = (new \Maremon\Models\EvolucaoRepository($pdo))->buscarRota(['evolucao'=>$evolucaoId,'origem'=>$pescadoId,'item'=>$itemId]);
        $rotas=$s->fetchAll(PDO::FETCH_ASSOC);
        if (count($rotas)!==1) throw new JogoException('Este item não corresponde a uma evolução válida deste Pescado.');
        $rota=$rotas[0];
        $destino=(int)$rota['pescado_destino_id'];
        $quantidade=(int)$rota['quantidade_item'];
        if ($destino===$pescadoId || $quantidade<1 || (int)$rota['hp_base']<1) {
            throw new JogoException('A evolução está configurada incorretamente.');
        }
        if ((int)$item['quantidade']<$quantidade) throw new JogoException('Quantidade de itens insuficiente.');
        $s = (new \Maremon\Models\EvolucaoRepository($pdo))->verificarEspecieDestino(['id'=>$pescadorId,'destino'=>$destino]);
        if ($s->fetchColumn()) throw new JogoException('Você já possui a espécie evoluída.');
        // Sem requisito de nível. Nível, experiência, status e equipe permanecem intactos.
        $hpNovoMax=(int)$rota['hp_base']+2*max(0,(int)$alvo['nivel']-5);
        $hpNovo=self::hpProporcional((int)$alvo['hp'],(int)$alvo['hp_max'],$hpNovoMax);
        $s = (new \Maremon\Models\EvolucaoRepository($pdo))->transformarPescado(['destino'=>$destino,'hp'=>$hpNovo,'estagio'=>(int)$rota['estagio_destino'],
            'id'=>$pescadorId,'origem'=>$pescadoId]);
        if ($s->rowCount()!==1) throw new JogoException('Não foi possível transformar o Pescado.');
        $s = (new \Maremon\Models\EvolucaoRepository($pdo))->consumirMaterial(['consumo'=>$quantidade,'id'=>$pescadorId,'item'=>$itemId,'minimo'=>$quantidade]);
        if ($s->rowCount()!==1) throw new JogoException('Falha ao consumir o item evolutivo.');
        $s = (new \Maremon\Models\EvolucaoRepository($pdo))->limparMaterial(['id'=>$pescadorId,'item'=>$itemId]);
        return ['sucesso'=>true,'mensagem'=>'Seu Pescado evoluiu para '.$rota['destino'].'!',
            'evoluiu'=>true,'pescado_id'=>$destino,'nome'=>$rota['destino'],'tipo'=>$rota['tipo'],
            'hp_anterior'=>(int)$alvo['hp'],'hp_atual'=>$hpNovo,'hp_max'=>$hpNovoMax,
            'status_atual'=>$alvo['status'],'estagio_evolucao'=>(int)$rota['estagio_destino'],
            'quantidade_restante'=>(int)$item['quantidade']-$quantidade];
    }
}
