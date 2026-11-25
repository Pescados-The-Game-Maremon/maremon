const telaJogo = document.getElementById("tela_jogo");
let cenaAtual = "cena_casa";

function trocarfundo(fundo) {
    telaJogo.className = fundo;
}

document.addEventListener("DOMContentLoaded", () => {
    trocarfundo("casa_inicial");
    goTo("cena_casa");
});

function goTo(cena) {
    const modelo = document.getElementById(cena);
    if (!modelo?.content) return;

    cenaAtual = cena;
    telaJogo.dataset.cena = cena;
    telaJogo.replaceChildren(modelo.content.cloneNode(true));

    if (cena !== "cena_casa" && cena !== "cena_combate") {
        const navegacao = document.getElementById("navegacao_jogo");
        telaJogo.append(navegacao.content.cloneNode(true));
    }
    posicionarPontosMapa(cena);
}

// Coordenadas na arte original (1536 × 1024), não na janela do navegador.
const mapasInteragiveis = {
    cena_casa: {
        imagem: [1536, 1024],
        pontos: {
            ponto_inventario: [880, 578, 1028, 715],
            ponto_historico: [36, 282, 100, 379],
            ponto_equipe: [365, 754, 605, 890],
            botao_varanda: [1110, 155, 1450, 655],
            botao_porta: [455, 220, 720, 690]
        }
    },
    cena_varanda: {
        imagem: [1536, 1024],
        pontos: { botao_boia: [770, 455, 1015, 690] }
    }
};

function posicionarPontosMapa(cena) {
    const mapa = mapasInteragiveis[cena];
    if (!mapa) return;

    const [iw, ih] = mapa.imagem;
    const largura = telaJogo.clientWidth;
    const altura = telaJogo.clientHeight;
    if (!largura || !altura) return;

    const conter = getComputedStyle(telaJogo).backgroundSize === "contain";
    const escala = conter ? Math.min(largura / iw, altura / ih) : Math.max(largura / iw, altura / ih);
    const deslocamentoX = (largura - iw * escala) / 2;
    const deslocamentoY = (altura - ih * escala) / 2;

    for (const [id, caixa] of Object.entries(mapa.pontos)) {
        const ponto = document.getElementById(id);
        if (!ponto) continue;
        const [x1, y1, x2, y2] = caixa;
        const alvoMinimo = ponto.classList.contains("ponto_objeto") ? 44 : 0;
        const w = Math.max(alvoMinimo, (x2 - x1) * escala);
        const h = Math.max(alvoMinimo, (y2 - y1) * escala);
        const x = ((x1 + x2) / 2) * escala + deslocamentoX - w / 2;
        const y = ((y1 + y2) / 2) * escala + deslocamentoY - h / 2;
        Object.assign(ponto.style, {
            left: `${Math.max(0, Math.min(x, largura - w))}px`,
            top: `${Math.max(0, Math.min(y, altura - h))}px`,
            width: `${w}px`, height: `${h}px`, right: "auto"
        });
    }
}

window.addEventListener("resize", () => posicionarPontosMapa(cenaAtual));

// Um único listener atende a barra clonada em todos os cenários.
let navegacaoEmAndamento = false;
telaJogo.addEventListener("click", async (evento) => {
    const botao = evento.target.closest("[data-destino], [data-sair]");
    if (!botao || navegacaoEmAndamento || cenaAtual === "cena_combate") return;

    // Não abandona uma requisição ou um turno ainda em processamento.
    if (combateOcupado || (cenaAtual === "cena_loja" && compraEmAndamento)) {
        alert("Aguarde a ação atual terminar antes de sair.");
        return;
    }

    navegacaoEmAndamento = true;
    try {
        if (botao.hasAttribute("data-sair")) {
            await sairDaConta();
        } else {
            window.location.assign(botao.dataset.destino);
        }
    } finally {
        navegacaoEmAndamento = false;
    }
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
