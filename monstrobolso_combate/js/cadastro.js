const formulario = document.getElementById("form_cadastro");
const mensagem = document.getElementById("mensagem");
formulario.addEventListener("submit", async function (evento) {
    evento.preventDefault();
    const botao = formulario.querySelector('button[type="submit"]');
    const nome = document.getElementById("nome").value;
    const usuario = document.getElementById("usuario").value;
    const senha = document.getElementById("senha").value;
    botao.disabled = true;
    mensagem.textContent = "Criando conta...";
    try {
        const resposta = await fetch("php/cadastro.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                nome: nome,
                usuario: usuario,
                senha: senha
            })
        });
        const dados = await resposta.json();
        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.erro || "Não foi possível criar a conta.");
        }
        mensagem.textContent = dados.mensagem;
        formulario.reset();
    }
    catch (erro) {
        mensagem.textContent = erro.message;
    }
    finally {
        botao.disabled = false;
    }
});
