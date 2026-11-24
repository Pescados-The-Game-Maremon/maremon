function trocarfundo(fundo) {
    const tela = document.getElementById("tela_jogo");
    tela.className = "";
    tela.classList.add(fundo);
}
// Define o fundo inicial ao carregar o jogo
document.addEventListener("DOMContentLoaded", function () {
    trocarfundo("casa_inicial");
    goTo("cena_casa");
});
function goTo(cena) {
    const modelo = document.getElementById(cena);
    if (modelo?.content) {
        document.getElementById("tela_jogo").replaceChildren(modelo.content.cloneNode(true));
        posicionarPontosMapa(cena);
    }
}

// Posiciona as áreas clicáveis usando as coordenadas originais das imagens.
// Assim os pontos continuam alinhados mesmo quando o fundo usa background-size: cover.
function posicionarPontosMapa(cena) {
    const tela = document.getElementById("tela_jogo");
    const mapa = {
        cena_casa: {
            imagem: [1536, 1024],
            pontos: {
                botao_varanda: [1080, 105, 1490, 700],
                botao_porta: [455, 220, 720, 690]
            }
        },
        cena_varanda: {
            imagem: [1536, 1024],
            pontos: { botao_boia: [770, 455, 1015, 690] }
        }
    }[cena];
    if (!mapa) return;

    const [iw, ih] = mapa.imagem;
    const largura = tela.clientWidth;
    const altura = tela.clientHeight;
    const escala = Math.max(largura / iw, altura / ih);
    const renderW = iw * escala;
    const renderH = ih * escala;
    const corteX = (renderW - largura) / 2;
    const corteY = (renderH - altura) / 2;

    Object.entries(mapa.pontos).forEach(([id, caixa]) => {
        const botao = document.getElementById(id);
        if (!botao) return;
        const [x1, y1, x2, y2] = caixa;
        botao.style.left = `${(x1 * escala - corteX) / largura * 100}%`;
        botao.style.top = `${(y1 * escala - corteY) / altura * 100}%`;
        botao.style.width = `${(x2 - x1) * escala / largura * 100}%`;
        botao.style.height = `${(y2 - y1) * escala / altura * 100}%`;
        botao.style.right = "auto";
    });
}

window.addEventListener("resize", () => {
    const casa = document.querySelector("#botao_varanda");
    const varanda = document.querySelector("#botao_boia");
    if (casa) posicionarPontosMapa("cena_casa");
    else if (varanda) posicionarPontosMapa("cena_varanda");
});
async function sairDaConta() {
    try {
        const resposta = await fetch("php/logout.php", {
            method: "POST",
            credentials: "same-origin"
        });
        const dados = await resposta.json();
        if (dados.sucesso) {
            window.location.href = "login.html";
        }
        else {
            alert("Não foi possível sair da conta.");
        }
    }
    catch (erro) {
        console.error("Erro ao sair:", erro);
    }
}
