<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Painel administrativo do Capitão Muzzarela — gerencie reservas, mesas, horários e cardápio.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardAdmin.css">
    <title>Painel Administrativo — Capitão Muzzarela</title>
</head>
<body>

    <!-- ╔══════════════════════════════════════════════════════╗ -->
    <!-- ║                    HEADER ADMIN                      ║ -->
    <!-- ╚══════════════════════════════════════════════════════╝ -->
    <header class="admin-header">
        <div class="admin-header__marca">
            <img
                src="<?= BASE_URL ?>/images/capitaoLogo-Header.webp"
                alt="Logo Capitão Muzzarela"
                class="admin-header__logo"
            >
            <span class="admin-header__titulo">Painel Administrativo</span>
        </div>

        <nav class="admin-header__nav" aria-label="Navegação do administrador">
            <span class="admin-header__usuario">
                👤 <?= htmlspecialchars($_SESSION['admin_nome'] ?? 'Administrador', ENT_QUOTES, 'UTF-8') ?>
            </span>
            <a href="<?= BASE_URL ?>/api/?action=admin-logout" class="admin-header__logout">
                Sair
            </a>
        </nav>
    </header>

    <!-- ╔══════════════════════════════════════════════════════╗ -->
    <!-- ║                   CONTEÚDO PRINCIPAL                 ║ -->
    <!-- ╚══════════════════════════════════════════════════════╝ -->
    <main class="admin-main">

        <section class="admin-boas-vindas" aria-labelledby="tituloBoasVindas">
            <h1 id="tituloBoasVindas">Olá, <?= htmlspecialchars($_SESSION['admin_nome'] ?? 'Administrador', ENT_QUOTES, 'UTF-8') ?>! 👋</h1>
            <p>O que você deseja gerenciar hoje?</p>
        </section>

        <!-- Grade de cards de navegação -->
        <nav class="admin-cards" aria-label="Módulos do sistema">

            <!-- Card: Reservas -->
            <a href="<?= BASE_URL ?>/../api/?action=admin-reservas" class="admin-card" aria-label="Gerenciar reservas">
                <div class="admin-card__icone" aria-hidden="true">📅</div>
                <div class="admin-card__conteudo">
                    <h2 class="admin-card__titulo">Reservas</h2>
                    <p class="admin-card__descricao">Visualize, gerencie e finalize as reservas de mesas do restaurante.</p>
                </div>
                <span class="admin-card__seta" aria-hidden="true">→</span>
            </a>

            <!-- Card: Horário de Funcionamento -->
            <a href="<?= BASE_URL ?>/../api/?action=admin-horarios" class="admin-card" aria-label="Gerenciar horários de funcionamento">
                <div class="admin-card__icone" aria-hidden="true">🕐</div>
                <div class="admin-card__conteudo">
                    <h2 class="admin-card__titulo">Horário de Funcionamento</h2>
                    <p class="admin-card__descricao">Configure os dias e horários de abertura e fechamento do estabelecimento.</p>
                </div>
                <span class="admin-card__seta" aria-hidden="true">→</span>
            </a>

            <!-- Card: Mesas -->
            <a href="<?= BASE_URL ?>/../api/?action=admin-mesas" class="admin-card" aria-label="Gerenciar mesas">
                <div class="admin-card__icone" aria-hidden="true">🪑</div>
                <div class="admin-card__conteudo">
                    <h2 class="admin-card__titulo">Mesas</h2>
                    <p class="admin-card__descricao">Gerencie as mesas disponíveis e suas capacidades.</p>
                </div>
                <span class="admin-card__seta" aria-hidden="true">→</span>
            </a>

            <!-- Card: Cardápio -->
            <a href="<?= BASE_URL ?>/../api/?action=admin-cardapio" class="admin-card" aria-label="Gerenciar cardápio">
                <div class="admin-card__icone" aria-hidden="true">🍕</div>
                <div class="admin-card__conteudo">
                    <h2 class="admin-card__titulo">Cardápio</h2>
                    <p class="admin-card__descricao">Adicione, edite e remova produtos e categorias do cardápio.</p>
                </div>
                <span class="admin-card__seta" aria-hidden="true">→</span>
            </a>

        </nav>

    </main>

    <!-- ╔══════════════════════════════════════════════════════╗ -->
    <!-- ║                      FOOTER ADMIN                    ║ -->
    <!-- ╚══════════════════════════════════════════════════════╝ -->
    <footer class="admin-footer">
        <p>&copy; <?= date('Y') ?> Capitão Muzzarela — Todos os direitos reservados.</p>
    </footer>

</body>
</html>
