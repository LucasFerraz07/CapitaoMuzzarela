<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Detalhes da reserva #<?= (int) $reserva['id'] ?> — Painel Administrativo Capitão Muzzarela.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboardAdmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/adminReservas.css">
    <title>Reserva #<?= (int) $reserva['id'] ?> — Painel Administrativo</title>
</head>
<body>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header__marca">
            <img src="<?= BASE_URL ?>/public/images/capitaoLogo-Header.webp" alt="Logo Capitão Muzzarela" class="admin-header__logo">
            <span class="admin-header__titulo">Painel Administrativo</span>
        </div>
        <nav class="admin-header__nav" aria-label="Navegação do administrador">
            <span class="admin-header__usuario">
                👤 <?= htmlspecialchars($_SESSION['admin_nome'] ?? 'Administrador', ENT_QUOTES, 'UTF-8') ?>
            </span>
            <a href="/CapitaoMuzzarela/public/api/?action=admin-logout" class="admin-header__logout">Sair</a>
        </nav>
    </header>

    <main class="admin-main">

        <!-- Breadcrumb -->
        <nav class="admin-breadcrumb" aria-label="Breadcrumb">
            <a href="/CapitaoMuzzarela/public/api/?action=admin-dashboard">Painel</a>
            <span aria-hidden="true">›</span>
            <a href="/CapitaoMuzzarela/public/api/?action=admin-reservas">Reservas</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Reserva #<?= (int) $reserva['id'] ?></span>
        </nav>

        <!-- Detalhe da reserva -->
        <section class="reserva-detalhe" aria-labelledby="tituloDetalhe">

            <header class="reserva-detalhe__header">
                <div>
                    <h1 id="tituloDetalhe">Reserva #<?= (int) $reserva['id'] ?></h1>
                    <span class="reserva-status--<?= htmlspecialchars($reserva['status'], ENT_QUOTES, 'UTF-8') ?> reserva-detalhe__badge">
                        <?= $reserva['status'] === 'ativa' ? '🟢 Ativa' : '⚫ Finalizada' ?>
                    </span>
                </div>

                <a href="/CapitaoMuzzarela/public/api/?action=admin-reservas" class="btn-voltar">
                    ← Voltar para lista
                </a>
            </header>

            <!-- Dados da reserva -->
            <div class="reserva-detalhe__grid">

                <div class="detalhe-grupo">
                    <h2 class="detalhe-grupo__titulo">👤 Dados do Cliente</h2>
                    <dl class="detalhe-lista">
                        <div class="detalhe-item">
                            <dt>Nome completo</dt>
                            <dd><?= htmlspecialchars($reserva['nome_completo'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div class="detalhe-item">
                            <dt>Telefone / WhatsApp</dt>
                            <dd>
                                <a href="https://wa.me/55<?= preg_replace('/\D/', '', $reserva['telefone']) ?>" target="_blank" rel="noopener noreferrer">
                                    <?= htmlspecialchars($reserva['telefone'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="detalhe-grupo">
                    <h2 class="detalhe-grupo__titulo">📅 Dados da Reserva</h2>
                    <dl class="detalhe-lista">
                        <div class="detalhe-item">
                            <dt>Data</dt>
                            <dd><?= htmlspecialchars($reserva['data_reserva'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div class="detalhe-item">
                            <dt>Horário</dt>
                            <dd><?= htmlspecialchars($reserva['horario_reserva'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div class="detalhe-item">
                            <dt>Mesa</dt>
                            <dd>Mesa <?= (int) $reserva['mesa_numero'] ?> (até <?= (int) $reserva['mesa_capacidade'] ?> pessoas)</dd>
                        </div>
                        <div class="detalhe-item">
                            <dt>Quantidade de pessoas</dt>
                            <dd><?= (int) $reserva['qntd_pessoas'] ?> pessoa<?= $reserva['qntd_pessoas'] > 1 ? 's' : '' ?></dd>
                        </div>
                    </dl>
                </div>

                <?php if (!empty($reserva['observacoes'])): ?>
                    <div class="detalhe-grupo detalhe-grupo--full">
                        <h2 class="detalhe-grupo__titulo">📝 Observações</h2>
                        <p class="detalhe-obs">
                            <?= htmlspecialchars($reserva['observacoes'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Ações -->
            <?php if ($reserva['status'] === 'ativa'): ?>
                <div class="reserva-detalhe__acoes">
                    <form method="POST" action="/CapitaoMuzzarela/public/api/?action=admin-reserva-status"
                          onsubmit="return confirm('Tem certeza que deseja finalizar a reserva #<?= (int) $reserva['id'] ?>?')">
                        <input type="hidden" name="id"     value="<?= (int) $reserva['id'] ?>">
                        <input type="hidden" name="status" value="finalizada">
                        <button type="submit" class="btn-finalizar btn-finalizar--grande">
                            ✅ Finalizar Reserva
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </section>

    </main>

    <footer class="admin-footer">
        <p>&copy; <?= date('Y') ?> Capitão Muzzarela — Todos os direitos reservados.</p>
    </footer>

</body>
</html>
