(async () => {
    const aviso = document.getElementById('aviso_historico');
    try {
        const r = await fetch('php/historico_batalhas.php', { credentials: 'same-origin' });
        const dados = await r.json();
        if (!r.ok || !dados.sucesso)
            throw new Error(dados.erro || 'Falha.');
        aviso.textContent = dados.batalhas.length + ' batalhas registradas.';
        const lista = document.getElementById('lista_equipe');
        for (const b of dados.batalhas) {
            const card = elementoHTML("<article></article>");
            card.className = 'card_equipe';
            const texto = elementoHTML("<div></div>");
            const titulo = elementoHTML("<h3></h3>");
            titulo.textContent = `#${b.id} · ${b.resultado}`;
            const desc = elementoHTML("<p></p>");
            desc.textContent = `${b.pescado_jogador} vs ${b.pescado_inimigo} · ${b.turnos} turnos · ${b.dinheiro_ganho} moedas`;
            const hora = elementoHTML("<p></p>");
            hora.textContent = b.iniciado_em || '';
            texto.append(titulo, desc, hora);
            card.appendChild(texto);
            lista.appendChild(card);
        }
    }
    catch (e) {
        aviso.textContent = e.message;
    }
})();
