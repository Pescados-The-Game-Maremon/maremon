(async () => {
    const aviso = document.querySelector('#aviso_pokedex');
    const lista = document.querySelector('#lista_equipe');
    const modelo = document.querySelector('#modelo_especie');
    const mostrarVazio = mensagem => {
        const texto = document.querySelector('#modelo_vazio').content.cloneNode(true);
        texto.firstElementChild.textContent = mensagem;
        lista.replaceChildren(texto);
    };
    try {
        const r = await fetch('php/catalogo_pescados.php', { credentials: 'same-origin' });
        const dados = await r.json();
        if (!r.ok || !dados.sucesso || !Array.isArray(dados.pescados)) {
            throw new Error(dados.erro || 'Falha ao consultar API.');
        }
        const cards = dados.pescados.map(p => {
            const card = modelo.content.firstElementChild.cloneNode(true);
            const imagem = card.querySelector('img');
            imagem.alt = p.nome ?? '';
            imagem.addEventListener('error', () => {
                imagem.addEventListener('error', () => {
                    imagem.src = 'assets/pescados/sem_sprite.svg';
                }, { once: true });
                imagem.src = caminhoSprite(p);
            }, { once: true });
            const original = caminhoSprite(p);
            const normalizado = original.replace(/^assets\/(pescados|bosses)\/(.+\.png)$/i, 'assets/sprites_enciclopedia/$1/$2');
            imagem.src = normalizado;
            card.querySelector('.especie_nome').textContent = p.nome ?? 'Pescado';
            const tipo = card.querySelector('.especie_tipo');
            tipo.textContent = p.tipo ?? '—';
            tipo.dataset.tipo = String(p.tipo ?? '').trim().toLowerCase();
            card.querySelector('.especie_descricao').textContent = p.descricao || 'Pescado do Maremon';
            for (const atributo of card.querySelectorAll('[data-stat]')) {
                atributo.textContent = p[atributo.dataset.stat] ?? '—';
            }
            return card;
        });
        aviso.textContent = `${cards.length} espécies cadastradas.`;
        if (cards.length) lista.replaceChildren(...cards);
        else mostrarVazio('Nenhuma espécie cadastrada.');
    } catch (erro) {
        aviso.textContent = 'Catálogo indisponível.';
        mostrarVazio(erro.message);
    } finally {
        lista.setAttribute('aria-busy', 'false');
    }
})();
