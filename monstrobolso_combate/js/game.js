function trocarfundo(fundo) {
    const tela = document.getElementById("tela_jogo");
    tela.className = "";
    tela.classList.add(fundo);
}

// Define o fundo inicial ao carregar o jogo
document.addEventListener("DOMContentLoaded", function() {
    trocarfundo("casa_inicial");
    goTo("cena_casa");
});

function goTo(cena) {
    document.getElementById("tela_jogo").innerHTML = document.getElementById(cena).innerHTML;
}



async function sairDaConta() {
    try {
        const resposta = await fetch("php/logout.php", {
            method: "POST",
            credentials: "same-origin"
        });

        const dados = await resposta.json();

        if (dados.sucesso) {
            window.location.href = "login.html";
        } else {
            alert("Não foi possível sair da conta.");
        }
    } catch (erro) {
        console.error("Erro ao sair:", erro);
    }
}