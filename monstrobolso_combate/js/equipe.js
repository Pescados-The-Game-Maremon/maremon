
const listaEquipe = document.getElementById("lista_equipe");
const mensagemEquipe = document.getElementById("mensagem_equipe");

let tokenEquipe = null;
let selecionando = false;

async function carregarEquipe() {
    try {
        const resposta = await fetch("php/minha_equipe.php", {
            credentials: "same-origin"
        });

        const dados = await resposta.json();

        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.erro || "Erro ao carregar equipe.");
        }

        tokenEquipe = dados.csrf_token;
        listaEquipe.innerHTML = "";

        if (dados.pescados.length === 0) {
            mensagemEquipe.textContent =
                "Você ainda não possui Pescados.";
            return;
        }

        mensagemEquipe.textContent = "";

        dados.pescados.forEach(pescado => {
            const card = document.createElement("div");

            const titulo = document.createElement("h3");
            titulo.textContent = pescado.nome;

            const detalhes = document.createElement("p");
            detalhes.textContent =
                `Nível ${pescado.nivel} | ` +
                `HP ${pescado.hp_atual}/${pescado.hp_max} | ` +
                `EXP ${pescado.exp ?? 0}`;

            const botao = document.createElement("button");

            const selecionado =
                pescado.selecionado === true ||
                pescado.selecionado === "t" ||
                pescado.selecionado === "1";

            botao.textContent = selecionado
                ? "Selecionado"
                : "Selecionar";

            botao.disabled =
                selecionado || Number(pescado.hp_atual) <= 0;

            botao.addEventListener("click", () => {
                selecionarPescado(pescado.id);
            });

            card.append(titulo, detalhes, botao);

            listaEquipe.appendChild(card);
        });

    } catch (erro) {
        mensagemEquipe.textContent = erro.message;
    }
}

async function selecionarPescado(pescadoId) {
    if (selecionando) return;

    selecionando = true;

    try {
        const resposta = await fetch(
            "php/selecionar_pescado.php",
            {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    pescado_id: pescadoId,
                    csrf_token: tokenEquipe
                })
            }
        );

        const dados = await resposta.json();

        if (!resposta.ok || !dados.sucesso) {
            throw new Error(
                dados.erro || "Erro ao selecionar Pescado."
            );
        }

        await carregarEquipe();

        mensagemEquipe.textContent = dados.mensagem;

    } catch (erro) {
        mensagemEquipe.textContent = erro.message;
    } finally {
        selecionando = false;
    }
}

carregarEquipe();
