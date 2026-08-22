@extends('layouts.app')

@section('title', 'Seleccionar Pasteurizadora - Central Hidraulica')

@section('content')
@php
    $modoRapido = request('modo') === 'rapido';
    $configsPorLinea = \App\Models\CentralHidraulicaConfiguracion::query()
        ->selectRaw('pasteurizador, count(*) as total')
        ->groupBy('pasteurizador')
        ->pluck('total', 'pasteurizador');
@endphp

<style>
    .central-select-shell {
        width: 100%;
        max-width: 100%;
    }

    .central-select-shell * {
        box-sizing: border-box;
        min-width: 0;
    }

    .central-select-shell :is(h1, h2, p, span, a) {
        overflow-wrap: anywhere;
    }

    @media (max-width: 640px) {
        .central-select-shell {
            padding-inline: 0.75rem;
        }

        .central-select-shell article {
            border-radius: 0.875rem;
        }
    }
</style>

<div class="central-select-shell mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="mb-10 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <a href="{{ $analisisRoute('index') }}"
               class="group inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-gray-600 transition-all duration-300 hover:bg-gray-200 hover:text-gray-900 sm:w-auto">
                <svg class="h-5 w-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver al Inicio</span>
            </a>
            <h1 class="min-w-0 break-words text-xl font-bold text-gray-800 sm:text-3xl">
                Seleccionar Pasteurizadora
            </h1>
        </div>

        <a href="{{ $analisisRoute('index') }}" class="create-action create-action--secondary">
            <i class="fas fa-table-cells-large"></i>
            Ver tablero
        </a>
    </div>

    <div class="grid grid-cols-1 items-stretch gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($lineas as $linea)
            <article class="flex h-full min-w-0 flex-col overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="flex aspect-[2/1] items-center justify-center bg-slate-50">
                    <img src="{{ asset('images/icono-pas-cover.png') }}"
                         alt="Pasteurizadora"
                         class="h-full w-full object-contain"
                         onerror="this.src='{{ asset('images/icono_pas.png') }}'">
                </div>
                <div class="flex flex-1 flex-col items-center px-4 pb-5 pt-4 text-center sm:px-6 sm:pb-6">
                    <h2 class="mb-1 break-words text-lg font-semibold text-gray-800">{{ $linea->nombre }}</h2>
                    <div class="mb-4 grid min-h-12 gap-2 text-sm text-gray-500">
                        <span class="inline-flex items-center justify-center gap-2">
                            <i class="fas fa-layer-group text-blue-600"></i>
                            Piso Superior y Piso Inferior
                        </span>
                        <span class="inline-flex items-center justify-center gap-2">
                            <i class="fas fa-oil-can text-blue-600"></i>
                            {{ (int) ($configsPorLinea[$linea->nombre] ?? 0) }} componentes configurados
                        </span>
                    </div>
                    <div class="mt-auto flex w-full flex-col gap-2">
                        <a href="{{ $modoRapido ? $analisisRoute('create-quick', ['linea_id' => $linea->id]) : $analisisRoute('create', $linea->id) }}"
                           class="create-action create-action--compact">
                            Seleccionar
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="{{ $analisisRoute('index', ['linea_id' => $linea->id]) }}"
                           class="create-action create-action--secondary create-action--compact">
                            Ver avance
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-gray-100 text-gray-400">
                    <i class="fas fa-oil-can text-2xl"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-900">No hay pasteurizadoras configuradas</h2>
                <p class="mt-1 text-sm text-gray-500">La configuracion existe por codigo de pasteurizador; faltan lineas activas con esos nombres.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
