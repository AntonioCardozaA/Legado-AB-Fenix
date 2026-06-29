document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-diagrama-lavadora]').forEach(function (diagrama) {
        var toggle = diagrama.querySelector('[data-action="toggle-animation"]');
        var reset = diagrama.querySelector('[data-action="reset-animation"]');
        var speed = diagrama.querySelector('[data-action="speed-animation"]');

        if (toggle) {
            toggle.addEventListener('click', function () {
                var paused = diagrama.classList.toggle('is-paused');
                var icon = toggle.querySelector('i');

                toggle.setAttribute('aria-label', paused ? 'Reanudar animacion' : 'Pausar animacion');
                toggle.setAttribute('title', paused ? 'Reanudar animacion' : 'Pausar animacion');

                if (icon) {
                    icon.className = paused ? 'fas fa-play' : 'fas fa-pause';
                }

                diagrama.querySelectorAll('svg').forEach(function (svg) {
                    if (typeof svg.pauseAnimations === 'function' && typeof svg.unpauseAnimations === 'function') {
                        if (paused) {
                            svg.pauseAnimations();
                        } else {
                            svg.unpauseAnimations();
                        }
                    }
                });
            });
        }

        if (reset) {
            reset.addEventListener('click', function () {
                diagrama.classList.remove('is-paused');
                diagrama.querySelectorAll('svg').forEach(function (svg) {
                    if (typeof svg.unpauseAnimations === 'function') {
                        svg.unpauseAnimations();
                    }

                    if (typeof svg.setCurrentTime === 'function') {
                        svg.setCurrentTime(0);
                    }
                });
                diagrama.classList.add('is-resetting');

                window.setTimeout(function () {
                    diagrama.classList.remove('is-resetting');
                }, 30);
            });
        }

        if (speed) {
            speed.addEventListener('input', function () {
                var value = parseFloat(speed.value || '1');
                var chainDuration = Math.max(0.45, 2 / value);
                var sprocketDuration = Math.max(0.45, 2 / value);

                diagrama.style.setProperty('--duracion-cadena', chainDuration + 's');
                diagrama.style.setProperty('--duracion-catarina', sprocketDuration + 's');
            });
        }
    });

    var monitorTooltip = document.createElement('div');
    monitorTooltip.className = 'monitor-industrial-tooltip';
    monitorTooltip.setAttribute('role', 'tooltip');
    document.body.appendChild(monitorTooltip);

    var activeMonitorTarget = null;
    var pinnedMonitorTarget = null;

    function escapeTooltipText(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getMonitorColor(target) {
        if (target.classList.contains('monitor-severity-red')) {
            return '#ef233c';
        }

        if (target.classList.contains('monitor-severity-orange')) {
            return '#ff7a1a';
        }

        if (target.classList.contains('monitor-severity-yellow')) {
            return '#f4c430';
        }

        return '#14b86a';
    }

    function buildMonitorTooltip(target) {
        var kind = target.dataset.monitorKind === 'reductor'
            ? 'Reductor monitoreado'
            : 'Componente afectado';
        var rows = [
            ['Reductor', target.dataset.monitorReductor],
            ['Componente', target.dataset.monitorComponente],
            ['Nivel', target.dataset.monitorDano],
            ['Ultimo analisis', target.dataset.monitorFecha],
            ['Observaciones', target.dataset.monitorObservaciones]
        ];

        monitorTooltip.style.setProperty('--monitor-tooltip-color', getMonitorColor(target));
        monitorTooltip.innerHTML = [
            '<div class="monitor-tooltip-title">' + escapeTooltipText(kind) + '</div>',
            '<div class="monitor-tooltip-grid">',
            rows.map(function (row) {
                return '<div class="monitor-tooltip-row">' +
                    '<span class="monitor-tooltip-label">' + escapeTooltipText(row[0]) + '</span>' +
                    '<span class="monitor-tooltip-value">' + escapeTooltipText(row[1] || '-') + '</span>' +
                    '</div>';
            }).join(''),
            '</div>'
        ].join('');
    }

    function getMonitorTarget(event) {
        return event.target.closest ? event.target.closest('[data-monitor-tooltip]') : null;
    }

    function isTouchLikePointer(event) {
        if (event.pointerType === 'touch' || event.pointerType === 'pen') {
            return true;
        }

        return !event.pointerType &&
            window.matchMedia &&
            window.matchMedia('(hover: none), (pointer: coarse)').matches;
    }

    function positionMonitorTooltip(event) {
        var offset = 14;
        var viewportPadding = 12;
        var rect = monitorTooltip.getBoundingClientRect();
        var left = event.clientX + offset;
        var top = event.clientY + offset;

        if (left + rect.width + viewportPadding > window.innerWidth) {
            left = event.clientX - rect.width - offset;
        }

        if (top + rect.height + viewportPadding > window.innerHeight) {
            top = event.clientY - rect.height - offset;
        }

        monitorTooltip.style.left = Math.max(viewportPadding, left) + 'px';
        monitorTooltip.style.top = Math.max(viewportPadding, top) + 'px';
    }

    function showMonitorTooltip(target, event) {
        if (activeMonitorTarget && activeMonitorTarget !== target) {
            activeMonitorTarget.classList.remove('is-tooltip-active');
        }

        activeMonitorTarget = target;
        buildMonitorTooltip(target);
        monitorTooltip.classList.add('is-visible');
        target.classList.add('is-tooltip-active');
        positionMonitorTooltip(event);
    }

    function hideMonitorTooltip() {
        if (activeMonitorTarget) {
            activeMonitorTarget.classList.remove('is-tooltip-active');
        }

        activeMonitorTarget = null;
        pinnedMonitorTarget = null;
        monitorTooltip.classList.remove('is-visible');
    }

    document.addEventListener('pointerover', function (event) {
        if (isTouchLikePointer(event)) {
            return;
        }

        var target = getMonitorTarget(event);

        if (!target) {
            return;
        }

        pinnedMonitorTarget = null;
        showMonitorTooltip(target, event);
    });

    document.addEventListener('pointermove', function (event) {
        if (activeMonitorTarget && !pinnedMonitorTarget) {
            positionMonitorTooltip(event);
        }
    });

    document.addEventListener('pointerout', function (event) {
        if (!activeMonitorTarget || pinnedMonitorTarget) {
            return;
        }

        if (event.relatedTarget && activeMonitorTarget.contains(event.relatedTarget)) {
            return;
        }

        hideMonitorTooltip();
    });

    document.addEventListener('pointerdown', function (event) {
        var target = getMonitorTarget(event);

        if (!target) {
            if (pinnedMonitorTarget) {
                hideMonitorTooltip();
            }

            return;
        }

        if (!isTouchLikePointer(event)) {
            return;
        }

        pinnedMonitorTarget = target;
        showMonitorTooltip(target, event);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            hideMonitorTooltip();
        }
    });

    window.addEventListener('scroll', hideMonitorTooltip, true);
    window.addEventListener('resize', hideMonitorTooltip);
});
