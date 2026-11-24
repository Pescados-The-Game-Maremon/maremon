<?php
namespace Maremon\Controllers;
use PDO;
use Maremon\Models\PescadosRepository;
final class PokedexController {
 public function __construct(private PDO $pdo) {}
 public function listar(): array {
   return ['sucesso'=>true,'pescados'=>(new PescadosRepository($this->pdo))->listar()];
 }
}
