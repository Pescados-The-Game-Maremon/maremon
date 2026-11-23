MONSTROBOLSO - COMO EXECUTAR

Abra o arquivo index.html no navegador ou execute o projeto pelo servidor local.

FLUXO DO JOGO
- Início abre a casa.
- Porta ou sacada levam à área externa/pescaria.
- Clique na boia para iniciar o combate.
- O botão MORDER executa o turno do jogador; o Botorosa responde automaticamente.
- Quando um dos monstros chega a 0 HP, o combate termina e, após 2 segundos, retorna à pescaria.

TROCA DE CENÁRIO
A função trocarfundo(nome) continua disponível em js/game.js.
Cenários aceitos: casa_inicial, fundo_boia e pescando.

COMBATE
- Ambos os monstros começam com 100 HP, ataque 25 e defesa 10.
- Seu monstro tem velocidade 15 e Botorosa tem velocidade 10.
- O ataque disponível é MORDER.

Os estilos utilizados estão reunidos em css/style.css. O arquivo CSS de fundos separado foi removido.
COLOQUE NA PASTA assets:
- casa_inicial.png
- pescando.png
- tela_combate.png
- inicial.png
- botorosa.png

Fluxo:
Início -> casa -> sacada -> pescando -> boia -> combate.
Os dois monstros têm HP 100, ataque 25 e defesa 10.
Seu monstro tem velocidade 15 e Botorosa 10.
O único ataque é MORDER.
Quando um chega a 0 HP, o combate termina e após 2 segundos volta para pescando.png.
