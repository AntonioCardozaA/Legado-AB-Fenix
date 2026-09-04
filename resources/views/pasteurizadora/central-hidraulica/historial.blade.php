@extends('layouts.app')

@section('title', 'Historial Central Hidraulica')

@section('content')
<style>
    .central-history-shell {
        width: 100%;
        max-width: 100%;
    }

    .central-history-shell * {
        box-sizing: border-box;
        min-width: 0;
    }

    .central-history-shell :is(h1, h2, h3, p, span, a, button, label, select, input) {
        overflow-wrap: anywhere;
    }

    @media (max-width: 640px) {
        .central-history-shell {
            padding: 1rem 0.75rem;
        }

        .central-history-shell select,
        .central-history-shell input {
            font-size: 16px;
        }

        .central-history-shell article {
            padding: 1rem;
        }
    }
</style>

<div class="central-history-shell mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <a href="{{ $analisisRoute('index') }}"
               class="group inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-gray-600 transition-all duration-300 hover:bg-gray-200 hover:text-gray-900 sm:w-auto">
                <svg class="h-5 w-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="font-medium">Volver</span>
            </a>
            <h1 class="min-w-0 break-words text-xl font-bold text-gray-800 sm:text-3xl">
                Historial de registros
            </h1>
        </div>
        <a href="{{ $analisisRoute('select-linea') }}" class="create-action">
            <i class="fas fa-plus"></i>
            Nuevo analisis
        </a>
    </div>

    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ $analisisRoute('historial') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500">Pasteurizadora</label>
                <select name="linea_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas</option>
                    @foreach($lineas as $linea)
                        <option value="{{ $linea->id }}" {{ request('linea_id') == $linea->id ? 'selected' : '' }}>{{ $linea->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500">Piso</label>
                <select name="piso" class="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos</option>
                    @foreach($pisosCentral as $piso => $label)
                        <option value="{{ $piso }}" {{ request('piso') === $piso ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500">Lado</label>
                <select name="lado" class="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos</option>
                    @foreach($ladosCentral as $lado => $label)
                        <option value="{{ $lado }}" {{ request('lado') === $lado ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500">Componente / revision</label>
                <select name="componente_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos</option>
                    @foreach($componentesCentral as $componente)
                        <option value="{{ $componente->id }}" {{ request('componente_id') == $componente->id ? 'selected' : '' }}>{{ $componente->nombre_display }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500">Mes</label>
                <input type="month" name="fecha" value="{{ request('fecha') }}" class="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="create-action create-action--compact w-full">
                    <i class="fas fa-filter"></i>
                    Filtrar
                </button>
            </div>
        </form>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-lg font-bold text-gray-900">{{ $analisis->count() }} registros encontrados</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($analisis as $item)
                @php
                    $badge = $item->estado_badge;
                    $actividadTexto = trim((string) preg_replace('/\s+/', ' ', (string) $item->actividad));
                @endphp
                <article class="p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="mb-2 flex flex-wrap gap-2">
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700">{{ $item->linea->nombre ?? 'N/A' }}</span>
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $item->piso_label }}</span>
                                @if($item->lado)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ $item->lado_label }}</span>
                                @endif
                                <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $badge['class'] }}">{{ $item->estado }}</span>
                            </div>
                            <h3 class="break-words text-lg font-bold text-gray-900">{{ $item->componente_nombre }}</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ optional($item->fecha_analisis)->format('d/m/Y') }}
                                @if($item->numero_orden)
                                    | Orden {{ $item->numero_orden }}
                                @endif
                                | Cantidad {{ $item->cantidad_display }}
                            </p>
                            <p class="mt-3 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($actividadTexto !== '' ? $actividadTexto : 'Sin actividad registrada.', 180) }}</p>
                        </div>
                        <div class="create-actions lg:justify-end">
                            <a href="{{ $analisisRoute('show', $item->id) }}" class="create-action create-action--secondary create-action--compact">
                                <i class="fas fa-eye"></i>
                                Ver
                            </a>
                            <a href="{{ $analisisRoute('edit', $item->id) }}" class="create-action create-action--secondary create-action--compact">
                                <i class="fas fa-pen"></i>
                                Editar
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="p-10 text-center text-gray-500">No hay registros en el historial con los filtros actuales.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
