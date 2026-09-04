@php
    $lineaValor = $linea ?? null;
    $lineaNombreDiagrama = trim((string) ($lineaNombre ?? ''));
    if ($lineaNombreDiagrama === '') {
        $lineaNombreDiagrama = is_object($lineaValor)
            ? (string) ($lineaValor->nombre ?? '')
            : (string) $lineaValor;
    }
    $lineaNombreDiagrama = trim($lineaNombreDiagrama) !== '' ? trim($lineaNombreDiagrama) : 'Linea sin asignar';
    $lineaCodigoDiagrama = \App\Support\EtiquetadoraCatalog::normalizarCodigoLinea($lineaValor ?: $lineaNombreDiagrama) ?? 'NA';
    $presentacionesDiagrama = collect(\App\Support\EtiquetadoraCatalog::presentacionesPorLinea($lineaValor ?: $lineaNombreDiagrama))->values();

    if ($presentacionesDiagrama->isEmpty()) {
        $presentacionesDiagrama = collect([[
            'label' => 'Etiqueta L-' . $lineaCodigoDiagrama,
            'image' => null,
            'botella' => ['forma' => 'longneck', 'tono' => 'amber', 'tapa' => 'silver', 'escala' => 1],
        ]]);
    }

    $totalPresentacionesDiagrama = max(1, $presentacionesDiagrama->count());
    $totalBotellasFlujoDiagrama = $totalPresentacionesDiagrama === 1
        ? 3
        : ($totalPresentacionesDiagrama === 2 ? 4 : $totalPresentacionesDiagrama);
    $duracionProcesoDiagrama = 12.0;
    $retardoZonaEtiquetado = $duracionProcesoDiagrama * 0.42;
    $intervaloAnimacion = $duracionProcesoDiagrama / $totalBotellasFlujoDiagrama;
    $usaCarruselEtiquetas = $totalPresentacionesDiagrama > 1;
    $presentacionesCabezalDiagrama = $presentacionesDiagrama;
    $intervaloCarruselEtiquetas = $intervaloAnimacion;
    $duracionCarruselEtiquetas = number_format($totalPresentacionesDiagrama * $intervaloCarruselEtiquetas, 2, '.', '');
@endphp

<section class="etq-process-diagram"
         data-etq-process-line="{{ $lineaNombreDiagrama }}"
         data-etq-process-code="{{ $lineaCodigoDiagrama }}"
         data-etq-presentations-count="{{ $totalPresentacionesDiagrama }}"
         data-etq-flow-count="{{ $totalBotellasFlujoDiagrama }}"
         style="--etq-process-duration: {{ number_format($duracionProcesoDiagrama, 2, '.', '') }}s"
         aria-label="Diagrama del proceso de etiquetado para {{ $lineaNombreDiagrama }}">
    <div class="etq-process-canvas" aria-hidden="true">
        <div class="etq-process-belt"></div>
        <div class="etq-process-arrow etq-process-arrow--in"></div>
        <div class="etq-process-arrow etq-process-arrow--out"></div>

        <div class="etq-process-machine">
            <div class="etq-process-machine-top">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="etq-process-machine-window">
                <span class="etq-process-machine-roller etq-process-machine-roller--left"></span>
                <span class="etq-process-machine-roller etq-process-machine-roller--right"></span>
                <div class="etq-process-label-head {{ $usaCarruselEtiquetas ? 'etq-process-label-head--carousel' : 'etq-process-label-head--static' }}"
                     data-etq-label-count="{{ min(4, $totalPresentacionesDiagrama) }}"
                     @if($usaCarruselEtiquetas) style="--etq-carousel-duration: {{ $duracionCarruselEtiquetas }}s" @endif>
                    <div class="etq-process-label-head-stack">
                        @forelse($presentacionesCabezalDiagrama as $indiceEtiqueta => $presentacion)
                            <span class="etq-process-label-head-item" @if($usaCarruselEtiquetas) style="--etq-label-delay: {{ number_format($retardoZonaEtiquetado + ($indiceEtiqueta * $intervaloCarruselEtiquetas), 2, '.', '') }}s" @endif>
                                @if(!empty($presentacion['image']))
                                    <img src="{{ asset('images/Etiquetas/' . $presentacion['image']) }}"
                                         alt="{{ $presentacion['label'] }}"
                                         class="etq-process-label-head-image">
                                @else
                                    <i class="fas fa-tag"></i>
                                @endif
                            </span>
                        @empty
                            <span class="etq-process-label-head-item" aria-hidden="true">
                                <i class="fas fa-tag"></i>
                            </span>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="etq-process-machine-base"></div>
        </div>

        @for($indiceFlujo = 0; $indiceFlujo < $totalBotellasFlujoDiagrama; $indiceFlujo++)
            @php
                $presentacion = $presentacionesDiagrama->get($indiceFlujo % $totalPresentacionesDiagrama);
            @endphp
            @include('etiquetadora.analisis-etiquetadora.partials.botella', [
                'presentacion' => $presentacion,
                'lineaCodigo' => $lineaCodigoDiagrama,
                'labeled' => false,
                'moving' => true,
                'index' => $indiceFlujo,
                'delay' => $indiceFlujo * $intervaloAnimacion,
            ])
        @endfor
    </div>
</section>
