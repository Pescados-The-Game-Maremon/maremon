const botoes = document.querySelectorAll("#iniciais button");
const mensagem = document.getElementById("mensagem");
let escolhendo = false;
botoes.forEach(botao => {
    botao.addEventListener("click", async () => {
        if (escolhendo)
            return;
        escolhendo = true;
        const pescado = botao.dataset.pescado;
        botoes.forEach(b => b.disabled = true);
        mensagem.textContent = "Salvando escolha...";
        try {
            const resposta = await fetch("php/selecionar_inicial.php", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    pescado: pescado,
                    csrf_token: TOKEN_CSRF
                })
            });
            const dados = await resposta.json();
            if (!resposta.ok || !dados.sucesso) {
                throw new Error(dados.erro || "Erro ao escolher Pescado.");
            }
            mensagem.textContent = dados.mensagem;
            window.location.href = "index.php";
        }
        catch (erro) {
            mensagem.textContent = erro.message;
            escolhendo = false;
            botoes.forEach(b => b.disabled = false);
        }
    });
});
