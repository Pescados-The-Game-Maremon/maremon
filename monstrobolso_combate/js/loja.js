const API_LOJA = "php/";
let tokenLoja = null;
let compraEmAndamento = false;
let versaoCargaLoja = 0;
let origemLoja = "escolher_destino";
async function abrirLoja() {
    origemLoja = cenaAtual === "cena_aqua_fora" ? "cena_aqua_fora" : "escolher_destino";
    trocarfundo("fundo_loja");
    goTo("cena_loja");
    await carregarLoja();
}
function voltarDaLoja() {
    if (compraEmAndamento)
        return;
    tokenLoja = null;
    versaoCargaLoja++;
    trocarfundo(origemLoja === "cena_aqua_fora" ? "aqua_fora" : "casa_inicial");
    goTo(origemLoja);
}
function mensagemLoja(texto) {
    const elemento = document.getElementById("mensagem_loja");
    if (elemento) {
        elemento.textContent = texto;
    }
}
async function carregarLoja() {
    const versao = ++versaoCargaLoja;
    mensagemLoja("Carregando produtos...");
    try {
        const resposta = await fetch(API_LOJA + "listar_loja.php", {
            credentials: "same-origin"
        });
        const dados = await resposta.json();
        if (versao !== versaoCargaLoja || cenaAtual !== "cena_loja") return;
        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.erro || "Erro ao carregar loja.");
        }
        tokenLoja = dados.csrf_token;
        const saldo = document.getElementById("saldo_loja");
        if (!saldo)
            return;
        saldo.textContent = Number(dados.dinheiro).toLocaleString("pt-BR");
        mostrarProdutos(dados.itens, dados.dinheiro);
        mensagemLoja("");
    }
    catch (erro) {
        if (versao !== versaoCargaLoja || cenaAtual !== "cena_loja") return;
        mensagemLoja(erro.message);
        console.error("Erro na loja:", erro);
    }
}
function mostrarProdutos(itens, saldoAtual) {
    const lista = document.getElementById("produtos_loja");
    const modelo = document.getElementById("modelo_produto_loja");
    if (!lista || !modelo) return;
    const formatar = valor => Number(valor).toLocaleString("pt-BR");
    document.getElementById("resumo_loja").textContent = `${itens.length} ${itens.length === 1 ? "produto" : "produtos"}`;
    const cards = itens.map(item => {
        const card = modelo.content.cloneNode(true);
        const preco = Number(item.preco);
        card.querySelector("h3").textContent = item.nome;
        card.querySelector("p").textContent = item.descricao || "";
        card.querySelector(".preco_loja").textContent = `${formatar(preco)} moedas / un.`;
        const quantidade = card.querySelector(".quantidade_loja");
        quantidade.setAttribute("aria-label", `Quantidade de ${item.nome}`);
        const botao = card.querySelector(".comprar_loja");
        const total = card.querySelector(".loja_total");
        function atualizarTotal() {
            const qtd = quantidade.valueAsNumber;
            const valida = Number.isInteger(qtd) && qtd >= 1 && qtd <= 99;
            const custo = qtd * preco;
            total.textContent = valida ? `Total: ${formatar(custo)}` : "Qtd. inválida";
            total.classList.toggle("saldo_insuficiente", valida && custo > saldoAtual);
            botao.disabled = compraEmAndamento || !valida || custo > saldoAtual;
            botao.title = valida && custo > saldoAtual ? "Saldo insuficiente" : "";
        }
        quantidade.addEventListener("input", atualizarTotal);
        botao.addEventListener("click", () => comprarItem(item.id, quantidade.valueAsNumber));
        // Recalcula ao finalizar uma compra, inclusive após atualizar o saldo.
        botao.addEventListener("atualizar-loja", atualizarTotal);
        atualizarTotal();
        return card;
    });
    lista.replaceChildren(...cards);
    if (!itens.length) lista.textContent = "Nenhum produto disponível no momento.";
}
async function comprarItem(itemId, quantidade) {
    if (compraEmAndamento)
        return;
    if (!Number.isInteger(quantidade) ||
        quantidade < 1 ||
        quantidade > 99) {
        mensagemLoja("Escolha uma quantidade entre 1 e 99.");
        return;
    }
    compraEmAndamento = true;
    document.querySelectorAll("#produtos_loja .comprar_loja, #produtos_loja .quantidade_loja, .loja_voltar").forEach(controle => controle.disabled = true);
    mensagemLoja("Processando compra...");
    try {
        const resposta = await fetch(API_LOJA + "comprar_item.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                item_id: itemId,
                quantidade: quantidade,
                csrf_token: tokenLoja
            })
        });
        const dados = await resposta.json();
        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.erro || "Erro ao comprar item.");
        }
        // Buscar o saldo atualizado do servidor.
        await carregarLoja();
        mensagemLoja(`Você comprou ${quantidade}x ${dados.item}!`);
    }
    catch (erro) {
        mensagemLoja(erro.message);
        // Atualiza saldo e botões após qualquer erro.
        await carregarLoja();
        mensagemLoja(erro.message);
    }
    finally {
        compraEmAndamento = false;
        document.querySelectorAll("#produtos_loja .quantidade_loja, .loja_voltar").forEach(controle => controle.disabled = false);
        document.querySelectorAll("#produtos_loja .comprar_loja").forEach(botao => botao.dispatchEvent(new Event("atualizar-loja")));
    }
}
