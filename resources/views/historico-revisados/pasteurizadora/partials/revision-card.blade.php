@php
    $nivelLabel = $nivelData['label'] ?? 'Sin nivel';
    $ladoLabel = $ladoData['label'] ?? null;
    $mostrarLado = (bool) ($componenteData['mostrar_lado'] ?? false) && filled($ladoLabel);
    $mostrarNivel = (bool) ($componenteData['mostrar_nivel'] ?? true);
    $revisadasLista = collect($componenteData['revisadas_lista'] ?? [])->map(fn ($numero) => (int) $numero)->all();
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
            <span>Módulo {{ $moduloData['numero'] }}</span>
            @if($mostrarNivel)
                <span>{{ $nivelLabel }}</span>
            @else
                <span>Todo el modulo</span>
            @endif
            @if($mostrarLado)
                <span>{{ $ladoLabel }}</span>
            @endif
        </div>
    </div>

    <div class="modal-review-progress">
        <div class="modal-level-component-progress-meta">
            <span>Avance de revisión</span>
            <span>{{ $componenteData['revisadas'] }}/{{ $componenteData['total'] }}</span>
        </div>
        <div class="progress-container" style="height: 18px;">
            <span class="progress-label">{{ $componenteData['porcentaje'] }}%</span>
            <div class="progress-bar bg-{{ $componenteData['color'] }}" style="width: {{ $componenteData['porcentaje'] }}%;"></div>
        </div>
    </div>

    @if($revisadasLista !== [])
        <div class="modal-review-numbers">
            <span class="modal-review-numbers-label">Números revisados</span>
            <div class="modal-review-number-list">
                @foreach($revisadasLista as $numero)
                    <span class="modal-review-number">{{ $numero }}</span>
                @endforeach
            </div>
        </div>
    @endif
</div>
