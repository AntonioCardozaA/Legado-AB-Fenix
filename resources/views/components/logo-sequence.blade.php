@props(['label' => 'Legado AB Fénix', 'baseClass' => ''])

<div {{ $attributes->class(['logo-sequence']) }} role="img" aria-label="{{ $label }}">
    <picture>
        <source srcset="{{ asset('images/optimized/logo1-32489536c283.webp') }}" type="image/webp">
        <img src="{{ asset('images/logo1.png') }}" alt="" aria-hidden="true"
             width="1254" height="1254" class="logo-sequence-base {{ $baseClass }}">
    </picture>

    @foreach ([
        2 => 'logo2-2ef164b28259.webp',
        3 => 'logo3-d5f55659541f.webp',
        4 => 'logo4-6b27cb0fdc70.webp',
    ] as $frame => $file)
        <picture>
            <source srcset="{{ asset('images/optimized/'.$file) }}" type="image/webp">
            <img src="{{ asset('images/logo'.$frame.'.png') }}" alt="" aria-hidden="true"
                 class="logo-sequence-frame logo-sequence-frame--{{ $frame }}">
        </picture>
    @endforeach
</div>
