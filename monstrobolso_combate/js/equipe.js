const listaEquipe = document.getElementById('lista_equipe');
const slotsEquipe = document.getElementById('slots_equipe');
const mensagemEquipe = document.getElementById('mensagem_equipe');
const filtroEquipe = document.getElementById('filtro_equipe');
const instrucaoEquipe = document.getElementById('instrucao_equipe');
let tokenEquipe = null;
let equipeOcupada = false;
let pescadosEquipe = [];
let destinoEquipe = 1;
let equipeEmBatalha = false;

async function consultarEquipe(endpoint, opcoes = {}) {
    const r = await fetch('php/' + endpoint, { credentials: 'same-origin', cache: 'no-store', ...opcoes });
    const dados = await r.json();
    if (!r.ok || !dados.sucesso) throw new Error(dados.erro || 'Erro ao atualizar equipe.');
    return dados;
}
function slotDo(p) { return Number(p.equipe_slot || 0); }
function vivo(p) { return Number(p.hp_atual) > 0; }
function ocupante(slot) { return pescadosEquipe.find(p => slotDo(p) === slot); }
function preencherImagem(img, p) {
    img.hidden = !p;
    if (!p) return;
    img.alt = p.nome;
    img.onerror = () => { img.onerror = null; img.src = 'assets/pescados/sem_sprite.svg'; };
    img.src = caminhoSprite(p);
}
function preencherHP(card, p) {
    const hp = card.querySelector('progress');
    hp.max = Math.max(1, Number(p.hp_max));
    hp.value = Math.max(0, Number(p.hp_atual));
    card.querySelector('.hp_texto').textContent = `HP ${p.hp_atual}/${p.hp_max}${vivo(p) ? '' : ' · Sem HP'}`;
    card.classList.toggle('sem_hp', !vivo(p));
    // Estados exclusivamente visuais da HUD; não modificam a lógica de HP.
    card.classList.toggle('hp_baixo', vivo(p) && hp.value / hp.max <= 0.3);
}
function escolherDestino(slot) {
    destinoEquipe = slot;
    renderizarEquipe();
}
function renderizarEquipe() {
    const bloqueado = equipeOcupada || equipeEmBatalha;
    const titulares = [1, 2, 3].map(ocupante).filter(Boolean);
    const primeiroVivo = titulares.find(vivo);
    slotsEquipe.replaceChildren();
    for (let slot = 1; slot <= 3; slot++) {
        const p = ocupante(slot);
        const card = document.getElementById('modelo_slot_equipe').content.firstElementChild.cloneNode(true);
        const selecionar = card.querySelector('.selecionar_slot');
        card.classList.toggle('destino', slot === destinoEquipe);
        card.classList.toggle('slot_vazio', !p);
        selecionar.setAttribute('aria-pressed', String(slot === destinoEquipe));
        selecionar.disabled = bloqueado;
        card.querySelector('.numero_slot').textContent = `0${slot} · ${slot === 1 ? 'PRIMEIRA POSIÇÃO' : 'SUPORTE'}`;
        card.querySelector('.nome_slot').textContent = p?.nome || 'Posição vazia';
        card.querySelector('.rotulo_slot').textContent = p ? (p === primeiroVivo ? 'Entra primeiro' : `Nv. ${p.nivel} · ${p.tipo}`) : 'Clique para preencher';
        preencherImagem(card.querySelector('img'), p);
        card.querySelector('.slot_hp').hidden = !p;
        if (p) preencherHP(card, p);
        selecionar.addEventListener('click', () => escolherDestino(slot));
        const remover = card.querySelector('.remover_slot');
        remover.hidden = !p;
        remover.disabled = bloqueado;
        if (p) remover.addEventListener('click', () => moverPescado(p, 0));
        slotsEquipe.appendChild(card);
    }
    instrucaoEquipe.textContent = equipeEmBatalha ? 'Finalize o combate para editar a formação.' : `Posição ${destinoEquipe} selecionada. Escolha um Pescado abaixo ou use os botões 1, 2 e 3 para movê-lo diretamente.`;
    listaEquipe.replaceChildren();
    const termo = filtroEquipe.value.trim().toLowerCase();
    const filtrados = pescadosEquipe.filter(p => `${p.nome} ${p.tipo}`.toLowerCase().includes(termo));
    if (!filtrados.length) {
    listaEquipe.innerHTML = '<p class="equipe_vazio">Nenhum Pescado encontrado.</p>';
}
    for (const p of filtrados) {
        const card = document.getElementById('modelo_pescado_equipe').content.firstElementChild.cloneNode(true);
        const slot = slotDo(p);
        card.classList.toggle('ativo', slot > 0);
        preencherImagem(card.querySelector('img'), p);
        card.querySelector('h3').textContent = p.nome;
        card.querySelector('.info_tipo').textContent = `${p.tipo} · Nv. ${p.nivel} · ${slot ? 'Posição ' + slot : 'Reserva'}`;
        card.querySelector('.info_stats').textContent = `ATK ${p.atk} · DEF ${p.def} · VEL ${p.spe} · EXP ${p.exp}`;
        preencherHP(card, p);
        const escolha = card.querySelector('.atribuir_slot');
        escolha.textContent = slot === destinoEquipe ? 'Nesta posição' : `Colocar na posição ${destinoEquipe}`;
        escolha.disabled = bloqueado || slot === destinoEquipe;
        escolha.addEventListener('click', () => moverPescado(p, destinoEquipe));
        card.querySelectorAll('[data-slot]').forEach(botao => {
            const alvo = Number(botao.dataset.slot);
            botao.disabled = bloqueado || alvo === slot;
            botao.setAttribute('aria-pressed', String(alvo === slot));
            botao.addEventListener('click', () => moverPescado(p, alvo));
        });
        listaEquipe.appendChild(card);
    }
    document.getElementById('resumo_equipe').textContent = `${titulares.length}/3 integrantes · ${titulares.filter(vivo).length} com HP`;
    document.getElementById('aviso_equipe_hp').hidden = Boolean(primeiroVivo);
}
async function carregarEquipe() {
    const dados = await consultarEquipe('minha_equipe.php');
    tokenEquipe = dados.csrf_token;
    pescadosEquipe = dados.pescados;
    equipeEmBatalha = Boolean(dados.em_combate);
    renderizarEquipe();
}
async function moverPescado(p, slot) {
    if (equipeOcupada || equipeEmBatalha || slotDo(p) === slot) return;
    equipeOcupada = true;
    renderizarEquipe();
    try {
        const r = await consultarEquipe('organizar_equipe.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({pescado_id:p.id,slot,csrf_token:tokenEquipe})});
        await carregarEquipe();
        mensagemEquipe.textContent = r.mensagem;
    } catch (e) {
        mensagemEquipe.textContent = e.message;
    } finally {
        equipeOcupada = false;
        renderizarEquipe();
    }
}
filtroEquipe.addEventListener('input', renderizarEquipe);
carregarEquipe().catch(e => mensagemEquipe.textContent = e.message);
