@extends('layouts.app')

@section('title', 'Crear Analisis Etiquetadora')

@section('content')
@include('etiquetadora.partials.styles')

<div class="etq-page">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <header class="mb-8">
            <div class="mb-4 flex items-center gap-3">
                <a href="{{ route('analisis-etiquetadora.select-linea') }}" class="text-gray-400 transition hover:text-blue-600">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-800">Crear Analisis</h1>
            </div>

            <div class="etq-context-strip">
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                    <div class="flex items-center gap-3">
                        <div class="flex min-h-20 min-w-20 flex-shrink-0 items-center justify-center rounded-xl bg-white p-2 shadow-sm">
                            @include('etiquetadora.partials.presentation-icons', ['linea' => $linea, 'size' => 'sm'])
                        </div>
                        <div>
                            <p class="font-semibold text-gray-600">Etiquetadora</p>
                            <p class="text-gray-800">{{ $linea->nombre ?? 'Linea ' . $linea->id }}</p>
                        </div>
                    </div>
                    <div id="componente-info" class="hidden">
                        <p class="font-semibold text-gray-600">Componente</p>
                        <p id="componente-nombre" class="text-gray-800"></p>
                    </div>
                    <div id="maquina-info" class="hidden">
                        <p class="font-semibold text-gray-600">Maquina</p>
                        <p id="maquina-nombre" class="text-gray-800"></p>
                    </div>
                </div>
            </div>
        </header>

        <div class="etq-form-surface">
            @include('etiquetadora.analisis-etiquetadora._form')
        </div>
    </div>
</div>
@endsection
