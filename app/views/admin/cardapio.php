<?php
/**
 * Escapa uma string para uso seguro dentro de atributos JavaScript inline.
 * Substituto compatível com PHP < 8.1 (sem ENT_JS).
 */
function escapeJs(string $valor): string
{
    return str_replace(
        ["\\",   "'",    '"',    "\n",  "\r",  "</"],
        ["\\\\", "\\'", '\\"', "\\n", "\\r", "<\\/"],
        $valor
    );
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/public/images/favicon.png">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Gestão do cardápio — Painel Administrativo Capitão Muzzarela.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboardAdmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/adminCardapio.css">
    <title>Cardápio — Painel Administrativo</title>
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
            <span aria-current="page">Cardápio</span>
        </nav>

        <!-- Cabeçalho -->
        <section class="admin-page-header">
            <div>
                <h1>🍕 Cardápio</h1>
                <p>Gerencie categorias e produtos do restaurante.</p>
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

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- SEÇÃO: CATEGORIAS                                              -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <section class="cardapio-secao" aria-labelledby="tituloCategorias">

            <div class="cardapio-secao__header">
                <h2 id="tituloCategorias">Categorias</h2>
                <button class="btn-primario" onclick="abrirModalCategoria()">
                    + Nova Categoria
                </button>
            </div>

            <?php if (empty($categorias)): ?>
                <p class="vazio">Nenhuma categoria cadastrada.</p>
            <?php else: ?>
                <div class="tabela-wrapper">
                    <table class="tabela" aria-label="Lista de categorias">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Nome</th>
                                <th scope="col">Produtos</th>
                                <th scope="col">Status</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $cat): ?>
                                <tr>
                                    <td><?= (int) $cat['id'] ?></td>
                                    <td><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int) $cat['total_produtos'] ?></td>
                                    <td>
                                        <span class="badge badge--<?= $cat['ativo'] ? 'ativo' : 'inativo' ?>">
                                            <?= $cat['ativo'] ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td class="acoes">
                                        <button
                                            class="btn-acao btn-acao--editar"
                                            onclick="abrirModalCategoria(<?= (int) $cat['id'] ?>, '<?= escapeJs(htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8')) ?>')"
                                            aria-label="Editar categoria <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                        >Editar</button>

                                        <a
                                            href="/CapitaoMuzzarela/public/api/?action=admin-categoria-toggle&id=<?= (int) $cat['id'] ?>"
                                            class="btn-acao btn-acao--toggle"
                                            aria-label="<?= $cat['ativo'] ? 'Desativar' : 'Ativar' ?> categoria <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                        ><?= $cat['ativo'] ? 'Desativar' : 'Ativar' ?></a>

                                        <?php if ((int) $cat['total_produtos'] === 0): ?>
                                            <a
                                                href="/CapitaoMuzzarela/public/api/?action=admin-categoria-excluir&id=<?= (int) $cat['id'] ?>"
                                                class="btn-acao btn-acao--excluir"
                                                onclick="return confirm('Excluir a categoria \'<?= escapeJs(htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8')) ?>\'?')"
                                                aria-label="Excluir categoria <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                            >Excluir</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </section>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- SEÇÃO: PRODUTOS POR CATEGORIA                                  -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <section class="cardapio-secao" aria-labelledby="tituloProdutos">

            <div class="cardapio-secao__header">
                <h2 id="tituloProdutos">Produtos</h2>
                <?php if (!empty($categorias)): ?>
                    <button class="btn-primario" onclick="abrirModalProduto()">
                        + Novo Produto
                    </button>
                <?php endif; ?>
            </div>

            <?php if (empty($produtos)): ?>
                <p class="vazio">Nenhum produto cadastrado.</p>
            <?php else: ?>

                <?php foreach ($produtos as $categoria): ?>
                    <div class="categoria-bloco">

                        <h3 class="categoria-bloco__titulo">
                            <?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?>
                            <span class="badge badge--<?= $categoria['ativo'] ? 'ativo' : 'inativo' ?>">
                                <?= $categoria['ativo'] ? 'Ativa' : 'Inativa' ?>
                            </span>
                        </h3>

                        <div class="produtos-grid">
                            <?php foreach ($categoria['produtos'] as $p): ?>
                                <article class="produto-card <?= !$p['disponivel'] ? 'produto-card--inativo' : '' ?>">

                                    <!-- Imagem -->
                                    <div class="produto-card__imagem">
                                        <?php if (!empty($p['imagem'])): ?>
                                            <img
                                                src="<?= BASE_URL ?>/public/images/produtos/<?= htmlspecialchars($p['imagem'], ENT_QUOTES, 'UTF-8') ?>"
                                                alt="<?= htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                loading="lazy"
                                            >
                                        <?php else: ?>
                                            <div class="produto-card__sem-imagem">📷</div>
                                        <?php endif; ?>

                                        <!-- Badges -->
                                        <div class="produto-card__badges">
                                            <?php if ($p['destaque']): ?>
                                                <span class="badge badge--destaque">⭐ Destaque</span>
                                            <?php endif; ?>
                                            <?php if (!$p['disponivel']): ?>
                                                <span class="badge badge--inativo">Indisponível</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Info -->
                                    <div class="produto-card__info">
                                        <h4 class="produto-card__nome">
                                            <?= htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8') ?>
                                        </h4>
                                        <?php if (!empty($p['descricao'])): ?>
                                            <p class="produto-card__descricao">
                                                <?= htmlspecialchars($p['descricao'], ENT_QUOTES, 'UTF-8') ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="produto-card__preco">
                                            R$ <?= number_format((float) $p['preco'], 2, ',', '.') ?>
                                        </p>
                                    </div>

                                    <!-- Ações -->
                                    <footer class="produto-card__acoes">
                                        <button
                                            class="btn-acao btn-acao--editar"
                                            onclick="abrirModalProduto(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)"
                                            aria-label="Editar produto <?= htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                        >Editar</button>

                                        <a
                                            href="/CapitaoMuzzarela/public/api/?action=admin-produto-excluir&id=<?= (int) $p['id'] ?>"
                                            class="btn-acao btn-acao--excluir"
                                            onclick="return confirm('Excluir o produto \'<?= escapeJs(htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8')) ?>\'?')"
                                            aria-label="Excluir produto <?= htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                        >Excluir</a>
                                    </footer>

                                </article>
                            <?php endforeach; ?>
                        </div>

                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </main>

    <footer class="admin-footer">
        <p>&copy; <?= date('Y') ?> Capitão Muzzarela — Todos os direitos reservados.</p>
    </footer>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: CATEGORIA                                                   -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div id="modalCategoria" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalCategoriaTitulo" hidden>
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modalCategoriaTitulo">Categoria</h2>
                <button class="modal-fechar" onclick="fecharModalCategoria()" aria-label="Fechar">&times;</button>
            </div>
            <form method="POST" action="/CapitaoMuzzarela/public/api/?action=admin-categoria-salvar" class="modal-form">
                <input type="hidden" name="id" id="categoriaId">

                <div class="campo-grupo">
                    <label for="categoriaNome">Nome <span class="obrigatorio">*</span></label>
                    <input type="text" id="categoriaNome" name="nome" maxlength="100" required placeholder="Ex.: Pizzas">
                </div>

                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModalCategoria()">Cancelar</button>
                    <button type="submit" class="btn-confirmar">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: PRODUTO                                                     -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div id="modalProduto" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalProdutoTitulo" hidden>
        <div class="modal-container modal-container--largo">
            <div class="modal-header">
                <h2 id="modalProdutoTitulo">Produto</h2>
                <button class="modal-fechar" onclick="fecharModalProduto()" aria-label="Fechar">&times;</button>
            </div>
            <form method="POST" action="/CapitaoMuzzarela/public/api/?action=admin-produto-salvar" enctype="multipart/form-data" class="modal-form">
                <input type="hidden" name="id" id="produtoId">

                <div class="campo-linha">
                    <div class="campo-grupo">
                        <label for="produtoNome">Nome <span class="obrigatorio">*</span></label>
                        <input type="text" id="produtoNome" name="nome" maxlength="100" required placeholder="Ex.: Pizza Calabresa">
                    </div>

                    <div class="campo-grupo">
                        <label for="produtoCategoria">Categoria <span class="obrigatorio">*</span></label>
                        <select id="produtoCategoria" name="categoria_produto_id" required>
                            <option value="">— Selecione —</option>
                            <?php foreach ($categorias as $cat): ?>
                                <?php if ($cat['ativo']): ?>
                                    <option value="<?= (int) $cat['id'] ?>">
                                        <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="campo-grupo">
                    <label for="produtoDescricao">Descrição</label>
                    <textarea id="produtoDescricao" name="descricao" rows="2" maxlength="255" placeholder="Ingredientes, detalhes…"></textarea>
                </div>

                <div class="campo-linha">
                    <div class="campo-grupo">
                        <label for="produtoPreco">Preço (R$) <span class="obrigatorio">*</span></label>
                        <input type="number" id="produtoPreco" name="preco" step="0.01" min="0.01" required placeholder="Ex.: 49.90">
                    </div>

                    <div class="campo-grupo">
                        <label for="produtoImagem">Imagem <small>(JPG, PNG, WEBP — máx. 2MB)</small></label>
                        <input type="file" id="produtoImagem" name="imagem" accept=".jpg,.jpeg,.png,.webp">
                        <div id="previewImagem" class="preview-imagem" hidden>
                            <img id="imgPreview" src="" alt="Preview da imagem">
                        </div>
                    </div>
                </div>

                <div class="campo-linha">
                    <div class="campo-grupo campo-grupo--checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" id="produtoDisponivel" name="disponivel" value="1" checked>
                            Disponível
                        </label>
                    </div>

                    <div class="campo-grupo campo-grupo--checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" id="produtoDestaque" name="destaque" value="1">
                            ⭐ Destaque <small>(máx. 4 por categoria)</small>
                        </label>
                    </div>
                </div>

                <div class="modal-acoes">
                    <button type="button" class="btn-cancelar" onclick="fecharModalProduto()">Cancelar</button>
                    <button type="submit" class="btn-confirmar">Salvar Produto</button>
                </div>

            </form>
        </div>
    </div>

    <script>
    /* ── Modal Categoria ──────────────────────────────────── */
    function abrirModalCategoria(id = null, nome = '') {
        document.getElementById('categoriaId').value  = id ?? '';
        document.getElementById('categoriaNome').value = nome;
        document.getElementById('modalCategoriaTitulo').textContent = id ? 'Editar Categoria' : 'Nova Categoria';
        document.getElementById('modalCategoria').hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function fecharModalCategoria() {
        document.getElementById('modalCategoria').hidden = true;
        document.body.style.overflow = '';
    }

    /* ── Modal Produto ────────────────────────────────────── */
    function abrirModalProduto(produto = null) {
        const titulo = document.getElementById('modalProdutoTitulo');
        titulo.textContent = produto ? 'Editar Produto' : 'Novo Produto';

        document.getElementById('produtoId').value             = produto?.id          ?? '';
        document.getElementById('produtoNome').value           = produto?.nome        ?? '';
        document.getElementById('produtoDescricao').value      = produto?.descricao   ?? '';
        document.getElementById('produtoPreco').value          = produto?.preco       ?? '';
        document.getElementById('produtoCategoria').value      = produto?.categoria_produto_id ?? '';
        document.getElementById('produtoDisponivel').checked   = produto ? produto.disponivel == 1 : true;
        document.getElementById('produtoDestaque').checked     = produto ? produto.destaque == 1 : false;

        // Preview da imagem atual
        const preview = document.getElementById('previewImagem');
        const imgEl   = document.getElementById('imgPreview');
        if (produto?.imagem) {
            imgEl.src     = '<?= BASE_URL ?>/public/images/produtos/' + produto.imagem;
            preview.hidden = false;
        } else {
            preview.hidden = true;
            imgEl.src = '';
        }

        document.getElementById('modalProduto').hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function fecharModalProduto() {
        document.getElementById('modalProduto').hidden = true;
        document.body.style.overflow = '';
    }

    // Preview ao selecionar nova imagem
    document.getElementById('produtoImagem').addEventListener('change', function () {
        const file    = this.files[0];
        const preview = document.getElementById('previewImagem');
        const imgEl   = document.getElementById('imgPreview');

        if (file) {
            imgEl.src      = URL.createObjectURL(file);
            preview.hidden = false;
        }
    });

    // Fecha modais ao clicar fora
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.hidden = true;
                document.body.style.overflow = '';
            }
        });
    });

    // Fecha modais com ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay:not([hidden])').forEach(m => {
                m.hidden = true;
                document.body.style.overflow = '';
            });
        }
    });
    </script>

</body>
</html>