<?php
/**
 * app/models/AdminModel.php
 *
 * Model responsável pelas operações de autenticação na tabela `usuarios`.
 */

declare(strict_types=1);

class AdminModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Busca um usuário ativo pelo e-mail.
     *
     * @param  string     $email
     * @return array|null  Dados do usuário ou null se não encontrado/inativo
     */
    public function buscarPorEmail(string $email): ?array
    {
        $sql = "
            SELECT id, nome, email, senha
            FROM   usuarios
            WHERE  email = :email
              AND  ativo = 1
            LIMIT  1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row ?: null;
    }
}
