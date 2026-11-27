<?php

namespace Maremon\Domain\Tipos;

use Maremon\Domain\Tipo;

class BLU extends Tipo
{
    public function codigo(): string
    {
        return "BLU";
    }

    public function vantajosoContra(): array
    {
        return ["RED"];
    }
}
