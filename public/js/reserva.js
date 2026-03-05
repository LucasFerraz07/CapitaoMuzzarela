/**
 * public/js/reserva.js
 *
 * Gerencia toda a interação do Modal de Reserva:
 *   - Abre/fecha o modal
 *   - Inicializa Flatpickr com dias fechados bloqueados (via AJAX)
 *   - Restringe o input de horário ao horário de funcionamento do dia
 *   - Busca mesas disponíveis via AJAX ao mudar data ou qtd_pessoas
 *   - Envia o formulário via AJAX e exibe feedback
 */

'use strict';

/* ============================================================
   Configuração
   ============================================================ */
const API_BASE = './public/api/?action=';

/* ============================================================
   Utilitários
   ============================================================ */
const $ = (sel) => document.querySelector(sel);

function debounce(fn, delay = 400) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

function formatarTelefone(valor) {
    const nums = valor.replace(/\D/g, '').slice(0, 11);
    if (nums.length <= 2)  return `(${nums}`;
    if (nums.length <= 6)  return `(${nums.slice(0,2)}) ${nums.slice(2)}`;
    if (nums.length <= 10) return `(${nums.slice(0,2)}) ${nums.slice(2,6)}-${nums.slice(6)}`;
    return `(${nums.slice(0,2)}) ${nums.slice(2,7)}-${nums.slice(7)}`;
}

/* ============================================================
   Referências DOM
   ============================================================ */
const modal         = $('#modalReserva');
const form          = $('#formReserva');
const feedback      = $('#modalFeedback');
const selectMesa    = $('#mesaId');
const infoMesa      = $('#infoMesa');
const btnEnviar     = $('#btnEnviarReserva');
const btnTexto      = btnEnviar?.querySelector('.btn-texto');
const btnLoading    = btnEnviar?.querySelector('.btn-loading');
const inputData     = $('#dataReserva');
const inputHorario  = $('#horarioReserva');
const inputQntd     = $('#qntdPessoas');
const inputTelefone = $('#telefone');

/* ============================================================
   Estado global dos horários de funcionamento
   ============================================================ */

/**
 * horariosFuncionamento: objeto indexado por dia_semana_id (1=Dom … 7=Sáb)
 * Preenchido uma única vez ao abrir o modal pela primeira vez.
 *
 * Exemplo:
 * {
 *   "1": { "fechado": true,  "hora_abertura": null,    "hora_fechamento": null },
 *   "2": { "fechado": false, "hora_abertura": "18:00", "hora_fechamento": "23:00" },
 *   ...
 * }
 */
let horariosFuncionamento = null;
let flatpickrInstance     = null;

/* ============================================================
   Mapeamento: dia da semana JS → dia_semana_id do banco
   JS Date.getDay(): 0=Dom, 1=Seg … 6=Sáb
   Banco (assumindo): 1=Dom, 2=Seg … 7=Sáb
   ============================================================ */
const jsDayToDbId = (jsDay) => jsDay + 1;

/* ============================================================
   Carrega horários de funcionamento via AJAX
   ============================================================ */
async function carregarHorarios() {
    if (horariosFuncionamento !== null) return; // já carregado

    try {
        const res  = await fetch(`${API_BASE}horarios-funcionamento`);
        const json = await res.json();

        if (json.sucesso) {
            horariosFuncionamento = json.horarios;
        } else {
            console.warn('Não foi possível carregar os horários de funcionamento.');
            horariosFuncionamento = {};
        }
    } catch (_) {
        console.warn('Erro de conexão ao carregar horários de funcionamento.');
        horariosFuncionamento = {};
    }
}

/* ============================================================
   Inicializa o Flatpickr no campo de data
   ============================================================ */
function inicializarFlatpickr() {
    if (flatpickrInstance) {
        flatpickrInstance.destroy();
    }

    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    flatpickrInstance = flatpickr('#dataReserva', {
        locale: 'pt',
        dateFormat: 'Y-m-d',
        minDate: 'today',
        disableMobile: true, // usa sempre o picker customizado

        // Bloqueia os dias em que o estabelecimento está fechado
        disable: [
            function (date) {
                if (!horariosFuncionamento) return false;

                const dbId   = jsDayToDbId(date.getDay());
                const horario = horariosFuncionamento[dbId];

                // Bloqueia se: não há registro, fechado = true, ou sem horário de abertura
                if (!horario) return true;
                if (horario.fechado) return true;
                if (!horario.hora_abertura) return true;

                return false;
            }
        ],

        // Ao selecionar uma data, atualiza o input de horário e busca mesas
        onChange: function (selectedDates, dateStr) {
            atualizarHorarioInput(dateStr);
            buscarMesasDebounced();
        },

        onReady: function (selectedDates, dateStr, instance) {
            // Aplica estilo customizado para combinar com o tema do projeto
            instance.calendarContainer.classList.add('flatpickr-capitao');
        }
    });
}

/* ============================================================
   Atualiza min/max do input de horário conforme o dia selecionado
   ============================================================ */
function atualizarHorarioInput(dateStr) {
    if (!dateStr || !horariosFuncionamento) return;

    // Converte dateStr (YYYY-MM-DD) para objeto Date
    const [ano, mes, dia] = dateStr.split('-').map(Number);
    const dateSel = new Date(ano, mes - 1, dia);
    const dbId    = jsDayToDbId(dateSel.getDay());
    const horario = horariosFuncionamento[dbId];

    if (!horario || horario.fechado || !horario.hora_abertura) {
        // Dia fechado — limpa e desabilita horário
        inputHorario.value = '';
        inputHorario.min   = '';
        inputHorario.max   = '';
        inputHorario.disabled = true;
        return;
    }

    inputHorario.min      = horario.hora_abertura;
    inputHorario.max      = horario.hora_fechamento;
    inputHorario.disabled = false;

    // Se o horário atual já estiver fora do intervalo, limpa
    if (inputHorario.value) {
        if (
            inputHorario.value < horario.hora_abertura ||
            inputHorario.value > horario.hora_fechamento
        ) {
            inputHorario.value = '';
        }
    }

    // Atualiza mensagem de info
    const infoHorario = $('#infoHorario');
    if (infoHorario) {
        infoHorario.textContent =
            `Funcionamento: ${horario.hora_abertura} às ${horario.hora_fechamento}`;
    }
}

/* ============================================================
   Abertura e fechamento do modal
   ============================================================ */
async function abrirModal() {
    modal.hidden = false;
    document.body.style.overflow = 'hidden';

    // Carrega horários (somente na primeira vez) e inicializa Flatpickr
    await carregarHorarios();
    inicializarFlatpickr();

    // Foca no primeiro campo
    setTimeout(() => $('#nomeCompleto')?.focus(), 150);
}

function fecharModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
    resetarFormulario();
}

function resetarFormulario() {
    form.reset();
    esconderFeedback();
    limparErros();
    resetarSelectMesa();
    resetarHorarioInput();

    if (flatpickrInstance) {
        flatpickrInstance.clear();
    }
}

function resetarHorarioInput() {
    inputHorario.value    = '';
    inputHorario.min      = '';
    inputHorario.max      = '';
    inputHorario.disabled = true;

    const infoHorario = $('#infoHorario');
    if (infoHorario) infoHorario.textContent = '';
}

/* ============================================================
   Eventos de abertura/fechamento
   ============================================================ */
document.querySelectorAll('#btn-header, .btn-reserva, .reserva-hero')
    .forEach((btn) => btn?.addEventListener('click', abrirModal));

$('#btnFecharModal')?.addEventListener('click', fecharModal);
$('#btnCancelarForm')?.addEventListener('click', fecharModal);

modal?.addEventListener('click', (e) => {
    if (e.target === modal) fecharModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.hidden) fecharModal();
});

/* ============================================================
   Formatação automática do telefone
   ============================================================ */
inputTelefone?.addEventListener('input', function () {
    this.value = formatarTelefone(this.value);
});

/* ============================================================
   Busca dinâmica de mesas disponíveis (AJAX GET)
   ============================================================ */
function resetarSelectMesa() {
    selectMesa.innerHTML = '<option value="">— Informe a data e quantidade de pessoas —</option>';
    selectMesa.disabled  = true;
    infoMesa.textContent = '';
}

async function buscarMesas() {
    const data = inputData.value;
    const qntd = parseInt(inputQntd.value, 10);

    if (!data || !qntd || qntd < 1) {
        resetarSelectMesa();
        return;
    }

    infoMesa.textContent = 'Buscando mesas disponíveis…';
    selectMesa.disabled  = true;
    selectMesa.innerHTML = '<option value="">Carregando…</option>';

    try {
        const url  = `${API_BASE}mesas-disponiveis&data=${encodeURIComponent(data)}&qntd_pessoas=${qntd}`;
        const res  = await fetch(url);
        const json = await res.json();

        if (!json.sucesso) {
            infoMesa.textContent = json.mensagem || 'Erro ao buscar mesas.';
            resetarSelectMesa();
            return;
        }

        const mesas = json.mesas ?? [];

        if (mesas.length === 0) {
            selectMesa.innerHTML = '<option value="">Nenhuma mesa disponível para esta data</option>';
            infoMesa.textContent = '😔 Sem mesas para a data/qtd selecionada.';
        } else {
            selectMesa.innerHTML = '<option value="">— Selecione uma mesa —</option>';
            mesas.forEach((m) => {
                const opt       = document.createElement('option');
                opt.value       = m.id;
                opt.textContent = `Mesa ${m.numero} (até ${m.capacidade} pessoa${m.capacidade > 1 ? 's' : ''})`;
                selectMesa.appendChild(opt);
            });
            selectMesa.disabled  = false;
            infoMesa.textContent = `✅ ${mesas.length} mesa${mesas.length > 1 ? 's' : ''} disponível${mesas.length > 1 ? 'eis' : ''}.`;
        }
    } catch (_) {
        infoMesa.textContent = 'Erro de conexão. Verifique sua internet.';
        resetarSelectMesa();
    }
}

const buscarMesasDebounced = debounce(buscarMesas, 350);

// inputData não usa addEventListener aqui pois o Flatpickr chama buscarMesasDebounced via onChange
inputQntd?.addEventListener('input', buscarMesasDebounced);

/* ============================================================
   Submissão do formulário (AJAX POST)
   ============================================================ */
form?.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (!validarFront()) return;

    setLoading(true);
    esconderFeedback();

    const formData = new FormData(form);

    try {
        const res  = await fetch(`${API_BASE}salvar-reserva`, { method: 'POST', body: formData });
        const json = await res.json();

        if (json.sucesso) {
            mostrarFeedback('sucesso', json.mensagem);
            form.reset();
            resetarSelectMesa();
            resetarHorarioInput();
            limparErros();
            if (flatpickrInstance) flatpickrInstance.clear();
            setTimeout(fecharModal, 4000);
        } else {
            if (json.erros && json.erros.length) {
                mostrarFeedbackLista('erro', json.mensagem, json.erros);
            } else {
                mostrarFeedback('erro', json.mensagem || 'Ocorreu um erro. Tente novamente.');
            }
        }
    } catch (_) {
        mostrarFeedback('erro', 'Falha na conexão. Verifique sua internet e tente novamente.');
    } finally {
        setLoading(false);
    }
});

/* ============================================================
   Helpers de UI
   ============================================================ */
function setLoading(ativo) {
    btnEnviar.disabled = ativo;
    btnTexto.hidden    = ativo;
    btnLoading.hidden  = !ativo;
}

function mostrarFeedback(tipo, msg) {
    feedback.className   = `modal-feedback ${tipo}`;
    feedback.textContent = msg;
    feedback.hidden      = false;
    feedback.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function mostrarFeedbackLista(tipo, titulo, erros) {
    feedback.className = `modal-feedback ${tipo}`;
    const ul = erros.map((e) => `<li>${e}</li>`).join('');
    feedback.innerHTML = `<strong>${titulo}</strong><ul>${ul}</ul>`;
    feedback.hidden    = false;
    feedback.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function esconderFeedback() {
    feedback.hidden      = true;
    feedback.textContent = '';
    feedback.className   = 'modal-feedback';
}

function limparErros() {
    document.querySelectorAll('.campo-erro').forEach((el) => (el.textContent = ''));
    document.querySelectorAll('.invalido').forEach((el) => el.classList.remove('invalido'));
}

/* ============================================================
   Validação front-end
   ============================================================ */
function validarFront() {
    limparErros();
    let valido = true;

    const marcar = (campoId, erroId, msg) => {
        const campo = document.getElementById(campoId);
        const erro  = document.getElementById(erroId);
        if (campo && erro) {
            campo.classList.add('invalido');
            erro.textContent = msg;
        }
        valido = false;
    };

    const nome = $('#nomeCompleto');
    if (!nome.value.trim() || nome.value.trim().length < 3) {
        marcar('nomeCompleto', 'erroNome', 'Informe seu nome completo (mínimo 3 caracteres).');
    }

    const tel = $('#telefone');
    if (!tel.value.trim()) {
        marcar('telefone', 'erroTelefone', 'Informe seu telefone.');
    }

    if (!inputData.value) {
        marcar('dataReserva', 'erroData', 'Selecione a data da reserva.');
    }

    if (!inputHorario.value) {
        marcar('horarioReserva', 'erroHorario', 'Selecione o horário.');
    } else if (horariosFuncionamento && inputData.value) {
        // Valida horário contra funcionamento
        const [ano, mes, dia] = inputData.value.split('-').map(Number);
        const dbId   = jsDayToDbId(new Date(ano, mes - 1, dia).getDay());
        const horario = horariosFuncionamento[dbId];

        if (horario && !horario.fechado && horario.hora_abertura) {
            if (
                inputHorario.value < horario.hora_abertura ||
                inputHorario.value > horario.hora_fechamento
            ) {
                marcar(
                    'horarioReserva',
                    'erroHorario',
                    `Horário fora do funcionamento (${horario.hora_abertura} às ${horario.hora_fechamento}).`
                );
            }
        }
    }

    const qntd = parseInt(inputQntd.value, 10);
    if (!qntd || qntd < 1) {
        marcar('qntdPessoas', 'erroQntd', 'Informe a quantidade de pessoas (mínimo 1).');
    }

    if (!selectMesa.value) {
        marcar('mesaId', 'erroMesa', 'Selecione uma mesa disponível.');
    }

    return valido;
}
