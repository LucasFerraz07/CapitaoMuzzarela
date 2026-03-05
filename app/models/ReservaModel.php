<?php
/**
 * app/models/ReservaModel.php
 *
 * Model responsável por todas as operações na tabela `reservas`.
 * Utiliza PDO com prepared statements para segurança máxima.
 */

declare(strict_types=1);

class ReservaModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Insere uma nova reserva no banco de dados.
     *
     * @param  array $dados  Dados validados da reserva
     * @return int           ID da reserva inserida
     * @throws RuntimeException  Se a mesa já estiver reservada na data (UNIQUE constraint)
     */
    public function criar(array $dados): int
    {
        $sql = "
            INSERT INTO reservas
                (nome_completo, telefone, data_reserva, horario_reserva,
                 qntd_pessoas, observacoes, mesas_id, status)
            VALUES
                (:nome_completo, :telefone, :data_reserva, :horario_reserva,
                 :qntd_pessoas, :observacoes, :mesas_id, 'ativa')
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(':nome_completo',   $dados['nome_completo'],   PDO::PARAM_STR);
        $stmt->bindValue(':telefone',        $dados['telefone'],        PDO::PARAM_STR);
        $stmt->bindValue(':data_reserva',    $dados['data_reserva'],    PDO::PARAM_STR);
        $stmt->bindValue(':horario_reserva', $dados['horario_reserva'], PDO::PARAM_STR);
        $stmt->bindValue(':qntd_pessoas',    $dados['qntd_pessoas'],    PDO::PARAM_INT);
        $stmt->bindValue(':observacoes',     $dados['observacoes'],     PDO::PARAM_STR);
        $stmt->bindValue(':mesas_id',        $dados['mesas_id'],        PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            // Código 23000 = violação de integridade (UNIQUE ou FK)
            if ($e->getCode() === '23000') {
                throw new RuntimeException(
                    'Esta mesa já está reservada para a data selecionada. Por favor, escolha outra mesa.'
                );
            }
            error_log('[ReservaModel::criar] ' . $e->getMessage());
            throw new RuntimeException('Não foi possível concluir a reserva. Tente novamente.');
        }

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Verifica se já existe reserva ativa para a combinação mesa + data.
     * (Verificação extra antes de tentar inserir)
     *
     * @param  int    $mesaId
     * @param  string $data    Formato Y-m-d
     * @return bool
     */
    public function existeReserva(int $mesaId, string $data): bool
    {
        $sql = "
            SELECT COUNT(*) FROM reservas
            WHERE  mesas_id   = :mesa_id
              AND  data_reserva = :data
              AND  status       = 'ativa'
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':mesa_id', $mesaId, PDO::PARAM_INT);
        $stmt->bindValue(':data',    $data,    PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }
}
