<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/public/images/favicon.png">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Gestão de mesas — Painel Administrativo Capitão Muzzarela.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboardAdmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/adminMesas.css">
    <title>Mesas — Painel Administrativo</title>
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
            <a href="<?= BASE_URL ?>/public/api/?action=admin-logout" class="admin-header__logout">Sair</a>
        </nav>
    </header>

    <main class="admin-main">

        <!-- Breadcrumb -->
        <nav class="admin-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= BASE_URL ?>/public/api/?action=admin-dashboard">Painel</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Mesas</span>
        </nav>

        <!-- Cabeçalho -->
        <section class="admin-page-header">
            <div>
                <h1>🪑 Mesas</h1>
                <p>Gerencie as mesas do restaurante e acompanhe a ocupação de hoje.</p>
            </div>
            <button class="btn-primario" onclick="abrirModal()">+ Nova Mesa</button>
        </section>

        <!-- Feedback -->
        <?php if (!empty($erro)): ?>
            <div class="alerta alerta--erro" role="alert">
                ⚠️ <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="alerta alerta--sucesso" role="alert">
                ✅ <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Listagem -->
        <section class="mesas-secao" aria-labelledby="tituloMesas">

            <?php if (empty($mesas)): ?>
                <div class="vazio">Nenhuma mesa cadastrada.</div>
            <?php else: ?>

                <p class="mesas-total">
                    <?= count($mesas) ?> mesa<?= count($mesas) !== 1 ? 's' : '' ?> cadastrada<?= count($mesas) !== 1 ? 's' : '' ?>
                </p>

                <div class="mesas-grid">
                    <?php foreach ($mesas as $m): ?>
                        <article class="mesa-card <?= $m['reservas_hoje'] > 0 ? 'mesa-card--ocupada' : 'mesa-card--livre' ?>">

                            <div class="mesa-card__numero">
                                Mesa <?= (int) $m['numero'] ?>
                            </div>

                            <div class="mesa-card__info">
                                <span class="mesa-card__capacidade">
                                    👥 Até <?= (int) $m['capacidade'] ?> pessoa<?= $m['capacidade'] > 1 ? 's' : '' ?>
                                </span>
                                <span class="mesa-card__status <?= $m['reservas_hoje'] > 0 ? 'status--ocupada' : 'status--livre' ?>">
                                    <?= $m['reservas_hoje'] > 0
                                        ? '🔴 ' . (int) $m['reservas_hoje'] . ' reserva' . ($m['reservas_hoje'] > 1 ? 's' : '') . ' hoje'
                                        : '🟢 Livre hoje'
                                    ?>
                                </span>
                            </div>

                            <footer class="mesa-card__acoes">
                                <button
                                    class="btn-acao btn-acao--editar"
                                    onclick="abrirModal(<?= (int) $m['id'] ?>, <?= (int) $m['numero'] ?>, <?= (int) $m['capacidade'] ?>)"
                                    aria-label="Editar mesa <?= (int) $m['numero'] ?>"
                                >Editar</button>

                                <?php if ($m['reservas_hoje'] == 0): ?>
                                    <a
                                        href="<?= BASE_URL ?>/public/api/?action=admin-mesa-excluir&id=<?= (int) $m['id'] ?>"
                                        class="btn-acao btn-acao--excluir"
                                        onclick="return confirm('Excluir a mesa <?= (int) $m['numero'] ?>?')"
                                        aria-label="Excluir mesa <?= (int) $m['numero'] ?>"
                                    >Excluir</a>
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

    <!-- Modal: Criar / Editar Mesa -->
    <div id="modalMesa" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalMesaTitulo" hidden>
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modalMesaTitulo">Mesa</h2>
                <button class="modal-fechar" onclick="fecharModal()" aria-label="Fechar">&times;</button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/public/api/?action=admin-mesa-salvar" class="modal-form">
                <input type="hidden" name="id" id="mesaId">

                <div class="campo-linha">
                    <div class="campo-grupo">
                        <label for="mesaNumero">Número da Mesa <span class="obrigatorio">*</span></label>
                        <input
                            type="number"
                            id="mesaNumero"
                            name="numero"
                            min="1"
                            max="999"
                            required
                            placeholder="Ex.: 5"
                        >
                    </div>

                    <div class="campo-grupo">
                        <label for="mesaCapacidade">Capacidade <span class="obrigatorio">*</span></label>
                        <input
                            type="number"
                            id="mesaCapacidade"
                            name="capacidade"
                            min="1"
                            max="50"
                            required
                            placeholder="Ex.: 4"
                        >
                    </div>
                </div>

                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn-confirmar">Salvar Mesa</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirModal(id = null, numero = '', capacidade = '') {
        document.getElementById('mesaId').value         = id ?? '';
        document.getElementById('mesaNumero').value     = numero;
        document.getElementById('mesaCapacidade').value = capacidade;
        document.getElementById('modalMesaTitulo').textContent = id ? 'Editar Mesa' : 'Nova Mesa';
        document.getElementById('modalMesa').hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function fecharModal() {
        document.getElementById('modalMesa').hidden = true;
        document.body.style.overflow = '';
    }

    // Fecha ao clicar fora do container
    document.getElementById('modalMesa').addEventListener('click', (e) => {
        if (e.target === document.getElementById('modalMesa')) fecharModal();
    });

    // Fecha com ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') fecharModal();
    });
    </script>

</body>
</html>
