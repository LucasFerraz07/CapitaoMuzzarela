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
    private CategoriaModel    $categoriaModel;
    private ProdutoModel      $produtoModel;
    private string            $raiz;

    public function __construct()
    {
        $this->adminModel        = new AdminModel();
        $this->reservaAdminModel = new ReservaAdminModel();
        $this->categoriaModel    = new CategoriaModel();
        $this->produtoModel      = new ProdutoModel();
        $this->raiz              = dirname(__DIR__, 2);
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
    // Cardápio — Listagem principal
    // =========================================================================

    /**
     * Endpoint: GET /api/?action=admin-cardapio
     * Exibe categorias e produtos agrupados.
     */
    public function exibirCardapio(): void
    {
        $this->exigirAutenticacao();

        $categorias = $this->categoriaModel->listar();
        $produtos   = $this->produtoModel->listarPorCategoria();
        $erro       = $_GET['erro']     ?? null;
        $sucesso    = $_GET['sucesso']  ?? null;

        require_once $this->raiz . '/app/views/admin/cardapio.php';
        exit;
    }

    // =========================================================================
    // Cardápio — CRUD Categorias
    // =========================================================================

    public function salvarCategoria(): void
    {
        $this->exigirAutenticacao();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('admin-cardapio');
            return;
        }

        $id   = (int)   ($_POST['id']   ?? 0);
        $nome = trim($_POST['nome'] ?? '');

        if (empty($nome) || mb_strlen($nome) > 100) {
            $this->redirecionarComErro('admin-cardapio', 'Nome da categoria inválido.');
            return;
        }

        try {
            if ($id > 0) {
                $this->categoriaModel->atualizar($id, $nome);
                $this->redirecionarComSucesso('admin-cardapio', 'Categoria atualizada com sucesso!');
            } else {
                $this->categoriaModel->criar($nome);
                $this->redirecionarComSucesso('admin-cardapio', 'Categoria criada com sucesso!');
            }
        } catch (RuntimeException $e) {
            $this->redirecionarComErro('admin-cardapio', $e->getMessage());
        }
    }

    public function alternarAtivoCategoria(): void
    {
        $this->exigirAutenticacao();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id < 1) {
            $this->redirecionar('admin-cardapio');
            return;
        }

        $this->categoriaModel->alternarAtivo($id);
        $this->redirecionar('admin-cardapio');
    }

    public function excluirCategoria(): void
    {
        $this->exigirAutenticacao();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id < 1) {
            $this->redirecionar('admin-cardapio');
            return;
        }

        try {
            $this->categoriaModel->excluir($id);
            $this->redirecionarComSucesso('admin-cardapio', 'Categoria excluída com sucesso!');
        } catch (RuntimeException $e) {
            $this->redirecionarComErro('admin-cardapio', $e->getMessage());
        }
    }

    // =========================================================================
    // Cardápio — CRUD Produtos
    // =========================================================================

    public function salvarProduto(): void
    {
        $this->exigirAutenticacao();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('admin-cardapio');
            return;
        }

        $id          = (int)   ($_POST['id']                   ?? 0);
        $nome        = trim($_POST['nome']                 ?? '');
        $descricao   = trim($_POST['descricao']            ?? '');
        $preco       = trim($_POST['preco']                ?? '');
        $categoriaId = (int)   ($_POST['categoria_produto_id'] ?? 0);
        $disponivel  = isset($_POST['disponivel'])  ? 1 : 0;
        $destaque    = isset($_POST['destaque'])    ? 1 : 0;

        // ── Validações ────────────────────────────────────────────────────────
        $erros = [];

        if (empty($nome) || mb_strlen($nome) > 100) {
            $erros[] = 'Nome do produto inválido (máx. 100 caracteres).';
        }

        if (!is_numeric($preco) || (float) $preco <= 0) {
            $erros[] = 'Preço inválido.';
        }

        if ($categoriaId < 1) {
            $erros[] = 'Selecione uma categoria.';
        }

        if (!empty($erros)) {
            $this->redirecionarComErro('admin-cardapio', implode(' ', $erros));
            return;
        }

        // ── Upload de imagem ──────────────────────────────────────────────────
        $nomeImagem = null;

        if (!empty($_FILES['imagem']['name'])) {
            $resultado = $this->processarUploadImagem($_FILES['imagem']);

            if ($resultado['erro']) {
                $this->redirecionarComErro('admin-cardapio', $resultado['mensagem']);
                return;
            }

            $nomeImagem = $resultado['nome'];
        }

        $dados = [
            'nome'                => $nome,
            'descricao'           => $descricao,
            'preco'               => number_format((float) $preco, 2, '.', ''),
            'disponivel'          => $disponivel,
            'destaque'            => $destaque,
            'categoria_produto_id'=> $categoriaId,
            'imagem'              => $nomeImagem,
        ];

        try {
            if ($id > 0) {
                $this->produtoModel->atualizar($id, $dados);
                $this->redirecionarComSucesso('admin-cardapio', 'Produto atualizado com sucesso!');
            } else {
                $this->produtoModel->criar($dados);
                $this->redirecionarComSucesso('admin-cardapio', 'Produto criado com sucesso!');
            }
        } catch (RuntimeException $e) {
            // Remove imagem enviada se houve erro ao salvar
            if ($nomeImagem) {
                @unlink($this->raiz . '/public/images/produtos/' . $nomeImagem);
            }
            $this->redirecionarComErro('admin-cardapio', $e->getMessage());
        }
    }

    public function excluirProduto(): void
    {
        $this->exigirAutenticacao();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id < 1) {
            $this->redirecionar('admin-cardapio');
            return;
        }

        $this->produtoModel->excluir($id, $this->raiz);
        $this->redirecionarComSucesso('admin-cardapio', 'Produto excluído com sucesso!');
    }

    // =========================================================================
    // Helper: upload de imagem
    // =========================================================================

    private function processarUploadImagem(array $arquivo): array
    {
        $tiposPermitidos = ProdutoModel::TIPOS_IMAGEM;
        $extPermitidas   = ProdutoModel::EXT_IMAGEM;
        $tamanhoMax      = 2 * 1024 * 1024; // 2MB

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            return ['erro' => true, 'mensagem' => 'Falha no upload da imagem.'];
        }

        if ($arquivo['size'] > $tamanhoMax) {
            return ['erro' => true, 'mensagem' => 'A imagem deve ter no máximo 2MB.'];
        }

        // Verifica tipo real do arquivo (não confiar apenas na extensão)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $tipo  = $finfo->file($arquivo['tmp_name']);

        if (!in_array($tipo, $tiposPermitidos, true)) {
            return ['erro' => true, 'mensagem' => 'Formato inválido. Use JPG, PNG ou WEBP.'];
        }

        $ext      = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        $ext      = strtolower($ext);

        if (!in_array($ext, $extPermitidas, true)) {
            return ['erro' => true, 'mensagem' => 'Extensão inválida. Use JPG, PNG ou WEBP.'];
        }

        // Gera nome único para evitar conflitos
        $nomeArquivo = uniqid('produto_', true) . '.' . $ext;
        $destino     = $this->raiz . '/public/images/produtos/' . $nomeArquivo;

        // Cria o diretório se não existir
        if (!is_dir(dirname($destino))) {
            mkdir(dirname($destino), 0755, true);
        }

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            return ['erro' => true, 'mensagem' => 'Não foi possível salvar a imagem.'];
        }

        return ['erro' => false, 'nome' => $nomeArquivo];
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

    private function redirecionarComSucesso(string $action, string $msg): void
    {
        $msg = urlencode($msg);
        header("Location: /CapitaoMuzzarela/public/api/?action={$action}&sucesso={$msg}");
        exit;
    }
}
