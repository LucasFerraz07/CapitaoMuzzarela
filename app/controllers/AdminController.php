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
    private MesaAdminModel    $mesaAdminModel;
    private HorarioAdminModel $horarioAdminModel;
    private UsuarioModel      $usuarioModel;
    private EmailService      $emailService;
    private string            $raiz;

    public function __construct()
    {
        $this->adminModel        = new AdminModel();
        $this->reservaAdminModel = new ReservaAdminModel();
        $this->categoriaModel    = new CategoriaModel();
        $this->produtoModel      = new ProdutoModel();
        $this->mesaAdminModel    = new MesaAdminModel();
        $this->horarioAdminModel = new HorarioAdminModel();
        $this->usuarioModel      = new UsuarioModel();
        $this->emailService      = new EmailService();
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
    // Mesas — Listagem
    // =========================================================================

    /**
     * Endpoint: GET /public/api/?action=admin-mesas
     * Exibe a listagem de mesas com status de ocupação hoje.
     */
    public function exibirMesas(): void
    {
        $this->exigirAutenticacao();

        $mesas   = $this->mesaAdminModel->listar();
        $erro    = $_GET['erro']    ?? null;
        $sucesso = $_GET['sucesso'] ?? null;

        require_once $this->raiz . '/app/views/admin/mesas.php';
        exit;
    }

    // =========================================================================
    // Mesas — Salvar (criar ou editar)
    // =========================================================================

    /**
     * Endpoint: POST /public/api/?action=admin-mesa-salvar
     */
    public function salvarMesa(): void
    {
        $this->exigirAutenticacao();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('admin-mesas');
            return;
        }

        $id         = (int) ($_POST['id']         ?? 0);
        $numero     = (int) ($_POST['numero']     ?? 0);
        $capacidade = (int) ($_POST['capacidade'] ?? 0);

        // Validações
        if ($numero < 1 || $numero > 999) {
            $this->redirecionarComErro('admin-mesas', 'Número da mesa inválido (1 a 999).');
            return;
        }

        if ($capacidade < 1 || $capacidade > 50) {
            $this->redirecionarComErro('admin-mesas', 'Capacidade inválida (1 a 50 pessoas).');
            return;
        }

        try {
            if ($id > 0) {
                $this->mesaAdminModel->atualizar($id, $numero, $capacidade);
                $this->redirecionarComSucesso('admin-mesas', 'Mesa atualizada com sucesso!');
            } else {
                $this->mesaAdminModel->criar($numero, $capacidade);
                $this->redirecionarComSucesso('admin-mesas', 'Mesa criada com sucesso!');
            }
        } catch (RuntimeException $e) {
            $this->redirecionarComErro('admin-mesas', $e->getMessage());
        }
    }

    // =========================================================================
    // Mesas — Excluir
    // =========================================================================

    /**
     * Endpoint: GET /public/api/?action=admin-mesa-excluir&id=N
     */
    public function excluirMesa(): void
    {
        $this->exigirAutenticacao();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id < 1) {
            $this->redirecionar('admin-mesas');
            return;
        }

        try {
            $this->mesaAdminModel->excluir($id);
            $this->redirecionarComSucesso('admin-mesas', 'Mesa excluída com sucesso!');
        } catch (RuntimeException $e) {
            $this->redirecionarComErro('admin-mesas', $e->getMessage());
        }
    }


    // =========================================================================
    // Horários de Funcionamento — Listagem
    // =========================================================================

    public function exibirHorarios(): void
    {
        $this->exigirAutenticacao();

        $horarios = $this->horarioAdminModel->listar();
        $erro     = $_GET['erro']    ?? null;
        $sucesso  = $_GET['sucesso'] ?? null;

        require_once $this->raiz . '/app/views/admin/horarios.php';
        exit;
    }

    // =========================================================================
    // Horários de Funcionamento — Salvar
    // =========================================================================

    public function salvarHorario(): void
    {
        $this->exigirAutenticacao();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('admin-horarios');
            return;
        }

        $id             = (int)   ($_POST['id']              ?? 0);
        $fechado        = isset($_POST['fechado']);
        $horaAbertura   = trim($_POST['hora_abertura']   ?? '');
        $horaFechamento = trim($_POST['hora_fechamento'] ?? '');

        if ($id < 1) {
            $this->redirecionar('admin-horarios');
            return;
        }

        if (!$fechado) {
            $regex = '/^([01][0-9]|2[0-3]):[0-5][0-9]$/';

            if (empty($horaAbertura) || !preg_match($regex, $horaAbertura)) {
                $this->redirecionarComErro('admin-horarios', 'Horário de abertura inválido.');
                return;
            }

            if (empty($horaFechamento) || !preg_match($regex, $horaFechamento)) {
                $this->redirecionarComErro('admin-horarios', 'Horário de fechamento inválido.');
                return;
            }

            if ($horaFechamento <= $horaAbertura) {
                $this->redirecionarComErro('admin-horarios', 'O horário de fechamento deve ser posterior ao de abertura.');
                return;
            }
        }

        $this->horarioAdminModel->atualizar(
            $id,
            $fechado,
            $fechado ? null : $horaAbertura,
            $fechado ? null : $horaFechamento
        );

        $this->redirecionarComSucesso('admin-horarios', 'Horário atualizado com sucesso!');
    }

    // =========================================================================
    // Usuários — Listagem
    // =========================================================================

    public function exibirUsuarios(): void
    {
        $this->exigirAutenticacao();

        $usuarios = $this->usuarioModel->listar();
        $erro     = $_GET['erro']    ?? null;
        $sucesso  = $_GET['sucesso'] ?? null;

        require_once $this->raiz . '/app/views/admin/usuarios.php';
        exit;
    }

    // =========================================================================
    // Usuários — Salvar (criar ou editar)
    // =========================================================================

    public function salvarUsuario(): void
    {
        $this->exigirAutenticacao();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('admin-usuarios');
            return;
        }

        $id    = (int)   ($_POST['id']    ?? 0);
        $nome  = trim($_POST['nome']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');

        // Validações comuns
        if (empty($nome) || mb_strlen($nome) > 100) {
            $this->redirecionarComErro('admin-usuarios', 'Nome inválido (máx. 100 caracteres).');
            return;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $this->redirecionarComErro('admin-usuarios', 'E-mail inválido.');
            return;
        }

        try {
            if ($id > 0) {
                // Edição — senha não é alterada aqui
                $this->usuarioModel->atualizar($id, $nome, $email);
                $this->redirecionarComSucesso('admin-usuarios', 'Usuário atualizado com sucesso!');
            } else {
                // Criação — senha obrigatória
                if (empty($senha) || mb_strlen($senha) < 8) {
                    $this->redirecionarComErro('admin-usuarios', 'A senha deve ter no mínimo 8 caracteres.');
                    return;
                }
                $this->usuarioModel->criar($nome, $email, $senha);
                $this->redirecionarComSucesso('admin-usuarios', 'Usuário criado com sucesso!');
            }
        } catch (RuntimeException $e) {
            $this->redirecionarComErro('admin-usuarios', $e->getMessage());
        }
    }

    // =========================================================================
    // Usuários — Alternar ativo/inativo
    // =========================================================================

    public function alternarAtivoUsuario(): void
    {
        $this->exigirAutenticacao();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id < 1) {
            $this->redirecionar('admin-usuarios');
            return;
        }

        try {
            $this->usuarioModel->alternarAtivo($id, (int) $_SESSION['admin_id']);
            $this->redirecionar('admin-usuarios');
        } catch (RuntimeException $e) {
            $this->redirecionarComErro('admin-usuarios', $e->getMessage());
        }
    }

    // =========================================================================
    // Usuários — Excluir
    // =========================================================================

    public function excluirUsuario(): void
    {
        $this->exigirAutenticacao();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id < 1) {
            $this->redirecionar('admin-usuarios');
            return;
        }

        try {
            $this->usuarioModel->excluir($id, (int) $_SESSION['admin_id']);
            $this->redirecionarComSucesso('admin-usuarios', 'Usuário excluído com sucesso!');
        } catch (RuntimeException $e) {
            $this->redirecionarComErro('admin-usuarios', $e->getMessage());
        }
    }

    // =========================================================================
    // Usuários — Enviar e-mail de redefinição de senha
    // =========================================================================

    public function enviarRedefinicaoSenha(): void
    {
        $this->exigirAutenticacao();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id < 1) {
            $this->redirecionar('admin-usuarios');
            return;
        }

        $usuario = $this->usuarioModel->buscarPorId($id);

        if (!$usuario) {
            $this->redirecionarComErro('admin-usuarios', 'Usuário não encontrado.');
            return;
        }

        $config = require $this->raiz . '/config/email.php';

        try {
            $token = $this->usuarioModel->criarTokenRedefinicao($id, $config['token_expiracao_minutos']);
            $this->emailService->enviarRedefinicaoSenha($usuario['email'], $usuario['nome'], $token);
            $this->redirecionarComSucesso('admin-usuarios', "E-mail de redefinição enviado para {$usuario['email']}.");
        } catch (RuntimeException $e) {
            $this->redirecionarComErro('admin-usuarios', $e->getMessage());
        }
    }

    // =========================================================================
    // Redefinição de senha — Exibir formulário (acesso público via token)
    // =========================================================================

    public function exibirFormRedefinicao(): void
    {
        $token   = trim($_GET['token'] ?? '');
        $usuario = null;
        $erro    = null;

        if (empty($token)) {
            $erro = 'Link inválido.';
        } else {
            $usuario = $this->usuarioModel->validarTokenRedefinicao($token);
            if (!$usuario) {
                $erro = 'Este link é inválido ou já expirou. Solicite um novo ao administrador.';
            }
        }

        require_once $this->raiz . '/app/views/admin/redefinir_senha.php';
        exit;
    }

    // =========================================================================
    // Redefinição de senha — Processar nova senha
    // =========================================================================

    public function processarRedefinicaoSenha(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('admin-login');
            return;
        }

        $token          = trim($_POST['token']          ?? '');
        $novaSenha      = trim($_POST['nova_senha']     ?? '');
        $confirmaSenha  = trim($_POST['confirma_senha'] ?? '');

        $usuario = $this->usuarioModel->validarTokenRedefinicao($token);

        if (!$usuario) {
            header("Location: /CapitaoMuzzarela/public/api/?action=redefinir-senha&token=" . urlencode($token) . "&erro=" . urlencode('Link inválido ou expirado.'));
            exit;
        }

        if (mb_strlen($novaSenha) < 8) {
            header("Location: /CapitaoMuzzarela/public/api/?action=redefinir-senha&token=" . urlencode($token) . "&erro=" . urlencode('A senha deve ter no mínimo 8 caracteres.'));
            exit;
        }

        if ($novaSenha !== $confirmaSenha) {
            header("Location: /CapitaoMuzzarela/public/api/?action=redefinir-senha&token=" . urlencode($token) . "&erro=" . urlencode('As senhas não coincidem.'));
            exit;
        }

        $this->usuarioModel->redefinirSenha($usuario['id'], $novaSenha, $token);

        header("Location: /CapitaoMuzzarela/public/api/?action=admin-login&sucesso=" . urlencode('Senha redefinida com sucesso! Faça login.'));
        exit;
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
