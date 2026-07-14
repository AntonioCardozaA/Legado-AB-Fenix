@extends('layouts.app')

@section('title', 'Editar Analisis Etiquetadora')

@section('content')
@include('etiquetadora.partials.styles')

<div class="etq-page">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <header class="mb-8">
            <div class="mb-4 flex items-center gap-3">
                <a href="{{ route('analisis-etiquetadora.show', $analisis) }}" class="text-gray-400 transition hover:text-blue-600">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Editar Analisis de Componente</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        ID: #{{ $analisis->id }}
                        @if($analisis->numero_orden)
                            | Orden: {{ $analisis->numero_orden }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="etq-context-strip">
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-4">
                    <div class="flex justify-center md:justify-start">
                        <div class="flex min-h-20 min-w-20 items-center justify-center rounded-xl bg-white p-2 shadow-sm">
                            @include('etiquetadora.partials.presentation-icons', ['linea' => $linea ?? $analisis->linea, 'size' => 'sm'])
                        </div>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-600">Etiquetadora</p>
                        <p class="text-gray-800">{{ $linea->nombre ?? 'Linea ' . $analisis->linea_id }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-600">Componente</p>
                        <p class="text-gray-800">{{ $analisis->componente->nombre ?? 'Componente no encontrado' }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-600">Maquina</p>
                        <p class="text-gray-800">Maquina {{ $analisis->maquina }}</p>
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
