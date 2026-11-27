<?php
namespace Maremon\Domain;
use Maremon\Exceptions\JogoException;
                                                                     
final class Equipe {
                         
    private array $integrantes = [];
    public function adicionar(Pescado $pescado): void {
        if (count($this->integrantes)>=3) throw new JogoException('A equipe já possui 3 membros.');
        $this->integrantes[]=$pescado;
    }
                            
    public function integrantes(): array {return $this->integrantes;}
    public function quantidade(): int {return count($this->integrantes);}
}
