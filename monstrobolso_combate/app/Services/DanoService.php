<?php
namespace Maremon\Services;
use Maremon\Exceptions\JogoException;
final class DanoService {
    // PHP não aceita duas assinaturas com o mesmo nome: __call simula a sobrecarga.
    // calcular(atacante,defensor,poder) ou calcular(atacante,defensor,poder,multiplicador,escudo).
    public function __call(string $metodo,array $argumentos): int {
        if ($metodo!=='calcular' || !in_array(count($argumentos),[3,5],true)) {
            throw new JogoException('Sobrecarga inválida para DanoService.');
        }
        [$atk,$def,$poder]=$argumentos;
        $multi=count($argumentos)===5 ? (float)$argumentos[3] : 1.0;
        $escudo=count($argumentos)===5 ? (float)$argumentos[4] : 1.0;
        if ((int)$poder<=0) return 0;
        $base=max(1,(int)floor(max(1,(int)$atk)*$poder/(max(1,(int)$def)*5)));
        return max(1,(int)floor($base*$multi*$escudo));
    }
}
