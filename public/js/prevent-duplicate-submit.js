(() => {
    const pending = new Map();

    // Bubble after each form's existing photo and side validators.
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form.matches?.('form[data-prevent-duplicate-submit]') || event.defaultPrevented) return;
        if (pending.has(form)) {
            event.preventDefault();
            return;
        }
        const buttons = [...form.querySelectorAll('button[type="submit"], input[type="submit"]')];
        const loading = form.dataset?.submitLoading === 'true';
        pending.set(form, { busy: form.getAttribute('aria-busy'), buttons: buttons.map((button) => ({
            button, disabled: button.disabled, html: loading ? button.innerHTML : null, classes: loading ? button.className : null
        })) });
        form.setAttribute('aria-busy', 'true');
        buttons.forEach((button) => {
            button.disabled = true;
            if (loading) {
                button.classList.add('opacity-75', 'cursor-wait');
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
            }
        });
    });

    window.addEventListener('pageshow', () => {
        pending.forEach(({ busy, buttons }, form) => {
            busy === null ? form.removeAttribute('aria-busy') : form.setAttribute('aria-busy', busy);
            buttons.forEach(({ button, disabled, html, classes }) => {
                button.disabled = disabled;
                if (html !== null) { button.innerHTML = html; button.className = classes; }
            });
        });
        pending.clear();
    });
})();
