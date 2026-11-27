let curandoEquipe = false;

function voltarDoCentroCura() {
    if (curandoEquipe) return;
    trocarfundo("aqua_fora");
    goTo("cena_aqua_fora");
}

async function curarEquipe() {
    if (curandoEquipe || cenaAtual !== "cena_aqua_dentro") return;

    const mensagem = document.getElementById("mensagem_cura");
    const enfermeira = document.getElementById("ponto_enfermeira");
    const voltar = document.getElementById("voltar_centro_cura");
    if (!mensagem || !enfermeira || !voltar) return;

    curandoEquipe = true;
    enfermeira.disabled = true;
    voltar.disabled = true;
    mensagem.dataset.estado = "aguardando";
    mensagem.textContent = "Curando seus Pescados...";

    try {
        const respostaToken = await fetch("php/curar_equipe.php", { credentials: "same-origin" });
        const dadosToken = await respostaToken.json();
        if (!respostaToken.ok || !dadosToken.sucesso) {
            throw new Error(dadosToken.erro || "Erro ao iniciar a cura.");
        }
        const resposta = await fetch("php/curar_equipe.php", {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ csrf_token: dadosToken.csrf_token })
        });
        const dados = await resposta.json();
        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.erro || "Erro ao curar.");
        }
        mensagem.dataset.estado = "sucesso";
        mensagem.textContent = "todos seus maremons foram curados!";
    } catch (erro) {
        mensagem.dataset.estado = "erro";
        mensagem.textContent = erro.message || "Não foi possível curar seus Pescados.";
    } finally {
        curandoEquipe = false;
        enfermeira.disabled = false;
        voltar.disabled = false;
    }
}
