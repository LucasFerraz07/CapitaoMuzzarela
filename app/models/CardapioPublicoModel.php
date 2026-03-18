<?php
/**
 * app/models/CardapioPublicoModel.php
 *
 * Model responsável pelas consultas públicas do cardápio.
 * Retorna produtos em destaque por categoria, para exibição na landing page.
 */

declare(strict_types=1);

class CardapioPublicoModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Retorna os produtos em destaque de uma categoria pelo nome da categoria.
     * Considera apenas produtos disponíveis (disponivel = 1) e com destaque ativo.
     * Limita a 4 produtos, conforme regra de negócio.
     *
     * @param  string $nomeCategoria  Ex: 'Pizzas', 'Bebidas'
     * @return array
     */
    public function getDestaquesPorCategoria(string $nomeCategoria): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id,
                p.nome,
                p.descricao,
                p.preco,
                p.imagem
            FROM   produtos p
            INNER  JOIN categoria_produto c ON c.id = p.categoria_produto_id
            WHERE  c.nome        = :nome_categoria
              AND  p.destaque    = 1
              AND  p.disponivel  = 1
              AND  c.ativo       = 1
            ORDER  BY p.nome ASC
            LIMIT  4
        ");

        $stmt->bindValue(':nome_categoria', $nomeCategoria, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Retorna quais das 4 categorias fixas possuem pelo menos 1 destaque disponível.
     * Usado no carregamento da página para ocultar botões sem destaques
     * e para saber qual categoria abrir automaticamente.
     *
     * @return string[]  Lista de nomes de categorias com destaques
     */
    public function getCategoriasComDestaques(): array
    {
        $categorias = ['Pizzas', 'Lanches', 'Bebidas', 'Sobremesas'];
        $placeholders = implode(',', array_fill(0, count($categorias), '?'));

        $stmt = $this->pdo->prepare("
            SELECT DISTINCT c.nome
            FROM   produtos p
            INNER  JOIN categoria_produto c ON c.id = p.categoria_produto_id
            WHERE  c.nome       IN ({$placeholders})
              AND  p.destaque   = 1
              AND  p.disponivel = 1
              AND  c.ativo      = 1
        ");

        $stmt->execute($categorias);

        return array_column($stmt->fetchAll(), 'nome');
    }

    /**
     * Retorna todos os produtos disponíveis agrupados por categoria.
     * Usado na página de cardápio completo.
     * Ordem: categorias ativas, produtos em ordem alfabética dentro de cada uma.
     *
     * @return array<string, array>  ['Pizzas' => [...produtos], 'Bebidas' => [...], ...]
     */
    public function getTodosPorCategoria(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                c.nome  AS categoria,
                p.nome,
                p.descricao,
                p.preco
            FROM   produtos p
            INNER  JOIN categoria_produto c ON c.id = p.categoria_produto_id
            WHERE  p.disponivel = 1
              AND  c.ativo      = 1
            ORDER  BY c.nome ASC, p.nome ASC
        ");

        $rows = $stmt->fetchAll();

        // Agrupa os produtos pelo nome da categoria
        $agrupados = [];
        foreach ($rows as $row) {
            $agrupados[$row['categoria']][] = [
                'nome'      => $row['nome'],
                'descricao' => $row['descricao'],
                'preco'     => $row['preco'],
            ];
        }

        return $agrupados;
    }
}
