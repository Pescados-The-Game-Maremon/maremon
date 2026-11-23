<?php
namespace Maremon\Services;
final class ProgressaoService {
    public static function hpMaximo(int $hpBase,int $nivel,int $estagio): int {
        return max(1,$hpBase+2*max(0,$nivel-5)+10*$estagio);
    }
    public static function atributo(int $base,int $nivel,int $estagio): int {
        return max(1,$base+max(0,$nivel-5)+3*$estagio);
    }
}
