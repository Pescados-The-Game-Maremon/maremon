
const spritesPescados = {
    "Trilobita": "assets/pescados/Trilobita.png",
    "Carnifibita": "assets/pescados/carnifibita.png",
    "Anomalocaris": "assets/pescados/Anomalocaris.png",
    "Aniquilocaris": "assets/pescados/aniquilocaris.png",
    "Hallucigenia": "assets/pescados/Hallucigenia.png",
    "Hallucinado": "assets/pescados/Hallucinado.png",
    "Peixe_Pescador": "assets/pescados/peixe_pescador.png",
    "Pescador_de_Gente": "assets/pescados/pescador_de_gente.png",
    "Peixe_Palhaco": "assets/pescados/peixe_palhaco.png",
    "Bobo_dos_Mares": "assets/pescados/bobo_dos_mares.png",
    "Peixe_Bolha": "assets/pescados/peixe_bolha.png",
    "Homem_Bolha": "assets/pescados/homem_bolha.png",
    "Caranguejo_Azul": "assets/pescados/caranguejo_azul.png",
    "Karkinos": "assets/bosses/karkinos_highres.png",
    "Pirarucu": "assets/pescados/pirarucu.png",
    "Poraque": "assets/pescados/poraque.png",
    "Narval": "assets/pescados/narval.png",
    "Tubarao_Martelo": "assets/pescados/tubarao_martelo.png",
    "Orca": "assets/pescados/orca.png",
    "Arraia": "assets/pescados/arraia.png",
    "Baiacu": "assets/pescados/baiacu.png",
    "Amargama": "assets/pescados/amargama.png",
    "Boto": "assets/pescados/boto.png",
    "Boto_Rosa": "assets/bosses/boto_rosa.png",
    "Truta_Arco_Iris": "assets/pescados/truta_arco_iris.png",
    "Monstro_do_Lago_Ness": "assets/bosses/ness_highres.png",
    "Bagre": "assets/pescados/bagre.png",
    "Namazu": "assets/bosses/namazu_highres.png",
    "Jacare": "assets/pescados/jacare.png",
    "Cipactli": "assets/bosses/cipactli_highres.png",
    "Cavalo_Marinho": "assets/pescados/cavalo_marinho.png",
    "Kelpie": "assets/bosses/kelpie_highres.png",
    "Tubarao_Raposa": "assets/pescados/tubarao_raposa.png",
    "Lusca": "assets/bosses/lusca_highres.png",
    "Tartaruga": "assets/pescados/tartaruga.png",
    "Genbu": "assets/bosses/genbu_highres.png",
    "Agua_Viva": "assets/pescados/agua_viva.png",
    "Hafgufa": "assets/pescados/hafgufa_highres.png",
    "Koi_Yin": "assets/pescados/koi_yin.png",
    "Koi_Yang": "assets/pescados/koi_yang.png",
    "Carpas_Siameses": "assets/pescados/carpas_siameses.png",
    "Polvo": "assets/pescados/polvo.png",
    "Lula": "assets/pescados/lula.png",
    "Kraken": "assets/pescados/kraken.png",
    "Baleia_Azul": "assets/pescados/baleia_azul.png",
    "Bake_Kujira": "assets/pescados/bake_kujira_highres.png",
    "Cachalote": "assets/pescados/baleia_cachalote.png",
    "Moby_Dick": "assets/pescados/moby_dick_highres.png",
    "Salmao": "assets/pescados/salmao.png",
    "Leviata": "assets/pescados/leviathan_highres.png",
    "Umi_Bozu": "assets/pescados/umi_bozu.png"
};

// Escala visual de cada espécie, sem alterar atributos ou HP.
// 1 = tamanho normal; 0.75 = 25% menor; 1.40 = 40% maior.
const escalaSpritesCombate = Object.freeze({
    "Trilobita": 0.60,
    "Carnifibita": 1.10,
    "Anomalocaris": 0.90,
    "Aniquilocaris": 1.18,
    "Hallucigenia": 0.72,
    "Hallucinado": 1.05,
    "Peixe_Pescador": 0.60,
    "Pescador_de_Gente": 1.15,
    "Peixe_Palhaco": 0.50,
    "Bobo_dos_Mares": 1.00,
    "Peixe_Bolha": 0.15,
    "Homem_Bolha": 1.05,
    "Caranguejo_Azul": 0.50,
    "Karkinos": 1.28,
    "Pirarucu": 0.60,
    "Poraque": 1.00,
    "Narval": 1.19,
    "Tubarao_Martelo": 1.24,
    "Orca": 1.20,
    "Arraia": 1.08,
    "Baiacu": 0.25,
    "Amargama": 1.35,
    "Boto": 1.10,
    "Boto_Rosa": 1.20,
    "Truta_Arco_Iris": 0.79,
    "Monstro_do_Lago_Ness": 1.75,
    "Bagre": 0.80,
    "Namazu": 1.34,
    "Jacare": 1.15,
    "Cipactli": 1.36,
    "Cavalo_Marinho": 0.20,
    "Kelpie": 1.45,
    "Tubarao_Raposa": 1.20,
    "Lusca": 1.95,
    "Tartaruga": 0.93,
    "Genbu": 1.35,
    "Agua_Viva": 1.00,
    "Hafgufa": 1.42,
    "Koi_Yin": 0.50,
    "Koi_Yang": 0.50,
    "Carpas_Siameses": 0.82,
    "Polvo": 2.00,
    "Lula": 0.89,
    "Kraken": 1.44,
    "Baleia_Azul": 2.00,
    "Bake_Kujira": 1.90,
    "Cachalote": 2.00,
    "Moby_Dick": 1.44,
    "Salmao": 0.60,
    "Leviata": 1.45,
    "Umi_Bozu": 2.00,
});

function normalizarNomeSprite(nome) {
    return String(nome || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().replace(/[^a-z0-9]/g, "");
}

// Aceita os nomes alternativos já usados no banco e os caminhos dos sprites.
const escalasPorNome = new Map(Object.entries(escalaSpritesCombate).map(
    ([nome, escala]) => [normalizarNomeSprite(nome), escala]
));
const aliasesEscala = {
    nessie: "Monstro_do_Lago_Ness",
    hagfufa: "Hafgufa",
    carpassiamesas: "Carpas_Siameses",
    baleiacachalote: "Cachalote",
    leviathan: "Leviata",
    umizobu: "Umi_Bozu"
};

function obterEscalaSpriteCombate(pescado) {
    const nome = normalizarNomeSprite(pescado?.nome);
    const alias = aliasesEscala[nome];
    const escalaNome = escalasPorNome.get(nome) ?? (alias ? escalaSpritesCombate[alias] : undefined);
    if (escalaNome !== undefined) return escalaNome;

    const arquivo = caminhoSprite(pescado).split("/").pop().toLowerCase();
    const especie = Object.keys(spritesPescados).find(chave =>
        spritesPescados[chave].split("/").pop().toLowerCase() === arquivo
    );
    return escalaSpritesCombate[especie] ?? (pescado?.boss_ordem ? 1.25 : 1);
}

const spritesCarregados = new Map();

function caminhoSprite(pescado) {
    if (pescado?.boss_ordem && pescado?.boss_sprite) {
        return pescado.boss_sprite;
    }
    if (pescado?.sprite) return pescado.sprite;
    const nome = String(pescado?.nome || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().replace(/[^a-z0-9]/g, "");
    const chave = Object.keys(spritesPescados).find(k => k.toLowerCase().replace(/[^a-z0-9]/g, "") === nome);
    return spritesPescados[chave] || "assets/pescados/sem_sprite.svg";
}

// As versões de combate são cópias padronizadas; o original continua disponível.
function caminhoSpriteCombate(pescado) {
    return caminhoSprite(pescado).replace(/^assets\/(pescados|bosses)\/(.+\.png)$/i, "assets/sprites_combate/$1/$2");
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
        "assets/pescados/sem_sprite.svg",
        ...pescados.filter(Boolean).map(caminhoSpriteCombate)
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

// Margem transparente superior das artes padronizadas (1024 × 1024).
// Usada apenas para evitar a HUD sem alterar a escala nem a âncora quando há espaço.
const margensSuperioresCombate = Object.freeze({
    "bosses/boto_rosa.png": 0.0508,
    "bosses/cipactli_highres.png": 0.0508,
    "bosses/genbu_highres.png": 0.3164,
    "bosses/karkinos_highres.png": 0.4355,
    "bosses/kelpie_highres.png": 0.2656,
    "bosses/lusca_highres.png": 0.4297,
    "bosses/namazu_highres.png": 0.1523,
    "bosses/ness_highres.png": 0.4902,
    "pescados/Anomalocaris.png": 0.5566,
    "pescados/Hallucigenia.png": 0.5049,
    "pescados/Hallucinado.png": 0.1455,
    "pescados/Trilobita.png": 0.1494,
    "pescados/agua_viva.png": 0.3369,
    "pescados/amargama.png": 0.4131,
    "pescados/aniquilocaris.png": 0.2754,
    "pescados/arraia.png": 0.0508,
    "pescados/bagre.png": 0.5234,
    "pescados/baiacu.png": 0.0508,
    "pescados/baleia_azul.png": 0.5205,
    "pescados/baleia_cachalote.png": 0.6445,
    "pescados/bobo_dos_mares.png": 0.3213,
    "pescados/boto.png": 0.3574,
    "pescados/caranguejo_azul.png": 0.4316,
    "pescados/carnifibita.png": 0.1963,
    "pescados/carpas_siameses.png": 0.0508,
    "pescados/cavalo_marinho.png": 0.0508,
    "pescados/hafgufa_highres.png": 0.1406,
    "pescados/homem_bolha.png": 0.0508,
    "pescados/jacare.png": 0.6123,
    "pescados/koi_yang.png": 0.0508,
    "pescados/koi_yin.png": 0.0508,
    "pescados/lula.png": 0.3477,
    "pescados/moby_dick_highres.png": 0.4395,
    "pescados/narval.png": 0.6377,
    "pescados/peixe_bolha.png": 0.3721,
    "pescados/peixe_palhaco.png": 0.2881,
    "pescados/peixe_pescador.png": 0.2705,
    "pescados/pescador_de_gente.png": 0.0508,
    "pescados/pirarucu.png": 0.0508,
    "pescados/polvo.png": 0.3496,
    "pescados/poraque.png": 0.6572,
    "pescados/salmao.png": 0.5742,
    "pescados/tartaruga.png": 0.4639,
    "pescados/truta_arco_iris.png": 0.6514,
    "pescados/tubarao_martelo.png": 0.6172,
    "pescados/tubarao_raposa.png": 0.6348,
    "pescados/umi_bozu.png": 0.0508
});

function atualizarSprite(lado, pescado) {
    const elemento = document.getElementById("sprite_" + lado);

    if (!elemento || !pescado) {
        return;
    }

    const original = caminhoSprite(pescado);
    const caminho = caminhoSpriteCombate(pescado);

    elemento.onerror = function () {
        this.style.setProperty("--margem-superior-sprite", 0);
        this.onerror = function () { this.onerror = null; this.src = "assets/pescados/sem_sprite.svg"; };
        if (this.getAttribute("src") !== original) this.src = original;
        else { this.onerror = null; this.src = "assets/pescados/sem_sprite.svg"; }
    };
    if (elemento.getAttribute("src") !== caminho) {
        elemento.src = caminho;
    }

    elemento.style.setProperty("--escala-combate", obterEscalaSpriteCombate(pescado));
    const chaveMargem = caminho.replace(/^assets\/sprites_combate\//, "");
    elemento.style.setProperty("--margem-superior-sprite", margensSuperioresCombate[chaveMargem] ?? 0);
    elemento.alt = pescado.nome;
    elemento.style.display = "block";
}

// Começar o carregamento ao entrar no jogo.
prepararSpritesCombate();
