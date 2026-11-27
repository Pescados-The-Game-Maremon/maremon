<?php
namespace Maremon\Controllers;
use PDO;
use Maremon\Domain\Jogador;
use Maremon\Domain\Pescado;
final class EquipeController {
    public function __construct(private PDO $pdo) {}
    public function listar(int $pescadorId): array {
        $s = (new \Maremon\Models\EquipeDadosRepository($this->pdo))->listarEquipe(['id'=>$pescadorId]);
        $pescados=$s->fetchAll(PDO::FETCH_ASSOC);
        $jogador=new Jogador($pescadorId,'Conta autenticada');
        foreach ($pescados as $registro) {
            if ($registro['equipe_slot']!==null) $jogador->equipe()->adicionar(new Pescado($registro));
        }
        return ['sucesso'=>true,'pescados'=>$pescados,'membros_na_equipe'=>$jogador->equipe()->quantidade()];
    }
}
