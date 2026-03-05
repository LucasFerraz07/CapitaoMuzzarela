<?php
/**
 * app/models/MesaModel.php
 *
 * Model responsável por todas as consultas relacionadas à tabela `mesas`.
 */

declare(strict_types=1);

class MesaModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Retorna mesas disponíveis para uma data específica,
     * filtrando pela capacidade mínima exigida.
     *
     * Uma mesa é considerada DISPONÍVEL quando:
     *   1. Sua capacidade >= quantidade de pessoas solicitada
     *   2. Não possui nenhuma reserva com status = 'ativa' na data informada
     *
     * @param  string $data         Data no formato Y-m-d
     * @param  int    $qntdPessoas  Número mínimo de lugares necessários
     * @return array<int, array>    Lista de mesas disponíveis
     */
    public function getMesasDisponiveis(string $data, int $qntdPessoas): array
    {
        $sql = "
            SELECT m.id, m.numero, m.capacidade
            FROM   mesas m
            WHERE  m.capacidade >= :qntd_pessoas
              AND  m.id NOT IN (
                       SELECT r.mesas_id
                       FROM   reservas r
                       WHERE  r.data_reserva = :data
                         AND  r.status       = 'ativa'
                   )
            ORDER  BY m.capacidade ASC, m.numero ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':qntd_pessoas', $qntdPessoas, PDO::PARAM_INT);
        $stmt->bindValue(':data', $data, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Retorna uma mesa pelo seu ID.
     *
     * @param  int        $id  ID da mesa
     * @return array|false     Dados da mesa ou false se não encontrada
     */
    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mesas WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }
}
