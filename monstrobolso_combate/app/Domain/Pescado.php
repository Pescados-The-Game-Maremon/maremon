<?php
namespace Maremon\Domain;
// Entidade com mais de 10 atributos, encapsulada e composta com um Tipo concreto.
final class Pescado {
    private int $id;
    private string $nome;
    private Tipo $tipo;
    private int $nivel;
    private int $exp;
    private int $hp;
    private int $hpMax;
    private int $atk;
    private int $def;
    private int $spe;
    private string $status;
    private int $statusTurnos;
    private int $estagioEvolucao;
    private ?int $equipeSlot;
    public function __construct(private array $dados) {
        $this->id=(int)($dados['id']??0);
        $this->nome=(string)($dados['nome']??'Pescado');
        $this->tipo=TipoFactory::criar((string)$dados['tipo']);
        $this->nivel=(int)($dados['nivel']??5);
        $this->exp=(int)($dados['exp']??0);
        $this->hp=(int)($dados['hp']??$dados['hp_atual']??0);
        $this->hpMax=(int)($dados['hp_max']??0);
        $this->atk=(int)($dados['atk']??0);
        $this->def=(int)($dados['def']??0);
        $this->spe=(int)($dados['spe']??0);
        $this->status=(string)($dados['status']??'normal');
        $this->statusTurnos=(int)($dados['status_turnos']??0);
        $this->estagioEvolucao=(int)($dados['estagio_evolucao']??0);
        $this->equipeSlot=isset($dados['equipe_slot'])?(int)$dados['equipe_slot']:null;
    }
    public function id(): int {return $this->id;}
    public function nome(): string {return $this->nome;}
    public function tipo(): Tipo {return $this->tipo;}
    public function nivel(): int {return $this->nivel;}
    public function hp(): int {return $this->hp;}
    public function atributo(string $nome): mixed {return $this->dados[$nome]??null;}
    public function dados(): array {return $this->dados;}
}
