<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/loginAdmin.css">
    <title>Área Restrita — Capitão Muzzarela</title>
</head>

<body>
    <div class="hero">
        <div class="esquerda"></div>

        <img src="<?= BASE_URL ?>/images/logoCapitaoAdmin.webp" alt="Logo do Capitão Muzzarela">

        <div class="direita">
            <div class="conteudoDireita">
                <h1>Painel do Administrador</h1>

                <?php if (!empty($_GET['erro'])): ?>
                    <div class="alerta-erro">
                        <?= htmlspecialchars($_GET['erro'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/api/?action=admin-login" method="post">
                    <input
                        type="email"
                        name="email"
                        placeholder="Email"
                        required
                        autocomplete="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input
                        type="password"
                        name="password"
                        placeholder="Senha"
                        required
                        minlength="8"
                        autocomplete="current-password">
                    <button type="submit">ENTRAR</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>