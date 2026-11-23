(async () => {
    const aviso = document.getElementById('aviso_pokedex');
    try {
        // Consumo assíncrono da API JSON do próprio projeto (MVC: PokedexController).
        const r = await fetch('php/catalogo_pescados.php', { credentials: 'same-origin' });
        const dados = await r.json();
        if (!r.ok || !dados.sucesso)
            throw new Error(dados.erro || 'Falha ao consultar API.');
        aviso.textContent = `${dados.pescados.length} espécies cadastradas.`;
        const lista = document.getElementById('lista_equipe');
        for (const p of dados.pescados) {
            const card = document.createElement('article');
            card.className = 'card_equipe';
            const imagem = document.createElement('img');
            imagem.alt = p.nome;
            imagem.src = spritesPescados[p.nome] || 'assets/inicial.png';
            const texto = document.createElement('div');
            const titulo = document.createElement('h3');
            titulo.textContent = `${p.nome} — ${p.tipo}`;
            const desc = document.createElement('p');
            desc.textContent = p.descricao || 'Pescado do Maremon';
            const stats = document.createElement('p');
            stats.textContent = `HP ${p.hp} · ATK ${p.atk} · DEF ${p.def} · VEL ${p.spe}`;
            texto.append(titulo, desc, stats);
            card.append(imagem, texto);
            lista.appendChild(card);
        }
    }
    catch (e) {
        aviso.textContent = e.message;
    }
})();
