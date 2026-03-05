<?php
/**
 * app/controllers/ReservaController.php
 *
 * Controller que gerencia as ações do sistema de reservas.
 * Recebe as requisições, valida os dados, aciona os Models
 * e retorna respostas JSON para o front-end (AJAX).
 */

declare(strict_types=1);

class ReservaController
{
    private ReservaModel  $reservaModel;
    private MesaModel     $mesaModel;
    private HorarioModel  $horarioModel;

    public function __construct()
    {
        $this->reservaModel = new ReservaModel();
        $this->mesaModel    = new MesaModel();
        $this->horarioModel = new HorarioModel();
    }

    // =========================================================================
    // ACTION: Retorna horários de funcionamento (chamada AJAX ao abrir o modal)
    // =========================================================================

    /**
     * Endpoint: GET /api/?action=horarios-funcionamento
     *
     * Responde com JSON contendo os horários de todos os dias da semana,
     * indexados pelo dia_semana_id, para uso no Flatpickr (frontend).
     *
     * Exemplo de resposta:
     * {
     *   "sucesso": true,
     *   "horarios": {
     *     "1": { "nome": "Domingo",   "fechado": true,  "hora_abertura": null,    "hora_fechamento": null },
     *     "2": { "nome": "Segunda",   "fechado": false, "hora_abertura": "18:00", "hora_fechamento": "23:00" },
     *     ...
     *   }
     * }
     */
    public function getHorariosFuncionamento(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(false, 'Método não permitido.', [], 405);
            return;
        }

        try {
            $horarios = $this->horarioModel->getTodosHorarios();
            $this->jsonResponse(true, '', ['horarios' => $horarios]);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage());
        }
    }

    // =========================================================================
    // ACTION: Retorna mesas disponíveis (chamada AJAX ao mudar data/qtd_pessoas)
    // =========================================================================

    /**
     * Endpoint: GET /api/?action=mesas-disponiveis&data=YYYY-MM-DD&qntd_pessoas=N
     *
     * Responde com JSON contendo a lista de mesas disponíveis.
     */
    public function getMesasDisponiveis(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(false, 'Método não permitido.', [], 405);
            return;
        }

        $data        = trim($_GET['data']         ?? '');
        $qntdPessoas = (int) ($_GET['qntd_pessoas'] ?? 0);

        // ── Validação básica ─────────────────────────────────────────────────
        $erros = [];

        if (empty($data) || !$this->validarData($data)) {
            $erros[] = 'Data inválida.';
        }

        if ($qntdPessoas < 1 || $qntdPessoas > 50) {
            $erros[] = 'Quantidade de pessoas inválida.';
        }

        if (!empty($erros)) {
            $this->jsonResponse(false, implode(' ', $erros));
            return;
        }

        // ── Verifica se o estabelecimento abre na data informada ─────────────
        $diaSemanaId = $this->getDiaSemanaId($data);
        $horario     = $this->horarioModel->getHorarioPorDia($diaSemanaId);

        if (!$horario || $horario['fechado'] || empty($horario['hora_abertura'])) {
            $this->jsonResponse(
                false,
                'O estabelecimento não funciona nesta data.',
                ['fechado' => true]
            );
            return;
        }

        // ── Consulta ao Model ────────────────────────────────────────────────
        try {
            $mesas = $this->mesaModel->getMesasDisponiveis($data, $qntdPessoas);
            $this->jsonResponse(true, '', ['mesas' => $mesas]);
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage());
        }
    }

    // =========================================================================
    // ACTION: Salva a reserva (chamada AJAX ao submeter o formulário)
    // =========================================================================

    /**
     * Endpoint: POST /api/?action=salvar-reserva
     *
     * Valida e persiste a reserva. Responde com JSON.
     */
    public function salvarReserva(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método não permitido.', [], 405);
            return;
        }

        // ── Coleta e sanitização dos dados recebidos ─────────────────────────
        $dados = [
            'nome_completo'   => trim($_POST['nome_completo']   ?? ''),
            'telefone'        => trim($_POST['telefone']        ?? ''),
            'data_reserva'    => trim($_POST['data_reserva']    ?? ''),
            'horario_reserva' => trim($_POST['horario_reserva'] ?? ''),
            'qntd_pessoas'    => (int) ($_POST['qntd_pessoas']  ?? 0),
            'observacoes'     => trim($_POST['observacoes']     ?? ''),
            'mesas_id'        => (int) ($_POST['mesas_id']      ?? 0),
        ];

        // ── Validação backend completa ───────────────────────────────────────
        $erros = $this->validarDados($dados);

        if (!empty($erros)) {
            $this->jsonResponse(false, 'Verifique os campos:', ['erros' => $erros]);
            return;
        }

        // ── Verificação extra: mesa ainda disponível? ────────────────────────
        if ($this->reservaModel->existeReserva($dados['mesas_id'], $dados['data_reserva'])) {
            $this->jsonResponse(
                false,
                'Esta mesa acabou de ser reservada por outra pessoa. Por favor, escolha outra mesa.'
            );
            return;
        }

        // ── Persistência ─────────────────────────────────────────────────────
        try {
            $idReserva = $this->reservaModel->criar($dados);
            $this->jsonResponse(
                true,
                "Reserva realizada com sucesso! Seu número de confirmação é #{$idReserva}. Te esperamos!",
                ['id' => $idReserva]
            );
        } catch (RuntimeException $e) {
            $this->jsonResponse(false, $e->getMessage());
        }
    }

    // =========================================================================
    // Helpers privados
    // =========================================================================

    /**
     * Converte uma data Y-m-d para o dia_semana_id conforme a tabela `dia_semana`.
     *
     * Assumimos que os IDs seguem a ordem:
     *   1 = Domingo, 2 = Segunda, 3 = Terça, 4 = Quarta,
     *   5 = Quinta,  6 = Sexta,   7 = Sábado
     * (padrão brasileiro com Domingo como dia 1)
     *
     * PHP date('w') retorna: 0=Dom, 1=Seg … 6=Sáb
     * Portanto: dia_semana_id = date('w') + 1
     */
    private function getDiaSemanaId(string $data): int
    {
        $dt = new DateTimeImmutable($data);
        return (int) $dt->format('w') + 1;
    }

    /**
     * Valida todos os campos da reserva e retorna array de erros.
     *
     * @param  array $dados
     * @return string[]  Mensagens de erro (vazio = sem erros)
     */
    private function validarDados(array $dados): array
    {
        $erros = [];

        // Nome completo
        if (empty($dados['nome_completo'])) {
            $erros[] = 'Nome completo é obrigatório.';
        } elseif (mb_strlen($dados['nome_completo']) < 3) {
            $erros[] = 'Nome completo deve ter ao menos 3 caracteres.';
        } elseif (mb_strlen($dados['nome_completo']) > 120) {
            $erros[] = 'Nome completo não pode ultrapassar 120 caracteres.';
        }

        // Telefone: aceita formatos (11) 9xxxx-xxxx / (11) xxxx-xxxx
        if (empty($dados['telefone'])) {
            $erros[] = 'Telefone é obrigatório.';
        } elseif (!preg_match('/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/', $dados['telefone'])) {
            $erros[] = 'Telefone inválido. Use o formato (11) 91234-5678.';
        }

        // Data
        if (empty($dados['data_reserva'])) {
            $erros[] = 'Data da reserva é obrigatória.';
        } elseif (!$this->validarData($dados['data_reserva'])) {
            $erros[] = 'Data da reserva inválida.';
        } else {
            $hoje = new DateTimeImmutable('today');
            $data = new DateTimeImmutable($dados['data_reserva']);
            if ($data < $hoje) {
                $erros[] = 'A data da reserva não pode ser no passado.';
            }

            // Verifica se o estabelecimento abre nesse dia (camada backend)
            $diaSemanaId = $this->getDiaSemanaId($dados['data_reserva']);
            $horario     = $this->horarioModel->getHorarioPorDia($diaSemanaId);

            if (!$horario || $horario['fechado'] || empty($horario['hora_abertura'])) {
                $erros[] = 'O estabelecimento não funciona na data selecionada.';
            } else {
                // Valida se o horário informado está dentro do funcionamento
                if (!empty($dados['horario_reserva'])) {
                    $hrReserva  = $dados['horario_reserva'];
                    $hrAbertura = $horario['hora_abertura'];
                    $hrFecha    = $horario['hora_fechamento'];

                    if ($hrReserva < $hrAbertura || $hrReserva > $hrFecha) {
                        $erros[] = "O horário deve ser entre {$hrAbertura} e {$hrFecha}.";
                    }
                }
            }
        }

        // Horário (formato)
        if (empty($dados['horario_reserva'])) {
            $erros[] = 'Horário da reserva é obrigatório.';
        } elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $dados['horario_reserva'])) {
            $erros[] = 'Horário inválido.';
        }

        // Quantidade de pessoas
        if ($dados['qntd_pessoas'] < 1) {
            $erros[] = 'Informe a quantidade de pessoas (mínimo 1).';
        } elseif ($dados['qntd_pessoas'] > 50) {
            $erros[] = 'Quantidade de pessoas muito elevada. Entre em contato por telefone.';
        }

        // Observações (opcional, mas limita tamanho)
        if (mb_strlen($dados['observacoes']) > 300) {
            $erros[] = 'Observações não podem ultrapassar 300 caracteres.';
        }

        // Mesa
        if ($dados['mesas_id'] < 1) {
            $erros[] = 'Selecione uma mesa disponível.';
        } else {
            $mesa = $this->mesaModel->getById($dados['mesas_id']);
            if (!$mesa) {
                $erros[] = 'Mesa selecionada não existe.';
            } elseif ($mesa['capacidade'] < $dados['qntd_pessoas']) {
                $erros[] = 'A mesa selecionada não comporta a quantidade de pessoas informada.';
            }
        }

        return $erros;
    }

    /**
     * Verifica se uma string é uma data válida no formato Y-m-d.
     */
    private function validarData(string $data): bool
    {
        $d = DateTimeImmutable::createFromFormat('Y-m-d', $data);
        return $d !== false && $d->format('Y-m-d') === $data;
    }

    /**
     * Envia resposta JSON padronizada e encerra a execução.
     */
    private function jsonResponse(
        bool   $sucesso,
        string $mensagem = '',
        array  $extra    = [],
        int    $httpCode = 200
    ): void {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            array_merge(['sucesso' => $sucesso, 'mensagem' => $mensagem], $extra),
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }
}
