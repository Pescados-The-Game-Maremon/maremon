// As iscas são desbloqueadas no servidor por vitórias do próprio jogador.
let tokenIsca = null;
let carregandoIscas = false;

async function carregarIscas() {
    if (carregandoIscas) return;
    carregandoIscas = true;

    const lista = document.getElementById("lista_iscas");
    const mensagem = document.getElementById("mensagem_iscas");
    if (!lista) {
        carregandoIscas = false;
        return;
    }

    lista.replaceChildren();
    if (mensagem) mensagem.textContent = "Consultando iscas...";

    try {
        const resposta = await fetch("php/listar_iscas.php", {
            credentials: "same-origin",
            cache: "no-store"
        });
        const dados = await resposta.json();
        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.erro || "Não foi possível carregar as iscas.");
        }

        tokenIsca = dados.csrf_token;
        for (const isca of dados.iscas) {
            const botao = document.createElement("button");
            botao.type = "button";
            botao.className = "isca_opcao";
            botao.disabled = !isca.liberado;
            if (isca.ordem === dados.isca_atual) {
                botao.classList.add("isca_selecionada");
            }

            const rotulo = document.createElement("strong");
            rotulo.textContent = `${isca.ordem}. ${isca.isca}`;
            const detalhe = document.createElement("span");
            detalhe.textContent = !isca.liberado
                ? "🔒 Derrote o chefe anterior"
                : isca.derrotado
                    ? `✓ ${isca.nome} derrotado · DNA na loja`
                    : `Chefe: ${isca.nome}`;
            botao.append(rotulo, detalhe);
            botao.addEventListener("click", () => selecionarIsca(isca.ordem));
            lista.appendChild(botao);
        }
        if (mensagem) mensagem.textContent = "A isca selecionada define o chefe raro (5% por encontro).";
    } catch (erro) {
        if (mensagem) mensagem.textContent = erro.message;
    } finally {
        carregandoIscas = false;
    }
}

async function selecionarIsca(ordem) {
    const mensagem = document.getElementById("mensagem_iscas");
    try {
        const resposta = await fetch("php/selecionar_isca.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({ordem, csrf_token: tokenIsca})
        });
        const dados = await resposta.json();
        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.erro || "Não foi possível selecionar.");
        }
        await carregarIscas();
        if (mensagem) mensagem.textContent = dados.mensagem;
    } catch (erro) {
        if (mensagem) mensagem.textContent = erro.message;
    }
}
