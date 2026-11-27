<?php
require __DIR__.'/../app/bootstrap.php';
use Maremon\Services\FormacaoEquipe as F;
function verificar($a,$b){if($a!==$b)throw new RuntimeException(json_encode([$a,$b]));}
$r=[['pescado_id'=>10,'equipe_slot'=>1,'hp'=>0],['pescado_id'=>20,'equipe_slot'=>2,'hp'=>12],['pescado_id'=>30,'equipe_slot'=>3,'hp'=>5],['pescado_id'=>40,'equipe_slot'=>null,'hp'=>6]];
verificar(F::mover($r,20,1),[1=>20,2=>10,3=>30]);
verificar(F::mover($r,40,2),[1=>10,2=>40,3=>30]);
verificar(F::mover($r,10,0),[2=>20,3=>30]);
verificar(F::primeiroVivo($r,[1=>10,2=>20,3=>30]),20);
verificar(F::primeiroVivo($r,[1=>10]),null);
verificar(F::primeiroVivo($r,[]),null);
try { F::mover($r,999,1);throw new RuntimeException('Aceitou peixe não possuído'); }catch(InvalidArgumentException $e){}
echo "OK: troca de posições, reserva, retirada, primeiro vivo e posse.\n";
