<div
    id="assistant-chat-widget"
    data-fetch-url="{{ route('assistant-chat.index', [], false) }}"
    data-send-url="{{ route('assistant-chat.store', [], false) }}"
    data-conversation-url-template="{{ route('assistant-chat.conversations.show', ['conversation' => '__CONVERSATION__'], false) }}"
    data-conversation-delete-url-template="{{ route('assistant-chat.conversations.destroy', ['conversation' => '__CONVERSATION__'], false) }}"
    data-csrf-token="{{ csrf_token() }}"
    class="fixed bottom-4 right-4 z-[90] sm:bottom-6 sm:right-6"
>
    <div
        id="assistant-chat-panel"
        class="mb-3 hidden h-[70vh] w-[calc(100vw-2rem)] max-w-md flex-col overflow-hidden rounded-[1.6rem] border border-slate-900/10 bg-white shadow-2xl shadow-slate-950/20 sm:h-[38rem]"
    >
        <div class="assistant-chat-header border-b border-white/10 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 px-5 py-4 text-white">
            <div class="flex items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="assistant-chat-brandmark inline-flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white ring-1 ring-amber-300/40">
                        <img
                            src="{{ asset('images/abfenix-ai-chat.png') }}"
                            alt="ABFenix.ai"
                            class="h-full w-full object-contain"
                            style="transform: scale(2.2);"
                        >
                    </span>
                    <div class="flex h-12 min-w-0 flex-col justify-center">
                        <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-300">ABFenix.ai</p>
                        <p id="assistant-chat-active-title" class="mt-0.5 max-w-[11rem] truncate text-xs font-semibold text-slate-400 sm:max-w-[14rem]">Nuevo chat</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        id="assistant-chat-new"
                        type="button"
                        class="assistant-chat-header-action inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white transition hover:bg-white/12"
                        title="Nuevo chat"
                        aria-label="Nuevo chat"
                    >
                        <i class="fas fa-pen-to-square text-sm"></i>
                    </button>
                    <div class="relative">
                        <button
                            id="assistant-chat-history"
                            type="button"
                            class="assistant-chat-header-action inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white transition hover:bg-white/12"
                            title="Historial"
                            aria-label="Historial de conversaciones"
                        >
                            <i class="fas fa-clock-rotate-left text-sm"></i>
                        </button>
                        <div
                            id="assistant-chat-history-menu"
                            class="assistant-chat-history-menu absolute right-0 top-11 z-20 hidden w-72 max-w-[calc(100vw-3rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-800 shadow-2xl shadow-slate-950/20"
                        >
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Recientes</p>
                            </div>
                            <div id="assistant-chat-conversations" class="max-h-80 overflow-y-auto py-2"></div>
                        </div>
                    </div>
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
                    <p class="text-[11px] text-slate-400">Enter envía | Shift + Enter agrega salto</p>
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

    #assistant-chat-widget .assistant-chat-history-menu {
        max-width: min(18rem, calc(100vw - 3rem));
    }

    #assistant-chat-widget .assistant-chat-conversation {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px 6px 16px;
        transition: background 160ms ease, color 160ms ease;
    }

    #assistant-chat-widget .assistant-chat-conversation:hover {
        background: #f8fafc;
    }

    #assistant-chat-widget .assistant-chat-conversation--active {
        background: #fff7ed;
        color: #9a3412;
    }

    #assistant-chat-widget .assistant-chat-conversation--active:hover {
        background: #ffedd5;
    }

    #assistant-chat-widget .assistant-chat-conversation-title {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 13px;
        font-weight: 800;
    }

    #assistant-chat-widget .assistant-chat-conversation-date {
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
    }

    #assistant-chat-widget .assistant-chat-conversation-open {
        min-width: 0;
        flex: 1 1 auto;
        display: grid;
        gap: 2px;
        padding: 4px 0;
        text-align: left;
    }

    #assistant-chat-widget .assistant-chat-conversation-delete {
        flex: 0 0 auto;
        width: 1.9rem;
        height: 1.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        color: #94a3b8;
        transition: background 160ms ease, color 160ms ease;
    }

    #assistant-chat-widget .assistant-chat-conversation-delete:hover {
        background: #fee2e2;
        color: #b91c1c;
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

    #assistant-chat-widget .assistant-chat-artifacts {
        margin-top: 12px;
        display: grid;
        gap: 10px;
    }

    #assistant-chat-widget .assistant-chat-artifact-image {
        display: block;
        width: 100%;
        max-height: 220px;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: #ffffff;
        object-fit: contain;
    }

    #assistant-chat-widget .assistant-chat-artifact-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: fit-content;
        max-width: 100%;
        border-radius: 999px;
        border: 1px solid rgba(22, 163, 74, 0.28);
        background: #f0fdf4;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
        color: #166534;
        text-decoration: none;
    }

    #assistant-chat-widget .assistant-chat-artifact-link:hover {
        background: #dcfce7;
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
    const conversationUrlTemplate = widget.dataset.conversationUrlTemplate || '';
    const conversationDeleteUrlTemplate = widget.dataset.conversationDeleteUrlTemplate || '';
    const csrfToken = widget.dataset.csrfToken || '';

    const panel = document.getElementById('assistant-chat-panel');
    const toggleButton = document.getElementById('assistant-chat-toggle');
    const closeButton = document.getElementById('assistant-chat-close');
    const newChatButton = document.getElementById('assistant-chat-new');
    const historyButton = document.getElementById('assistant-chat-history');
    const historyMenu = document.getElementById('assistant-chat-history-menu');
    const conversationsContainer = document.getElementById('assistant-chat-conversations');
    const activeTitle = document.getElementById('assistant-chat-active-title');
    const messagesContainer = document.getElementById('assistant-chat-messages');
    const draftInput = document.getElementById('assistant-chat-draft');
    const sendButton = document.getElementById('assistant-chat-send');

    if (!panel || !toggleButton || !messagesContainer || !draftInput || !sendButton) {
        return;
    }

    let sending = false;
    let historyLoaded = false;
    let messages = [];
    let conversations = [];
    let activeConversationId = null;
    const assistantBrand = 'ABFenix.ai';

    const introMessage = {
        id: 'assistant-intro',
        role: 'assistant',
        content: 'Hola, soy su asistente ABFenix.ai. ¿En qué puedo ayudarte?',
        metadata: {},
    };

    introMessage.content = 'Hola, soy su asistente ABFenix.ai. \u00bfEn qu\u00e9 puedo ayudarte?';

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

    function safeExternalUrl(value) {
        const url = String(value || '').trim();

        return /^https?:\/\//i.test(url) ? url : '';
    }

    function renderSources(message) {
        return message.metadata.sources
            .slice(0, 2)
            .map(source => {
                const label = escapeHtml(source.reference || source.type || 'Referencia');
                const url = safeExternalUrl(source.url);

                if (!url) {
                    return label;
                }

                return `<a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" class="underline decoration-dotted underline-offset-2">${label}</a>`;
            })
            .join(' | ');
    }

    function hasArtifacts(message) {
        return Array.isArray(message?.metadata?.artifacts) && message.metadata.artifacts.length > 0;
    }

    function artifactUrl(artifact, download) {
        const url = String(artifact?.url || '');

        if (!download || url === '') {
            return url;
        }

        return url + (url.includes('?') ? '&' : '?') + 'download=1';
    }

    function renderArtifacts(message) {
        if (!hasArtifacts(message)) {
            return '';
        }

        const items = message.metadata.artifacts.map((artifact) => {
            const url = artifactUrl(artifact, false);
            const downloadUrl = artifactUrl(artifact, true);
            const mimeType = String(artifact?.mime_type || '');
            const kind = String(artifact?.kind || '');
            const label = artifact?.label || artifact?.file_name || 'Adjunto';

            if (url === '') {
                return '';
            }

            if (kind === 'image' || kind === 'svg' || mimeType.startsWith('image/')) {
                return `
                    <a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" title="${escapeHtml(label)}">
                        <img class="assistant-chat-artifact-image" src="${escapeHtml(url)}" alt="${escapeHtml(label)}">
                    </a>
                `;
            }

            const icon = kind === 'svg' || mimeType === 'image/svg+xml' ? 'fa-file-image' : 'fa-file-excel';

            return `
                <a class="assistant-chat-artifact-link" href="${escapeHtml(downloadUrl)}" download>
                    <i class="fas ${icon}"></i>
                    <span>${escapeHtml(label)}</span>
                </a>
            `;
        }).join('');

        return `<div class="assistant-chat-artifacts">${items}</div>`;
    }

    function currentMessages() {
        return messages.length > 0 ? messages : [introMessage];
    }

    function conversationUrl(id) {
        return conversationUrlTemplate.replace('__CONVERSATION__', encodeURIComponent(String(id)));
    }

    function conversationDeleteUrl(id) {
        return conversationDeleteUrlTemplate.replace('__CONVERSATION__', encodeURIComponent(String(id)));
    }

    function updateActiveTitle() {
        const active = conversations.find(conversation => Number(conversation.id) === Number(activeConversationId));
        const title = active ? active.title : 'Nuevo chat';

        if (activeTitle) {
            activeTitle.textContent = title || 'Nuevo chat';
        }
    }

    function renderConversations() {
        if (!conversationsContainer) {
            return;
        }

        if (!Array.isArray(conversations) || conversations.length === 0) {
            conversationsContainer.innerHTML = `
                <div class="px-4 py-6 text-center text-sm font-semibold text-slate-500">
                    A\u00fan no hay conversaciones guardadas.
                </div>
            `;
            updateActiveTitle();
            return;
        }

        conversationsContainer.innerHTML = conversations.map(conversation => {
            const isActive = Number(conversation.id) === Number(activeConversationId);
            const activeClass = isActive ? 'assistant-chat-conversation--active' : '';
            const date = conversation.created_at_label || conversation.last_message_at_human || '';

            return `
                <div class="assistant-chat-conversation ${activeClass}" data-conversation-row="${escapeHtml(conversation.id)}">
                    <button
                        type="button"
                        class="assistant-chat-conversation-open"
                        data-conversation-id="${escapeHtml(conversation.id)}"
                        title="${escapeHtml(conversation.title || 'Chat operativo')}"
                    >
                        <span class="assistant-chat-conversation-title">${escapeHtml(conversation.title || 'Chat operativo')}</span>
                        <span class="assistant-chat-conversation-date">${escapeHtml(date)}</span>
                    </button>
                    <button
                        type="button"
                        class="assistant-chat-conversation-delete"
                        data-conversation-delete-id="${escapeHtml(conversation.id)}"
                        title="Eliminar chat"
                        aria-label="Eliminar chat"
                    >
                        <i class="fas fa-trash-can text-[11px]"></i>
                    </button>
                </div>
            `;
        }).join('');

        updateActiveTitle();
    }

    function closeHistoryMenu() {
        historyMenu?.classList.add('hidden');
    }

    function toggleHistoryMenu() {
        historyMenu?.classList.toggle('hidden');
        renderConversations();
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
                        ${hasArtifacts(message) ? renderArtifacts(message) : ''}
                        ${hasSources(message) ? `
                            <p class="assistant-chat-source mt-3 text-[11px] font-medium">
                                Fuentes usadas: ${renderSources(message)}
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

    async function loadHistory(conversationId = null) {
        try {
            const url = conversationId
                ? fetchUrl + (fetchUrl.includes('?') ? '&' : '?') + 'conversation_id=' + encodeURIComponent(String(conversationId))
                : fetchUrl;
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('history');
            }

            const data = await response.json();
            messages = Array.isArray(data.messages) ? data.messages : [];
            conversations = Array.isArray(data.conversations) ? data.conversations : conversations;
            activeConversationId = data.active_conversation_id || data.active_conversation?.id || null;
            historyLoaded = true;
            renderConversations();
            renderMessages();
        } catch (error) {
            messages = [];
            historyLoaded = true;
            renderConversations();
            renderMessages();
        }
    }

    async function loadConversation(conversationId) {
        if (!conversationId || sending || conversationUrlTemplate === '') {
            return;
        }

        try {
            const response = await fetch(conversationUrl(conversationId), {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('conversation');
            }

            const data = await response.json();
            messages = Array.isArray(data.messages) ? data.messages : [];
            activeConversationId = data.conversation?.id || conversationId;
            historyLoaded = true;
            closeHistoryMenu();
            renderConversations();
            renderMessages();
        } catch (error) {
            //
        }
    }

    async function confirmConversationDelete() {
        if (window.Swal) {
            const result = await window.Swal.fire({
                title: 'Eliminar chat',
                text: 'Se eliminar\u00e1 esta conversaci\u00f3n del historial.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'S\u00ed, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#94a3b8',
            });

            return result.isConfirmed;
        }

        return window.confirm('Se eliminar\u00e1 esta conversaci\u00f3n del historial.');
    }

    async function deleteConversation(conversationId) {
        if (!conversationId || sending || conversationDeleteUrlTemplate === '') {
            return;
        }

        const confirmed = await confirmConversationDelete();

        if (!confirmed) {
            return;
        }

        try {
            const wasActive = Number(conversationId) === Number(activeConversationId);
            const response = await fetch(conversationDeleteUrl(conversationId), {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                throw new Error('delete-conversation');
            }

            const data = await response.json();
            conversations = Array.isArray(data.conversations) ? data.conversations : [];
            activeConversationId = wasActive
                ? (data.active_conversation_id || null)
                : activeConversationId;
            messages = wasActive
                ? (Array.isArray(data.messages) ? data.messages : [])
                : messages;

            closeHistoryMenu();
            renderConversations();
            renderMessages();
        } catch (error) {
            //
        }
    }

    function startNewChat() {
        if (sending) {
            return;
        }

        activeConversationId = null;
        messages = [];
        historyLoaded = true;
        closeHistoryMenu();
        renderConversations();
        renderMessages();
        updateSendButton();
        draftInput.focus();
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
                    conversation_id: activeConversationId,
                    page_context: pageContext(),
                }),
            });

            if (!response.ok) {
                throw new Error('send');
            }

            const data = await response.json();

            if (data.conversation) {
                activeConversationId = data.conversation.id;
            }

            if (Array.isArray(data.conversations)) {
                conversations = data.conversations;
            }

            if (data.user_message) {
                messages.push(data.user_message);
            }

            if (data.message) {
                messages.push(data.message);
            }

            draftInput.value = '';
            historyLoaded = true;
            renderConversations();
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
    newChatButton?.addEventListener('click', startNewChat);
    historyButton?.addEventListener('click', toggleHistoryMenu);
    conversationsContainer?.addEventListener('click', event => {
        const target = event.target instanceof Element ? event.target : event.target?.parentElement;
        const deleteButton = target?.closest('[data-conversation-delete-id]');

        if (deleteButton) {
            event.stopPropagation();
            deleteConversation(deleteButton.dataset.conversationDeleteId);
            return;
        }

        const button = target?.closest('[data-conversation-id]');

        if (!button) {
            return;
        }

        loadConversation(button.dataset.conversationId);
    });
    document.addEventListener('click', event => {
        if (!historyMenu || historyMenu.classList.contains('hidden')) {
            return;
        }

        if (historyMenu.contains(event.target) || historyButton?.contains(event.target)) {
            return;
        }

        closeHistoryMenu();
    });
    sendButton.addEventListener('click', sendMessage);
    draftInput.addEventListener('input', updateSendButton);
    draftInput.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });

    renderMessages();
    renderConversations();
    updateSendButton();
})();
</script>
