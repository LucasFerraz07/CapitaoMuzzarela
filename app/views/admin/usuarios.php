<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/public/images/favicon.png">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboardAdmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/adminUsuarios.css">
    <title>Usuários — Painel Administrativo</title>
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
            <span aria-current="page">Usuários</span>
        </nav>

        <!-- Cabeçalho da página -->
        <section class="admin-page-header">
            <div>
                <h1>👥 Usuários</h1>
                <p>Gerencie os administradores com acesso ao painel.</p>
            </div>
            <button class="btn-novo" onclick="abrirModalNovo()" aria-label="Novo usuário">
                + Novo Usuário
            </button>
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
        <section class="usuarios-secao">
            <?php if (empty($usuarios)): ?>
                <p class="lista-vazia">Nenhum usuário cadastrado ainda.</p>
            <?php else: ?>
                <div class="usuarios-grid">
                    <?php foreach ($usuarios as $u): ?>
                        <?php
                            $ehLogado  = (int) $u['id'] === (int) ($_SESSION['admin_id'] ?? 0);
                            $ativo     = (bool) $u['ativo'];
                            $criadoEm  = date('d/m/Y', strtotime($u['criado_em']));
                        ?>
                        <article class="usuario-card <?= $ativo ? '' : 'usuario-card--inativo' ?>">

                            <div class="usuario-card__avatar" aria-hidden="true">
                                <?= mb_strtoupper(mb_substr($u['nome'], 0, 1, 'UTF-8'), 'UTF-8') ?>
                            </div>

                            <div class="usuario-card__info">
                                <strong class="usuario-card__nome">
                                    <?= htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($ehLogado): ?>
                                        <span class="badge-voce">Você</span>
                                    <?php endif; ?>
                                </strong>
                                <span class="usuario-card__email">
                                    <?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="usuario-card__criado">
                                    Criado em <?= $criadoEm ?>
                                </span>
                                <span class="usuario-card__status <?= $ativo ? 'status--ativo' : 'status--inativo' ?>">
                                    <?= $ativo ? '🟢 Ativo' : '🔴 Inativo' ?>
                                </span>
                            </div>

                            <footer class="usuario-card__acoes">

                                <button
                                    class="btn-acao btn-acao--editar"
                                    onclick="abrirModalEditar(
                                        <?= (int) $u['id'] ?>,
                                        '<?= htmlspecialchars($u['nome'],  ENT_QUOTES, 'UTF-8') ?>',
                                        '<?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?>'
                                    )"
                                >Editar</button>

                                <?php if (!$ehLogado): ?>

                                    <a
                                        href="/CapitaoMuzzarela/public/api/?action=admin-usuario-toggle&id=<?= (int) $u['id'] ?>"
                                        class="btn-acao <?= $ativo ? 'btn-acao--desativar' : 'btn-acao--ativar' ?>"
                                        onclick="return confirm('<?= $ativo ? 'Desativar este usuário?' : 'Ativar este usuário?' ?>')"
                                    ><?= $ativo ? 'Desativar' : 'Ativar' ?></a>

                                    <a
                                        href="/CapitaoMuzzarela/public/api/?action=admin-usuario-redefinir-senha&id=<?= (int) $u['id'] ?>"
                                        class="btn-acao btn-acao--senha"
                                        onclick="return confirm('Enviar e-mail de redefinição de senha para <?= htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8') ?>?')"
                                    >Redefinir Senha</a>

                                    <a
                                        href="/CapitaoMuzzarela/public/api/?action=admin-usuario-excluir&id=<?= (int) $u['id'] ?>"
                                        class="btn-acao btn-acao--excluir"
                                        onclick="return confirm('Excluir permanentemente o usuário <?= htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8') ?>? Esta ação não pode ser desfeita.')"
                                    >Excluir</a>

                                <?php else: ?>
                                    <span class="aviso-proprio">Sua conta — ações restritas</span>
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

    <!-- Modal: Criar / Editar Usuário -->
    <div id="modalUsuario" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalUsuarioTitulo" hidden>
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modalUsuarioTitulo">Novo Usuário</h2>
                <button class="modal-fechar" onclick="fecharModal()" aria-label="Fechar">&times;</button>
            </div>
            <form method="POST" action="/CapitaoMuzzarela/public/api/?action=admin-usuario-salvar" class="modal-form">
                <input type="hidden" name="id" id="usuarioId" value="0">

                <div class="campo-grupo">
                    <label for="usuarioNome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" id="usuarioNome" name="nome" maxlength="100" required placeholder="Nome completo">
                </div>

                <div class="campo-grupo">
                    <label for="usuarioEmail">E-mail <span class="obrigatorio">*</span></label>
                    <input type="email" id="usuarioEmail" name="email" maxlength="150" required placeholder="email@exemplo.com">
                </div>

                <!-- Senha: apenas na criação -->
                <div class="campo-grupo" id="campoSenha">
                    <label for="usuarioSenha">Senha <span class="obrigatorio">*</span></label>
                    <input type="password" id="usuarioSenha" name="senha" minlength="8" placeholder="Mínimo 8 caracteres">
                    <span class="campo-info">A senha poderá ser redefinida pelo usuário via e-mail após o cadastro.</span>
                </div>

                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn-confirmar" id="btnSalvarUsuario">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirModalNovo() {
        document.getElementById('modalUsuarioTitulo').textContent = 'Novo Usuário';
        document.getElementById('usuarioId').value    = '0';
        document.getElementById('usuarioNome').value  = '';
        document.getElementById('usuarioEmail').value = '';
        document.getElementById('usuarioSenha').value = '';

        // Senha obrigatória na criação
        const campoSenha = document.getElementById('campoSenha');
        const inputSenha = document.getElementById('usuarioSenha');
        campoSenha.hidden   = false;
        inputSenha.required = true;

        document.getElementById('modalUsuario').hidden = false;
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('usuarioNome').focus(), 100);
    }

    function abrirModalEditar(id, nome, email) {
        document.getElementById('modalUsuarioTitulo').textContent = 'Editar Usuário';
        document.getElementById('usuarioId').value    = id;
        document.getElementById('usuarioNome').value  = nome;
        document.getElementById('usuarioEmail').value = email;
        document.getElementById('usuarioSenha').value = '';

        // Senha não é editada aqui — usa redefinição por e-mail
        const campoSenha = document.getElementById('campoSenha');
        const inputSenha = document.getElementById('usuarioSenha');
        campoSenha.hidden   = true;
        inputSenha.required = false;

        document.getElementById('modalUsuario').hidden = false;
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('usuarioNome').focus(), 100);
    }

    function fecharModal() {
        document.getElementById('modalUsuario').hidden = true;
        document.body.style.overflow = '';
    }

    // Fecha ao clicar fora
    document.getElementById('modalUsuario').addEventListener('click', (e) => {
        if (e.target === document.getElementById('modalUsuario')) fecharModal();
    });

    // Fecha com ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') fecharModal();
    });
    </script>

</body>
</html>
