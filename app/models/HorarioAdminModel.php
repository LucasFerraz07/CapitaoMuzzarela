<?php
/**
 * app/models/HorarioAdminModel.php
 *
 * Model responsável pelas operações administrativas
 * na tabela `horario_funcionamento`.
 */

declare(strict_types=1);

class HorarioAdminModel
{
    private PDO $pdo;

    // Ordem de exibição: Segunda → Domingo
    private const ORDEM_DIAS = [2, 3, 4, 5, 6, 7, 1];

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Retorna os 7 horários com o nome do dia,
     * ordenados de Segunda a Domingo.
     */
    public function listar(): array
    {
        $ordem = implode(',', self::ORDEM_DIAS);

        $stmt = $this->pdo->query("
            SELECT
                h.id,
                h.hora_abertura,
                h.hora_fechamento,
                h.fechado,
                d.id   AS dia_id,
                d.nome AS dia_nome
            FROM horario_funcionamento h
            INNER JOIN dia_semana d ON d.id = h.dia_semana_id
            ORDER BY FIELD(d.id, {$ordem})
        ");

        return $stmt->fetchAll();
    }

    /**
     * Retorna um horário pelo ID.
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                h.id,
                h.hora_abertura,
                h.hora_fechamento,
                h.fechado,
                d.id   AS dia_id,
                d.nome AS dia_nome
            FROM horario_funcionamento h
            INNER JOIN dia_semana d ON d.id = h.dia_semana_id
            WHERE h.id = :id
            LIMIT 1
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Atualiza o horário de um dia.
     *
     * Quando fechado = true, hora_abertura e hora_fechamento são gravados como NULL.
     */
    public function atualizar(int $id, bool $fechado, ?string $horaAbertura, ?string $horaFechamento): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE horario_funcionamento
            SET
                fechado          = :fechado,
                hora_abertura    = :hora_abertura,
                hora_fechamento  = :hora_fechamento
            WHERE id = :id
        ");

        $stmt->bindValue(':fechado',         $fechado ? 1 : 0,  PDO::PARAM_INT);
        $stmt->bindValue(':hora_abertura',   $fechado ? null : $horaAbertura,  PDO::PARAM_STR);
        $stmt->bindValue(':hora_fechamento', $fechado ? null : $horaFechamento, PDO::PARAM_STR);
        $stmt->bindValue(':id',              $id,                PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
