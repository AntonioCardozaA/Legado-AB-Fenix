@extends('layouts.app')

@section('title', 'Seleccionar Etiquetadora')

@section('content')
@include('etiquetadora.partials.styles')

<div class="etq-page">
    <div class="etq-container">
        <header class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <a href="{{ route('analisis-etiquetadora.index') }}" class="etq-back-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Volver al Inicio</span>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Seleccionar Etiquetadora</h1>
                    <p class="etq-subtitle">Cada linea contiene Etiquetadora A, B y C.</p>
                </div>
            </div>
        </header>

        <section class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            @forelse($lineas as $linea)
                <a href="{{ route('analisis-etiquetadora.create', $linea->id) }}" class="group">
                    <article class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex flex-col items-center text-center">
                            <div class="mb-4 flex min-h-32 w-full items-center justify-center rounded-2xl bg-slate-50 px-3 py-4 transition-transform duration-300 group-hover:scale-105">
                                @include('etiquetadora.partials.presentation-icons', ['linea' => $linea, 'size' => 'lg'])
                            </div>

                            <h2 class="mb-1 text-lg font-semibold text-gray-800">
                                {{ $linea->nombre ?? 'Etiquetadora ' . $linea->id }}
                            </h2>
                            <p class="mb-4 line-clamp-2 text-sm text-gray-500">
                                {{ $linea->descripcion ?? 'Equipo disponible para analisis' }}
                            </p>

                            <span class="create-action create-action--compact">
                                Seleccionar
                                <i class="fas fa-chevron-right text-xs"></i>
                            </span>
                        </div>
                    </article>
                </a>
            @empty
                <div class="col-span-full etq-empty">
                    <i class="fas fa-circle-info mb-3 text-3xl text-gray-300"></i>
                    <h3 class="text-base font-semibold text-gray-800">No hay etiquetadoras disponibles</h3>
                    <p class="mt-1 text-sm text-gray-500">No se encontraron lineas activas de Etiquetadora.</p>
                    <a href="{{ route('analisis-etiquetadora.index') }}" class="create-action create-action--secondary mt-6">
                        <i class="fas fa-arrow-left"></i>
                        Volver al Inicio
                    </a>
                </div>
            @endforelse
        </section>
    </div>
</div>
@endsection
