<?php
/**
 * app/models/ReservaAdminModel.php
 *
 * Model responsável pelas consultas administrativas na tabela `reservas`.
 * Separado do ReservaModel (usado pelo cliente) para manter responsabilidades claras.
 */

declare(strict_types=1);

class ReservaAdminModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Retorna todas as reservas com filtros opcionais.
     *
     * @param  string|null $data    Filtro por data (Y-m-d)
     * @param  string|null $status  Filtro por status ('ativa' | 'finalizada')
     * @return array
     */
    public function listar(?string $data = null, ?string $status = null): array
    {
        $sql = "
            SELECT
                r.id,
                r.nome_completo,
                r.telefone,
                DATE_FORMAT(r.data_reserva, '%d/%m/%Y') AS data_reserva,
                r.data_reserva                          AS data_reserva_raw,
                TIME_FORMAT(r.horario_reserva, '%H:%i') AS horario_reserva,
                r.qntd_pessoas,
                r.observacoes,
                r.status,
                m.numero AS mesa_numero,
                m.capacidade AS mesa_capacidade
            FROM reservas r
            INNER JOIN mesas m ON m.id = r.mesas_id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($data)) {
            $sql .= " AND r.data_reserva = :data";
            $params[':data'] = $data;
        }

        if (!empty($status)) {
            $sql .= " AND r.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY r.data_reserva DESC, r.horario_reserva ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Retorna os dados completos de uma reserva pelo ID.
     *
     * @param  int        $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                r.id,
                r.nome_completo,
                r.telefone,
                DATE_FORMAT(r.data_reserva, '%d/%m/%Y') AS data_reserva,
                r.data_reserva                          AS data_reserva_raw,
                TIME_FORMAT(r.horario_reserva, '%H:%i') AS horario_reserva,
                r.qntd_pessoas,
                r.observacoes,
                r.status,
                m.numero     AS mesa_numero,
                m.capacidade AS mesa_capacidade
            FROM reservas r
            INNER JOIN mesas m ON m.id = r.mesas_id
            WHERE r.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Atualiza o status de uma reserva.
     *
     * @param  int    $id
     * @param  string $status  'ativa' | 'finalizada'
     * @return bool
     */
    public function atualizarStatus(int $id, string $status): bool
    {
        $statusPermitidos = ['ativa', 'finalizada'];

        if (!in_array($status, $statusPermitidos, true)) {
            return false;
        }

        $sql = "UPDATE reservas SET status = :status WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':id',     $id,     PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
