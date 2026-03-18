<?php
/**
 * app/models/UsuarioModel.php
 *
 * Model responsável pelo CRUD de usuários e tokens de redefinição de senha.
 */

declare(strict_types=1);

class UsuarioModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // =========================================================================
    // CRUD de Usuários
    // =========================================================================

    /**
     * Retorna todos os usuários ordenados por nome.
     */
    public function listar(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, nome, email, ativo, criado_em
            FROM   usuarios
            ORDER  BY nome ASC
        ");

        return $stmt->fetchAll();
    }

    /**
     * Retorna um usuário pelo ID.
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, nome, email, ativo, criado_em
            FROM   usuarios
            WHERE  id = :id
            LIMIT  1
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Retorna um usuário pelo e-mail (inclui senha para autenticação).
     */
    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM usuarios WHERE email = :email LIMIT 1
        ");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Cria um novo usuário.
     *
     * @throws RuntimeException Se o e-mail já estiver em uso
     */
    public function criar(string $nome, string $email, string $senha): int
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios (nome, email, senha, ativo)
                VALUES (:nome, :email, :senha, 1)
            ");
            $stmt->bindValue(':nome',  $nome,                          PDO::PARAM_STR);
            $stmt->bindValue(':email', $email,                         PDO::PARAM_STR);
            $stmt->bindValue(':senha', password_hash($senha, PASSWORD_BCRYPT));
            $stmt->execute();

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Este e-mail já está cadastrado.');
            }
            throw new RuntimeException('Não foi possível criar o usuário.');
        }
    }

    /**
     * Atualiza nome e e-mail de um usuário.
     *
     * @throws RuntimeException Se o e-mail já pertencer a outro usuário
     */
    public function atualizar(int $id, string $nome, string $email): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id
            ");
            $stmt->bindValue(':nome',  $nome,  PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':id',    $id,    PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Este e-mail já está em uso por outro usuário.');
            }
            throw new RuntimeException('Não foi possível atualizar o usuário.');
        }
    }

    /**
     * Ativa ou desativa um usuário.
     *
     * @throws RuntimeException Se tentar desativar o próprio usuário logado
     */
    public function alternarAtivo(int $id, int $idLogado): bool
    {
        if ($id === $idLogado) {
            throw new RuntimeException('Você não pode desativar sua própria conta.');
        }

        $stmt = $this->pdo->prepare("
            UPDATE usuarios SET ativo = NOT ativo WHERE id = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Exclui um usuário permanentemente.
     *
     * @throws RuntimeException Se tentar excluir o próprio usuário logado
     */
    public function excluir(int $id, int $idLogado): bool
    {
        if ($id === $idLogado) {
            throw new RuntimeException('Você não pode excluir sua própria conta.');
        }

        // Remove tokens de redefinição vinculados
        $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE usuario_id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // =========================================================================
    // Tokens de redefinição de senha
    // =========================================================================

    /**
     * Cria um token de redefinição de senha para o usuário.
     * Invalida tokens anteriores do mesmo usuário.
     *
     * @return string Token gerado
     */
    public function criarTokenRedefinicao(int $usuarioId, int $expiracaoMinutos): string
    {
        // Invalida tokens anteriores não usados
        $stmt = $this->pdo->prepare("
            UPDATE password_resets
            SET usado = 1
            WHERE usuario_id = :id AND usado = 0
        ");
        $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        // Gera token seguro
        $token    = bin2hex(random_bytes(32));
        $expiraEm = (new DateTimeImmutable())
            ->modify("+{$expiracaoMinutos} minutes")
            ->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare("
            INSERT INTO password_resets (usuario_id, token, expira_em, usado)
            VALUES (:usuario_id, :token, :expira_em, 0)
        ");
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':token',      $token,     PDO::PARAM_STR);
        $stmt->bindValue(':expira_em',  $expiraEm,  PDO::PARAM_STR);
        $stmt->execute();

        return $token;
    }

    /**
     * Valida um token de redefinição.
     * Retorna os dados do usuário vinculado ou null se inválido/expirado.
     */
    public function validarTokenRedefinicao(string $token): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.nome, u.email
            FROM   password_resets pr
            INNER  JOIN usuarios u ON u.id = pr.usuario_id
            WHERE  pr.token    = :token
              AND  pr.usado    = 0
              AND  pr.expira_em > NOW()
            LIMIT  1
        ");
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Redefine a senha do usuário e marca o token como usado.
     */
    public function redefinirSenha(int $usuarioId, string $novaSenha, string $token): bool
    {
        // Atualiza a senha
        $stmt = $this->pdo->prepare("
            UPDATE usuarios SET senha = :senha WHERE id = :id
        ");
        $stmt->bindValue(':senha', password_hash($novaSenha, PASSWORD_BCRYPT));
        $stmt->bindValue(':id',    $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        // Marca o token como usado
        $stmt = $this->pdo->prepare("
            UPDATE password_resets SET usado = 1 WHERE token = :token
        ");
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();

        return true;
    }
}
