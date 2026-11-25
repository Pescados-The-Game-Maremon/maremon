const formulario = document.getElementById("form_login");
const mensagem = document.getElementById("mensagem");
formulario.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    const usuario = document.getElementById("usuario").value;
    const senha = document.getElementById("senha").value;
    const botao = formulario.querySelector("button");
    botao.disabled = true;
    mensagem.textContent = "Entrando...";
    try {
        const resposta = await fetch("php/login.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                usuario,
                senha
            })
        });
        const dados = await resposta.json();
        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.erro || "Não foi possível entrar.");
        }
        mensagem.textContent = dados.mensagem;
        // Entrar no jogo após autenticação
        window.location.href = "escolher_inicial.php";
    }
    catch (erro) {
        mensagem.textContent = erro.message;
    }
    finally {
        botao.disabled = false;
    }
});
