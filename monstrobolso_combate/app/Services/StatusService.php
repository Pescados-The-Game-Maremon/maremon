<?php
namespace Maremon\Services;
final class StatusService {
    private const STATUS = ['envenenado','queimado','congelado','dormindo','paralisado'];
    public static function aplicarAtaque(array &$alvo, array $ataque): ?string {
        $novo = $ataque['status_aplicado'] ?? null;
        if (!in_array($novo,self::STATUS,true) || (int)($alvo['hp']??0)<=0) return null;
        if (($alvo['status']??'normal')!=='normal') return null;
        $chance = max(0,min(100,(int)($ataque['chance_status']??0)));
        if ($chance===0 || random_int(1,100)>$chance) return null;
        $alvo['status']=$novo;
        $alvo['status_turnos']=max(1,min(10,(int)($ataque['duracao_status']??3)));
        return $alvo['nome'].' ficou '.$novo.'!';
    }
    public static function podeAgir(array &$pescado): ?string {
        $status=$pescado['status']??'normal';
        if ($status==='dormindo' || $status==='congelado') {
            if ($status==='congelado' && random_int(1,100)<=35) {
                $pescado['status']='normal'; $pescado['status_turnos']=0;
                return $pescado['nome'].' descongelou!';
            }
            $pescado['status_turnos']=max(0,(int)($pescado['status_turnos']??1)-1);
            $mensagem=$pescado['nome'].($status==='dormindo'?' está dormindo!':' está congelado!');
            if ($pescado['status_turnos']===0) $pescado['status']='normal';
            return $mensagem;
        }
        if ($status==='paralisado' && random_int(1,100)<=25) return $pescado['nome'].' não conseguiu agir por paralisia!';
        return null;
    }
    public static function finalTurno(array &$pescado): ?string {
        $status=$pescado['status']??'normal';
        if ((int)($pescado['hp']??0)<=0) return null;
        $mensagem=null;
        if ($status==='envenenado' || $status==='queimado') {
            $percentual=$status==='envenenado' ? .08 : .06;
            $dano=max(1,(int)floor(max(1,(int)$pescado['hp_max'])*$percentual));
            $pescado['hp']=max(0,(int)$pescado['hp']-$dano);
            $mensagem=$pescado['nome'].' sofreu '.$dano.' de dano por '.$status.'.';
        }
        if (in_array($status,['envenenado','queimado','paralisado'],true)) {
            $pescado['status_turnos']=max(0,(int)($pescado['status_turnos']??1)-1);
            if ($pescado['status_turnos']===0) {
                $pescado['status']='normal';
                $mensagem=($mensagem ? $mensagem.' ' : '').$pescado['nome'].' se recuperou da condição.';
            }
        }
        return $mensagem;
    }
}
