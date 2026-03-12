<?php
/**
 * public/api/index.php
 *
 * Roteador leve para os endpoints da API REST e painel administrativo.
 *
 * Rotas disponíveis:
 *
 * — Reservas (AJAX) —
 *   GET  ?action=horarios-funcionamento
 *   GET  ?action=mesas-disponiveis&data=YYYY-MM-DD&qntd_pessoas=N
 *   POST ?action=salvar-reserva
 *
 * — Admin: Autenticação —
 *   GET  ?action=admin-login              → exibe tela de login
 *   POST ?action=admin-login              → processa login
 *   GET  ?action=admin-logout             → encerra sessão
 *   GET  ?action=admin-dashboard          → painel principal
 *
 * — Admin: Reservas —
 *   GET  ?action=admin-reservas           → listagem
 *   GET  ?action=admin-reserva-detalhe    → detalhe de uma reserva
 *   POST ?action=admin-reserva-status     → atualiza status
 *
 * — Admin: Mesas —
 *   GET  ?action=admin-mesas              → listagem
 *   POST ?action=admin-mesa-salvar        → criar/editar mesa
 *   GET  ?action=admin-mesa-excluir       → excluir mesa
 *
 * — Admin: Cardápio —
 *   GET  ?action=admin-cardapio           → listagem categorias + produtos
 *   POST ?action=admin-categoria-salvar   → criar/editar categoria
 *   GET  ?action=admin-categoria-toggle   → ativar/desativar categoria
 *   GET  ?action=admin-categoria-excluir  → excluir categoria
 *   POST ?action=admin-produto-salvar     → criar/editar produto
 *   GET  ?action=admin-produto-excluir    → excluir produto
 */

declare(strict_types=1);

// ── Sessão ────────────────────────────────────────────────────────────────────
session_start();

// ── Cabeçalhos de segurança ───────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// ── Autoload / includes ───────────────────────────────────────────────────────
$root = dirname(__DIR__, 2);

require_once $root . '/config/Database.php';
require_once $root . '/app/models/HorarioModel.php';
require_once $root . '/app/models/MesaModel.php';
require_once $root . '/app/models/ReservaModel.php';
require_once $root . '/app/models/AdminModel.php';
require_once $root . '/app/models/ReservaAdminModel.php';
require_once $root . '/app/models/MesaAdminModel.php';
require_once $root . '/app/models/CategoriaModel.php';
require_once $root . '/app/models/ProdutoModel.php';
require_once $root . '/app/controllers/ReservaController.php';
require_once $root . '/app/controllers/AdminController.php';

// ── Roteamento ────────────────────────────────────────────────────────────────
// ── URL base do projeto (usada nas views para CSS, imagens e links)
define('BASE_URL', '/CapitaoMuzzarela');

$action = $_GET['action'] ?? '';

$reservaController = new ReservaController();
$adminController   = new AdminController();

switch ($action) {

    // ── Rotas de Reservas ─────────────────────────────────────────────────────
    case 'horarios-funcionamento':
        $reservaController->getHorariosFuncionamento();
        break;

    case 'mesas-disponiveis':
        $reservaController->getMesasDisponiveis();
        break;

    case 'salvar-reserva':
        $reservaController->salvarReserva();
        break;

    // ── Rotas do Admin ────────────────────────────────────────────────────────
    case 'admin-login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminController->processarLogin();
        } else {
            $adminController->exibirLogin();
        }
        break;

    case 'admin-logout':
        $adminController->logout();
        break;

    case 'admin-dashboard':
        $adminController->exibirDashboard();
        break;

    case 'admin-reservas':
        $adminController->exibirReservas();
        break;

    case 'admin-reserva-detalhe':
        $adminController->exibirReservaDetalhe();
        break;

    case 'admin-reserva-status':
        $adminController->atualizarStatusReserva();
        break;

    // ── Rotas de Mesas ───────────────────────────────────────────────────────────
    case 'admin-mesas':
        $adminController->exibirMesas();
        break;

    case 'admin-mesa-salvar':
        $adminController->salvarMesa();
        break;

    case 'admin-mesa-excluir':
        $adminController->excluirMesa();
        break;

    // ── Rotas do Cardápio ─────────────────────────────────────────────────────
    case 'admin-cardapio':
        $adminController->exibirCardapio();
        break;

    case 'admin-categoria-salvar':
        $adminController->salvarCategoria();
        break;

    case 'admin-categoria-toggle':
        $adminController->alternarAtivoCategoria();
        break;

    case 'admin-categoria-excluir':
        $adminController->excluirCategoria();
        break;

    case 'admin-produto-salvar':
        $adminController->salvarProduto();
        break;

    case 'admin-produto-excluir':
        $adminController->excluirProduto();
        break;

    // ── Rota não encontrada ───────────────────────────────────────────────────
    default:
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['sucesso' => false, 'mensagem' => 'Endpoint não encontrado.']);
        break;
}
