"""Gera cópias de combate sem alterar as artes originais.
Uso: python -m pip install Pillow; python tools/padronizar_sprites.py
"""
from pathlib import Path
from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
SIZE = 1024
MARGIN = 52

def padronizar(imagem):
    imagem = imagem.convert('RGBA')
    alpha = imagem.getchannel('A')
    # Desconsidera ruído quase invisível na determinação da borda.
    caixa = alpha.point(lambda x: 255 if x >= 24 else 0).getbbox()
    if not caixa:
        raise ValueError('imagem sem conteúdo visível')
    recorte = imagem.crop(caixa)
    escala = (SIZE - 2 * MARGIN) / max(recorte.size)
    largura, altura = (max(1, round(n * escala)) for n in recorte.size)
    recorte = recorte.resize((largura, altura), Image.Resampling.LANCZOS)
    quadro = Image.new('RGBA', (SIZE, SIZE), (0, 0, 0, 0))
    quadro.paste(recorte, ((SIZE - largura) // 2, SIZE - MARGIN - altura))
    return quadro

def main():
    quantidade = 0
    for pasta in ('pescados', 'bosses'):
        saida = ROOT / 'assets' / 'sprites_combate' / pasta
        saida.mkdir(parents=True, exist_ok=True)
        for arquivo in sorted((ROOT / 'assets' / pasta).iterdir()):
            if arquivo.suffix.lower() != '.png':
                continue
            with Image.open(arquivo) as imagem:
                padronizar(imagem).save(saida / arquivo.name, optimize=True)
            quantidade += 1
    print(f'{quantidade} sprites padronizados em assets/sprites_combate/.')

if __name__ == '__main__':
    main()
