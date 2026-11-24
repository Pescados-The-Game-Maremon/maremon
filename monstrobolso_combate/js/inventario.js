let itemSelecionado = null;
let pescadoSelecionado = null;
let tokenInventario = null;
let usandoItem = false;
let evolucoes = [];
let emCombate = false;
const painelEvolucao = document.getElementById("painel_evolucao");
const escolherEvolucao = document.getElementById("escolher_evolucao");
const detalheEvolucao = document.getElementById("detalhe_evolucao");
const evolutivo = () => ["evoluir", "dna_boss"].includes(itemSelecionado?.efeito);
const listaItens = document.getElementById("lista_itens");
const listaPescados = document.getElementById("lista_pescados");
const mensagem = document.getElementById("mensagem_inventario");
const botaoUsar = document.getElementById("botao_usar");
async function carregarInventario() {
    const resposta = await fetch("php/listar_inventario.php", { credentials: "same-origin" });
    const dados = await resposta.json();
    if (!resposta.ok || !dados.sucesso) {
        throw new Error(dados.erro || "Erro ao carregar inventário.");
    }
    tokenInventario = dados.csrf_token;
    evolucoes = dados.evolucoes || [];
    emCombate = Boolean(dados.em_combate);
    // Limpar seleções ao atualizar os dados.
    itemSelecionado = null;
    pescadoSelecionado = null;
    mostrarItens(dados.itens);
    mostrarPescados(dados.pescados);
    atualizarBotaoUsar();
}
function mostrarItens(itens) {
    listaItens.innerHTML = "";
    if (itens.length === 0) {
        listaItens.textContent =
            "Sua mochila está vazia. Visite a loja!";
        return;
    }
    itens.forEach(item => {
        const card = elementoHTML("<button></button>");
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
        const card = elementoHTML("<button></button>");
        card.type = "button";
        card.className = "pescado_inventario";
        card.textContent =
            `${pescado.nome} | Nv. ${pescado.nivel} | ` +
                `HP ${pescado.hp_atual}/${pescado.hp_max} | ` +
                `Status: ${pescado.status || "normal"} | Evolução: ${pescado.estagio_evolucao ?? 0}`;
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
function rotaSelecionada() {
    return evolucoes.find(e => String(e.id) === escolherEvolucao.value
        && Number(e.pescado_origem_id) === Number(pescadoSelecionado?.id)
        && Number(e.item_necessario_id) === Number(itemSelecionado?.id));
}
function atualizarBotaoUsar() {
    const efeitosDisponiveis = [
        "curar_hp", "curar_total", "reviver", "restaurar_tudo", "curar_status",
        "curar_veneno", "curar_queimadura", "curar_congelamento", "curar_sono", "curar_paralisia"
    ];
    const ehEvolucao = evolutivo();
    painelEvolucao.hidden = !ehEvolucao;
    botaoUsar.textContent = ehEvolucao ? "Evoluir Pescado" : "✦ Usar item";
    if (ehEvolucao) {
        const anterior = escolherEvolucao.value;
        const rotas = evolucoes.filter(e => Number(e.pescado_origem_id) === Number(pescadoSelecionado?.id)
            && Number(e.item_necessario_id) === Number(itemSelecionado?.id));
        escolherEvolucao.replaceChildren();
        for (const rota of rotas) {
            const opt = elementoHTML("<option></option>");
            opt.value = rota.id;
            opt.textContent = `${rota.destino} (${rota.tipo})`;
            escolherEvolucao.appendChild(opt);
        }
        if (rotas.some(e => String(e.id) === anterior)) escolherEvolucao.value = anterior;
        const rota = rotaSelecionada();
        escolherEvolucao.disabled = usandoItem || !rota;
        const jaPossui = Number(rota?.ja_possui) === 1;
        const suficiente = rota && Number(itemSelecionado.quantidade) >= Number(rota.quantidade_item);
        detalheEvolucao.textContent = !pescadoSelecionado ? "Selecione um Pescado."
            : !rota ? "Este item não evolui a espécie escolhida."
            : emCombate ? "Finalize o combate para evoluir."
            : jaPossui ? "Você já possui esta espécie."
            : !suficiente ? `Você precisa de ${rota.quantidade_item} unidade(s) deste item.`
            : `${pescadoSelecionado.nome} → ${rota.destino}. Consome ${rota.quantidade_item} × ${itemSelecionado.nome}. Sem requisito de nível.`;
        botaoUsar.disabled = usandoItem || emCombate || !rota || jaPossui || !suficiente;
    } else {
        botaoUsar.disabled = usandoItem || !itemSelecionado || !pescadoSelecionado
            || !efeitosDisponiveis.includes(itemSelecionado?.efeito);
    }
}
escolherEvolucao.addEventListener("change", atualizarBotaoUsar);
async function usarItemSelecionado() {
    if (usandoItem ||
        !itemSelecionado ||
        !pescadoSelecionado) {
        return;
    }
    const rota = evolutivo() ? rotaSelecionada() : null;
    if (evolutivo() && (!rota || botaoUsar.disabled)) return;
    if (rota && !window.confirm(`Evoluir ${pescadoSelecionado.nome} para ${rota.destino}? O item será consumido.`)) return;
    usandoItem = true;
    botaoUsar.disabled = true;
    mensagem.textContent = "Utilizando item...";
    try {
        const resposta = await fetch("php/usar_item.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                item_id: itemSelecionado.id,
                pescado_id: pescadoSelecionado.id,
                evolucao_id: rota?.id ?? null,
                csrf_token: tokenInventario
            })
        });
        const dados = await resposta.json();
        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.erro || "Erro ao utilizar item.");
        }
        await carregarInventario();
        mensagem.textContent =
            `${dados.mensagem} ` +
                `HP: ${dados.hp_anterior} → ${dados.hp_atual}/${dados.hp_max}` +
                ` · Status: ${dados.status_atual ?? "normal"}` +
                (dados.estagio_evolucao !== undefined ? ` · Evolução ${dados.estagio_evolucao}` : "");
    }
    catch (erro) {
        mensagem.textContent = erro.message;
    }
    finally {
        usandoItem = false;
        atualizarBotaoUsar();
    }
}
botaoUsar.addEventListener("click", usarItemSelecionado);
carregarInventario().catch(erro => {
    mensagem.textContent = erro.message;
});
