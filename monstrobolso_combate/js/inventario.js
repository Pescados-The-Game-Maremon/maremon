let itemSelecionado = null;
let pescadoSelecionado = null;
let tokenInventario = null;
let usandoItem = false;
let evolucoes = [];
let emCombate = false;
const cardsPescados = new Map();
const painelEvolucao = document.getElementById("painel_evolucao");
const escolherEvolucao = document.getElementById("escolher_evolucao");
const detalheEvolucao = document.getElementById("detalhe_evolucao");
const evolutivo = () => ["evoluir", "dna_boss"].includes(itemSelecionado?.efeito);
const listaItens = document.getElementById("lista_itens");
const efeitosCura = [
    "curar_hp", "curar_total", "reviver", "restaurar_tudo", "curar_status",
    "curar_veneno", "curar_queimadura", "curar_congelamento", "curar_sono", "curar_paralisia"
];
const efeitosEvolucao = ["evoluir", "dna_boss"];
const categorias = [
    { id: "cura", aba: document.getElementById("aba_cura"), painel: document.getElementById("painel_cura"),
        grade: document.getElementById("itens_cura"), contagem: document.getElementById("contagem_cura"),
        resumo: document.getElementById("resumo_cura") },
    { id: "evolucao", aba: document.getElementById("aba_evolucao"), painel: document.getElementById("painel_itens_evolucao"),
        grade: document.getElementById("itens_evolucao"), contagem: document.getElementById("contagem_evolucao"),
        resumo: document.getElementById("resumo_evolucao") }
];
let categoriaAtiva = "cura";
const resumoSelecao = document.getElementById("resumo_selecao");
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
// Classificação pelo efeito do banco, não pelo nome exibido do item.
function categoriaItem(item) {
    if (efeitosEvolucao.includes(item.efeito)) return "evolucao";
    if (efeitosCura.includes(item.efeito)) return "cura";
    // Também acolhe materiais cadastrados nas categorias existentes do jogo.
    const categoria = String(item.categoria || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
    if (categoria === "evolucao") return "evolucao";
    if (categoria === "cura" || categoria === "status") return "cura";
    return null;
}
function nomeExibicao(nome) {
    return window.nomeMaremon ? window.nomeMaremon(nome) : String(nome || "").replace(/_/g, " ");
}
function desmarcarCards(seletor) {
    document.querySelectorAll(seletor).forEach(card => {
        card.classList.remove("selecionado");
        card.setAttribute("aria-pressed", "false");
    });
}
function selecionarCategoria(id) {
    if (usandoItem || categoriaAtiva === id) return;
    categoriaAtiva = id;
    itemSelecionado = null;
    desmarcarCards(".item_inventario");
    categorias.forEach(categoria => {
        const ativa = categoria.id === id;
        categoria.aba.setAttribute("aria-selected", String(ativa));
        categoria.aba.tabIndex = ativa ? 0 : -1;
        categoria.painel.hidden = !ativa;
    });
    mensagem.textContent = "";
    atualizarBotaoUsar();
}
categorias.forEach((categoria, indice) => {
    categoria.aba.addEventListener("click", () => selecionarCategoria(categoria.id));
    categoria.aba.addEventListener("keydown", evento => {
        let destino;
        if (evento.key === "ArrowLeft" || evento.key === "ArrowRight") destino = categorias[1 - indice];
        else if (evento.key === "Home") destino = categorias[0];
        else if (evento.key === "End") destino = categorias[categorias.length - 1];
        else return;
        evento.preventDefault();
        if (!usandoItem) {
            selecionarCategoria(destino.id);
            destino.aba.focus();
        }
    });
});
function mostrarItens(itens) {
    categorias.forEach(categoria => {
        const filtrados = itens.filter(item => categoriaItem(item) === categoria.id);
        const unidades = filtrados.reduce((total, item) => total + Number(item.quantidade), 0);
        categoria.grade.replaceChildren();
        categoria.contagem.textContent = String(filtrados.length);
        categoria.resumo.textContent = `${filtrados.length} ${filtrados.length === 1 ? "tipo" : "tipos"} · ${unidades} ${unidades === 1 ? "unidade" : "unidades"}`;
        if (!filtrados.length) {
            const vazio = elementoHTML('<p class="inventario_vazio"></p>');
            vazio.textContent = categoria.id === "cura"
                ? "Você não possui itens de cura. Visite a loja!"
                : "Você não possui pedras evolutivas ou DNAs.";
            categoria.grade.appendChild(vazio);
        }
        filtrados.forEach(item => {
            const card = elementoHTML('<button type="button" class="item_inventario" aria-pressed="false"><span class="item_placeholder" aria-hidden="true"><img class="item_imagem" alt=""></span><span class="item_texto"><span class="item_linha_nome"><strong class="item_nome"></strong><span class="item_quantidade"></span></span><span class="item_descricao"></span></span></button>');
            card.querySelector(".item_nome").textContent = nomeExibicao(item.nome);
            const imagemItem = card.querySelector(".item_imagem");
            const caminhoImagem = window.imagemItemMaremon ? window.imagemItemMaremon(item.nome) : "";
            if (imagemItem && caminhoImagem) {
                imagemItem.src = caminhoImagem;
                imagemItem.alt = nomeExibicao(item.nome);
                imagemItem.addEventListener("error", () => { imagemItem.removeAttribute("src"); imagemItem.alt = ""; }, { once: true });
            }
            card.querySelector(".item_quantidade").textContent = `×${item.quantidade}`;
            card.querySelector(".item_descricao").textContent = item.descricao || "Sem descrição cadastrada.";
            card.addEventListener("click", () => {
                if (usandoItem) return;
                desmarcarCards(".item_inventario");
                card.classList.add("selecionado");
                card.setAttribute("aria-pressed", "true");
                itemSelecionado = item;
                mensagem.textContent = "";
                atualizarBotaoUsar();
            });
            categoria.grade.appendChild(card);
        });
    });
    listaItens.setAttribute("aria-busy", "false");
}
function mostrarPescados(pescados) {
    cardsPescados.clear();
    listaPescados.innerHTML = "";
    if (pescados.length === 0) {
        const vazio = elementoHTML('<p class="inventario_vazio">Você ainda não possui Pescados.</p>');
        listaPescados.appendChild(vazio);
        return;
    }
    pescados.forEach(pescado => {
        const card = elementoHTML('<button type="button" class="pescado_inventario" aria-pressed="false"><strong class="pescado_nome"></strong><span class="pescado_detalhe"></span></button>');
        card.querySelector(".pescado_nome").textContent = `${nomeExibicao(pescado.nome)} · Nv. ${pescado.nivel}`;
        card.querySelector(".pescado_detalhe").textContent =
            `HP ${pescado.hp_atual}/${pescado.hp_max} · Status: ${pescado.status || "normal"} · Evolução: ${pescado.estagio_evolucao ?? 0}`;
        card.addEventListener("click", () => {
            if (usandoItem || !podeReceberItem(pescado)) return;
            desmarcarCards(".pescado_inventario");
            card.classList.add("selecionado");
            card.setAttribute("aria-pressed", "true");
            pescadoSelecionado = pescado;
            atualizarBotaoUsar();
        });
        cardsPescados.set(card, pescado);
        listaPescados.appendChild(card);
    });
}
// Reproduz os critérios de ItemService e EvolucaoService para orientar a seleção.
// O servidor continua validando cada uso antes de consumir o item.
function rotasValidas(pescado, item = itemSelecionado) {
    if (!pescado || !item) return [];
    return evolucoes.filter(e => Number(e.pescado_origem_id) === Number(pescado.id)
        && Number(e.item_necessario_id) === Number(item.id)
        && Number(e.ja_possui) !== 1
        && Number(e.quantidade_item) >= 1
        && Number(item.quantidade) >= Number(e.quantidade_item));
}
function podeReceberItem(pescado, item = itemSelecionado) {
    if (!pescado) return false;
    if (!item) return true;
    if (emCombate || Number(item.quantidade) < 1) return false;
    if (efeitosEvolucao.includes(item.efeito)) return rotasValidas(pescado, item).length > 0;
    const hpMax = Math.max(1, Number(pescado.hp_max));
    const hp = Math.max(0, Math.min(hpMax, Number(pescado.hp_atual ?? hpMax)));
    const status = pescado.status ?? "normal";
    const condicoes = {curar_veneno: "envenenado", curar_queimadura: "queimado",
        curar_congelamento: "congelado", curar_sono: "dormindo", curar_paralisia: "paralisado"};
    switch (item.efeito) {
        case "curar_hp":
        case "curar_total": return hp > 0 && hp < hpMax;
        case "reviver": return hp === 0;
        case "restaurar_tudo": return hp > 0 && (hp < hpMax || status !== "normal");
        case "curar_status": return status !== "normal";
        default: return Object.hasOwn(condicoes, item.efeito) && status === condicoes[item.efeito];
    }
}
function atualizarAlvos() {
    if (pescadoSelecionado && !podeReceberItem(pescadoSelecionado)) pescadoSelecionado = null;
    let disponiveis = 0;
    for (const [card, pescado] of cardsPescados) {
        const compativel = podeReceberItem(pescado);
        const selecionado = compativel && pescadoSelecionado === pescado;
        card.disabled = usandoItem || !compativel;
        card.classList.toggle("incompativel", !compativel);
        card.classList.toggle("selecionado", selecionado);
        card.setAttribute("aria-pressed", String(selecionado));
        card.title = compativel ? "" : "Este Pescado não pode receber o item selecionado.";
        if (compativel) disponiveis++;
    }
    return disponiveis;
}
function rotaSelecionada() {
    return rotasValidas(pescadoSelecionado).find(e => String(e.id) === escolherEvolucao.value);
}
function atualizarBotaoUsar() {
    const disponiveis = atualizarAlvos();
    resumoSelecao.textContent = !itemSelecionado ? "Selecione um item da mochila."
        : emCombate ? "Finalize o combate para usar itens pela mochila."
        : !disponiveis && cardsPescados.size ? "Nenhum Pescado pode receber este item no momento."
        : !pescadoSelecionado ? `${nomeExibicao(itemSelecionado.nome)} selecionado. Escolha um Pescado compatível.`
        : `${nomeExibicao(itemSelecionado.nome)} → ${nomeExibicao(pescadoSelecionado.nome)}`;
    const ehEvolucao = evolutivo();
    painelEvolucao.hidden = !ehEvolucao;
    botaoUsar.textContent = ehEvolucao ? "Evoluir Pescado" : "✦ Usar item";
    if (ehEvolucao) {
        const anterior = escolherEvolucao.value;
        const rotas = rotasValidas(pescadoSelecionado);
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
            || emCombate || !podeReceberItem(pescadoSelecionado);
    }
}
escolherEvolucao.addEventListener("change", atualizarBotaoUsar);
async function usarItemSelecionado() {
    if (usandoItem || botaoUsar.disabled ||
        !itemSelecionado ||
        !pescadoSelecionado) {
        return;
    }
    const rota = evolutivo() ? rotaSelecionada() : null;
    if (evolutivo() && (!rota || botaoUsar.disabled)) return;
    if (rota && !window.confirm(`Evoluir ${pescadoSelecionado.nome} para ${rota.destino}? O item será consumido.`)) return;
    usandoItem = true;
    atualizarBotaoUsar();
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
    listaItens.setAttribute("aria-busy", "false");
    categorias.forEach(categoria => {
        categoria.resumo.textContent = "Indisponível";
        categoria.grade.replaceChildren();
        const aviso = elementoHTML('<p class="inventario_vazio">Não foi possível carregar os itens. Recarregue a página.</p>');
        categoria.grade.appendChild(aviso);
    });
    mensagem.textContent = erro.message;
});
