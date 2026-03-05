<?php
/**
 * public/api/index.php
 *
 * Roteador leve para os endpoints da API REST do sistema de reservas.
 * Todas as requisições AJAX do front-end chegam aqui.
 *
 * Rotas disponíveis:
 *   GET  /api/?action=mesas-disponiveis&data=YYYY-MM-DD&qntd_pessoas=N
 *   POST /api/?action=salvar-reserva
 */

declare(strict_types=1);

// ── Cabeçalhos de segurança ───────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// ── Autoload / includes ───────────────────────────────────────────────────────
$root = dirname(__DIR__, 2); // sobe dois níveis: public/api → raiz do projeto

require_once $root . '/config/Database.php';
require_once $root . '/app/models/MesaModel.php';
require_once $root . '/app/models/ReservaModel.php';
require_once $root . '/app/controllers/ReservaController.php';

// ── Roteamento pela query string ?action=... ──────────────────────────────────
$action     = $_GET['action'] ?? '';
$controller = new ReservaController();

switch ($action) {
    case 'mesas-disponiveis':
        $controller->getMesasDisponiveis();
        break;

    case 'salvar-reserva':
        $controller->salvarReserva();
        break;

    default:
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['sucesso' => false, 'mensagem' => 'Endpoint não encontrado.']);
        break;
}
