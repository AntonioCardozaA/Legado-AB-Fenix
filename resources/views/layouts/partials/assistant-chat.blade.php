<div
    id="assistant-chat-widget"
    data-fetch-url="{{ route('assistant-chat.index', [], false) }}"
    data-send-url="{{ route('assistant-chat.store', [], false) }}"
    data-clear-url="{{ route('assistant-chat.destroy', [], false) }}"
    data-csrf-token="{{ csrf_token() }}"
    class="fixed bottom-4 right-4 z-[90] sm:bottom-6 sm:right-6"
>
    <div
        id="assistant-chat-panel"
        class="mb-3 hidden h-[70vh] w-[calc(100vw-2rem)] max-w-md flex-col overflow-hidden rounded-[1.6rem] border border-slate-900/10 bg-white shadow-2xl shadow-slate-950/20 sm:h-[38rem]"
    >
        <div class="assistant-chat-header border-b border-white/10 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 px-5 py-4 text-white">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="assistant-chat-brandmark inline-flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white ring-1 ring-amber-300/40">
                        <img
                            src="{{ asset('images/abfenix-ai-chat.png') }}"
                            alt="ABFenix.ai"
                            class="h-full w-full object-contain"
                            style="transform: scale(2.2);"
                        >
                    </span>
                    <div>
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-300">ABFenix.ai</p>
                    <h3 class="mt-1 text-lg font-black">Chat operativo</h3>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        id="assistant-chat-clear"
                        type="button"
                        class="assistant-chat-header-action inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white transition hover:bg-white/12"
                        title="Limpiar historial"
                    >
                        <i class="fas fa-broom text-sm"></i>
                    </button>
                    <button
                        id="assistant-chat-close"
                        type="button"
                        class="assistant-chat-header-action inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white transition hover:bg-white/12"
                        title="Cerrar"
                    >
                        <i class="fas fa-xmark text-sm"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-hidden bg-slate-50">
            <div id="assistant-chat-messages" class="h-full space-y-4 overflow-y-auto px-4 py-4 sm:px-5"></div>
        </div>

        <div class="border-t border-slate-200 bg-white px-4 py-4 sm:px-5">
            <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-2">
                <textarea
                    id="assistant-chat-draft"
                    rows="3"
                    placeholder="Escribe tu pregunta..."
                    class="w-full resize-none border-0 bg-transparent px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                ></textarea>
                <div class="flex items-center justify-between gap-3 px-2 pb-1">
                    <p class="text-[11px] text-slate-400">Enter envia | Shift + Enter agrega salto</p>
                    <button
                        id="assistant-chat-send"
                        type="button"
                        class="assistant-chat-send-button inline-flex items-center gap-2 rounded-full bg-slate-950 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300"
                    >
                        <i class="fas fa-paper-plane"></i>
                        Enviar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <button
        id="assistant-chat-toggle"
        type="button"
        class="assistant-chat-trigger group inline-flex items-center gap-3 rounded-full border border-slate-700/80 bg-slate-950/95 px-4 py-3 text-sm font-semibold text-slate-50 shadow-xl shadow-slate-950/30 transition hover:translate-y-[-1px] hover:border-slate-600 hover:bg-slate-900"
    >
        <span class="assistant-chat-trigger-avatar inline-flex h-11 w-11 items-center justify-center overflow-hidden rounded-full bg-white ring-1 ring-amber-300/40 shadow-inner shadow-slate-900/10">
            <img
                src="{{ asset('images/abfenix-ai-chat.png') }}"
                alt="ABFenix.ai"
                class="h-full w-full object-contain"
                style="transform: scale(2.2);"
            >
        </span>
        <span class="text-left leading-tight">
            <span class="assistant-chat-trigger-name block text-[11px] uppercase tracking-[0.22em] text-slate-400">ABFenix.ai</span>
            <span class="assistant-chat-trigger-label block text-[15px] text-slate-100">Abrir chat</span>
        </span>
    </button>
</div>

<style>
    #assistant-chat-panel {
        border-color: rgba(15, 23, 42, 0.08);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
    }

    #assistant-chat-widget .assistant-chat-header {
        background:
            radial-gradient(circle at top left, rgba(251, 191, 36, 0.18), transparent 34%),
            linear-gradient(135deg, #020617 0%, #0f172a 58%, #1e293b 100%);
    }

    #assistant-chat-widget .assistant-chat-brandmark,
    #assistant-chat-widget .assistant-chat-trigger-avatar {
        background: linear-gradient(180deg, #ffffff 0%, #fff8eb 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 8px 18px rgba(15, 23, 42, 0.12);
    }

    #assistant-chat-widget .assistant-chat-header-action {
        border-color: rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.06);
    }

    #assistant-chat-widget .assistant-chat-header-action:hover {
        background: rgba(255, 255, 255, 0.14);
    }

    #assistant-chat-widget .assistant-chat-send-button {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }

    #assistant-chat-widget .assistant-chat-send-button:hover {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    }

    #assistant-chat-widget .assistant-chat-trigger {
        border-color: rgba(51, 65, 85, 0.75);
        background: linear-gradient(135deg, rgba(2, 6, 23, 0.96) 0%, rgba(15, 23, 42, 0.94) 100%);
        box-shadow: 0 24px 45px rgba(15, 23, 42, 0.28);
    }

    #assistant-chat-widget .assistant-chat-trigger:hover {
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.96) 100%);
    }

    #assistant-chat-widget .assistant-chat-trigger-name {
        color: #cbd5e1;
    }

    #assistant-chat-widget .assistant-chat-trigger-label {
        color: #f8fafc;
    }

    #assistant-chat-widget .assistant-chat-bubble--user {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff;
    }

    #assistant-chat-widget .assistant-chat-meta--user {
        color: #cbd5e1;
    }

    #assistant-chat-widget .assistant-chat-meta--assistant,
    #assistant-chat-widget .assistant-chat-source {
        color: #94a3b8;
    }

    #assistant-chat-widget .assistant-chat-dot--1 {
        background: #f59e0b;
    }

    #assistant-chat-widget .assistant-chat-dot--2 {
        background: #fb923c;
    }

    #assistant-chat-widget .assistant-chat-dot--3 {
        background: #64748b;
    }
</style>

<script>
(function initAssistantChatWidget() {
    const widget = document.getElementById('assistant-chat-widget');

    if (!widget || widget.dataset.initialized === 'true') {
        return;
    }

    widget.dataset.initialized = 'true';

    const fetchUrl = widget.dataset.fetchUrl || '';
    const sendUrl = widget.dataset.sendUrl || '';
    const clearUrl = widget.dataset.clearUrl || '';
    const csrfToken = widget.dataset.csrfToken || '';

    const panel = document.getElementById('assistant-chat-panel');
    const toggleButton = document.getElementById('assistant-chat-toggle');
    const closeButton = document.getElementById('assistant-chat-close');
    const clearButton = document.getElementById('assistant-chat-clear');
    const messagesContainer = document.getElementById('assistant-chat-messages');
    const draftInput = document.getElementById('assistant-chat-draft');
    const sendButton = document.getElementById('assistant-chat-send');

    if (!panel || !toggleButton || !messagesContainer || !draftInput || !sendButton) {
        return;
    }

    let sending = false;
    let historyLoaded = false;
    let messages = [];
    const assistantBrand = 'ABFenix.ai';

    const introMessage = {
        id: 'assistant-intro',
        role: 'assistant',
        content: 'Hola, soy su asistente ABFenix.ai ¿En qué puedo ayudarte?',
        metadata: {},
    };

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatMessage(value) {
        return escapeHtml(value || '').replace(/\n/g, '<br>');
    }

    function hasSources(message) {
        return Array.isArray(message?.metadata?.sources) && message.metadata.sources.length > 0;
    }

    function formatSources(message) {
        return message.metadata.sources
            .slice(0, 2)
            .map(source => source.reference || source.type || 'Referencia')
            .join(' | ');
    }

    function currentMessages() {
        return messages.length > 0 ? messages : [introMessage];
    }

    function setSendingState(active) {
        sending = active;
        sendButton.disabled = active || draftInput.value.trim() === '';
    }

    function renderMessages() {
        const rendered = currentMessages().map(message => {
            const alignment = message.role === 'user' ? 'flex justify-end' : 'flex justify-start';
            const bubble = message.role === 'user'
                ? 'assistant-chat-bubble--user max-w-[88%] rounded-[1.25rem] rounded-br-md px-4 py-3 text-sm leading-6 text-white shadow-sm'
                : 'assistant-chat-bubble--assistant max-w-[92%] rounded-[1.25rem] rounded-bl-md border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-700 shadow-sm';
            const metaTone = message.role === 'user' ? 'assistant-chat-meta--user' : 'assistant-chat-meta--assistant';
            const iconClass = message.role === 'user' ? 'fas fa-user' : 'fas fa-wand-magic-sparkles';
            const label = message.role === 'user' ? 'Tu mensaje' : assistantBrand;

            return `
                <div class="${alignment}">
                    <div class="${bubble}">
                        <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide ${metaTone}">
                            <i class="${iconClass}"></i>
                            <span>${label}</span>
                        </div>
                        <div class="mt-2 whitespace-normal break-words">${formatMessage(message.content)}</div>
                        ${hasSources(message) ? `
                            <p class="assistant-chat-source mt-3 text-[11px] font-medium">
                                Base usada: ${escapeHtml(formatSources(message))}
                            </p>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');

        const typing = sending ? `
            <div class="flex justify-start">
                <div class="assistant-chat-bubble--assistant max-w-[85%] rounded-[1.25rem] rounded-bl-md border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500 shadow-sm">
                    <div class="assistant-chat-meta--assistant flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        <span>${assistantBrand}</span>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="assistant-chat-dot--1 h-2.5 w-2.5 animate-pulse rounded-full"></span>
                        <span class="assistant-chat-dot--2 h-2.5 w-2.5 animate-pulse rounded-full" style="animation-delay: 120ms;"></span>
                        <span class="assistant-chat-dot--3 h-2.5 w-2.5 animate-pulse rounded-full" style="animation-delay: 240ms;"></span>
                    </div>
                </div>
            </div>
        ` : '';

        messagesContainer.innerHTML = rendered + typing;
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function updateSendButton() {
        sendButton.disabled = sending || draftInput.value.trim() === '';
    }

    function pageContext() {
        const path = window.location.pathname.toLowerCase();
        const title = document.querySelector('header h2')?.textContent?.trim() || document.title;
        const section = document.querySelector('main h1, main h2')?.textContent?.trim() || title;
        const recordInput = document.querySelector('input[name="id"], input[name="plan_accion_id"], input[name="linea_id"]');

        let module = null;

        if (path.includes('lavadora')) {
            module = 'lavadora';
        } else if (path.includes('pasteurizadora')) {
            module = 'pasteurizadora';
        } else if (path.includes('etiquetadora')) {
            module = 'etiquetadora';
        }

        return {
            page_title: title,
            current_url: window.location.href,
            current_path: path,
            module,
            section,
            entity_label: document.querySelector('main h1')?.textContent?.trim() || null,
            record_id: recordInput && recordInput.value ? Number(recordInput.value) : null,
        };
    }

    async function loadHistory() {
        try {
            const response = await fetch(fetchUrl, {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('history');
            }

            const data = await response.json();
            messages = Array.isArray(data.messages) ? data.messages : [];
            historyLoaded = true;
            renderMessages();
        } catch (error) {
            messages = [];
            historyLoaded = true;
            renderMessages();
        }
    }

    async function sendMessage() {
        const message = draftInput.value.trim();

        if (message === '' || sending) {
            return;
        }

        setSendingState(true);
        renderMessages();

        try {
            const response = await fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    message,
                    page_context: pageContext(),
                }),
            });

            if (!response.ok) {
                throw new Error('send');
            }

            const data = await response.json();

            if (data.user_message) {
                messages.push(data.user_message);
            }

            if (data.message) {
                messages.push(data.message);
            }

            draftInput.value = '';
            historyLoaded = true;
        } catch (error) {
            messages.push({
                id: 'assistant-error-' + Date.now(),
                role: 'assistant',
                content: 'No pude responder en este momento. Intenta de nuevo en unos segundos.',
                metadata: {
                    fallback: true,
                    error: true,
                },
            });
        } finally {
            setSendingState(false);
            renderMessages();
            updateSendButton();
        }
    }

    async function clearHistory() {
        let confirmed = window.confirm('Se borraran los mensajes guardados de este chat.');

        if (window.Swal) {
            const result = await window.Swal.fire({
                title: 'Limpiar historial',
                text: 'Se borraran los mensajes guardados de este chat.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Si, limpiar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#94a3b8',
            });

            confirmed = result.isConfirmed;
        }

        if (!confirmed) {
            return;
        }

        try {
            await fetch(clearUrl, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            messages = [];
            historyLoaded = true;
            renderMessages();
        } catch (error) {
            //
        }
    }

    function openPanel() {
        panel.classList.remove('hidden');
        panel.classList.add('flex');
        toggleButton.classList.add('hidden');

        if (!historyLoaded) {
            loadHistory();
        } else {
            renderMessages();
        }
    }

    function closePanel() {
        panel.classList.add('hidden');
        panel.classList.remove('flex');
        toggleButton.classList.remove('hidden');
    }

    toggleButton.addEventListener('click', openPanel);
    closeButton?.addEventListener('click', closePanel);
    clearButton?.addEventListener('click', clearHistory);
    sendButton.addEventListener('click', sendMessage);
    draftInput.addEventListener('input', updateSendButton);
    draftInput.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });

    renderMessages();
    updateSendButton();
})();
</script>
