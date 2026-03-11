<?php
/**
 * app/models/CategoriaModel.php
 *
 * Model responsável pelas operações na tabela `categoria_produto`.
 */

declare(strict_types=1);

class CategoriaModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Retorna todas as categorias.
     */
    public function listar(): array
    {
        $stmt = $this->pdo->query("
            SELECT c.id, c.nome, c.ativo,
                   COUNT(p.id) AS total_produtos
            FROM   categoria_produto c
            LEFT   JOIN produtos p ON p.categoria_produto_id = c.id
            GROUP  BY c.id
            ORDER  BY c.nome ASC
        ");

        return $stmt->fetchAll();
    }

    /**
     * Retorna uma categoria pelo ID.
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM categoria_produto WHERE id = :id LIMIT 1
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Cria uma nova categoria.
     *
     * @throws RuntimeException Se o nome já existir
     */
    public function criar(string $nome): int
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO categoria_produto (nome, ativo) VALUES (:nome, 1)
            ");
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $stmt->execute();

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Já existe uma categoria com este nome.');
            }
            throw new RuntimeException('Não foi possível criar a categoria.');
        }
    }

    /**
     * Atualiza o nome de uma categoria.
     *
     * @throws RuntimeException Se o nome já existir
     */
    public function atualizar(int $id, string $nome): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE categoria_produto SET nome = :nome WHERE id = :id
            ");
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindValue(':id',   $id,   PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Já existe uma categoria com este nome.');
            }
            throw new RuntimeException('Não foi possível atualizar a categoria.');
        }
    }

    /**
     * Ativa ou desativa uma categoria.
     */
    public function alternarAtivo(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE categoria_produto SET ativo = NOT ativo WHERE id = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Exclui uma categoria.
     * Retorna false se houver produtos vinculados.
     */
    public function excluir(int $id): bool
    {
        // Verifica se há produtos vinculados
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM produtos WHERE categoria_produto_id = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException(
                'Não é possível excluir esta categoria pois há produtos vinculados a ela.'
            );
        }

        $stmt = $this->pdo->prepare("
            DELETE FROM categoria_produto WHERE id = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
