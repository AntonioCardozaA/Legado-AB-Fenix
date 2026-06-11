@php
    $red = $red ?? [];
    $x = $red['x'] ?? 0;
    $y = $red['y'] ?? 0;
    $w = $red['w'] ?? 100;
    $h = $red['h'] ?? 100;
    $type = $red['type'] ?? 'ancha';
    $fill = $red['fill'] ?? '#fff56b';
    $label = $red['label'] ?? null;
    $labelX = $red['labelX'] ?? ($x + ($w / 2));
    $labelY = $red['labelY'] ?? ($y - 8);
    $labelAnchor = $red['labelAnchor'] ?? 'middle';
    $bottomLabel = $red['bottomLabel'] ?? null;
    $bottomLabelX = $red['bottomLabelX'] ?? ($x + ($w / 2));
    $bottomLabelY = $red['bottomLabelY'] ?? ($y + $h - 12);

    $points = $red['points'] ?? match ($type) {
        'entrada' => implode(' ', [
            ($x) . ',' . $y,
            ($x + $w - 8) . ',' . $y,
            ($x + $w) . ',' . ($y + 12),
            ($x + $w - 8) . ',' . ($y + $h),
            ($x) . ',' . ($y + $h),
        ]),
        'estrecha' => implode(' ', [
            ($x + 12) . ',' . $y,
            ($x + $w - 12) . ',' . $y,
            ($x + $w) . ',' . ($y + 18),
            ($x + $w - 18) . ',' . ($y + $h - 28),
            ($x + ($w / 2) + 16) . ',' . ($y + $h),
            ($x + ($w / 2) - 16) . ',' . ($y + $h),
            ($x) . ',' . ($y + $h - 28),
            ($x + 8) . ',' . ($y + 18),
        ]),
        'salida' => implode(' ', [
            ($x + 12) . ',' . $y,
            ($x + $w - 8) . ',' . ($y + 8),
            ($x + $w) . ',' . ($y + 12),
            ($x + $w - 24) . ',' . ($y + $h),
            ($x + 38) . ',' . ($y + $h),
            ($x) . ',' . ($y + $h - 28),
        ]),
        'loca' => implode(' ', [
            ($x) . ',' . $y,
            ($x + $w) . ',' . $y,
            ($x + $w) . ',' . ($y + $h),
            ($x + 232) . ',' . ($y + $h),
            ($x + 160) . ',' . ($y + $h - 38),
            ($x) . ',' . ($y + $h - 38),
        ]),
        default => implode(' ', [
            ($x + 10) . ',' . $y,
            ($x + $w - 8) . ',' . $y,
            ($x + $w) . ',' . ($y + 22),
            ($x + $w - 28) . ',' . ($y + $h - 14),
            ($x + $w - 54) . ',' . ($y + $h),
            ($x + 42) . ',' . ($y + $h),
            ($x) . ',' . ($y + $h - 32),
            ($x + 12) . ',' . ($y + 28),
        ]),
    };
@endphp

@if ($label)
    <text class="red-label" x="{{ $labelX }}" y="{{ $labelY }}" text-anchor="{{ $labelAnchor }}">{{ $label }}</text>
@endif

<g class="red-panel red-panel-{{ $type }}">
    <polygon class="zona-panel" points="{{ $points }}" fill="{{ $fill }}" />
    <polygon class="zona-borde" points="{{ $points }}" />

    @if ($type === 'entrada')
        <polyline
            class="linea-interna"
            points="{{ $x + 88 }},{{ $y + 58 }} {{ $x + 78 }},{{ $y + 70 }} {{ $x + 78 }},{{ $y + 112 }} {{ $x + 120 }},{{ $y + 112 }} {{ $x + 120 }},{{ $y + 68 }} {{ $x + 132 }},{{ $y + 58 }}"
        />
        <polyline
            class="linea-interna"
            points="{{ $x + 38 }},{{ $y + $h - 72 }} {{ $x + 84 }},{{ $y + $h - 64 }} {{ $x + 84 }},{{ $y + $h - 50 }} {{ $x + 122 }},{{ $y + $h - 50 }} {{ $x + 122 }},{{ $y + $h - 86 }} {{ $x + 140 }},{{ $y + $h - 102 }}"
        />
        <polyline
            class="linea-interna"
            points="{{ $x + 126 }},{{ $y + 116 }} {{ $x + 150 }},{{ $y + 152 }} {{ $x + 142 }},{{ $y + 205 }} {{ $x + 152 }},{{ $y + 222 }} {{ $x + 139 }},{{ $y + 238 }}"
        />
        <rect class="ranura-interna" x="{{ $x + 112 }}" y="{{ $y + 36 }}" width="30" height="6" />
    @elseif ($type === 'loca')
        @foreach ([52, 96, 142, 188, 234] as $slotX)
            <rect class="ranura-interna" x="{{ $x + $slotX }}" y="{{ $y + 16 }}" width="28" height="5" />
        @endforeach
        <polyline
            class="linea-interna"
            points="{{ $x + 44 }},{{ $y + 48 }} {{ $x + 64 }},{{ $y + 70 }} {{ $x + 64 }},{{ $y + 104 }} {{ $x + 102 }},{{ $y + 104 }} {{ $x + 102 }},{{ $y + 62 }} {{ $x + 94 }},{{ $y + 48 }}"
        />
        <polyline
            class="linea-interna"
            points="{{ $x + 118 }},{{ $y + 50 }} {{ $x + 128 }},{{ $y + 68 }} {{ $x + 128 }},{{ $y + 104 }} {{ $x + 168 }},{{ $y + 104 }} {{ $x + 168 }},{{ $y + 64 }} {{ $x + 176 }},{{ $y + 48 }}"
        />
        <polyline
            class="linea-interna"
            points="{{ $x + 196 }},{{ $y + 104 }} {{ $x + 196 }},{{ $y + 82 }} {{ $x + 278 }},{{ $y + 44 }}"
        />
    @else
        <rect class="ranura-interna" x="{{ $x + ($w * 0.48) }}" y="{{ $y + 34 }}" width="{{ max(26, $w * 0.2) }}" height="6" />

        @if ($type !== 'estrecha')
            <polyline
                class="linea-interna"
                points="{{ $x + ($w * 0.28) }},{{ $y + 64 }} {{ $x + ($w * 0.12) }},{{ $y + 96 }} {{ $x + ($w * 0.12) }},{{ $y + 134 }} {{ $x + ($w * 0.42) }},{{ $y + 164 }} {{ $x + ($w * 0.42) }},{{ $y + 236 }} {{ $x + ($w * 0.34) }},{{ $y + 248 }} {{ $x + ($w * 0.42) }},{{ $y + 260 }}"
            />
            <polyline
                class="linea-interna"
                points="{{ $x + ($w * 0.48) }},{{ $y + 122 }} {{ $x + ($w * 0.76) }},{{ $y + 122 }} {{ $x + ($w * 0.76) }},{{ $y + 54 }} {{ $x + ($w * 0.86) }},{{ $y + 52 }}"
            />
            <line
                class="linea-interna"
                x1="{{ $x + ($w * 0.62) }}"
                y1="{{ $y + 54 }}"
                x2="{{ $x + ($w * 0.62) }}"
                y2="{{ $y + 192 }}"
            />
        @endif

        @if (($red['drop'] ?? true) !== false)
            @php
                $cx = $red['dropX'] ?? ($x + ($w * 0.33));
                $cy = $red['dropY'] ?? ($y + $h - 74);
            @endphp
            <path
                class="linea-interna"
                d="M {{ $cx }} {{ $cy - 48 }} C {{ $cx - 26 }} {{ $cy - 12 }}, {{ $cx - 22 }} {{ $cy + 22 }}, {{ $cx }} {{ $cy + 22 }} C {{ $cx + 22 }} {{ $cy + 22 }}, {{ $cx + 26 }} {{ $cy - 12 }}, {{ $cx }} {{ $cy - 48 }} Z"
            />
            <path
                class="linea-interna"
                d="M {{ $cx }} {{ $cy - 34 }} C {{ $cx - 15 }} {{ $cy - 8 }}, {{ $cx - 12 }} {{ $cy + 10 }}, {{ $cx }} {{ $cy + 10 }} C {{ $cx + 12 }} {{ $cy + 10 }}, {{ $cx + 15 }} {{ $cy - 8 }}, {{ $cx }} {{ $cy - 34 }} Z"
            />
        @endif
    @endif

    @if ($bottomLabel)
        <text class="red-label red-label-bottom" x="{{ $bottomLabelX }}" y="{{ $bottomLabelY }}" text-anchor="middle">{{ $bottomLabel }}</text>
    @endif
</g>
