// Defer only Canvas construction. Keep tables, totals and controls available immediately.
export function createVisibleChartRenderer(environment = window) {
    const pending = new Map();
    let observer;
    const flush = element => {
        const render = pending.get(element);
        if (!render) return;
        pending.delete(element);
        observer?.unobserve(element);
        render();
    };
    environment.addEventListener('beforeprint', () => [...pending.keys()].forEach(flush));
    const schedule = (element, render) => {
        if (!element || typeof environment.IntersectionObserver !== 'function' || environment.matchMedia?.('print').matches) {
            render();
            return;
        }
        const bounds = element.getBoundingClientRect();
        if (bounds.bottom >= -250 && bounds.top <= environment.innerHeight + 250) {
            pending.delete(element);
            observer?.unobserve(element);
            render();
            return;
        }
        observer ??= new environment.IntersectionObserver(entries => {
            entries.filter(entry => entry.isIntersecting).forEach(entry => flush(entry.target));
        }, { rootMargin: '250px' });
        pending.set(element, render); // A newer filter replaces the pending chart configuration.
        observer.observe(element);
    };
    schedule.cancel = element => {
        pending.delete(element);
        observer?.unobserve(element);
    };
    return schedule;
}
