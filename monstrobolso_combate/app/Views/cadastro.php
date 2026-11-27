<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#123d62">
    <title>Criar conta | Maremon</title>
    <link rel="stylesheet" href="css/autenticacao.css">
</head>
<body class="auth-page">
    <main class="auth-layout">
        <section class="brand-side" aria-label="Apresentação do Maremon">
            <a class="brand-mark" href="login.php" aria-label="Maremon início">
                <span class="brand-icon">🐚</span>
                <span>MAREMON</span>
            </a>
            <div class="brand-copy">
                <span class="eyebrow">SUA JORNADA COMEÇA AGORA</span>
                <h1>Há um mundo<br><em>para descobrir.</em></h1>
                <p>Crie seu cadastro, prepare seus equipamentos e venha explorar os mistérios do oceano.</p>
                <div class="world-tags"><span>✦ Explore</span><span>〰 Pesque</span><span>✧ Colecione</span></div>
            </div>
            <div class="brand-footer"><span class="bubble">◌</span> Novos mares, novas amizades e descobertas.</div>
        </section>

        <section class="auth-card register-card" aria-labelledby="auth-title">
            <header class="card-heading">
                <div class="heading-icon">🧭</div>
                <div><span class="eyebrow">VENHA PARA A TRIPULAÇÃO</span><h2 id="auth-title">Crie sua conta</h2></div>
            </header>
            <div class="auth-tabs" aria-label="Acesso à conta">
                <a href="login.php">Entrar</a>
                <a class="active" href="cadastro.php" aria-current="page">Criar conta</a>
            </div>
            <p class="card-intro">Escolha seus dados de acesso e comece sua aventura marinha.</p>

            <form id="form_cadastro" class="auth-form">
                <label for="nome">Nome do pescador</label>
                <div class="input-wrap"><span aria-hidden="true">♙</span><input type="text" id="nome" required maxlength="100" autocomplete="name" placeholder="Como podemos chamar você?"></div>

                <label for="usuario">Nome de usuário</label>
                <div class="input-wrap"><span aria-hidden="true">⚓</span><input type="text" id="usuario" required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]{3,30}" autocomplete="username" placeholder="De 3 a 30 letras, números ou _"></div>

                <label for="senha">Senha</label>
                <div class="input-wrap"><span aria-hidden="true">🔒</span><input type="password" id="senha" required minlength="8" autocomplete="new-password" placeholder="Pelo menos 8 caracteres"></div>

                <button class="primary-action" type="submit"><span>Criar minha conta</span><span aria-hidden="true">➜</span></button>
            </form>
            <p id="mensagem" class="form-message" role="status" aria-live="polite"></p>
            <p class="switch-copy">Já faz parte da tripulação? <a href="login.php">Entre na sua conta</a></p>
            <div class="card-note"><span>✧</span> Seus dados são protegidos e sua senha é armazenada com segurança.</div>
        </section>
    </main>
    <script src="js/cadastro.js"></script>

</body>
</html>
