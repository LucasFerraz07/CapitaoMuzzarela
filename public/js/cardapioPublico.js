/**
 * public/js/cardapioPublico.js
 *
 * Gerencia a seção de Cardápio na landing page:
 *   - Ao carregar, descobre quais categorias têm destaques e oculta as sem produtos
 *   - Abre automaticamente a primeira categoria disponível
 *   - Ao clicar em um botão de categoria, busca os produtos via AJAX e renderiza os cards
 */

'use strict';

const API_BASE_CARDAPIO = './public/api/?action=';

/* ============================================================
   Referências DOM
   ============================================================ */
const menuBotoes = document.querySelectorAll('.btn-menu');
const cardsContainer = document.querySelector('.cards');

/* ============================================================
   Mapa: id do botão → nome da categoria no banco
   ============================================================ */
const CATEGORIAS = {
    'btn-pizzas':      'Pizzas',
    'btn-lanches':     'Lanches',
    'btn-bebidas':     'Bebidas',
    'btn-sobremesas':  'Sobremesas',
};

/* ============================================================
   Renderiza os cards de produto na tela
   ============================================================ */
function renderizarCards(produtos) {
    cardsContainer.innerHTML = '';

    if (produtos.length === 0) {
        cardsContainer.innerHTML = '<p class="cardapio-vazio">Nenhum destaque disponível no momento.</p>';
        return;
    }

    produtos.forEach((p) => {
        const imagemSrc = p.imagem
            ? `./public/images/produtos/${p.imagem}`
            : './public/images/pizzaCardapio.webp';

        const preco = parseFloat(p.preco).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        });

        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `
            <img src="${imagemSrc}" alt="${escapeHtml(p.nome)}" loading="lazy" />
            <h3>${escapeHtml(p.nome)}</h3>
            <p>${escapeHtml(p.descricao ?? '')}</p>
            <span class="card-preco">${preco}</span>
        `;

        cardsContainer.appendChild(card);
    });
}

/* ============================================================
   Exibe estado de loading nos cards
   ============================================================ */
function renderizarLoading() {
    cardsContainer.innerHTML = `
        <div class="card card--loading"></div>
        <div class="card card--loading"></div>
        <div class="card card--loading"></div>
        <div class="card card--loading"></div>
    `;
}

/* ============================================================
   Busca os destaques de uma categoria via AJAX
   ============================================================ */
async function buscarDestaques(categoria, botao) {
    // Marca botão como ativo
    menuBotoes.forEach((b) => b.classList.remove('btn-menu--ativo'));
    botao.classList.add('btn-menu--ativo');

    renderizarLoading();

    try {
        const res  = await fetch(`${API_BASE_CARDAPIO}cardapio-destaques&categoria=${encodeURIComponent(categoria)}`);
        const json = await res.json();

        if (json.sucesso) {
            renderizarCards(json.produtos);
        } else {
            cardsContainer.innerHTML = '<p class="cardapio-vazio">Não foi possível carregar os produtos.</p>';
        }
    } catch (_) {
        cardsContainer.innerHTML = '<p class="cardapio-vazio">Erro de conexão. Tente novamente.</p>';
    }
}

/* ============================================================
   Inicialização: descobre categorias com destaques
   ============================================================ */
async function inicializar() {
    try {
        const res  = await fetch(`${API_BASE_CARDAPIO}cardapio-categorias-ativas`);
        const json = await res.json();

        if (!json.sucesso) return;

        const ativas = json.categorias ?? [];
        let primeiroBotaoAtivo = null;

        menuBotoes.forEach((botao) => {
            const categoria = CATEGORIAS[botao.id];

            if (!ativas.includes(categoria)) {
                // Oculta botões de categorias sem destaques
                botao.style.display = 'none';
            } else {
                botao.style.display = '';
                if (!primeiroBotaoAtivo) primeiroBotaoAtivo = botao;
            }
        });

        // Abre automaticamente a primeira categoria disponível
        if (primeiroBotaoAtivo) {
            const categoria = CATEGORIAS[primeiroBotaoAtivo.id];
            buscarDestaques(categoria, primeiroBotaoAtivo);
        }
    } catch (_) {
        // Falha silenciosa — botões permanecem visíveis
    }
}

/* ============================================================
   Eventos dos botões de categoria
   ============================================================ */
menuBotoes.forEach((botao) => {
    botao.addEventListener('click', () => {
        const categoria = CATEGORIAS[botao.id];
        if (categoria) buscarDestaques(categoria, botao);
    });
});

/* ============================================================
   Utilitário: escapa HTML para evitar XSS nos cards
   ============================================================ */
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* ============================================================
   Inicia quando o DOM estiver pronto
   ============================================================ */
document.addEventListener('DOMContentLoaded', inicializar);
