<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#082c55">
    <title>Escolha seu inicial - Maremon</title>
    <link rel="stylesheet" href="css/escolher_inicial.css">
</head>
<body class="starter-page">
    <main class="starter-shell">
        <header class="starter-heading">
            <span class="starter-eyebrow">SUA JORNADA COMEÇA AQUI</span>
            <h1>Escolha seu <span>companheiro!</span></h1>
            <p>Todo grande explorador precisa de um parceiro. Escolha seu primeiro Pescado e comece sua aventura pelos mares.</p>
        </header>

        <section class="starter-options" id="iniciais" aria-label="Pescados iniciais">
            <button class="starter-card starter-card--deep" type="button"
                    data-pescado="Anomalocaris" aria-pressed="false">
                <span class="starter-number">001</span>
                <span class="starter-art">
                    <img src="assets/pescados/Anomalocaris.png" alt="Anomalocaris">
                </span>
                <span class="starter-info">
                    <span class="starter-kicker">INICIAL 01</span>
                    <span class="starter-name">Anomalocaris</span>
                    <span class="starter-caption">Um novo parceiro para explorar o oceano.</span>
                    <span class="starter-pick">Selecionar este Pescado</span>
                </span>
                <span class="starter-check" aria-hidden="true">✓</span>
            </button>

            <button class="starter-card starter-card--mid" type="button"
                    data-pescado="Hallucigenia" aria-pressed="false">
                <span class="starter-number">002</span>
                <span class="starter-art">
                    <img src="assets/pescados/Hallucigenia.png" alt="Hallucigenia">
                </span>
                <span class="starter-info">
                    <span class="starter-kicker">INICIAL 02</span>
                    <span class="starter-name">Hallucigenia</span>
                    <span class="starter-caption">Uma companhia para desbravar águas desconhecidas.</span>
                    <span class="starter-pick">Selecionar este Pescado</span>
                </span>
                <span class="starter-check" aria-hidden="true">✓</span>
            </button>

            <button class="starter-card starter-card--light" type="button"
                    data-pescado="Trilobita" aria-pressed="false">
                <span class="starter-number">003</span>
                <span class="starter-art">
                    <img src="assets/pescados/Trilobita.png" alt="Trilobita">
                </span>
                <span class="starter-info">
                    <span class="starter-kicker">INICIAL 03</span>
                    <span class="starter-name">Trilobita</span>
                    <span class="starter-caption">Seu primeiro passo rumo às profundezas.</span>
                    <span class="starter-pick">Selecionar este Pescado</span>
                </span>
                <span class="starter-check" aria-hidden="true">✓</span>
            </button>
        </section>

        <footer class="starter-footer">
            <p id="mensagem" class="starter-message" role="status" aria-live="polite">Escolha um dos três Pescados para continuar.</p>
            <button id="confirmar-inicial" class="starter-confirm" type="button" disabled>
                <span aria-hidden="true">◉</span>
                <span>Confirmar escolha</span>
                <kbd>ENTER</kbd>
            </button>
            <span class="starter-hint">Clique em um cartão para selecionar · ENTER para confirmar</span>
        </footer>
    </main>

    <script>
        const TOKEN_CSRF = <?= json_encode($token) ?>;
    </script>
    <script src="js/escolher_inicial.js"></script>
</body>
</html>
