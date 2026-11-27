<?php
namespace Maremon\Domain;
use Maremon\Exceptions\JogoException;
// Agregação: Equipe recebe objetos Pescado, que existem fora dela.
final class Equipe {
    /** @var Pescado[] */
    private array $integrantes = [];
    public function adicionar(Pescado $pescado): void {
        if (count($this->integrantes)>=3) throw new JogoException('A equipe já possui 3 membros.');
        $this->integrantes[]=$pescado;
    }
    /** @return Pescado[] */
    public function integrantes(): array {return $this->integrantes;}
    public function quantidade(): int {return count($this->integrantes);}
}
