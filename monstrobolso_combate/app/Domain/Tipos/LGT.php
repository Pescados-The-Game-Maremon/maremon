<?php

namespace Maremon\Domain\Tipos;

use Maremon\Domain\Tipo;

class LGT extends Tipo
{
    public function codigo(): string
    {
        return "LGT";
    }

    public function vantajosoContra(): array
    {
        return ["DRK"];
    }
}
