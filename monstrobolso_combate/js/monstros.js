
const spritesPescados = {
    "Trilobita": "assets/pescados/Trilobita.png",
    "Anomalocaris": "assets/pescados/Anomalocaris.png",
    "Hallucigenia": "assets/pescados/Hallucigenia.png",
    "Namazu": "assets/namazu.png",
    "Boto_Rosa": "assets/botorosa.png"
};

const spritesCarregados = new Map();

function caminhoSprite(pescado) {
    if (pescado?.boss_ordem && pescado?.boss_sprite) {
        return pescado.boss_sprite;
    }
    return spritesPescados[pescado?.nome] || "assets/inicial.png";
}

function carregarImagem(caminho) {
    if (spritesCarregados.has(caminho)) {
        return spritesCarregados.get(caminho);
    }

    const promessa = new Promise((resolve, reject) => {
        const imagem = new Image();

        imagem.onload = async () => {
            try {
                if (typeof imagem.decode === "function") {
                    await imagem.decode();
                }

                resolve(imagem);
            } catch (erro) {
                reject(erro);
            }
        };

        imagem.onerror = () => {
            reject(new Error("Erro ao carregar: " + caminho));
        };

        imagem.src = caminho;
    });

    spritesCarregados.set(caminho, promessa);

    return promessa;
}

async function prepararSpritesCombate(pescados = []) {
    const caminhos = [
        ...Object.values(spritesPescados),
        "assets/inicial.png",
        ...pescados.filter(Boolean).map(caminhoSprite)
    ];

    const caminhosUnicos = [...new Set(caminhos)];

    await Promise.all(
        caminhosUnicos.map(caminho =>
            carregarImagem(caminho).catch(erro => {
                console.warn(erro.message);
            })
        )
    );
}

function atualizarSprite(lado, pescado) {
    const elemento = document.getElementById("sprite_" + lado);

    if (!elemento || !pescado) {
        return;
    }

    const caminho = caminhoSprite(pescado);

    if (elemento.getAttribute("src") !== caminho) {
        elemento.src = caminho;
    }

    elemento.onerror = function () {
        this.onerror = null;
        this.src = pescado.nome === "Namazu"
            ? "assets/namazu.png"
            : pescado.nome === "Boto_Rosa"
                ? "assets/botorosa.png"
                : "assets/inicial.png";
    };
    elemento.alt = pescado.nome;
    elemento.style.display = "block";
}

// Começar o carregamento ao entrar no jogo.
prepararSpritesCombate();
