<?php
/**
 * app/controllers/AdminController.php
 *
 * Controller responsável pela autenticação do painel administrativo.
 * Gerencia login, logout e proteção de rotas via PHP Session.
 */

declare(strict_types=1);

class AdminController
{
    private AdminModel $adminModel;

    public function __construct()
    {
        $this->adminModel = new AdminModel();
    }

    // =========================================================================
    // ACTION: Exibe a tela de login
    // =========================================================================

    /**
     * Endpoint: GET /api/?action=admin-login
     *
     * Se o admin já estiver logado, redireciona para o dashboard.
     * Caso contrário, exibe a view de login.
     */
    public function exibirLogin(): void
    {
        if ($this->estaAutenticado()) {
            $this->redirecionar('admin-dashboard');
            return;
        }

        require_once dirname(__DIR__, 2) . '/app/views/admin/loginAdmin.php';
        exit;
    }

    // =========================================================================
    // ACTION: Processa o formulário de login
    // =========================================================================

    /**
     * Endpoint: POST /api/?action=admin-login
     *
     * Valida as credenciais e inicia a sessão em caso de sucesso.
     */
    public function processarLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('admin-login');
            return;
        }

        $email = trim($_POST['email']    ?? '');
        $senha = trim($_POST['password'] ?? '');

        // ── Validação básica ─────────────────────────────────────────────────
        if (empty($email) || empty($senha)) {
            $this->redirecionarComErro('admin-login', 'Preencha todos os campos.');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirecionarComErro('admin-login', 'E-mail inválido.');
            return;
        }

        // ── Busca o usuário no banco ─────────────────────────────────────────
        $usuario = $this->adminModel->buscarPorEmail($email);

        // Verifica senha com hash (password_verify é seguro contra timing attacks)
        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            $this->redirecionarComErro('admin-login', 'E-mail ou senha incorretos.');
            return;
        }

        // ── Inicia a sessão ───────────────────────────────────────────────────
        session_regenerate_id(true); // previne session fixation

        $_SESSION['admin_id']    = $usuario['id'];
        $_SESSION['admin_nome']  = $usuario['nome'];
        $_SESSION['admin_email'] = $usuario['email'];

        $this->redirecionar('admin-dashboard');
    }

    // =========================================================================
    // ACTION: Logout
    // =========================================================================

    /**
     * Endpoint: GET /api/?action=admin-logout
     *
     * Destrói a sessão e redireciona para o login.
     */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
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
    // ACTION: Exibe o dashboard
    // =========================================================================

    /**
     * Endpoint: GET /api/?action=admin-dashboard
     *
     * Protegido: redireciona para login se não autenticado.
     */
    public function exibirDashboard(): void
    {
        $this->exigirAutenticacao();

        require_once dirname(__DIR__, 2) . '/app/views/admin/dashboardAdmin.php';
        exit;
    }

    // =========================================================================
    // Helpers públicos
    // =========================================================================

    /**
     * Verifica se o admin está autenticado.
     * Pode ser chamado por outros controllers para proteger rotas.
     */
    public function estaAutenticado(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    /**
     * Redireciona para o login se não estiver autenticado.
     * Use no início de qualquer action protegida.
     */
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

    /**
     * Redireciona para uma action do roteador.
     */
    private function redirecionar(string $action): void
    {
        header("Location: " . BASE_URL . "/api/?action={$action}");
        exit;
    }

    /**
     * Redireciona com mensagem de erro na query string.
     */
    private function redirecionarComErro(string $action, string $erro): void
    {
        $msg = urlencode($erro);
        header("Location: /public/api/?action={$action}&erro={$msg}");
        exit;
    }
}
