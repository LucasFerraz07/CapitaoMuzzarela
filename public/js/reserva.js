/**
 * public/js/reserva.js
 *
 * Gerencia toda a interação do Modal de Reserva:
 *   - Abre/fecha o modal
 *   - Busca mesas disponíveis via AJAX ao mudar data ou qtd_pessoas
 *   - Envia o formulário via AJAX e exibe feedback
 */

'use strict';

/* ============================================================
   Configuração
   ============================================================ */
const API_BASE = './public/api/?action='; // ajuste se o projeto estiver em subpasta

/* ============================================================
   Utilitários
   ============================================================ */

/** Seleciona elemento pelo seletor CSS */
const $ = (sel) => document.querySelector(sel);

/** Debounce: executa a função somente após `delay` ms sem novos chamados */
function debounce(fn, delay = 400) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

/** Formata telefone enquanto o usuário digita: (11) 91234-5678 */
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
const modal          = $('#modalReserva');
const form           = $('#formReserva');
const feedback       = $('#modalFeedback');
const selectMesa     = $('#mesaId');
const infoMesa       = $('#infoMesa');
const btnEnviar      = $('#btnEnviarReserva');
const btnTexto       = btnEnviar?.querySelector('.btn-texto');
const btnLoading     = btnEnviar?.querySelector('.btn-loading');
const inputData      = $('#dataReserva');
const inputQntd      = $('#qntdPessoas');
const inputTelefone  = $('#telefone');

/* ============================================================
   Abertura e fechamento do modal
   ============================================================ */

/** Abre o modal de reserva */
function abrirModal() {
    modal.hidden = false;
    document.body.style.overflow = 'hidden'; // impede scroll da página
    // Define data mínima = hoje
    const hoje = new Date().toISOString().split('T')[0];
    inputData.min = hoje;
    // Foca no primeiro campo
    setTimeout(() => $('#nomeCompleto')?.focus(), 100);
}

/** Fecha o modal e reseta o formulário */
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
}

/* ============================================================
   Botões que abrem o modal (selecionamos todos de uma vez)
   ============================================================ */
document.querySelectorAll(
    '#btn-header, .btn-reserva, .reserva-hero'
).forEach((btn) => btn?.addEventListener('click', abrirModal));

$('#btnFecharModal')?.addEventListener('click', fecharModal);
$('#btnCancelarForm')?.addEventListener('click', fecharModal);

// Fecha ao clicar fora do container
modal?.addEventListener('click', (e) => {
    if (e.target === modal) fecharModal();
});

// Fecha com ESC
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
    selectMesa.disabled = true;
    infoMesa.textContent = '';
}

/** Realiza a busca de mesas via AJAX */
async function buscarMesas() {
    const data  = inputData.value;
    const qntd  = parseInt(inputQntd.value, 10);

    // Só dispara a busca se ambos os campos estiverem preenchidos
    if (!data || !qntd || qntd < 1) {
        resetarSelectMesa();
        return;
    }

    infoMesa.textContent = 'Buscando mesas disponíveis…';
    selectMesa.disabled = true;
    selectMesa.innerHTML = '<option value="">Carregando…</option>';

    try {
        const url = `${API_BASE}mesas-disponiveis&data=${encodeURIComponent(data)}&qntd_pessoas=${qntd}`;
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
                const opt = document.createElement('option');
                opt.value       = m.id;
                opt.textContent = `Mesa ${m.numero} (até ${m.capacidade} pessoa${m.capacidade > 1 ? 's' : ''})`;
                selectMesa.appendChild(opt);
            });
            selectMesa.disabled = false;
            infoMesa.textContent = `✅ ${mesas.length} mesa${mesas.length > 1 ? 's' : ''} disponível${mesas.length > 1 ? 'eis' : ''}.`;
        }
    } catch (_) {
        infoMesa.textContent = 'Erro de conexão. Verifique sua internet.';
        resetarSelectMesa();
    }
}

const buscarMesasDebounced = debounce(buscarMesas, 350);

inputData?.addEventListener('change', buscarMesasDebounced);
inputQntd?.addEventListener('input',  buscarMesasDebounced);

/* ============================================================
   Submissão do formulário (AJAX POST)
   ============================================================ */

form?.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Validação simples no front antes de enviar
    if (!validarFront()) return;

    setLoading(true);
    esconderFeedback();

    const formData = new FormData(form);

    try {
        const res  = await fetch(`${API_BASE}salvar-reserva`, {
            method: 'POST',
            body: formData,
        });
        const json = await res.json();

        if (json.sucesso) {
            mostrarFeedback('sucesso', json.mensagem);
            form.reset();
            resetarSelectMesa();
            limparErros();
            // Fecha o modal automaticamente após 4 s
            setTimeout(fecharModal, 4000);
        } else {
            // Exibe erros detalhados ou mensagem geral
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

/** Ativa/desativa estado de loading no botão de envio */
function setLoading(ativo) {
    btnEnviar.disabled = ativo;
    btnTexto.hidden    = ativo;
    btnLoading.hidden  = !ativo;
}

/** Exibe mensagem de feedback no modal */
function mostrarFeedback(tipo, msg) {
    feedback.className      = `modal-feedback ${tipo}`;
    feedback.textContent    = msg;
    feedback.hidden         = false;
    feedback.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/** Exibe feedback com lista de erros */
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
    document.querySelectorAll('.invalido').forEach((el) =>
        el.classList.remove('invalido')
    );
}

/* ============================================================
   Validação front-end (camada extra de UX antes do AJAX)
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

    if (!$('#horarioReserva').value) {
        marcar('horarioReserva', 'erroHorario', 'Selecione o horário.');
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
