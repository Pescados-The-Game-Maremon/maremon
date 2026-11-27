<?php
namespace Maremon\Services;
use InvalidArgumentException;
final class FormacaoEquipe {
                                                                                    
    public static function mover(array $registros,int $id,int $destino): array {
        if ($destino<0 || $destino>3) throw new InvalidArgumentException('Posição inválida.');
        $formacao=[]; $origem=0; $possui=false;
        foreach ($registros as $p) {
            $pId=(int)$p['pescado_id']; $slot=(int)($p['equipe_slot']??0);
            if ($slot>=1 && $slot<=3) $formacao[$slot]=$pId;
            if ($pId===$id) { $possui=true; $origem=$slot>=1 && $slot<=3?$slot:0; }
        }
        if (!$possui) throw new InvalidArgumentException('Pescado não pertence à conta.');
        if ($origem===$destino) return $formacao;
        $ocupante=$formacao[$destino]??null;
        if ($origem>0) unset($formacao[$origem]);
        if ($destino>0) {
            $formacao[$destino]=$id;
            if ($origem>0 && $ocupante!==null) $formacao[$origem]=$ocupante;
        }
        ksort($formacao);
        return $formacao;
    }
    public static function primeiroVivo(array $registros,array $formacao): ?int {
        $hp=[];
        foreach($registros as $p) $hp[(int)$p['pescado_id']]=(int)$p['hp'];
        ksort($formacao);
        foreach($formacao as $id) if (($hp[$id]??0)>0) return $id;
        return null;
    }
}
