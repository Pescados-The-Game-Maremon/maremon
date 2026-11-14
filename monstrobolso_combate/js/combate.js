
const API_COMBATE = "/Maremon/monstrobolso_combate/php/";

let combateOcupado = false;
let combateIniciado = false;
let tokenCaptura = null;
let capturaTentada = false;

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

    tokenCaptura = dados.csrf_captura;
    capturaTentada = dados.captura_tentada;
    atualizarBotaoCaptura();

        atualizarInterfaceCombate(dados);
        await carregarAtaques();

        combateIniciado = true;
        atualizarBotaoCaptura();

        mensagemCombate(
            `Um ${dados.inimigo.nome} selvagem apareceu!`
        );

    } catch (erro) {
        mensagemCombate(erro.message);
    } finally {
        combateOcupado = false;
        atualizarBotaoCaptura();
    }
}

function atualizarInterfaceCombate(dados) {
    atualizarPescado("jogador", dados.jogador);
    atualizarPescado("inimigo", dados.inimigo);

    atualizarSprite("jogador", dados.jogador);
    atualizarSprite("inimigo", dados.inimigo);
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

    // Mostrar a experiência recebida
    mensagens.push(
        `${dados.jogador.nome} ganhou ${dados.exp_ganha} EXP!`
    );

} else if (dados.resultado === "derrota") {
    mensagens.push("Seu Pescado foi derrotado!");
}

// Verificar se o Pescado subiu de nível
if (dados.niveis_ganhos > 0) {
    mensagens.push(
        `${dados.jogador.nome} subiu do nível ` +
        `${dados.nivel_anterior} para o nível ` +
        `${dados.nivel_novo}!`
    );

    mensagens.push(
        "HP máximo +2, Ataque +1, Defesa +1 e Velocidade +1 por nível!"
    );
}

        mensagemCombate(mensagens.join(" "));

        if (dados.resultado !== "continuar") {
            combateIniciado = false;
             voltarParaPescando();
        }

    } catch (erro) {
        mensagemCombate(erro.message);
    } finally {
        combateOcupado = false;

        if (combateIniciado) {
            bloquearAtaques(false);
            atualizarBotaoCaptura();
        }
    }
}


async function fugirCombate() {
    if (combateOcupado || !combateIniciado) return;

    combateOcupado = true;
    bloquearAtaques(true);
    atualizarBotaoCaptura();

    try {
        await requisitarCombate(
            "fugir_combate.php",
            {
                method: "POST"
            }
        );

        // Encerrar o combate no navegador
        combateIniciado = false;
        tokenCaptura = null;
        capturaTentada = false;

        // Voltar para a varanda
        trocarfundo("varanda");
        goTo("cena_varanda");

    } catch (erro) {
        mensagemCombate(erro.message);

    } finally {
        combateOcupado = false;
        atualizarBotaoCaptura();

        if (combateIniciado) {
            bloquearAtaques(false);
        }
    }
}

function voltarParaPescando() {
    trocarfundo("varanda");
    goTo("cena_varanda");
}

function atualizarBotaoCaptura() {
    const botao = document.getElementById("botao_capturar");

    if (!botao) return;

    botao.disabled =
        !combateIniciado ||
        combateOcupado ||
        capturaTentada;
}


async function capturarPescado() {
    if (
        combateOcupado ||
        !combateIniciado ||
        capturaTentada
    ) {
        return;
    }

    combateOcupado = true;

    bloquearAtaques(true);
    atualizarBotaoCaptura();

    mensagemCombate("Tentando capturar o Pescado...");

    try {
        const dados = await requisitarCombate(
            "capturar_pescado.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    csrf_captura: tokenCaptura
                })
            }
        );

        capturaTentada = dados.captura_tentada;

        mensagemCombate(dados.mensagem);

        if (dados.capturou) {
            combateIniciado = false;
            tokenCaptura = null;
            voltarParaPescando();
            bloquearAtaques(true);
        }

    } catch (erro) {
        mensagemCombate(erro.message);

   } finally {
    combateOcupado = false;

    if (combateIniciado) {
        bloquearAtaques(false);
    }

    atualizarBotaoCaptura();
}
}
