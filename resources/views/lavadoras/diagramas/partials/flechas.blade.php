@php
    $flechas = $flechas ?? [];
    $markerId = $markerId ?? 'flecha-verde';
@endphp

<defs>
    <marker
        id="{{ $markerId }}"
        markerWidth="12"
        markerHeight="12"
        refX="10"
        refY="6"
        orient="auto"
        markerUnits="strokeWidth"
    >
        <path d="M 0 0 L 12 6 L 0 12 z" class="flecha-punta" />
    </marker>
</defs>

<g class="flechas-direccion" aria-label="Flechas de direccion">
    @foreach ($flechas as $flecha)
        @if (($flecha['type'] ?? 'line') === 'polyline')
            <polyline
                class="flecha-direccion"
                points="{{ $flecha['points'] }}"
                marker-end="url(#{{ $markerId }})"
            />
        @else
            <line
                class="flecha-direccion"
                x1="{{ $flecha['x1'] }}"
                y1="{{ $flecha['y1'] }}"
                x2="{{ $flecha['x2'] }}"
                y2="{{ $flecha['y2'] }}"
                marker-end="url(#{{ $markerId }})"
            />
        @endif
    @endforeach
</g>
