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
            const card = elementoHTML("<article></article>");
            card.className = 'card_equipe';
            const imagem = elementoHTML("<img>");
            imagem.alt = p.nome;
            imagem.onerror = () => { imagem.onerror = null; imagem.src = 'assets/pescados/sem_sprite.svg'; };
            imagem.src = caminhoSprite(p);
            const texto = elementoHTML("<div></div>");
            const titulo = elementoHTML("<h3></h3>");
            titulo.textContent = `${p.nome} — ${p.tipo}`;
            const desc = elementoHTML("<p></p>");
            desc.textContent = p.descricao || 'Pescado do Maremon';
            const stats = elementoHTML("<p></p>");
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
