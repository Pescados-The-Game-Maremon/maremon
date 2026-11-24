<?php
namespace Maremon\Controllers;
use PDO;
use Maremon\Domain\Jogador;
use Maremon\Domain\Pescado;
final class EquipeController {
    public function __construct(private PDO $pdo) {}
    public function listar(int $pescadorId): array {
        $s=$this->pdo->prepare('SELECT pescado_id AS id,nome,tipo,nivel,exp,status,status_turnos,
          estagio_evolucao,COALESCE(hp_atual,hp_max) AS hp_atual,hp_max,atk,def,spe,
          selecionado,equipe_slot
          FROM public.vw_equipe_maremon WHERE jogador_id=:id
          ORDER BY selecionado DESC,equipe_slot NULLS LAST,nome');
        $s->execute(['id'=>$pescadorId]);
        $pescados=$s->fetchAll(PDO::FETCH_ASSOC);
        $jogador=new Jogador($pescadorId,'Conta autenticada');
        foreach ($pescados as $registro) {
            if ($registro['equipe_slot']!==null) $jogador->equipe()->adicionar(new Pescado($registro));
        }
        return ['sucesso'=>true,'pescados'=>$pescados,'membros_na_equipe'=>$jogador->equipe()->quantidade()];
    }
}
