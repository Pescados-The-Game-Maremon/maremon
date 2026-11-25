(async () => {
    const aviso = document.querySelector('#aviso_historico');
    const lista = document.querySelector('#lista_equipe');
    const modelo = document.querySelector('#modelo_batalha');
    const resultados = {
        ativo: 'Em andamento', vitoria: 'Vitória', derrota: 'Derrota',
        fuga: 'Fuga', captura: 'Captura', interrompido: 'Interrompida'
    };
    const mostrarVazio = mensagem => {
        const texto = document.querySelector('#modelo_vazio').content.cloneNode(true);
        texto.firstElementChild.textContent = mensagem;
        lista.replaceChildren(texto);
    };
    try {
        const r = await fetch('php/historico_batalhas.php', { credentials: 'same-origin' });
        const dados = await r.json();
        if (!r.ok || !dados.sucesso || !Array.isArray(dados.batalhas)) {
            throw new Error(dados.erro || 'Falha ao consultar histórico.');
        }
        const cards = dados.batalhas.map(b => {
            const card = modelo.content.firstElementChild.cloneNode(true);
            const resultado = String(b.resultado ?? '').toLowerCase();
            card.querySelector('.batalha_numero').textContent = `Batalha #${b.id}`;
            const rotulo = card.querySelector('.batalha_resultado');
            rotulo.textContent = Object.hasOwn(resultados, resultado) ? resultados[resultado] : (b.resultado || '—');
            rotulo.dataset.resultado = resultado;
            card.querySelector('.batalha_jogador').textContent = b.pescado_jogador ?? '—';
            card.querySelector('.batalha_inimigo').textContent = b.pescado_inimigo ?? '—';
            card.querySelector('.batalha_turnos').textContent = b.turnos ?? '—';
            card.querySelector('.batalha_moedas').textContent = b.dinheiro_ganho ?? '—';
            card.querySelector('.batalha_hora').textContent = b.iniciado_em ? `Início: ${b.iniciado_em}` : '';
            return card;
        });
        aviso.textContent = `${cards.length} batalhas registradas.`;
        if (cards.length) lista.replaceChildren(...cards);
        else mostrarVazio('Nenhuma batalha registrada ainda.');
    } catch (erro) {
        aviso.textContent = 'Histórico indisponível.';
        mostrarVazio(erro.message);
    } finally {
        lista.setAttribute('aria-busy', 'false');
    }
})();
