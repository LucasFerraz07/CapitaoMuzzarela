<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Horários de funcionamento — Painel Administrativo Capitão Muzzarela.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboardAdmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/adminHorarios.css">
    <title>Horários — Painel Administrativo</title>
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
            <span aria-current="page">Horários de Funcionamento</span>
        </nav>

        <!-- Cabeçalho -->
        <section class="admin-page-header">
            <div>
                <h1>🕐 Horários de Funcionamento</h1>
                <p>Configure os horários de abertura e fechamento de cada dia da semana.</p>
            </div>
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
        <section class="horarios-secao" aria-labelledby="tituloHorarios">

            <div class="horarios-grid">
                <?php foreach ($horarios as $h): ?>
                    <article class="horario-card <?= $h['fechado'] ? 'horario-card--fechado' : 'horario-card--aberto' ?>">

                        <div class="horario-card__dia">
                            <?= htmlspecialchars($h['dia_nome'], ENT_QUOTES, 'UTF-8') ?>
                        </div>

                        <div class="horario-card__info">
                            <?php if ($h['fechado']): ?>
                                <span class="horario-card__status status--fechado">🔴 Fechado</span>
                            <?php else: ?>
                                <span class="horario-card__status status--aberto">🟢 Aberto</span>
                                <span class="horario-card__horario">
                                    <?= substr($h['hora_abertura'], 0, 5) ?> → <?= substr($h['hora_fechamento'], 0, 5) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <footer class="horario-card__acoes">
                            <button
                                class="btn-acao btn-acao--editar"
                                onclick="abrirModal(
                                    <?= (int) $h['id'] ?>,
                                    '<?= htmlspecialchars($h['dia_nome'], ENT_QUOTES, 'UTF-8') ?>',
                                    <?= $h['fechado'] ? 'true' : 'false' ?>,
                                    '<?= $h['hora_abertura']   ? substr($h['hora_abertura'],   0, 5) : '' ?>',
                                    '<?= $h['hora_fechamento'] ? substr($h['hora_fechamento'], 0, 5) : '' ?>'
                                )"
                                aria-label="Editar horário de <?= htmlspecialchars($h['dia_nome'], ENT_QUOTES, 'UTF-8') ?>"
                            >Editar</button>
                        </footer>

                    </article>
                <?php endforeach; ?>
            </div>

        </section>

    </main>

    <footer class="admin-footer">
        <p>&copy; <?= date('Y') ?> Capitão Muzzarela — Todos os direitos reservados.</p>
    </footer>

    <!-- Modal: Editar Horário -->
    <div id="modalHorario" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalHorarioTitulo" hidden>
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modalHorarioTitulo">Editar Horário</h2>
                <button class="modal-fechar" onclick="fecharModal()" aria-label="Fechar">&times;</button>
            </div>
            <form method="POST" action="/CapitaoMuzzarela/public/api/?action=admin-horario-salvar" class="modal-form">
                <input type="hidden" name="id" id="horarioId">

                <p class="modal-dia" id="modalDiaNome"></p>

                <!-- Checkbox: Fechado -->
                <div class="campo-grupo campo-grupo--checkbox">
                    <label class="checkbox-label">
                        <input type="checkbox" id="horarioFechado" name="fechado" value="1" onchange="toggleHorarios(this)">
                        Fechado neste dia
                    </label>
                </div>

                <!-- Horários (desabilitados quando fechado) -->
                <div class="campo-linha" id="camposHorario">
                    <div class="campo-grupo">
                        <label for="horaAbertura">Abertura <span class="obrigatorio">*</span></label>
                        <input
                            type="time"
                            id="horaAbertura"
                            name="hora_abertura"
                            min="00:00"
                            max="23:59"
                        >
                    </div>

                    <div class="campo-grupo">
                        <label for="horaFechamento">Fechamento <span class="obrigatorio">*</span></label>
                        <input
                            type="time"
                            id="horaFechamento"
                            name="hora_fechamento"
                            min="00:00"
                            max="23:59"
                        >
                    </div>
                </div>

                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn-confirmar">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirModal(id, diaNome, fechado, horaAbertura, horaFechamento) {
        document.getElementById('horarioId').value        = id;
        document.getElementById('modalDiaNome').textContent = diaNome;
        document.getElementById('horarioFechado').checked = fechado;
        document.getElementById('horaAbertura').value     = horaAbertura;
        document.getElementById('horaFechamento').value   = horaFechamento;

        toggleHorarios(document.getElementById('horarioFechado'));

        document.getElementById('modalHorario').hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function fecharModal() {
        document.getElementById('modalHorario').hidden = true;
        document.body.style.overflow = '';
    }

    function toggleHorarios(checkbox) {
        const campos    = document.getElementById('camposHorario');
        const abertura  = document.getElementById('horaAbertura');
        const fechamento = document.getElementById('horaFechamento');
        const fechado   = checkbox.checked;

        campos.classList.toggle('campos--desabilitados', fechado);
        abertura.disabled  = fechado;
        fechamento.disabled = fechado;
        abertura.required  = !fechado;
        fechamento.required = !fechado;
    }

    // Fecha ao clicar fora
    document.getElementById('modalHorario').addEventListener('click', (e) => {
        if (e.target === document.getElementById('modalHorario')) fecharModal();
    });

    // Fecha com ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') fecharModal();
    });
    </script>

</body>
</html>
