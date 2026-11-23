<?php

namespace Maremon\Domain\Tipos;

use Maremon\Domain\Tipo;

class GRN extends Tipo
{
    public function codigo(): string
    {
        return "GRN";
    }

    public function vantajosoContra(): array
    {
        return ["BLU"];
    }
}
