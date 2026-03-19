<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cardápio completo — Capitão Muzzarela">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/cardapio.css">
    <title>Cardápio — Capitão Muzzarela</title>
</head>
<body>

    <!-- Cabeçalho -->
    <header class="cardapio-header">
        <a href="<?= BASE_URL ?>/" class="cardapio-voltar" aria-label="Voltar ao site">← Voltar</a>
        <div class="cardapio-header__centro">
            <img
                src="<?= BASE_URL ?>/public/images/capitaoLogo-Header.webp"
                alt="Logo Capitão Muzzarela"
                class="cardapio-header__logo"
            >
            <div>
                <h1 class="cardapio-header__nome">Capitão Muzzarela</h1>
                <p class="cardapio-header__subtitulo">Cardápio</p>
            </div>
        </div>
        <div class="cardapio-header__espacador" aria-hidden="true"></div>
    </header>

    <!-- Ornamento decorativo -->
    <div class="cardapio-ornamento" aria-hidden="true">
        <span class="ornamento-linha"></span>
        <span class="ornamento-simbolo">✦</span>
        <span class="ornamento-linha"></span>
    </div>

    <!-- Conteúdo principal -->
    <main class="cardapio-main">

        <?php if (empty($produtos)): ?>
            <p class="cardapio-vazio">Nosso cardápio está sendo atualizado. Volte em breve!</p>

        <?php else: ?>

            <?php foreach ($produtos as $categoria => $itens): ?>

                <section class="cardapio-secao" aria-labelledby="cat-<?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="cardapio-secao__cabecalho">
                        <span class="cardapio-secao__linha" aria-hidden="true"></span>
                        <h2
                            class="cardapio-secao__titulo"
                            id="cat-<?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>"
                        ><?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="cardapio-secao__linha" aria-hidden="true"></span>
                    </div>

                    <ul class="cardapio-lista" role="list">
                        <?php foreach ($itens as $produto): ?>
                            <li class="cardapio-item">
                                <div class="cardapio-item__esquerda">
                                    <span class="cardapio-item__nome">
                                        <?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if (!empty($produto['descricao'])): ?>
                                        <span class="cardapio-item__descricao">
                                            <?= htmlspecialchars($produto['descricao'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="cardapio-item__pontilhado" aria-hidden="true"></span>
                                <span class="cardapio-item__preco">
                                    <?= 'R$ ' . number_format((float) $produto['preco'], 2, ',', '.') ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                </section>

            <?php endforeach; ?>

        <?php endif; ?>

    </main>

    <!-- Rodapé -->
    <footer class="cardapio-footer">
        <div class="cardapio-ornamento" aria-hidden="true">
            <span class="ornamento-linha"></span>
            <span class="ornamento-simbolo">✦</span>
            <span class="ornamento-linha"></span>
        </div>
        <p>Av. Jorge Tibiriçá, 1219 — Vila Canevari, Cruzeiro – SP</p>
        <p class="cardapio-footer__aviso">Preços e disponibilidade sujeitos a alteração sem aviso prévio.</p>
    </footer>

</body>
</html>
