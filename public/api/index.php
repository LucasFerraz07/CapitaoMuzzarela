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
 * — Admin —
 *   GET  ?action=admin-login        → exibe tela de login
 *   POST ?action=admin-login        → processa login
 *   GET  ?action=admin-logout       → encerra sessão
 *   GET  ?action=admin-dashboard    → painel principal (protegido)
 */

declare(strict_types=1);

define('BASE_URL', '/CapitaoMuzzarela');

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
require_once $root . '/app/controllers/ReservaController.php';
require_once $root . '/app/controllers/AdminController.php';

// ── Roteamento ────────────────────────────────────────────────────────────────
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

    // ── Rota não encontrada ───────────────────────────────────────────────────
    default:
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['sucesso' => false, 'mensagem' => 'Endpoint não encontrado.']);
        break;
}
