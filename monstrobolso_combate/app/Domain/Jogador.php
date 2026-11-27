<?php
namespace Maremon\Domain;
                                                                    
final class Jogador {
    private Equipe $equipe;
    public function __construct(private int $id,private string $usuario) {
        $this->equipe=new Equipe();
    }
    public function id(): int {return $this->id;}
    public function usuario(): string {return $this->usuario;}
    public function equipe(): Equipe {return $this->equipe;}
}
