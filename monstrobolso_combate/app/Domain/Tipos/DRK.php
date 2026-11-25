<?php

namespace Maremon\Domain\Tipos;

use Maremon\Domain\Tipo;

class DRK extends Tipo
{
    public function codigo(): string
    {
        return "DRK";
    }

    public function vantajosoContra(): array
    {
        // DRK tem vantagem contra todos os outros tipos coloridos.
        // LGT vence DRK. DRK contra DRK é neutro (mesmo tipo).
        return ["RED", "BLU", "YEL", "GRN", "PUR"];
    }
}
