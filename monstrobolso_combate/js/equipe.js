const listaEquipe = document.getElementById('lista_equipe');
const slotsEquipe = document.getElementById('slots_equipe');
const mensagemEquipe = document.getElementById('mensagem_equipe');
let tokenEquipe = null;
let equipeOcupada = false;
async function consultarEquipe(endpoint, opcoes = {}) {
    const r = await fetch('php/' + endpoint, { credentials: 'same-origin', ...opcoes });
    const dados = await r.json();
    if (!r.ok || !dados.sucesso)
        throw new Error(dados.erro || 'Erro ao atualizar equipe.');
    return dados;
}
function booleano(valor) { return valor === true || valor === 't' || valor === '1' || valor === 1; }
async function carregarEquipe() {
    try {
        const dados = await consultarEquipe('minha_equipe.php');
        tokenEquipe = dados.csrf_token;
        const pescados = dados.pescados;
        const titulares = pescados.filter(p => Number(p.equipe_slot) >= 1 && Number(p.equipe_slot) <= 3);
        listaEquipe.replaceChildren();
        slotsEquipe.replaceChildren();
        for (let slot = 1; slot <= 3; slot++) {
            const ocupado = pescados.find(p => Number(p.equipe_slot) === slot);
            const linha = document.createElement('div');
            linha.className = 'slot_equipe';
            linha.textContent = `Posição ${slot}: ${ocupado?.nome || 'Vazia'}`;
            slotsEquipe.appendChild(linha);
        }
        if (!pescados.length)
            listaEquipe.textContent = 'Você ainda não possui Pescados.';
        for (const pescado of pescados) {
            const card = document.createElement('article');
            card.className = 'card_equipe';
            if (booleano(pescado.selecionado))
                card.classList.add('ativo');
            const img = document.createElement('img');
            img.alt = pescado.nome;
            const sprite = (typeof spritesPescados !== 'undefined') ? spritesPescados[pescado.nome] : null;
            if (sprite)
                img.src = sprite;
            else
                img.hidden = true;
            const detalhes = document.createElement('div');
            detalhes.className = 'infos_pescado';
            const titulo = document.createElement('h3');
            titulo.textContent = pescado.nome + (booleano(pescado.selecionado) ? ' · ATIVO' : '');
            const info = document.createElement('p');
            info.textContent = `${pescado.tipo} · Nv. ${pescado.nivel} · Evolução ${pescado.estagio_evolucao} · EXP ${pescado.exp}`;
            const stats = document.createElement('p');
            stats.textContent = `HP ${pescado.hp_atual}/${pescado.hp_max} · ATK ${pescado.atk} · DEF ${pescado.def} · VEL ${pescado.spe} · ${pescado.status}`;
            const botoes = document.createElement('div');
            botoes.className = 'botoes_equipe';
            const selecionar = document.createElement('button');
            selecionar.textContent = 'Tornar ativo';
            selecionar.disabled = booleano(pescado.selecionado) || Number(pescado.hp_atual) <= 0;
            selecionar.onclick = () => enviarEquipe('selecionar_pescado.php', { pescado_id: pescado.id, csrf_token: tokenEquipe });
            const posicao = document.createElement('select');
            posicao.setAttribute('aria-label', 'Posição na equipe');
            [0, 1, 2, 3].forEach(n => {
                const opt = document.createElement('option');
                opt.value = n;
                opt.textContent = n === 0 ? 'Reserva' : `Posição ${n}`;
                posicao.appendChild(opt);
            });
            posicao.value = String(pescado.equipe_slot ?? 0);
            const organizar = document.createElement('button');
            organizar.textContent = 'Organizar';
            organizar.onclick = () => enviarEquipe('organizar_equipe.php', {
                pescado_id: pescado.id, slot: Number(posicao.value), csrf_token: tokenEquipe
            });
            botoes.append(selecionar, posicao, organizar);
            detalhes.append(titulo, info, stats, botoes);
            card.append(img, detalhes);
            listaEquipe.appendChild(card);
        }
        mensagemEquipe.textContent = `Equipe: ${titulares.length}/3 posição(ões) preenchida(s). ` +
            (titulares.length < 2 ? 'Para trocar durante a batalha, coloque pelo menos dois Pescados nas posições 1, 2 ou 3 e inicie um NOVO combate.' : 'Você poderá trocar entre integrantes que estiverem com HP.');
    }
    catch (e) {
        mensagemEquipe.textContent = e.message;
    }
}
async function enviarEquipe(endpoint, dados) {
    if (equipeOcupada)
        return;
    equipeOcupada = true;
    try {
        const r = await consultarEquipe(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(dados) });
        await carregarEquipe();
        mensagemEquipe.textContent = r.mensagem;
    }
    catch (e) {
        mensagemEquipe.textContent = e.message;
    }
    finally {
        equipeOcupada = false;
    }
}
carregarEquipe();
