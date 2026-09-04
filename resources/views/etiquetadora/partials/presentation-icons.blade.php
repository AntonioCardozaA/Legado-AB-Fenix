@php
    $lineaValor = $linea ?? null;
    $presentaciones = \App\Support\EtiquetadoraCatalog::presentacionesPorLinea($lineaValor);
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
