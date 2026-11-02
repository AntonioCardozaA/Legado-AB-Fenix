(() => {
    const initial = document.getElementById('userDirectory');
    if (!initial || typeof AbortController !== 'function' || !window.history?.pushState) return;
    const base = new URL(initial.dataset.directoryUrl, location.href);
    let controller, version = 0, pending = false, committedSearch = location.search;
    const fullNavigation = url => location.assign(url);

    window.navigateUserDirectory = async (value, push = true) => {
        const url = new URL(value, location.href);
        if (url.origin !== base.origin || url.pathname !== base.pathname) { fullNavigation(url.href); return; }
        controller?.abort();
        controller = new AbortController();
        const current = ++version;
        pending = true;
        const root = document.getElementById('userDirectory');
        root.setAttribute('aria-busy', 'true');
        document.getElementById('directory-status').textContent = 'Consultando usuarios...';
        try {
            const response = await fetch(url, { signal: controller.signal, cache: 'no-store',
                headers: { 'X-Directory-Fragment': '1', Accept: 'text/html' } });
            if (current !== version) return;
            if (response.redirected || !response.ok) throw new Error('Full document required.');
            const html = await response.text();
            if (current !== version) return;
            const page = new DOMParser().parseFromString(html, 'text/html');
            const replacement = page.getElementById('userDirectory');
            if (!replacement || replacement.querySelector('script') || new URL(replacement.dataset.directoryUrl, location.href).href !== base.href) {
                throw new Error('Unexpected directory response.');
            }
            const active = root.contains(document.activeElement) ? document.activeElement : null;
            const focus = active ? { id: active.id, start: active.selectionStart, end: active.selectionEnd } : null;
            root.replaceWith(replacement);
            if (push) history.pushState({ ...history.state, userDirectory: true }, '', url);
            committedSearch = url.search;
            const target = focus?.id ? document.getElementById(focus.id) : replacement;
            if (active) target?.focus({ preventScroll: true });
            if (focus?.start != null && typeof target?.setSelectionRange === 'function') target.setSelectionRange(focus.start, focus.end);
            document.getElementById('directory-status').textContent = 'Directorio actualizado.';
        } catch (error) {
            if (current !== version || error.name === 'AbortError') return;
            root.removeAttribute('aria-busy');
            fullNavigation(url.href);
        } finally {
            if (current === version) pending = false;
        }
    };
    history.replaceState({ ...history.state, userDirectory: true }, '', location.href);
    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest('a');
        if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
        if (!link.closest('#userDirectory [data-directory-pagination], #userDirectory [data-directory-reset]')) return;
        const url = new URL(link.href, location.href);
        if (url.origin !== base.origin || url.pathname !== base.pathname) return;
        event.preventDefault();
        window.navigateUserDirectory(url);
    });
    window.addEventListener('popstate', () => {
        if (location.pathname === base.pathname && (pending || location.search !== committedSearch)) window.navigateUserDirectory(location.href, false);
    });
})();
