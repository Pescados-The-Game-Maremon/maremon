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
