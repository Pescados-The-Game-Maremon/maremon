
const menu = document.getElementById("menu");
const jogo = document.getElementById("jogo");
const cenario = document.getElementById("cenario");

const btnInicio = document.getElementById("btnInicio");
const porta = document.getElementById("porta");
const sacada = document.getElementById("sacada");
const boia = document.getElementById("boia");

const combate = document.getElementById("combate");
const btnMorder = document.getElementById("btnMorder");
const mensagemCombate = document.getElementById("mensagemCombate");

const hpJogador = document.getElementById("hpJogador");
const hpInimigo = document.getElementById("hpInimigo");

const textoHpJogador = document.getElementById("textoHpJogador");
const textoHpInimigo = document.getElementById("textoHpInimigo");

// MONSTRO DO JOGADOR

const meuMonstro = {
    nome: "Seu Monstro",
    hp: 200,
    hpMax: 200,
    ataque: 250,
    defesa: 10,
    velocidade: 15,
    imagem: "assets/inicial.png"
};

// INIMIGOS DISPONÍVEIS

const inimigos = {
    botorosa: {
        nome: "Botorosa",
        hp: 100,
        hpMax: 100,
        ataque: 25,
        defesa: 10,
        velocidade: 10,
        imagem: "assets/botorosa.png"
    },

    namazu: {
        nome: "Namazu",
        hp: 120,
        hpMax: 120,
        ataque: 30,
        defesa: 12,
        velocidade: 8,
        imagem: "assets/namazu.png"
    }
};

// INIMIGO UTILIZADO NO COMBATE ATUAL

let inimigoAtual = null;
let combateEmAndamento = false;
let temporizadorCombate = null;

// MENU INICIAL

btnInicio.addEventListener("click", function () {
    menu.style.display = "none";
    jogo.style.display = "block";
    voltarParaCasa();
});

// VOLTAR PARA A CASA

function voltarParaCasa() {
    clearTimeout(temporizadorCombate);
    temporizadorCombate = null;

    combateEmAndamento = false;
    inimigoAtual = null;

    combate.style.display = "none";
    cenario.style.display = "block";
    cenario.src = "assets/casa_inicial.png";

    porta.style.display = "block";
    sacada.style.display = "block";
    boia.style.display = "none";
}

// IR PARA A PESCA

sacada.addEventListener("click", function () {
    if (combateEmAndamento) return;

    cenario.src = "assets/pescando.png";

    porta.style.display = "none";
    sacada.style.display = "none";
    boia.style.display = "block";
});

// CLICAR NA BOIA

boia.addEventListener("click", function (evento) {
    evento.preventDefault();

    if (combateEmAndamento) return;

    // Escolha o inimigo aqui:
    iniciarCombate("namazu");

    // Para enfrentar o Namazu, use:
    // iniciarCombate("namazu");
});

// INICIAR COMBATE COM QUALQUER INIMIGO

function iniciarCombate(nomeInimigo) {
    if (combateEmAndamento) return;

    const modeloInimigo = inimigos[nomeInimigo];

    if (!modeloInimigo) {
        console.error("Inimigo não encontrado:", nomeInimigo);
        return;
    }

    combateEmAndamento = true;

    // Cria uma cópia dos atributos do inimigo escolhido.
    // Assim, o HP não fica zerado nos próximos combates.
    inimigoAtual = {
        ...modeloInimigo,
        hp: modeloInimigo.hpMax
    };

    meuMonstro.hp = meuMonstro.hpMax;

    atualizarStatus();

    cenario.style.display = "none";

    porta.style.display = "none";
    sacada.style.display = "none";
    boia.style.display = "none";

    const imagemJogador =
        document.querySelector("#jogadorMonstro img");

    const imagemInimigo =
        document.querySelector("#inimigo img");

    if (imagemJogador) {
        imagemJogador.src = meuMonstro.imagem;
        imagemJogador.style.display = "block";
    }

    if (imagemInimigo) {
        imagemInimigo.src = inimigoAtual.imagem;
        imagemInimigo.style.display = "block";
    }

    combate.style.display = "block";

    mensagemCombate.textContent =
        "Um " + inimigoAtual.nome +
        " apareceu! O que seu monstro fará?";

    btnMorder.style.display = "block";
    btnMorder.disabled = false;

    btnAura.style.display = "block";
    btnAura.disabled = false;
}

// BOTÃO MORDER

btnMorder.addEventListener("click", function () {
    if (!combateEmAndamento || btnMorder.disabled) return;

    btnMorder.disabled = true;

    // O jogador ataca primeiro se for mais rápido.
    if (meuMonstro.velocidade > inimigoAtual.velocidade) {
        atacar(meuMonstro, inimigoAtual);

        if (inimigoAtual.hp <= 0) {
            finalizarCombate("Você venceu!");
            return;
        }

        temporizadorCombate = setTimeout(function () {
            turnoInimigo();
        }, 900);
    } else {
        turnoInimigo();
    }
});

// TURNO DO INIMIGO ATUAL

function turnoInimigo() {
    if (!combateEmAndamento) return;

    atacar(inimigoAtual, meuMonstro);

    if (meuMonstro.hp <= 0) {
        finalizarCombate("Seu monstro foi derrotado!");
        return;
    }

    temporizadorCombate = setTimeout(function () {
        if (!combateEmAndamento) return;

        if (inimigoAtual.hp <= 0) {
            finalizarCombate("Você venceu!");
            return;
        }

        mensagemCombate.textContent =
            "O que seu monstro fará?";

        btnMorder.disabled = false;
    }, 900);
}

// ATAQUE GENÉRICO

function atacar(atacante, defensor) {
    const dano = Math.max(
        1,
        atacante.ataque - defensor.defesa
    );

    defensor.hp = Math.max(
        0,
        defensor.hp - dano
    );

    atualizarStatus();

    mensagemCombate.textContent =
        atacante.nome + " usou MORDER! Causou " +
        dano + " de dano.";
}

// ATUALIZAR BARRAS DE VIDA

function atualizarStatus() {
    hpJogador.style.width =
        (meuMonstro.hp / meuMonstro.hpMax * 100) + "%";

    if (inimigoAtual) {
        hpInimigo.style.width =
            (inimigoAtual.hp / inimigoAtual.hpMax * 100) + "%";

        textoHpInimigo.textContent =
            inimigoAtual.hp + " / " + inimigoAtual.hpMax;
    }

    textoHpJogador.textContent =
        meuMonstro.hp + " / " + meuMonstro.hpMax;
}

// FINALIZAR COMBATE

function finalizarCombate(mensagem) {
    if (!combateEmAndamento) return;

    clearTimeout(temporizadorCombate);
    temporizadorCombate = null;

    mensagemCombate.textContent = mensagem;
    btnMorder.disabled = true;

    temporizadorCombate = setTimeout(function () {
        combate.style.display = "none";

        cenario.style.display = "block";
        cenario.src = "assets/pescando.png";

        porta.style.display = "none";
        sacada.style.display = "none";
        boia.style.display = "block";

        combateEmAndamento = false;
        temporizadorCombate = null;
        inimigoAtual = null;
    }, 2000);
}
