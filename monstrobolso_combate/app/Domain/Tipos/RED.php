<?php

namespace Maremon\Domain\Tipos;

use Maremon\Domain\Tipo;

class RED extends Tipo
{
    public function codigo(): string
    {
        return "RED";
    }

    public function vantajosoContra(): array
    {
        return ["YEL"];
    }
}
