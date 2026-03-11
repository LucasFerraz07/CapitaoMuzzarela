<?php
/**
 * app/models/ProdutoModel.php
 *
 * Model responsável pelas operações na tabela `produtos`.
 */

declare(strict_types=1);

class ProdutoModel
{
    private PDO $pdo;

    // Limite de produtos em destaque por categoria
    private const MAX_DESTAQUES = 4;

    // Formatos de imagem aceitos
    public const TIPOS_IMAGEM = ['image/jpeg', 'image/png', 'image/webp'];
    public const EXT_IMAGEM   = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Retorna todos os produtos agrupados por categoria.
     */
    public function listarPorCategoria(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                p.id, p.nome, p.descricao, p.preco,
                p.imagem, p.disponivel, p.destaque,
                c.id   AS categoria_id,
                c.nome AS categoria_nome,
                c.ativo AS categoria_ativa
            FROM   produtos p
            INNER  JOIN categoria_produto c ON c.id = p.categoria_produto_id
            ORDER  BY c.nome ASC, p.nome ASC
        ");

        $rows = $stmt->fetchAll();

        // Agrupa por categoria
        $agrupado = [];
        foreach ($rows as $row) {
            $catId = $row['categoria_id'];
            if (!isset($agrupado[$catId])) {
                $agrupado[$catId] = [
                    'id'       => $catId,
                    'nome'     => $row['categoria_nome'],
                    'ativo'    => $row['categoria_ativa'],
                    'produtos' => [],
                ];
            }
            $agrupado[$catId]['produtos'][] = $row;
        }

        return array_values($agrupado);
    }

    /**
     * Retorna um produto pelo ID.
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM produtos WHERE id = :id LIMIT 1
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Conta os destaques ativos de uma categoria (excluindo um ID específico).
     */
    public function contarDestaquesPorCategoria(int $categoriaId, int $excluirId = 0): int
    {
        $sql = "
            SELECT COUNT(*) FROM produtos
            WHERE  categoria_produto_id = :categoria_id
              AND  destaque = 1
        ";

        $params = [':categoria_id' => $categoriaId];

        if ($excluirId > 0) {
            $sql .= " AND id != :excluir_id";
            $params[':excluir_id'] = $excluirId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Cria um novo produto.
     *
     * @throws RuntimeException Se o limite de destaques for atingido
     */
    public function criar(array $dados): int
    {
        // Verifica limite de destaques
        if (!empty($dados['destaque'])) {
            $total = $this->contarDestaquesPorCategoria((int) $dados['categoria_produto_id']);
            if ($total >= self::MAX_DESTAQUES) {
                throw new RuntimeException(
                    'Limite de ' . self::MAX_DESTAQUES . ' destaques por categoria atingido. Remova o destaque de outro produto antes.'
                );
            }
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO produtos
                (nome, descricao, preco, imagem, disponivel, destaque, categoria_produto_id)
            VALUES
                (:nome, :descricao, :preco, :imagem, :disponivel, :destaque, :categoria_produto_id)
        ");

        $stmt->bindValue(':nome',                 $dados['nome'],                PDO::PARAM_STR);
        $stmt->bindValue(':descricao',            $dados['descricao'] ?? null,   PDO::PARAM_STR);
        $stmt->bindValue(':preco',                $dados['preco'],               PDO::PARAM_STR);
        $stmt->bindValue(':imagem',               $dados['imagem'] ?? null,      PDO::PARAM_STR);
        $stmt->bindValue(':disponivel',           $dados['disponivel'] ? 1 : 0,  PDO::PARAM_INT);
        $stmt->bindValue(':destaque',             $dados['destaque']   ? 1 : 0,  PDO::PARAM_INT);
        $stmt->bindValue(':categoria_produto_id', $dados['categoria_produto_id'], PDO::PARAM_INT);

        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Atualiza um produto existente.
     *
     * @throws RuntimeException Se o limite de destaques for atingido
     */
    public function atualizar(int $id, array $dados): bool
    {
        // Verifica limite de destaques (excluindo o próprio produto)
        if (!empty($dados['destaque'])) {
            $total = $this->contarDestaquesPorCategoria(
                (int) $dados['categoria_produto_id'],
                $id
            );
            if ($total >= self::MAX_DESTAQUES) {
                throw new RuntimeException(
                    'Limite de ' . self::MAX_DESTAQUES . ' destaques por categoria atingido. Remova o destaque de outro produto antes.'
                );
            }
        }

        // Se não enviou nova imagem, mantém a atual
        $sqlImagem = !empty($dados['imagem']) ? ', imagem = :imagem' : '';

        $stmt = $this->pdo->prepare("
            UPDATE produtos SET
                nome                 = :nome,
                descricao            = :descricao,
                preco                = :preco,
                disponivel           = :disponivel,
                destaque             = :destaque,
                categoria_produto_id = :categoria_produto_id
                {$sqlImagem}
            WHERE id = :id
        ");

        $stmt->bindValue(':nome',                 $dados['nome'],                 PDO::PARAM_STR);
        $stmt->bindValue(':descricao',            $dados['descricao'] ?? null,    PDO::PARAM_STR);
        $stmt->bindValue(':preco',                $dados['preco'],                PDO::PARAM_STR);
        $stmt->bindValue(':disponivel',           $dados['disponivel'] ? 1 : 0,   PDO::PARAM_INT);
        $stmt->bindValue(':destaque',             $dados['destaque']   ? 1 : 0,   PDO::PARAM_INT);
        $stmt->bindValue(':categoria_produto_id', $dados['categoria_produto_id'], PDO::PARAM_INT);
        $stmt->bindValue(':id',                   $id,                            PDO::PARAM_INT);

        if (!empty($dados['imagem'])) {
            $stmt->bindValue(':imagem', $dados['imagem'], PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Exclui um produto e sua imagem do disco.
     */
    public function excluir(int $id, string $raizProjeto): bool
    {
        $produto = $this->buscarPorId($id);

        if (!$produto) return false;

        // Remove a imagem do disco se existir
        if (!empty($produto['imagem'])) {
            $caminhoImagem = $raizProjeto . '/public/images/produtos/' . $produto['imagem'];
            if (file_exists($caminhoImagem)) {
                unlink($caminhoImagem);
            }
        }

        $stmt = $this->pdo->prepare("DELETE FROM produtos WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
