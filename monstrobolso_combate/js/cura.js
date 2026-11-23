let curandoEquipe = false;
async function curarEquipe() {
    if (curandoEquipe)
        return;
    curandoEquipe = true;
    const mensagem = document.getElementById("mensagem_cura");
    const botao = document.querySelector("#tela_jogo .centro_cura button");
    if (botao)
        botao.disabled = true;
    if (mensagem) {
        mensagem.textContent = "Curando seus Pescados...";
    }
    try {
        const respostaToken = await fetch("php/curar_equipe.php", { credentials: "same-origin" });
        const dadosToken = await respostaToken.json();
        if (!respostaToken.ok || !dadosToken.sucesso) {
            throw new Error(dadosToken.erro || "Erro ao iniciar a cura.");
        }
        const resposta = await fetch("php/curar_equipe.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                csrf_token: dadosToken.csrf_token
            })
        });
        const dados = await resposta.json();
        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.erro || "Erro ao curar.");
        }
        if (mensagem) {
            mensagem.textContent = dados.mensagem;
        }

        const painel = document.querySelector("#tela_jogo .centro_cura");

        if (painel) {
            painel.classList.remove("cura_concluida");
            void painel.offsetWidth;
            painel.classList.add("cura_concluida");
        }
    }
    catch (erro) {
        if (mensagem) {
            mensagem.textContent = erro.message;
        }
    }
    finally {
        curandoEquipe = false;
        if (botao)
            botao.disabled = false;
    }
}
