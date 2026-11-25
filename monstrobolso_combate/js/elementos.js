// Somente marcação constante do próprio projeto. Dados de API entram por textContent.
function elementoHTML(marcacao) {
    return document.createRange().createContextualFragment(marcacao).firstElementChild;
}
