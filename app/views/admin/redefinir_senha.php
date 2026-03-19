<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/public/images/favicon.png">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/redefinirSenha.css">
    <title>Redefinir Senha — Capitão Muzzarela</title>
</head>
<body class="login-body">

    <div class="login-card">

        <div class="login-logo">
            <img src="<?= BASE_URL ?>/public/images/capitaoLogo-Header.webp" alt="Logo Capitão Muzzarela">
        </div>

        <h1 class="login-titulo">Redefinir Senha</h1>

        <?php if (!empty($_GET['erro'])): ?>
            <div class="login-alerta login-alerta--erro" role="alert">
                ⚠️ <?= htmlspecialchars($_GET['erro'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <!-- Token inválido ou expirado — só exibe mensagem, sem formulário -->
            <div class="login-alerta login-alerta--erro" role="alert">
                ⚠️ <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <p class="login-voltar">
                <a href="<?= BASE_URL ?>/public/api/?action=admin-login">← Voltar para o login</a>
            </p>

        <?php else: ?>
            <!-- Token válido — exibe formulário -->
            <p class="login-subtitulo">
                Olá, <strong><?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?></strong>!
                Escolha uma nova senha para sua conta.
            </p>

            <form method="POST" action="<?= BASE_URL ?>/public/api/?action=admin-processar-redefinicao" class="login-form" novalidate>

                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div class="campo-grupo">
                    <label for="novaSenha">Nova Senha <span class="obrigatorio">*</span></label>
                    <input
                        type="password"
                        id="novaSenha"
                        name="nova_senha"
                        minlength="8"
                        required
                        placeholder="Mínimo 8 caracteres"
                        autofocus
                    >
                </div>

                <div class="campo-grupo">
                    <label for="confirmaSenha">Confirmar Senha <span class="obrigatorio">*</span></label>
                    <input
                        type="password"
                        id="confirmaSenha"
                        name="confirma_senha"
                        minlength="8"
                        required
                        placeholder="Repita a nova senha"
                    >
                    <span class="campo-erro" id="erroConfirma"></span>
                </div>

                <button type="submit" class="login-btn">Redefinir Senha</button>

            </form>

            <p class="login-voltar">
                <a href="<?= BASE_URL ?>/public/api/?action=admin-login">← Voltar para o login</a>
            </p>

            <script>
            document.querySelector('form').addEventListener('submit', function (e) {
                const nova     = document.getElementById('novaSenha').value;
                const confirma = document.getElementById('confirmaSenha').value;
                const erro     = document.getElementById('erroConfirma');

                if (nova !== confirma) {
                    e.preventDefault();
                    erro.textContent = 'As senhas não coincidem.';
                    document.getElementById('confirmaSenha').focus();
                } else {
                    erro.textContent = '';
                }
            });
            </script>

        <?php endif; ?>

    </div>

</body>
</html>
