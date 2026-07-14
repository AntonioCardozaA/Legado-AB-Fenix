@php
    $lineaValor = $linea ?? null;
    $lineaNombre = is_object($lineaValor)
        ? (string) ($lineaValor->nombre ?? '')
        : (string) $lineaValor;

    preg_match('/(\d{1,2})/', $lineaNombre, $coincidenciasLinea);
    $lineaCodigo = isset($coincidenciasLinea[1])
        ? str_pad($coincidenciasLinea[1], 2, '0', STR_PAD_LEFT)
        : null;

    $catalogoPresentaciones = [
        '04' => [
            ['label' => 'Corona Mega', 'image' => 'coronamega.png'],
            ['label' => 'Victoria', 'image' => 'victoriamega.png'],
        ],
        '05' => [
            ['label' => 'Victoria', 'image' => 'victoria1-cuarto.png'],
        ],
        '06' => [
            ['label' => 'Modelo Especial', 'image' => 'Modelo especial.png'],
            ['label' => 'Corona Extra', 'image' => 'corona extra .png'],
            ['label' => 'Modelo Negra', 'image' => 'Modelo negra grande.png'],
            ['label' => 'Bud Light', 'image' => 'budligth grande.png'],
        ],
        '10' => [
            ['label' => 'Barrilito', 'image' => 'Barrilito.png'],
        ],
        '12' => [
            ['label' => 'Modelo Especial', 'image' => 'Modelo especial355ml.png'],
            ['label' => 'Modelito Especial', 'image' => 'Modelito210ml.png'],
            ['label' => 'Modelo Negra', 'image' => 'Negra modelo 355ml.png'],
        ],
        '13' => [
            ['label' => 'Michelob Ultra', 'image' => 'Michelob-ultra.png'],
            ['label' => 'Pacifico Clara', 'image' => 'Pacifico-clara.png'],
        ],
    ];

    $presentaciones = $catalogoPresentaciones[$lineaCodigo] ?? [];
    $size = $size ?? 'sm';
    $showNames = (bool) ($showNames ?? false);
    $limit = isset($limit) ? (int) $limit : null;

    if ($limit) {
        $presentaciones = array_slice($presentaciones, 0, $limit);
    }

    $tituloPresentaciones = collect($presentaciones)->pluck('label')->implode(', ');
@endphp

@if(count($presentaciones) > 0)
    <span class="etq-presentations etq-presentations--{{ $size }}" title="{{ $tituloPresentaciones }}">
        <span class="etq-presentations-icons" aria-label="{{ $tituloPresentaciones }}">
            @foreach($presentaciones as $presentacion)
                <img src="{{ asset('images/Etiquetas/' . $presentacion['image']) }}"
                     alt="{{ $presentacion['label'] }}"
                     title="{{ $presentacion['label'] }}"
                     class="etq-presentation-image">
            @endforeach
        </span>

        @if($showNames)
            <span class="etq-presentations-names">{{ $tituloPresentaciones }}</span>
        @endif
    </span>
@else
    <span class="etq-presentations etq-presentations--{{ $size }}">
        <img src="{{ asset('images/icono-maquina.png') }}" alt="Etiquetadora" class="h-5 w-5 object-contain">
    </span>
@endif
