<?php
namespace Maremon\Services;
use Maremon\Exceptions\JogoException;
final class ItemService {
    // Aplica efeitos à cópia em memória; o endpoint decide se faz COMMIT.
    public function aplicar(array $item,array $alvo,bool $emCombate=false): array {
        $efeito=(string)$item['efeito'];
        $hpMax=max(1,(int)$alvo['hp_max']);
        $hp=max(0,min($hpMax,(int)($alvo['hp']??$alvo['hp_atual']??$hpMax)));
        $status=(string)($alvo['status']??'normal');
        $novo=$alvo;
        $novo['hp']=$hp;
        $novo['hp_max']=$hpMax;
        $novo['status']=$status;
        $novo['status_turnos']=(int)($alvo['status_turnos']??0);
        if ($efeito==='curar_hp' || $efeito==='curar_total') {
            if ($hp<=0 || $hp >= $hpMax) throw new JogoException('Este Pescado não precisa dessa cura.');
            $novo['hp']=$efeito==='curar_hp' ? min($hpMax,$hp+max(1,(int)$item['valor_efeito'])) : $hpMax;
        } elseif ($efeito==='reviver') {
            if ($hp>0) throw new JogoException('Revive só funciona em Pescados com zero HP.');
            $novo['hp']=(int)ceil($hpMax/2);
        } elseif ($efeito==='restaurar_tudo') {
            if ($hp<=0) throw new JogoException('Full Restore não revive um Pescado.');
            if ($hp===$hpMax && $status==='normal') throw new JogoException('O Pescado já está recuperado.');
            $novo['hp']=$hpMax; $novo['status']='normal'; $novo['status_turnos']=0;
        } elseif (in_array($efeito,['curar_veneno','curar_queimadura','curar_congelamento','curar_sono','curar_paralisia','curar_status'],true)) {
            $mapa=['curar_veneno'=>'envenenado','curar_queimadura'=>'queimado','curar_congelamento'=>'congelado','curar_sono'=>'dormindo','curar_paralisia'=>'paralisado'];
            if ($status==='normal' || ($efeito!=='curar_status' && $status!==$mapa[$efeito])) throw new JogoException('O Pescado não possui essa condição.');
            $novo['status']='normal'; $novo['status_turnos']=0;
        } elseif (in_array($efeito,['evoluir','dna_boss'],true)) {
            throw new JogoException('Evolua pela mochila, fora do combate, escolhendo uma espécie compatível.');
        } else {
            throw new JogoException('O efeito deste item ainda não foi implementado.');
        }
        return $novo;
    }
}
