const botoes = [...document.querySelectorAll("#iniciais [data-pescado]")];
const mensagem = document.getElementById("mensagem");
const confirmar = document.getElementById("confirmar-inicial");

let pescadoSelecionado = null;
let enviando = false;

function selecionar(botao) {
    if (enviando) return;

    pescadoSelecionado = botao.dataset.pescado;
    botoes.forEach(item => {
        const selecionado = item === botao;
        item.classList.toggle("selected", selecionado);
        item.setAttribute("aria-pressed", String(selecionado));
    });

    confirmar.disabled = false;
    mensagem.classList.remove("error");
    mensagem.textContent = `${pescadoSelecionado} foi selecionado! Confirme para começar sua aventura.`;
}

botoes.forEach(botao => {
    botao.onclick = () => selecionar(botao);
});

async function confirmarEscolha() {
    if (enviando || !pescadoSelecionado) return;

    enviando = true;
    confirmar.disabled = true;
    botoes.forEach(botao => botao.disabled = true);
    mensagem.classList.remove("error");
    mensagem.textContent = "Salvando seu companheiro...";

    try {
        const resposta = await fetch("php/selecionar_inicial.php", {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                pescado: pescadoSelecionado,
                csrf_token: TOKEN_CSRF
            })
        });

        const dados = await resposta.json();
        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.erro || "Erro ao escolher Pescado.");
        }

        mensagem.textContent = dados.mensagem;
        window.location.href = "index.php";
    } catch (erro) {
        mensagem.classList.add("error");
        mensagem.textContent = erro.message;
        enviando = false;
        confirmar.disabled = !pescadoSelecionado;
        botoes.forEach(botao => botao.disabled = false);
    }
}

confirmar.onclick = confirmarEscolha;

document.onkeydown = evento => {
    if (evento.key === "Enter" && pescadoSelecionado && !enviando) {
        evento.preventDefault();
        confirmarEscolha();
    }
};
