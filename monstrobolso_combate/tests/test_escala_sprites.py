"""Verifica escalas, âncoras e animações no Chromium, sem banco de dados."""
from pathlib import Path
from playwright.sync_api import sync_playwright

root = Path(__file__).resolve().parents[1]
css = (root / 'css/combate.css').read_text()
js = (root / 'js/monstros.js').read_text()
html = f'''<style>body{{margin:0}} #tela_jogo{{position:relative;width:100vw;height:100vh}}{css}</style>
<div id="tela_jogo"><div id="interface_combate">
<img id="sprite_jogador" class="sprite_pescado"><img id="sprite_inimigo" class="sprite_pescado">
</div></div><script>{js}</script>'''
with sync_playwright() as pw:
    browser = pw.chromium.launch(executable_path='/usr/bin/chromium' if Path('/usr/bin/chromium').exists() else None, args=['--no-sandbox'])
    page = browser.new_page()
    for w, h in [(1440,900), (1280,720), (1920,1080), (390,844), (844,390)]:
        page.set_viewport_size({'width':w,'height':h})
        page.set_content(html)
        result = page.evaluate('''() => {
            const verificar = (lado, nome) => {
                atualizarSprite(lado, {nome});
                const e = document.getElementById('sprite_'+lado);
                const r = e.getBoundingClientRect();
                return {width:r.width, height:r.height, x:r.x+r.width/2,
                    apoio:r.bottom-r.height*52/1024, top:r.top, left:r.left, right:r.right};
            };
            const nomes=Object.keys(spritesPescados);
            const resultados={};
            for(const lado of ['jogador','inimigo']) {
                resultados[lado]=nomes.map(nome=>[nome,verificar(lado,nome)]);
            }
            return {resultados, total:Object.keys(escalaSpritesCombate).length,
                aliases:['Nessie','Hagfufa','Baleia Cachalote','Leviatã','Umi Zobu'].map(nome=>obterEscalaSpriteCombate({nome})),
                desconhecido:obterEscalaSpriteCombate({nome:'Desconhecido'}),
                normalizado:obterEscalaSpriteCombate({nome:'Peixe palhaço'})};
        }''')
        assert result['total']==51
        assert result['aliases']==[1.4,1.42,1.38,1.45,1.4]
        assert result['normalizado']==.68 and result['desconhecido']==1
        for lado, items in result['resultados'].items():
            values=dict(items)
            x=w*(.21 if lado=='jogador' else .81)
            y=h*(.52 if lado=='jogador' else .47)
            for nome,r in items:
                assert abs(r['x']-x)<1 and abs(r['apoio']-y)<1, (nome,lado,r,x,y)
                assert abs(r['width']-r['height'])<1
                assert r['left']>=-1 and r['right']<=w+1 and r['top']>=-1, (nome,lado,r)
            assert values['Umi_Bozu']['width']>values['Baiacu']['width']*1.5
            assert values['Baleia_Azul']['width']>values['Cavalo_Marinho']['width']*1.7
            page.evaluate('''lado=>{
                atualizarSprite(lado,{nome:'Baleia_Azul'});
                document.getElementById('sprite_'+lado).style.setProperty('--escala-combate',1);
            }''',lado)
            escala_normal=page.locator('#sprite_'+lado).evaluate('e=>e.getBoundingClientRect().width')
            page.evaluate('''lado=>{
                document.getElementById('sprite_'+lado).style.setProperty('--escala-combate',1.2);
            }''',lado)
            escala_maior=page.locator('#sprite_'+lado).evaluate('e=>e.getBoundingClientRect().width')
            assert escala_maior>escala_normal, (lado,escala_normal,escala_maior)
            if w>700:
                page.evaluate('''lado=>{
                    document.getElementById('sprite_'+lado).style.setProperty('--escala-combate',5);
                }''',lado)
                escala_baleia=page.locator('#sprite_'+lado).evaluate('e=>e.getBoundingClientRect().width')
                page.evaluate('''lado=>{
                    document.getElementById('sprite_'+lado).style.setProperty('--escala-combate',1.42);
                }''',lado)
                escala_reduzida=page.locator('#sprite_'+lado).evaluate('e=>e.getBoundingClientRect().width')
                assert escala_baleia>escala_reduzida, (lado,escala_baleia,escala_reduzida)
            # A dimensão não muda durante avanço, impacto ou troca de espécie.
            page.evaluate('''lado=>atualizarSprite(lado,{nome:'Umi_Bozu'})''',lado)
            e=page.locator('#sprite_'+lado)
            width=e.evaluate('e=>e.offsetWidth')
            for classe in ['animando_ataque','recebendo_impacto']:
                e.evaluate('(e,c)=>e.classList.add(c)',classe)
                page.wait_for_timeout(100)
                assert e.evaluate('e=>e.offsetWidth')==width
                e.evaluate('(e,c)=>e.classList.remove(c)',classe)
            page.evaluate('''lado=>atualizarSprite(lado,{nome:'Cavalo_Marinho'})''',lado)
            assert e.evaluate('e=>e.offsetWidth')<width
        print(f'OK: 51 escalas, duas âncoras, troca e animações em {w}x{h}')
    browser.close()
