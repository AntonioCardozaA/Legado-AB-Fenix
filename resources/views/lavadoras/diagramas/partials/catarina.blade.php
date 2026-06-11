@php
    $x = $catarina['x'] ?? ($x ?? 0);
    $y = $catarina['y'] ?? ($y ?? 0);
    $r = $catarina['r'] ?? ($r ?? 18);
    $dientes = $catarina['dientes'] ?? ($dientes ?? 10);
    $sentido = $catarina['sentido'] ?? ($sentido ?? 'normal');
    $label = $catarina['label'] ?? ($label ?? null);
@endphp

<g class="catarina catarina-{{ $sentido }}" aria-label="{{ $label ?? 'Catarina' }}">
    @for ($i = 0; $i < $dientes; $i++)
        @php
            $angulo = round((360 / $dientes) * $i, 2);
        @endphp
        <rect
            class="catarina-diente"
            x="{{ $x - ($r * 0.16) }}"
            y="{{ $y - ($r * 1.28) }}"
            width="{{ $r * 0.32 }}"
            height="{{ $r * 0.34 }}"
            rx="{{ $r * 0.05 }}"
            transform="rotate({{ $angulo }} {{ $x }} {{ $y }})"
        />
    @endfor

    <circle class="catarina-cuerpo" cx="{{ $x }}" cy="{{ $y }}" r="{{ $r }}" />
    <circle class="catarina-centro" cx="{{ $x }}" cy="{{ $y }}" r="{{ $r * 0.34 }}" />

    @for ($i = 0; $i < 6; $i++)
        @php
            $angulo = deg2rad(60 * $i);
            $px = $x + cos($angulo) * ($r * 0.62);
            $py = $y + sin($angulo) * ($r * 0.62);
        @endphp
        <circle class="catarina-perno" cx="{{ $px }}" cy="{{ $py }}" r="{{ $r * 0.1 }}" />
    @endfor
</g>
