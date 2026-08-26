@php
    $nivelLabel = $nivelData['label'] ?? 'Sin nivel';
    $ladoLabel = $ladoData['label'] ?? null;
    $mostrarLado = (bool) ($componenteData['mostrar_lado'] ?? false) && filled($ladoLabel);
@endphp

<div class="modal-level-component modal-review-card">
    <div class="modal-review-summary">
        <div class="componente-nombre">
            <div class="componente-imagen">
                <img
                    src="{{ asset('images/componentes-pasteurizadora/' . $componenteData['codigo'] . '.png') }}"
                    alt="{{ $componenteData['nombre'] }}"
                    class="componente-img"
                    onerror="this.src='{{ asset('images/extras/sin imagen.png') }}'">
            </div>
            <span>{{ $componenteData['nombre'] }}</span>
        </div>

        <div class="modal-review-context">
            <span>Modulo {{ $moduloData['numero'] }}</span>
            <span>{{ $nivelLabel }}</span>
            @if($mostrarLado)
                <span>{{ $ladoLabel }}</span>
            @endif
        </div>
    </div>

    <div class="modal-review-progress">
        <div class="modal-level-component-progress-meta">
            <span>Avance de revision</span>
            <span>{{ $componenteData['revisadas'] }}/{{ $componenteData['total'] }}</span>
        </div>
        <div class="progress-container" style="height: 18px;">
            <span class="progress-label">{{ $componenteData['porcentaje'] }}%</span>
            <div class="progress-bar bg-{{ $componenteData['color'] }}" style="width: {{ $componenteData['porcentaje'] }}%;"></div>
        </div>
    </div>
</div>
