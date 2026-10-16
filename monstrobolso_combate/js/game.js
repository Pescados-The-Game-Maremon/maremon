
const menu = document.getElementById("menu");
const jogo = document.getElementById("jogo");
const cenario = document.getElementById("cenario");

const btnInicio = document.getElementById("btnInicio");
const porta = document.getElementById("porta");
const sacada = document.getElementById("sacada");
const boia = document.getElementById("boia");
const voltarQuarto = document.getElementById("voltarQuarto");
const cama = document.getElementById("cama");
const btnTeamBuilder = document.getElementById("btnTeamBuilder");
const teamBuilder = document.getElementById("teamBuilder");
const fecharTeamBuilder = document.getElementById("fecharTeamBuilder");

const combate = document.getElementById("combate");
const btnMorder = document.getElementById("btnMorder");
const mensagemCombate = document.getElementById("mensagemCombate");

const hpJogador = document.getElementById("hpJogador");
const hpInimigo = document.getElementById("hpInimigo");

const textoHpJogador = document.getElementById("textoHpJogador");
const textoHpInimigo = document.getElementById("textoHpInimigo");

const meuMonstro = {
    nome: "Seu Monstro",
    hp: 100,
    hpMax: 100,
    ataque: 25,
    defesa: 10,
    velocidade: 15
};

const botorosa = {
    nome: "Botorosa",
    hp: 100,
    hpMax: 100,
    ataque: 25,
    defesa: 10,
    velocidade: 10
};

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
    combateEmAndamento = false;

    combate.style.display = "none";
    cenario.style.display = "block";
    cenario.src = "assets/casa_inicial.png";

    porta.style.display = "block";
    sacada.style.display = "block";
    boia.style.display = "none";
    voltarQuarto.style.display = "none";
    cama.style.display = "block";
    btnTeamBuilder.style.display = "block";
    fecharTeam();
    atualizarTeamBuilder();
}

// IR PARA A PESCA

sacada.addEventListener("click", function () {
    if (combateEmAndamento) return;

    cenario.src = "assets/pescando.png";

    porta.style.display = "none";
    sacada.style.display = "none";
    boia.style.display = "block";
    voltarQuarto.style.display = "block";
    cama.style.display = "none";
    btnTeamBuilder.style.display = "block";
    atualizarTeamBuilder();
});

// VOLTAR DA SACADA PARA O QUARTO
voltarQuarto.addEventListener("click", function () {
    if (combateEmAndamento) return;
    voltarParaCasa();
});

// DORMIR NA CAMA E RECUPERAR A VIDA
cama.addEventListener("click", function () {
    if (combateEmAndamento) return;
    meuMonstro.hp = meuMonstro.hpMax;
    atualizarStatus();
    alert("Você dormiu um pouco e descansou. Seu monstro recuperou toda a vida!");
});

// CLICAR NA BOIA — APENAS UM EVENTO

boia.addEventListener("click", function (evento) {
    evento.preventDefault();

    if (combateEmAndamento) return;

    if (meuMonstro.hp <= 0) {
        alert("Seu monstro está exausto! Volte para o quarto e durma na cama antes de pescar novamente.");
        voltarParaCasa();
        return;
    }

    console.log("BOIA CLICADA!");

    iniciarCombate();
});

// INICIAR COMBATE

function iniciarCombate() {
    if (combateEmAndamento) return;

    combateEmAndamento = true;

    // O HP do jogador é mantido entre os combates.
    // Apenas o inimigo recupera a vida ao iniciar uma nova luta.
    botorosa.hp = botorosa.hpMax;

    atualizarStatus();

    cenario.style.display = "none";

    porta.style.display = "none";
    sacada.style.display = "none";
    boia.style.display = "none";
    voltarQuarto.style.display = "none";
    cama.style.display = "none";
    btnTeamBuilder.style.display = "none";
    fecharTeam();

    const imagemJogador =
        document.querySelector("#jogadorMonstro img");

    const imagemInimigo =
        document.querySelector("#inimigo img");

    if (imagemJogador) {
        imagemJogador.src = "assets/inicial.png";
        imagemJogador.style.display = "block";
    }

    if (imagemInimigo) {
        imagemInimigo.src = "assets/botorosa.png";
        imagemInimigo.style.display = "block";
    }

    combate.style.display = "block";

    mensagemCombate.textContent =
        "Um Botorosa apareceu! O que seu monstro fará?";

    btnMorder.style.display = "block";
    btnMorder.disabled = false;
}

// BOTÃO MORDER

btnMorder.addEventListener("click", function () {
    if (!combateEmAndamento || btnMorder.disabled) return;

    btnMorder.disabled = true;

    // O jogador ataca primeiro porque é mais rápido.
    if (meuMonstro.velocidade > botorosa.velocidade) {
        atacar(meuMonstro, botorosa);

        if (botorosa.hp <= 0) {
            finalizarCombate("Você venceu!");
            return;
        }

        temporizadorCombate = setTimeout(function () {
            turnoBotorosa();
        }, 900);
    } else {
        turnoBotorosa();
    }
});

// TURNO DO BOTOROSA

function turnoBotorosa() {
    if (!combateEmAndamento) return;

    atacar(botorosa, meuMonstro);

    if (meuMonstro.hp <= 0) {
        finalizarCombate("Seu monstro foi derrotado!");
        return;
    }

    temporizadorCombate = setTimeout(function () {
        if (!combateEmAndamento) return;


        if (botorosa.hp <= 0) {
            finalizarCombate("Você venceu!");
            return;
        }

        mensagemCombate.textContent =
            "O que seu monstro fará?";

        btnMorder.disabled = false;
    }, 900);
}

// ATAQUE MORDER

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

    if (atacante === meuMonstro) {
        mensagemCombate.textContent =
            "Seu monstro usou MORDER! Causou " +
            dano + " de dano.";
    } else {
        mensagemCombate.textContent =
            "Botorosa usou MORDER! Causou " +
            dano + " de dano.";
    }
}

// ATUALIZAR BARRAS DE VIDA

function atualizarStatus() {
    hpJogador.style.width =
        (meuMonstro.hp / meuMonstro.hpMax * 100) + "%";

    hpInimigo.style.width =
        (botorosa.hp / botorosa.hpMax * 100) + "%";

    textoHpJogador.textContent =
        meuMonstro.hp + " / " + meuMonstro.hpMax;

    textoHpInimigo.textContent =
        botorosa.hp + " / " + botorosa.hpMax;
}

// FINALIZAR COMBATE

function finalizarCombate(mensagem) {
    if (!combateEmAndamento) return;

    const jogadorDerrotado = meuMonstro.hp <= 0;

    mensagemCombate.textContent = mensagem;
    btnMorder.disabled = true;

    temporizadorCombate = setTimeout(function () {
        combate.style.display = "none";
        combateEmAndamento = false;

        if (jogadorDerrotado) {
            // Derrota: retorna ao quarto, mas não cura automaticamente.
            // O jogador precisa clicar na cama para recuperar o HP.
            voltarParaCasa();
            mensagemCombate.textContent =
                "Seu monstro precisa descansar. Vá até a cama para recuperar o HP.";
            return;
        }

        // Vitória: retorna à sacada e mantém o HP restante.
        cenario.style.display = "block";
        cenario.src = "assets/pescando.png";
        btnTeamBuilder.style.display = "block";
        atualizarTeamBuilder();

        porta.style.display = "none";
        sacada.style.display = "none";
        boia.style.display = "block";
        voltarQuarto.style.display = "block";
        cama.style.display = "none";
    }, 2000);
}

/* TEAM BUILDER: acessível nos mapas, nunca durante o combate */
btnTeamBuilder.addEventListener("click", function () {
    if (combateEmAndamento || combate.style.display === "block") return;
    atualizarTeamBuilder();
    teamBuilder.classList.add("aberto");
});

fecharTeamBuilder.addEventListener("click", fecharTeam);

teamBuilder.addEventListener("click", function (evento) {
    if (evento.target === teamBuilder) fecharTeam();
});

document.addEventListener("keydown", function (evento) {
    if (evento.key === "Escape") fecharTeam();
});

function fecharTeam() {
    teamBuilder.classList.remove("aberto");
}

function atualizarTeamBuilder() {
    const hpAtual = Math.max(0, meuMonstro.hp);
    const hpMaximo = meuMonstro.hpMax;
    const porcentagem = hpMaximo > 0 ? (hpAtual / hpMaximo) * 100 : 0;

    document.getElementById("teamHpText").textContent =
        hpAtual + " / " + hpMaximo;
    document.getElementById("teamHpBar").style.width = porcentagem + "%";
    document.getElementById("teamHpBar").style.background =
        porcentagem > 50 ? "#52d77a" : porcentagem > 20 ? "#f0c64e" : "#ed5b62";

    document.getElementById("teamAtaque").textContent = meuMonstro.ataque;
    document.getElementById("teamDefesa").textContent = meuMonstro.defesa;
    document.getElementById("teamVelocidade").textContent = meuMonstro.velocidade;
}
