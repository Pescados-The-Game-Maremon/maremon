from pathlib import Path
from playwright.sync_api import sync_playwright
import re
root=Path(__file__).resolve().parents[1]
mock='''<script>
let mockPlayers=[{id:1,nome:'Baiacu',tipo:'GRN',nivel:5,exp:10,hp_atual:0,hp_max:54,atk:16,def:24,spe:12,equipe_slot:1},{id:2,nome:'Narval',tipo:'BLU',nivel:5,exp:0,hp_atual:65,hp_max:65,atk:23,def:18,spe:20,equipe_slot:2},{id:3,nome:'Cavalo_Marinho',tipo:'BLU',nivel:5,exp:0,hp_atual:42,hp_max:42,atk:14,def:15,spe:23,equipe_slot:null}];
let negar=true;
window.fetch=async (url,o={})=>{
let d={sucesso:true};let status=200;
if(url.endsWith('minha_equipe.php'))Object.assign(d,{pescados:mockPlayers,csrf_token:'t',em_combate:false});
else if(url.endsWith('organizar_equipe.php')){let req=JSON.parse(o.body);let p=mockPlayers.find(x=>x.id===req.pescado_id);let outro=mockPlayers.find(x=>x.equipe_slot===req.slot&&x!==p);if(outro)outro.equipe_slot=p.equipe_slot;p.equipe_slot=req.slot||null;d.mensagem='Formação atualizada';}
else if(url.endsWith('listar_iscas.php'))Object.assign(d,{iscas:[{ordem:1,isca:'Isca',liberado:true,derrotado:false,nome:'Boto Rosa'}],isca_atual:1,csrf_token:'t'});
else if(url.endsWith('iniciar_combate.php')){if(negar){d={sucesso:false,erro:'Todos os Pescados da equipe estão sem HP. Vá ao Centro de Cura.'};status=400;}else{let j={id:2,nome:'Narval',tipo:'BLU',hp:65,hp_max:65,nivel:5,ativo:true};let i={id:3,nome:'Cavalo_Marinho',tipo:'BLU',hp:42,hp_max:42,nivel:5,ativo:false};Object.assign(d,{jogador:j,inimigo:i,equipe:[j],boss_ordem:null,troca_obrigatoria:false,combate_id:123,csrf_captura:'c',csrf_acao:'a',captura_tentada:false});}}
else if(url.endsWith('buscar_ataques.php'))d.ataques=[{id:1,nome:'Ataque teste',tipo:'BLU',pp_atual:5,pp_max:5}];
else if(url.endsWith('listar_loja.php'))Object.assign(d,{dinheiro:200,csrf_token:'t',itens:[{id:1,nome:'Item <b>seguro</b>',descricao:'teste',preco:20}]});
return new Response(JSON.stringify(d),{status,headers:{'Content-Type':'application/json'}});
};
</script>'''
def html(f):
 s=re.sub(r'<\?(?:php|=).*?\?>','',(root/f).read_text(),flags=re.S)
 s=re.sub(r'<script src="(js/[^"?]+)(?:\?[^"\n]*)?"></script>',lambda m:'<script>'+ (root/m[1]).read_text()+'</script>',s)
 s=re.sub(r'<link rel="stylesheet"\s+href="(css/[^"?]+)(?:\?[^"\n]*)?">',lambda m:'<style>'+ (root/m[1]).read_text()+'</style>',s)
 return s.replace('</head>',mock+'</head>')
with sync_playwright() as pw:
 browser=pw.chromium.launch(executable_path='/usr/bin/chromium' if Path('/usr/bin/chromium').exists() else None,args=['--no-sandbox'],headless=True)
 page=browser.new_page(viewport={'width':1440,'height':900});errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
 page.set_content(html('equipe.php'));page.locator('.formacao_slot').first.wait_for(timeout=3000);assert page.locator('.nome_slot').all_text_contents()==['Baiacu','Narval','Posição vazia']
 page.locator('.card_equipe').filter(has_text='Narval').locator('[data-slot="1"]').click();page.wait_for_function("document.querySelector('.nome_slot').textContent==='Narval'",timeout=3000);assert page.locator('.nome_slot').all_text_contents()==['Narval','Baiacu','Posição vazia']
 page.locator('.card_equipe').filter(has_text='Cavalo_Marinho').locator('[data-slot="3"]').click();page.wait_for_function("document.querySelectorAll('.nome_slot')[2].textContent==='Cavalo_Marinho'",timeout=3000);print('OK: equipe renderizada e troca/adição por botões.')
 page.close();page=browser.new_page(viewport={'width':1440,'height':900});page.on('pageerror',lambda e:errors.append(str(e)))
 page.set_content(html('index.php'));page.evaluate("prepararSpritesCombate=async()=>{};trocarfundo('varanda');goTo('cena_varanda');carregarIscas()");page.locator('.isca_opcao').wait_for(timeout=3000);page.evaluate('abrirCombate()');page.wait_for_function("document.querySelector('#aviso_pesca').textContent.includes('Centro de Cura')",timeout=3000);assert page.locator('#interface_combate').count()==0;assert page.locator('#botao_boia').is_enabled();print('OK: início negado sem abrir a arena.')
 page.evaluate('negar=false;abrirCombate()');page.locator('#ataques_combate .botao_ataque').wait_for(state='attached',timeout=3000);assert page.evaluate("caminhoSpriteCombate({nome:'Narval'})")=="assets/sprites_combate/pescados/narval.png";print('OK: início válido, sprites e ataques.')
 page.evaluate('abrirLoja()');page.locator('.produto_loja').wait_for(timeout=3000);assert page.locator('.produto_loja h3').inner_text()=='Item <b>seguro</b>';assert page.locator('.produto_loja h3 b').count()==0;print('OK: loja e texto de API seguro.')
 assert not errors,errors
 browser.close()
