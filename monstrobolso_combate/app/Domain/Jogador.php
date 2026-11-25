<?php
namespace Maremon\Domain;
// Encapsulamento da identidade. Associação da conta com a equipe.
final class Jogador {
    private Equipe $equipe;
    public function __construct(private int $id,private string $usuario) {
        $this->equipe=new Equipe();
    }
    public function id(): int {return $this->id;}
    public function usuario(): string {return $this->usuario;}
    public function equipe(): Equipe {return $this->equipe;}
}
