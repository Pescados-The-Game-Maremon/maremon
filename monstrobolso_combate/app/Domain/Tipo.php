<?php

namespace Maremon\Domain;

abstract class Tipo
{
    abstract public function codigo(): string;

    /** @return string[] */
    abstract public function vantajosoContra(): array;

    public function multiplicadorContra(Tipo $outro): float
    {
        return in_array($outro->codigo(), $this->vantajosoContra(), true)
            ? 1.3
            : 1.0;
    }
}
