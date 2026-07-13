@php
    $logoPath = public_path('images/logo1.png');
    $icon192Path = public_path('images/icon-192.png');
    $icon512Path = public_path('images/icon-512.png');
    $manifestPath = public_path('site.webmanifest');

    $logoVersion = file_exists($logoPath) ? filemtime($logoPath) : time();
    $icon192Version = file_exists($icon192Path) ? filemtime($icon192Path) : $logoVersion;
    $icon512Version = file_exists($icon512Path) ? filemtime($icon512Path) : $logoVersion;
    $manifestVersion = file_exists($manifestPath) ? filemtime($manifestPath) : $logoVersion;
@endphp

<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo1.png') }}?v={{ $logoVersion }}">
<link rel="shortcut icon" href="{{ asset('images/logo1.png') }}?v={{ $logoVersion }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icon-192.png') }}?v={{ $icon192Version }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/icon-192.png') }}?v={{ $icon192Version }}">
<link rel="icon" type="image/png" sizes="512x512" href="{{ asset('images/icon-512.png') }}?v={{ $icon512Version }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}?v={{ $manifestVersion }}">

<meta name="theme-color" content="#111827">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Legado AB Fénix') }}">
