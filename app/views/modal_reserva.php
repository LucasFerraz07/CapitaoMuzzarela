<?php
/**
 * app/views/modal_reserva.php
 *
 * View parcial: Modal de Reserva de Mesa.
 * Inclua este arquivo dentro do <body> do seu index.html
 * (renomeado para index.php) ou carregue-o via PHP include.
 */
?>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║           MODAL DE RESERVA DE MESA                   ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<div id="modalReserva" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitulo" hidden>
    <div class="modal-container">

        <!-- Cabeçalho do modal -->
        <div class="modal-header">
            <h2 id="modalTitulo">Reservar Mesa</h2>
            <button class="modal-fechar" id="btnFecharModal" aria-label="Fechar modal">&times;</button>
        </div>

        <!-- Área de feedback (sucesso / erro) -->
        <div id="modalFeedback" class="modal-feedback" hidden></div>

        <!-- Formulário de Reserva -->
        <form id="formReserva" novalidate autocomplete="off">

            <!-- Nome Completo -->
            <div class="campo-grupo">
                <label for="nomeCompleto">Nome Completo <span class="obrigatorio">*</span></label>
                <input
                    type="text"
                    id="nomeCompleto"
                    name="nome_completo"
                    placeholder="Ex.: João da Silva"
                    maxlength="120"
                    required
                />
                <span class="campo-erro" id="erroNome"></span>
            </div>

            <!-- Telefone -->
            <div class="campo-grupo">
                <label for="telefone">Telefone / WhatsApp <span class="obrigatorio">*</span></label>
                <input
                    type="tel"
                    id="telefone"
                    name="telefone"
                    placeholder="(11) 91234-5678"
                    maxlength="20"
                    required
                />
                <span class="campo-erro" id="erroTelefone"></span>
            </div>

            <!-- Data e Horário na mesma linha -->
            <div class="campo-linha">
                <div class="campo-grupo">
                    <label for="dataReserva">Data <span class="obrigatorio">*</span></label>
                    <input
                        type="date"
                        id="dataReserva"
                        name="data_reserva"
                        required
                    />
                    <span class="campo-erro" id="erroData"></span>
                </div>

                <div class="campo-grupo">
                    <label for="horarioReserva">Horário <span class="obrigatorio">*</span></label>
                    <input
                        type="time"
                        id="horarioReserva"
                        name="horario_reserva"
                        min="11:00"
                        max="23:00"
                        required
                    />
                    <span class="campo-erro" id="erroHorario"></span>
                </div>
            </div>

            <!-- Quantidade de Pessoas -->
            <div class="campo-grupo">
                <label for="qntdPessoas">Quantidade de Pessoas <span class="obrigatorio">*</span></label>
                <input
                    type="number"
                    id="qntdPessoas"
                    name="qntd_pessoas"
                    min="1"
                    max="50"
                    placeholder="Ex.: 4"
                    required
                />
                <span class="campo-erro" id="erroQntd"></span>
            </div>

            <!-- Select de Mesas (carregado via AJAX) -->
            <div class="campo-grupo">
                <label for="mesaId">Mesa Disponível <span class="obrigatorio">*</span></label>
                <select id="mesaId" name="mesas_id" required disabled>
                    <option value="">— Informe a data e quantidade de pessoas —</option>
                </select>
                <span class="campo-info" id="infoMesa"></span>
                <span class="campo-erro" id="erroMesa"></span>
            </div>

            <!-- Observações -->
            <div class="campo-grupo">
                <label for="observacoes">Observações <small>(opcional)</small></label>
                <textarea
                    id="observacoes"
                    name="observacoes"
                    rows="3"
                    maxlength="300"
                    placeholder="Alguma preferência especial? Aniversário, restrição alimentar…"
                ></textarea>
                <span class="campo-erro" id="erroObs"></span>
            </div>

            <!-- Botões de ação -->
            <div class="modal-acoes">
                <button type="button" class="btn-cancelar" id="btnCancelarForm">Cancelar</button>
                <button type="submit" class="btn-confirmar" id="btnEnviarReserva">
                    <span class="btn-texto">Confirmar Reserva</span>
                    <span class="btn-loading" hidden>Aguarde…</span>
                </button>
            </div>

        </form><!-- /#formReserva -->

    </div><!-- /.modal-container -->
</div><!-- /#modalReserva -->
