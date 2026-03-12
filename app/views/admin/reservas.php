<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/public/images/favicon.png">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Gerenciamento de reservas — Painel Administrativo Capitão Muzzarela.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboardAdmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/adminReservas.css">
    <title>Reservas — Painel Administrativo</title>
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
            <span aria-current="page">Reservas</span>
        </nav>

        <!-- Título da página -->
        <section class="admin-page-header" aria-labelledby="tituloPagina">
            <h1 id="tituloPagina">Reservas</h1>
            <p>Gerencie todas as reservas do restaurante.</p>
        </section>

        <!-- Filtros -->
        <section class="admin-filtros" aria-label="Filtros de reserva">
            <form method="GET" action="/CapitaoMuzzarela/public/api/" class="filtros-form">
                <input type="hidden" name="action" value="admin-reservas">

                <div class="filtro-grupo">
                    <label for="filtroData">Data</label>
                    <input
                        type="date"
                        id="filtroData"
                        name="data"
                        value="<?= htmlspecialchars($filtroData ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="filtro-grupo">
                    <label for="filtroStatus">Status</label>
                    <select id="filtroStatus" name="status">
                        <option value="">Todos</option>
                        <option value="ativa"      <?= ($filtroStatus ?? '') === 'ativa'      ? 'selected' : '' ?>>Ativa</option>
                        <option value="finalizada" <?= ($filtroStatus ?? '') === 'finalizada' ? 'selected' : '' ?>>Finalizada</option>
                    </select>
                </div>

                <button type="submit" class="btn-filtrar">Filtrar</button>

                <?php if (!empty($filtroData) || !empty($filtroStatus)): ?>
                    <a href="/CapitaoMuzzarela/public/api/?action=admin-reservas" class="btn-limpar">Limpar filtros</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Listagem de reservas -->
        <section class="admin-reservas-lista" aria-label="Lista de reservas">

            <?php if (empty($reservas)): ?>
                <div class="reservas-vazio">
                    <p>😔 Nenhuma reserva encontrada<?= (!empty($filtroData) || !empty($filtroStatus)) ? ' para os filtros aplicados' : '' ?>.</p>
                </div>
            <?php else: ?>

                <p class="reservas-total">
                    <?= count($reservas) ?> reserva<?= count($reservas) !== 1 ? 's' : '' ?> encontrada<?= count($reservas) !== 1 ? 's' : '' ?>
                </p>

                <div class="reservas-grid">
                    <?php foreach ($reservas as $r): ?>
                        <article class="reserva-card reserva-card--<?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?>">

                            <header class="reserva-card__header">
                                <span class="reserva-card__id">#<?= (int) $r['id'] ?></span>
                                <span class="reserva-card__status reserva-status--<?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= $r['status'] === 'ativa' ? '🟢 Ativa' : '⚫ Finalizada' ?>
                                </span>
                            </header>

                            <div class="reserva-card__body">
                                <p class="reserva-card__nome">
                                    👤 <?= htmlspecialchars($r['nome_completo'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p class="reserva-card__info">
                                    📅 <?= htmlspecialchars($r['data_reserva'], ENT_QUOTES, 'UTF-8') ?>
                                    &nbsp;🕐 <?= htmlspecialchars($r['horario_reserva'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p class="reserva-card__info">
                                    🪑 Mesa <?= (int) $r['mesa_numero'] ?>
                                    &nbsp;👥 <?= (int) $r['qntd_pessoas'] ?> pessoa<?= $r['qntd_pessoas'] > 1 ? 's' : '' ?>
                                </p>
                            </div>

                            <footer class="reserva-card__footer">
                                <a
                                    href="/CapitaoMuzzarela/public/api/?action=admin-reserva-detalhe&id=<?= (int) $r['id'] ?>"
                                    class="btn-detalhe"
                                    aria-label="Ver detalhes da reserva #<?= (int) $r['id'] ?>"
                                >
                                    Ver detalhes
                                </a>

                                <?php if ($r['status'] === 'ativa'): ?>
                                    <form method="POST" action="/CapitaoMuzzarela/public/api/?action=admin-reserva-status"
                                          onsubmit="return confirm('Finalizar a reserva #<?= (int) $r['id'] ?>?')">
                                        <input type="hidden" name="id"     value="<?= (int) $r['id'] ?>">
                                        <input type="hidden" name="status" value="finalizada">
                                        <button type="submit" class="btn-finalizar" aria-label="Finalizar reserva #<?= (int) $r['id'] ?>">
                                            Finalizar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </footer>

                        </article>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        </section>

    </main>

    <footer class="admin-footer">
        <p>&copy; <?= date('Y') ?> Capitão Muzzarela — Todos os direitos reservados.</p>
    </footer>

</body>
</html>
