<?php
require_once __DIR__.'/../app/bootstrap.php';
use Maremon\Services\EvolucaoService;
use Maremon\Services\ItemService;
use Maremon\Exceptions\JogoException;
function verificar($ok,$mensagem) {if (!$ok) throw new RuntimeException($mensagem);}
verificar(EvolucaoService::hpProporcional(25,50,100)===50,'HP proporcional');
verificar(EvolucaoService::hpProporcional(0,50,100)===0,'Não revive');
verificar(EvolucaoService::hpProporcional(50,50,100)===100,'HP completo');
verificar(EvolucaoService::hpProporcional(1,100,10)===1,'HP mínimo positivo');
verificar(EvolucaoService::hpProporcional(200,100,20)===20,'HP limitado ao máximo');
foreach (['dna_boss','evoluir'] as $efeito) {
 foreach ([true,false] as $combate) {
  try { (new ItemService())->aplicar(['efeito'=>$efeito],['hp'=>10,'hp_max'=>20],$combate); throw new RuntimeException('Evolução antiga indevidamente permitida'); }
  catch (JogoException $e) { verificar(str_contains($e->getMessage(),'Evolua pela mochila'),'Mensagem correta'); }
 }
}
verificar((new ItemService())->aplicar(['efeito'=>'curar_hp','valor_efeito'=>5],['hp'=>10,'hp_max'=>20])['hp']===15,'Cura preservada');
echo "OK: HP, bloqueio do sistema antigo e cura.\n";
