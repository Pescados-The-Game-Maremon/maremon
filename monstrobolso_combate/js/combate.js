
const API_COMBATE = "/Maremon/monstrobolso_combate/php/";

let combateOcupado = false;
let combateIniciado = false;

async function requisitarCombate(arquivo, opcoes = {}) {
    const resposta = await fetch(API_COMBATE + arquivo, {
        credentials: "same-origin",
        ...opcoes
    });

    const dados = await resposta.json();

    if (!resposta.ok || !dados.sucesso) {
        throw new Error(dados.erro || "Erro no combate.");
    }

    return dados;
}

function mensagemCombate(texto) {
    const elemento = document.getElementById("mensagem_combate");

    if (elemento) {
        elemento.textContent = texto;
    }
}

function bloquearAtaques(bloquear) {
    document.querySelectorAll("#ataques_combate button")
        .forEach(botao => {
            botao.disabled = bloquear;
        });
}

async function abrirCombate() {
    if (combateOcupado) return;

    combateOcupado = true;
    combateIniciado = false;

    trocarfundo("fundo_combate");
    goTo("cena_combate");

    mensagemCombate("Procurando um Pescado selvagem...");

    try {
        const dados = await requisitarCombate(
            "iniciar_combate.php"
        );

        atualizarInterfaceCombate(dados);
        await carregarAtaques();

        combateIniciado = true;

        mensagemCombate(
            `Um ${dados.inimigo.nome} selvagem apareceu!`
        );

    } catch (erro) {
        mensagemCombate(erro.message);
    } finally {
        combateOcupado = false;
    }
}

function atualizarInterfaceCombate(dados) {
    atualizarPescado("jogador", dados.jogador);
    atualizarPescado("inimigo", dados.inimigo);
}

function atualizarPescado(lado, pescado) {
    document.getElementById("nome_" + lado).textContent =
        `${pescado.nome} - Nv. ${pescado.nivel}`;

    document.getElementById("hp_" + lado).textContent =
        `HP: ${pescado.hp}/${pescado.hp_max}`;

    const porcentagem = Math.max(
        0,
        Math.min(
            100,
            (Number(pescado.hp) / Number(pescado.hp_max)) * 100
        )
    );

    const barra = document.getElementById("barra_" + lado);

    barra.style.width = porcentagem + "%";

    if (porcentagem > 50) {
        barra.style.background = "#33cc55";
    } else if (porcentagem > 20) {
        barra.style.background = "#e6bf28";
    } else {
        barra.style.background = "#e04444";
    }
}

async function carregarAtaques() {
    const dados = await requisitarCombate(
        "buscar_ataques.php"
    );

    const lista = document.getElementById("ataques_combate");

    lista.innerHTML = "";

    if (!dados.ataques.length) {
        throw new Error("Este Pescado não possui ataques.");
    }

    dados.ataques.forEach(ataque => {
        const botao = document.createElement("button");

        botao.textContent = ataque.nome;

        botao.addEventListener("click", () => {
            executarAtaque(ataque.id);
        });

        lista.appendChild(botao);
    });
}

async function executarAtaque(ataqueId) {
    if (combateOcupado || !combateIniciado) return;

    combateOcupado = true;
    bloquearAtaques(true);

    try {
        const dados = await requisitarCombate(
            "executar_turno.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    ataque_id: ataqueId
                })
            }
        );

        atualizarInterfaceCombate(dados);

        const mensagens = dados.eventos.map(evento => {
            if (!evento.acertou) {
                return `${evento.atacante} errou ${evento.ataque}!`;
            }

            return `${evento.atacante} usou ${evento.ataque} e causou ${evento.dano} de dano!`;
        });

        if (dados.resultado === "vitoria") {
            mensagens.push("Você venceu o combate!");
        } else if (dados.resultado === "derrota") {
            mensagens.push("Seu Pescado foi derrotado!");
        }

        mensagemCombate(mensagens.join(" "));

        if (dados.resultado !== "continuar") {
            combateIniciado = false;
        }

    } catch (erro) {
        mensagemCombate(erro.message);
    } finally {
        combateOcupado = false;

        if (combateIniciado) {
            bloquearAtaques(false);
        }
    }
}

function fugirCombate() {
    if (combateOcupado) return;

    combateIniciado = false;

    trocarfundo("varanda");
    goTo("cena_varanda");
}
