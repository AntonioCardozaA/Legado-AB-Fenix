@php
    $presentacionBotella = $presentacion ?? [];
    $datosBotella = $presentacionBotella['botella'] ?? [];
    $formaBotella = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($datosBotella['forma'] ?? 'longneck'))) ?: 'longneck';
    $tonoBotella = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($datosBotella['tono'] ?? 'amber'))) ?: 'amber';
    $tapaBotella = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($datosBotella['tapa'] ?? 'silver'))) ?: 'silver';
    $escalaBotella = is_numeric($datosBotella['escala'] ?? null)
        ? max(0.72, min(1.2, (float) $datosBotella['escala']))
        : 1.0;
    $indiceBotella = (int) ($index ?? 0);
    $retardoBotella = max(0, (float) ($delay ?? 0));
    $etiquetada = (bool) ($labeled ?? false);
    $animada = (bool) ($moving ?? false);
    $lineaCodigoBotella = (string) ($lineaCodigo ?? 'NA');
    $labelBotella = (string) ($presentacionBotella['label'] ?? ('Etiqueta L-' . $lineaCodigoBotella));
    $imagenEtiqueta = (string) ($presentacionBotella['image'] ?? '');
    $imagenBotellaReal = (string) ($datosBotella['image'] ?? '');
    $imagenBotellaEtiquetada = (string) ($datosBotella['image_labeled'] ?? '');
    $labelWidth = (string) ($datosBotella['label_width'] ?? '56%');
    $labelHeight = (string) ($datosBotella['label_height'] ?? '34%');
    $labelBottom = (string) ($datosBotella['label_bottom'] ?? '20%');
    $labelLeft = (string) ($datosBotella['label_left'] ?? '50%');
    $labelCurveInset = (string) ($datosBotella['label_curve_inset'] ?? '8%');
    $labelScaleX = (string) ($datosBotella['label_scale_x'] ?? '0.9');
    $labelRotateY = (string) ($datosBotella['label_rotate_y'] ?? '0deg');
    $labelTranslateY = (string) ($datosBotella['label_translate_y'] ?? '0px');
    $labelFit = preg_replace('/[^a-z-]/', '', strtolower((string) ($datosBotella['label_fit'] ?? 'fill'))) ?: 'fill';
    $botellaEtiquetadaScale = (string) ($datosBotella['labeled_scale'] ?? '1');
    $botellaEtiquetadaX = (string) ($datosBotella['labeled_translate_x'] ?? '0px');
    $botellaEtiquetadaY = (string) ($datosBotella['labeled_translate_y'] ?? '0px');
    $usaBotellaReal = $imagenBotellaReal !== '';
    $usaBotellaEtiquetada = $imagenBotellaEtiquetada !== '' && ($etiquetada || $animada);
    $clasesBotella = [
        'etq-process-bottle',
        $usaBotellaReal ? 'etq-process-bottle--photo' : '',
        'etq-process-bottle--' . $formaBotella,
        'etq-process-bottle--' . $tonoBotella,
        'etq-process-bottle--cap-' . $tapaBotella,
        ($etiquetada || $animada) ? 'etq-process-bottle--labeled' : 'etq-process-bottle--empty',
        $animada ? 'etq-process-bottle--moving' : '',
    ];
    $estilosBotella = [
        '--etq-bottle-scale: ' . number_format($escalaBotella, 2, '.', ''),
        '--etq-photo-label-width: ' . $labelWidth,
        '--etq-photo-label-height: ' . $labelHeight,
        '--etq-photo-label-bottom: ' . $labelBottom,
        '--etq-photo-label-left: ' . $labelLeft,
        '--etq-photo-label-curve-inset: ' . $labelCurveInset,
        '--etq-photo-label-scale-x: ' . $labelScaleX,
        '--etq-photo-label-rotate-y: ' . $labelRotateY,
        '--etq-photo-label-translate-y: ' . $labelTranslateY,
        '--etq-photo-label-fit: ' . $labelFit,
        '--etq-labeled-bottle-scale: ' . $botellaEtiquetadaScale,
        '--etq-labeled-bottle-x: ' . $botellaEtiquetadaX,
        '--etq-labeled-bottle-y: ' . $botellaEtiquetadaY,
    ];
    $clasesImagenBase = [
        'etq-process-bottle-photo-base',
        $usaBotellaEtiquetada ? 'etq-process-bottle-photo-base--under-labeled' : '',
        ($usaBotellaEtiquetada && $animada) ? 'etq-process-bottle-photo-base--animated' : '',
    ];
    $clasesImagenEtiquetada = [
        'etq-process-bottle-photo-labeled',
        $animada ? 'etq-process-bottle-photo-labeled--animated' : '',
    ];

    if ($animada) {
        $estilosBotella[] = '--etq-delay: ' . number_format($retardoBotella, 2, '.', '') . 's';
    }
@endphp

<div class="{{ trim(implode(' ', array_filter($clasesBotella))) }}"
     style="{{ implode('; ', $estilosBotella) }}"
     data-etq-sequence="{{ $indiceBotella + 1 }}"
     data-etq-presentation="{{ $labelBotella }}"
     data-etq-shape="{{ $formaBotella }}"
     data-etq-tone="{{ $tonoBotella }}"
     data-etq-labeled="{{ ($etiquetada || $animada) ? 'true' : 'false' }}">
    @if($usaBotellaReal)
        <span class="etq-process-bottle-photo-shell">
            <img src="{{ asset('images/Etiquetas/' . $imagenBotellaReal) }}"
                 alt="Botella {{ $labelBotella }}"
                 class="{{ trim(implode(' ', array_filter($clasesImagenBase))) }}">
            @if($usaBotellaEtiquetada)
                <img src="{{ asset('images/Etiquetas/' . $imagenBotellaEtiquetada) }}"
                     alt="Botella {{ $labelBotella }} etiquetada"
                     class="{{ trim(implode(' ', array_filter($clasesImagenEtiquetada))) }}">
            @elseif($etiquetada || $animada)
                <span class="etq-process-photo-label {{ $animada ? 'etq-process-bottle-label--animated' : '' }}">
                    @if($imagenEtiqueta !== '')
                        <img src="{{ asset('images/Etiquetas/' . $imagenEtiqueta) }}"
                             alt="{{ $labelBotella }}">
                    @else
                        <span aria-hidden="true">
                            <i class="fas fa-tag"></i>
                        </span>
                    @endif
                </span>
            @endif
        </span>
    @else
        <span class="etq-process-bottle-cap"></span>
        <span class="etq-process-bottle-neck"></span>
        <span class="etq-process-bottle-body">
            @if($etiquetada || $animada)
                <span class="etq-process-bottle-label {{ $animada ? 'etq-process-bottle-label--animated' : '' }}">
                    @if($imagenEtiqueta !== '')
                        <img src="{{ asset('images/Etiquetas/' . $imagenEtiqueta) }}"
                             alt="{{ $labelBotella }}">
                    @else
                        <span aria-hidden="true">
                            <i class="fas fa-tag"></i>
                        </span>
                    @endif
                </span>
            @endif
        </span>
    @endif
</div>
