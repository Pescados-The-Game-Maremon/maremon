<?php

namespace Maremon\Domain\Tipos;

use Maremon\Domain\Tipo;

class PUR extends Tipo
{
    public function codigo(): string
    {
        return "PUR";
    }

    public function vantajosoContra(): array
    {
        return ["GRN"];
    }
}
