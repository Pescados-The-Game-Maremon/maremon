const API_LOJA = "php/";
let tokenLoja = null;
let compraEmAndamento = false;
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
    mensagemLoja("Carregando produtos...");
    try {
        const resposta = await fetch(API_LOJA + "listar_loja.php", {
            credentials: "same-origin"
        });
        const dados = await resposta.json();
        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.erro || "Erro ao carregar loja.");
        }
        tokenLoja = dados.csrf_token;
        const saldo = document.getElementById("saldo_loja");
        if (!saldo)
            return;
        saldo.textContent = dados.dinheiro;
        mostrarProdutos(dados.itens, dados.dinheiro);
        mensagemLoja("");
    }
    catch (erro) {
        mensagemLoja(erro.message);
        console.error("Erro na loja:", erro);
    }
}
function mostrarProdutos(itens, saldoAtual) {
    const lista = document.getElementById("produtos_loja");
    if (!lista)
        return;
    lista.innerHTML = "";
    itens.forEach(item => {
        const card = elementoHTML("<div></div>");
        card.className = "produto_loja";
        const nome = elementoHTML("<h3></h3>");
        nome.textContent = item.nome;
        const descricao = elementoHTML("<p></p>");
        descricao.textContent = item.descricao || "";
        const preco = elementoHTML("<span></span>");
        preco.className = "preco_loja";
        preco.textContent = `${item.preco} moedas`;
        const controles = elementoHTML("<div></div>");
        controles.className = "controles_loja";
        const quantidade = elementoHTML("<input>");
        quantidade.type = "number";
        quantidade.min = "1";
        quantidade.max = "99";
        quantidade.step = "1";
        quantidade.value = "1";
        quantidade.className = "quantidade_loja";
        quantidade.setAttribute("aria-label", `Quantidade de ${item.nome}`);
        const botao = elementoHTML("<button></button>");
        botao.className = "comprar_loja";
        botao.textContent = "Comprar";
        // Desabilitar itens que custam mais que o saldo.
        botao.disabled = Number(item.preco) > saldoAtual;
        botao.addEventListener("click", () => {
            comprarItem(item.id, Number(quantidade.value));
        });
        controles.append(quantidade, botao);
        card.append(nome, descricao, preco, controles);
        lista.appendChild(card);
    });
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
    const botoes = document.querySelectorAll("#produtos_loja .comprar_loja");
    botoes.forEach(botao => botao.disabled = true);
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
    }
}
