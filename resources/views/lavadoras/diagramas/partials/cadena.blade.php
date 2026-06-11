@php
    $paths = $paths ?? [];
    $shapePath = $shapePath ?? null;
    $centerPaths = $centerPaths ?? [];
    $chainTransform = $chainTransform ?? null;
    $patternId = $patternId ?? ('patron-cadena-' . uniqid());
    $metalGradientId = $patternId . '-metal';
    $shadowFilterId = $patternId . '-shadow';
    $clipPathId = $patternId . '-clip';
@endphp

@if ($shapePath)
    <defs>
        <linearGradient id="{{ $metalGradientId }}" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#b0b8bb" />
            <stop offset="28%" stop-color="#747e82" />
            <stop offset="58%" stop-color="#3f484c" />
            <stop offset="100%" stop-color="#8a9497" />
        </linearGradient>

        <filter id="{{ $shadowFilterId }}" x="-4%" y="-8%" width="108%" height="116%">
            <feDropShadow dx="0" dy="1.6" stdDeviation="1.2" flood-color="#000000" flood-opacity="0.65" />
        </filter>

        <clipPath id="{{ $clipPathId }}">
            <path @if($chainTransform) transform="{{ $chainTransform }}" @endif d="{{ $shapePath }}" fill-rule="evenodd" clip-rule="evenodd" />
        </clipPath>
    </defs>

    <g class="cadena-grupo cadena-exacta-grupo" aria-label="Recorrido exacto animado de cadena" filter="url(#{{ $shadowFilterId }})">
        <g @if($chainTransform) transform="{{ $chainTransform }}" @endif>
            <path class="cadena-exacta-sombra" d="{{ $shapePath }}" fill-rule="evenodd" />
            <path class="cadena-exacta-cuerpo" d="{{ $shapePath }}" fill-rule="evenodd" style="fill: url(#{{ $metalGradientId }});" />
            <path class="cadena-exacta-borde" d="{{ $shapePath }}" fill-rule="evenodd" />
        </g>

        <g clip-path="url(#{{ $clipPathId }})" @if($chainTransform) transform="{{ $chainTransform }}" @endif>
            @foreach ($centerPaths as $path)
                <path class="cadena-exacta-eslabon-sombra" d="{{ $path }}" />
                <path class="cadena-exacta-eslabon-luz" d="{{ $path }}" />
                <path class="cadena-exacta-animada" d="{{ $path }}" />
            @endforeach
        </g>
    </g>
@else
    <g class="cadena-grupo" aria-label="Recorrido animado de cadena">
        @foreach ($paths as $index => $path)
            @php
                $d = is_array($path) ? ($path['d'] ?? '') : $path;
                $class = is_array($path) ? ($path['class'] ?? '') : '';
            @endphp

            <path class="cadena-sombra {{ $class }}" d="{{ $d }}" />
            <path class="cadena-cuerpo {{ $class }}" d="{{ $d }}" />
            <path class="cadena-animada {{ $class }}" d="{{ $d }}" />
            <path class="cadena-brillo {{ $class }}" d="{{ $d }}" />
        @endforeach
    </g>
@endif
