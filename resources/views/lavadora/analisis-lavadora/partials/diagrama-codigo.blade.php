@php
    $lineaNombre = $lineaSeleccionada->nombre ?? null;

    $grupoDiagrama = match ($lineaNombre) {
        'L-04', 'L-09' => 'l04-l09',
        'L-05', 'L-12', 'L-13' => 'l05-l12-l13',
        'L-06', 'L-07' => 'l06-l07',
        default => null,
    };
@endphp

<div class="lavadora-diagrama-codigo">
    @if ($grupoDiagrama)
        @include('lavadoras.diagramas.partials.embebido', [
            'lineaNombre' => $lineaNombre,
            'grupo' => $grupoDiagrama,
            'monitorAlertas' => $monitorAlertas ?? [],
        ])
    @else
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-800">
            <div class="flex items-center gap-3">
                <i class="fas fa-triangle-exclamation"></i>
                <p class="font-semibold">
                    Todavia no hay diagrama SVG configurado para {{ $lineaNombre ?? 'esta lavadora' }}.
                </p>
            </div>
        </div>
    @endif
</div>
