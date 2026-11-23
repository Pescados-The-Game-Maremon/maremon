<?php

namespace Maremon\Domain\Tipos;

use Maremon\Domain\Tipo;

class YEL extends Tipo
{
    public function codigo(): string
    {
        return "YEL";
    }

    public function vantajosoContra(): array
    {
        return ["PUR"];
    }
}
