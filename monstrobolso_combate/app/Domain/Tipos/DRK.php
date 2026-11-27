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
                                                                   
                                                                
        return ["RED", "BLU", "YEL", "GRN", "PUR"];
    }
}
