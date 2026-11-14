
const spritesPescados = {
    "Trilobita": "assets/pescados/trilobita.png",
    "Anomalocaris": "assets/pescados/anomalocaris.png",
    "Hallucigenia": "assets/pescados/hallucigenia.png"
};

function atualizarSprite(lado, pescado) {
    const imagem = document.getElementById(
        "sprite_" + lado
    );

    if (!imagem) return;

    const caminho = spritesPescados[pescado.nome];

    if (caminho) {
        imagem.src = caminho;
        imagem.alt = pescado.nome;
        imagem.style.display = "block";
    } else {
        imagem.removeAttribute("src");
        imagem.style.display = "none";
    }
}
