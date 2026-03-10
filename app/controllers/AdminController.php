<?php
/**
 * app/controllers/AdminController.php
 *
 * Controller responsável pela autenticação e pelo painel administrativo.
 * Gerencia login, logout, sessão e todas as telas do admin.
 */

declare(strict_types=1);

class AdminController
{
    private AdminModel        $adminModel;
    private ReservaAdminModel $reservaAdminModel;

    public function __construct()
    {
        $this->adminModel        = new AdminModel();
        $this->reservaAdminModel = new ReservaAdminModel();
    }

    // =========================================================================
    // Autenticação
    // =========================================================================

    public function exibirLogin(): void
    {
        if ($this->estaAutenticado()) {
            $this->redirecionar('admin-dashboard');
            return;
        }

        require_once dirname(__DIR__, 2) . '/app/views/admin/loginAdmin.php';
        exit;
    }

    public function processarLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('admin-login');
            return;
        }

        $email = trim($_POST['email']    ?? '');
        $senha = trim($_POST['password'] ?? '');

        if (empty($email) || empty($senha)) {
            $this->redirecionarComErro('admin-login', 'Preencha todos os campos.');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirecionarComErro('admin-login', 'E-mail inválido.');
            return;
        }

        $usuario = $this->adminModel->buscarPorEmail($email);

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            $this->redirecionarComErro('admin-login', 'E-mail ou senha incorretos.');
            return;
        }

        session_regenerate_id(true);

        $_SESSION['admin_id']    = $usuario['id'];
        $_SESSION['admin_nome']  = $usuario['nome'];
        $_SESSION['admin_email'] = $usuario['email'];

        $this->redirecionar('admin-dashboard');
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        $this->redirecionar('admin-login');
    }

    // =========================================================================
    // Dashboard
    // =========================================================================

    public function exibirDashboard(): void
    {
        $this->exigirAutenticacao();

        require_once dirname(__DIR__, 2) . '/app/views/admin/dashboardAdmin.php';
        exit;
    }

    // =========================================================================
    // Reservas — Listagem
    // =========================================================================

    /**
     * Endpoint: GET /api/?action=admin-reservas
     * Exibe a listagem de reservas com filtros opcionais por data e status.
     */
    public function exibirReservas(): void
    {
        $this->exigirAutenticacao();

        $filtroData   = trim($_GET['data']   ?? '');
        $filtroStatus = trim($_GET['status'] ?? '');

        if (!empty($filtroData) && !$this->validarData($filtroData)) {
            $filtroData = '';
        }

        $statusPermitidos = ['ativa', 'finalizada', ''];
        if (!in_array($filtroStatus, $statusPermitidos, true)) {
            $filtroStatus = '';
        }

        $reservas = $this->reservaAdminModel->listar(
            $filtroData   ?: null,
            $filtroStatus ?: null
        );

        require_once dirname(__DIR__, 2) . '/app/views/admin/reservas.php';
        exit;
    }

    // =========================================================================
    // Reservas — Detalhe
    // =========================================================================

    /**
     * Endpoint: GET /api/?action=admin-reserva-detalhe&id=N
     * Exibe os detalhes de uma reserva específica.
     */
    public function exibirReservaDetalhe(): void
    {
        $this->exigirAutenticacao();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id < 1) {
            $this->redirecionar('admin-reservas');
            return;
        }

        $reserva = $this->reservaAdminModel->buscarPorId($id);

        if (!$reserva) {
            $this->redirecionar('admin-reservas');
            return;
        }

        require_once dirname(__DIR__, 2) . '/app/views/admin/reserva_detalhe.php';
        exit;
    }

    // =========================================================================
    // Reservas — Atualizar status
    // =========================================================================

    /**
     * Endpoint: POST /api/?action=admin-reserva-status
     * Atualiza o status de uma reserva e redireciona para a listagem.
     */
    public function atualizarStatusReserva(): void
    {
        $this->exigirAutenticacao();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('admin-reservas');
            return;
        }

        $id     = (int)   ($_POST['id']     ?? 0);
        $status = trim($_POST['status'] ?? '');

        if ($id < 1 || !in_array($status, ['ativa', 'finalizada'], true)) {
            $this->redirecionar('admin-reservas');
            return;
        }

        $this->reservaAdminModel->atualizarStatus($id, $status);

        $this->redirecionar('admin-reservas');
    }

    // =========================================================================
    // Helpers públicos
    // =========================================================================

    public function estaAutenticado(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public function exigirAutenticacao(): void
    {
        if (!$this->estaAutenticado()) {
            $this->redirecionar('admin-login');
            exit;
        }
    }

    // =========================================================================
    // Helpers privados
    // =========================================================================

    private function validarData(string $data): bool
    {
        $d = DateTimeImmutable::createFromFormat('Y-m-d', $data);
        return $d !== false && $d->format('Y-m-d') === $data;
    }

    private function redirecionar(string $action): void
    {
        header("Location: /CapitaoMuzzarela/public/api/?action={$action}");
        exit;
    }

    private function redirecionarComErro(string $action, string $erro): void
    {
        $msg = urlencode($erro);
        header("Location: /CapitaoMuzzarela/public/api/?action={$action}&erro={$msg}");
        exit;
    }
}
