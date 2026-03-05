<?php
/**
 * app/models/HorarioModel.php
 *
 * Model responsável por consultas na tabela `horario_funcionamento`.
 */

declare(strict_types=1);

class HorarioModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Retorna os horários de funcionamento de todos os dias da semana.
     *
     * O índice do array retornado é o número do dia da semana no padrão
     * JavaScript/PHP (0 = Domingo, 1 = Segunda … 6 = Sábado),
     * assumindo que a tabela `dia_semana` siga essa ordem de IDs.
     *
     * Retorno por item:
     *   [
     *     'dia_semana_id'   => int,
     *     'nome'            => string,
     *     'fechado'         => bool,
     *     'hora_abertura'   => string|null  (HH:MM),
     *     'hora_fechamento' => string|null  (HH:MM),
     *   ]
     *
     * @return array<int, array>
     */
    public function getTodosHorarios(): array
    {
        $sql = "
            SELECT
                hf.dia_semana_id,
                ds.nome,
                hf.fechado,
                TIME_FORMAT(hf.hora_abertura,   '%H:%i') AS hora_abertura,
                TIME_FORMAT(hf.hora_fechamento, '%H:%i') AS hora_fechamento
            FROM horario_funcionamento hf
            INNER JOIN dia_semana ds ON ds.id = hf.dia_semana_id
            ORDER BY hf.dia_semana_id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $rows = $stmt->fetchAll();

        // Indexa pelo dia_semana_id para acesso O(1) no controller
        $indexado = [];
        foreach ($rows as $row) {
            $indexado[(int) $row['dia_semana_id']] = [
                'dia_semana_id'   => (int)  $row['dia_semana_id'],
                'nome'            =>        $row['nome'],
                'fechado'         => (bool) $row['fechado'],
                'hora_abertura'   => $row['hora_abertura']   ?? null,
                'hora_fechamento' => $row['hora_fechamento'] ?? null,
            ];
        }

        return $indexado;
    }

    /**
     * Retorna o horário de funcionamento de um dia específico da semana.
     *
     * @param  int         $diaSemanaId  ID do dia (1=Dom … 7=Sáb, conforme seu banco)
     * @return array|null                Dados do dia ou null se não cadastrado
     */
    public function getHorarioPorDia(int $diaSemanaId): ?array
    {
        $sql = "
            SELECT
                hf.dia_semana_id,
                ds.nome,
                hf.fechado,
                TIME_FORMAT(hf.hora_abertura,   '%H:%i') AS hora_abertura,
                TIME_FORMAT(hf.hora_fechamento, '%H:%i') AS hora_fechamento
            FROM horario_funcionamento hf
            INNER JOIN dia_semana ds ON ds.id = hf.dia_semana_id
            WHERE hf.dia_semana_id = :dia_semana_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':dia_semana_id', $diaSemanaId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        if (!$row) return null;

        return [
            'dia_semana_id'   => (int)  $row['dia_semana_id'],
            'nome'            =>        $row['nome'],
            'fechado'         => (bool) $row['fechado'],
            'hora_abertura'   => $row['hora_abertura']   ?? null,
            'hora_fechamento' => $row['hora_fechamento'] ?? null,
        ];
    }
}
