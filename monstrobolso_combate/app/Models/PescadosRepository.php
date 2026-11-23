<?php
namespace Maremon\Models;
use PDO;
final class PescadosRepository {
 public function __construct(private PDO $pdo) {}
 public function listar(): array {
   return $this->pdo->query('SELECT p.id,p.nome,p.tipo,p."desc" AS descricao,
     s.hp,s.atk,s.def,s.spe
     FROM public.pescado p JOIN public.stats s ON s.pescado_id=p.id ORDER BY p.nome')
     ->fetchAll(PDO::FETCH_ASSOC);
 }
}
