@extends('layouts.app')

@section('title', 'Diagrama L-05 / L-12 / L-13')

@section('content')
<link rel="stylesheet" href="{{ asset('css/diagramas-lavadoras.css') }}">

@php
    $nav = [
        ['route' => 'lavadoras.diagramas.l05-l12-l13', 'label' => 'L-05 / L-12 / L-13'],
        ['route' => 'lavadoras.diagramas.l06-l07', 'label' => 'L-06 / L-07'],
        ['route' => 'lavadoras.diagramas.l04-l09', 'label' => 'L-04 / L-09'],
    ];
    $lineas = ['L-05', 'L-12', 'L-13'];
@endphp

<div class="diagramas-lavadoras-page">
    <div class="diagrama-page-header">
        <div>
            <h1 class="diagrama-page-title">Diagramas de cadena L-05 / L-12 / L-13</h1>
            <p class="diagrama-page-subtitle">Recorridos SVG generados desde las mascaras exactas de cada lavadora.</p>
        </div>

        <nav class="diagrama-nav" aria-label="Diagramas de lavadoras">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" class="diagrama-nav-link {{ request()->routeIs($item['route']) ? 'is-active' : '' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    @foreach ($lineas as $linea)
        @include('lavadoras.diagramas.partials.embebido', [
            'lineaNombre' => $linea,
            'grupo' => 'l05-l12-l13',
        ])
    @endforeach
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/diagramas-lavadoras.js') }}"></script>
@endsection
