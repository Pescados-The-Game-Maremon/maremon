
let itemSelecionado = null;
let pescadoSelecionado = null;
let tokenInventario = null;
let usandoItem = false;

const listaItens = document.getElementById("lista_itens");
const listaPescados = document.getElementById("lista_pescados");
const mensagem = document.getElementById("mensagem_inventario");
const botaoUsar = document.getElementById("botao_usar");

async function carregarInventario() {
    const resposta = await fetch(
        "php/listar_inventario.php",
        { credentials: "same-origin" }
    );

    const dados = await resposta.json();

    if (!resposta.ok || !dados.sucesso) {
        throw new Error(
            dados.erro || "Erro ao carregar inventário."
        );
    }

    tokenInventario = dados.csrf_token;

    // Limpar seleções ao atualizar os dados.
    itemSelecionado = null;
    pescadoSelecionado = null;

    mostrarItens(dados.itens);
    mostrarPescados(dados.pescados);

    botaoUsar.disabled = true;
}

function mostrarItens(itens) {
    listaItens.innerHTML = "";

    if (itens.length === 0) {
        listaItens.textContent =
            "Sua mochila está vazia. Visite a loja!";
        return;
    }

    itens.forEach(item => {
        const card = document.createElement("button");

        card.type = "button";
        card.className = "item_inventario";

        card.textContent =
            `${item.nome} ×${item.quantidade} — ` +
            `${item.descricao || ""}`;

        card.addEventListener("click", () => {
            document.querySelectorAll(".item_inventario")
                .forEach(b => b.classList.remove("selecionado"));

            card.classList.add("selecionado");

            itemSelecionado = item;
            atualizarBotaoUsar();
        });

        listaItens.appendChild(card);
    });
}

function mostrarPescados(pescados) {
    listaPescados.innerHTML = "";

    if (pescados.length === 0) {
        listaPescados.textContent =
            "Você ainda não possui Pescados.";
        return;
    }

    pescados.forEach(pescado => {
        const card = document.createElement("button");

        card.type = "button";
        card.className = "pescado_inventario";

        card.textContent =
            `${pescado.nome} | Nv. ${pescado.nivel} | ` +
            `HP ${pescado.hp_atual}/${pescado.hp_max}`;

        card.addEventListener("click", () => {
            document.querySelectorAll(".pescado_inventario")
                .forEach(b => b.classList.remove("selecionado"));

            card.classList.add("selecionado");

            pescadoSelecionado = pescado;
            atualizarBotaoUsar();
        });

        listaPescados.appendChild(card);
    });
}

function atualizarBotaoUsar() {
    const efeitosDisponiveis = [
        "curar_hp",
        "curar_total",
        "reviver"
    ];

    botaoUsar.disabled =
        usandoItem ||
        !itemSelecionado ||
        !pescadoSelecionado ||
        !efeitosDisponiveis.includes(
            itemSelecionado?.efeito
        );
}

async function usarItemSelecionado() {
    if (
        usandoItem ||
        !itemSelecionado ||
        !pescadoSelecionado
    ) {
        return;
    }

    usandoItem = true;
    botaoUsar.disabled = true;
    mensagem.textContent = "Utilizando item...";

    try {
        const resposta = await fetch(
            "php/usar_item.php",
            {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    item_id: itemSelecionado.id,
                    pescado_id: pescadoSelecionado.id,
                    csrf_token: tokenInventario
                })
            }
        );

        const dados = await resposta.json();

        if (!resposta.ok || !dados.sucesso) {
            throw new Error(
                dados.erro || "Erro ao utilizar item."
            );
        }

        await carregarInventario();

        mensagem.textContent =
            `${dados.mensagem} ` +
            `HP: ${dados.hp_anterior} → ` +
            `${dados.hp_atual}/${dados.hp_max}`;

    } catch (erro) {
        mensagem.textContent = erro.message;

    } finally {
        usandoItem = false;
        atualizarBotaoUsar();
    }
}

botaoUsar.addEventListener(
    "click",
    usarItemSelecionado
);

carregarInventario().catch(erro => {
    mensagem.textContent = erro.message;
});
