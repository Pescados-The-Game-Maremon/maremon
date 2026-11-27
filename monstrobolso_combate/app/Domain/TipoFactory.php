<?php
namespace Maremon\Domain;
use Maremon\Exceptions\JogoException;
final class TipoFactory {
    public static function criar(string $codigo): Tipo {
        $codigo = strtoupper(trim($codigo));
        if (!in_array($codigo, ['RED','YEL','PUR','GRN','BLU','LGT','DRK'], true)) {
            throw new JogoException("Tipo inválido: " . $codigo);
        }
        $classe = 'Maremon\\Domain\\Tipos\\' . $codigo;
        return new $classe();
    }
}
