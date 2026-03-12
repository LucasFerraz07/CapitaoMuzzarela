<?php
/**
 * app/models/MesaAdminModel.php
 *
 * Model responsável pelas operações administrativas na tabela `mesas`.
 * Separado do MesaModel (usado pelo sistema de reservas público).
 */

declare(strict_types=1);

class MesaAdminModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Retorna todas as mesas com a contagem de reservas ativas hoje.
     */
    public function listar(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                m.id,
                m.numero,
                m.capacidade,
                COUNT(r.id) AS reservas_hoje
            FROM mesas m
            LEFT JOIN reservas r
                ON  r.mesas_id     = m.id
                AND r.data_reserva = CURDATE()
                AND r.status       = 'ativa'
            GROUP BY m.id
            ORDER BY m.numero ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Retorna uma mesa pelo ID.
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM mesas WHERE id = :id LIMIT 1
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Cria uma nova mesa.
     *
     * @throws RuntimeException Se o número da mesa já existir
     */
    public function criar(int $numero, int $capacidade): int
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO mesas (numero, capacidade) VALUES (:numero, :capacidade)
            ");
            $stmt->bindValue(':numero',     $numero,     PDO::PARAM_INT);
            $stmt->bindValue(':capacidade', $capacidade, PDO::PARAM_INT);
            $stmt->execute();

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException("Já existe uma mesa com o número {$numero}.");
            }
            throw new RuntimeException('Não foi possível criar a mesa.');
        }
    }

    /**
     * Atualiza os dados de uma mesa.
     *
     * @throws RuntimeException Se o número já pertencer a outra mesa
     */
    public function atualizar(int $id, int $numero, int $capacidade): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE mesas
                SET numero = :numero, capacidade = :capacidade
                WHERE id = :id
            ");
            $stmt->bindValue(':numero',     $numero,     PDO::PARAM_INT);
            $stmt->bindValue(':capacidade', $capacidade, PDO::PARAM_INT);
            $stmt->bindValue(':id',         $id,         PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException("Já existe uma mesa com o número {$numero}.");
            }
            throw new RuntimeException('Não foi possível atualizar a mesa.');
        }
    }

    /**
     * Verifica se a mesa possui reservas ativas.
     */
    public function temReservasAtivas(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM reservas
            WHERE mesas_id = :id AND status = 'ativa'
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Exclui uma mesa.
     *
     * @throws RuntimeException Se houver reservas ativas vinculadas
     */
    public function excluir(int $id): bool
    {
        if ($this->temReservasAtivas($id)) {
            throw new RuntimeException(
                'Não é possível excluir esta mesa pois ela possui reservas ativas.'
            );
        }

        $stmt = $this->pdo->prepare("DELETE FROM mesas WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
