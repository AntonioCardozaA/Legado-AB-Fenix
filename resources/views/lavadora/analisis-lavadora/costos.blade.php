@extends('layouts.app')

@section('title', 'Administrar costos del analisis')

@section('content')
@php
    $automaticTotal = round($costEntries->filter(fn ($entry) => $entry->isAutomatic())->sum('total_cost'), 2);
    $manualTotal = round($costEntries->filter(fn ($entry) => $entry->isManual())->sum('total_cost'), 2);
    $registeredTotal = round($costEntries->sum('total_cost'), 2);
    $automaticSyncLabel = $missingAutomaticEntriesCount > 0
        ? ($automaticEntryCount > 0 ? 'Completar costos automaticos' : 'Aplicar costos automaticos')
        : 'Reconstruir costos automaticos';
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-white px-6 py-7 sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600">
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 font-semibold text-slate-700">Analisis #{{ $analisislavadora->id }}</span>
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 font-semibold text-slate-700">{{ $analisislavadora->linea->nombre ?? 'Sin linea' }}</span>
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 font-semibold text-slate-700">{{ optional($analisislavadora->fecha_analisis)->format('d/m/Y') ?? 'Sin fecha' }}</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Administrar costos</h1>
                        <p class="mt-2 max-w-3xl text-sm text-slate-500">
                            Revisa los costos automaticos aplicables, completa gastos faltantes y corrige este analisis sin alterar las reglas globales.
                        </p>
                    </div>
                    <div class="text-sm text-slate-600">
                        <span class="font-semibold">{{ $analisislavadora->componente->nombre ?? 'Componente no asignado' }}</span>
                        @if($analisislavadora->reductor)
                            <span class="mx-2 text-slate-300">|</span>{{ $analisislavadora->reductor }}
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('analisis-lavadora.index', array_filter(['linea_id' => $analisislavadora->linea_id], fn ($value) => filled($value))) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-200">
                        <i class="fas fa-arrow-left"></i>
                        Volver al analisis
                    </a>
                    <a href="{{ route('lavadora.costos.index') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg border border-sky-200 bg-sky-100 px-4 py-2.5 text-sm font-semibold text-sky-800 transition hover:bg-sky-200">
                        <i class="fas fa-chart-line"></i>
                        Panel de costos
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-4 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:grid-cols-3 sm:px-8">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total registrado</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">${{ number_format($registeredTotal, 2) }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Automaticos</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">${{ number_format($automaticTotal, 2) }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Manuales</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">${{ number_format($manualTotal, 2) }}</p>
            </div>
        </div>
    </section>

    @if($costEntries->isEmpty())
        <section class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5 text-sm text-amber-900 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-xl bg-white text-amber-600">
                    <i class="fas fa-triangle-exclamation"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold">Este analisis aun no tiene costos registrados</h2>
                    <p class="mt-1 leading-6">
                        Aqui puedes revisar si alguna regla automatica aplica y agregar manualmente los gastos faltantes para incorporarlos al control de gastos.
                    </p>
                </div>
            </div>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i class="fas fa-wand-magic-sparkles"></i>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Reglas automaticas aplicables</h2>
                    <p class="text-sm text-slate-500">Activa, desactiva o sincroniza solo para este analisis las reglas detectadas por el sistema.</p>
                </div>
            </div>

            @if($canSyncAutomaticCosts)
                <form method="POST" action="{{ route('analisis-lavadora.costos.automatic.sync', ['analisislavadora' => $analisislavadora->id]) }}">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700 lg:w-auto">
                        <i class="fas fa-arrows-rotate"></i>
                        {{ $automaticSyncLabel }}
                    </button>
                </form>
            @endif
        </div>

        @if($missingAutomaticEntriesCount > 0)
            <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-xl bg-white text-amber-600">
                        <i class="fas fa-clock-rotate-left"></i>
                    </span>
                    <div>
                        <h3 class="font-bold">Hay costos automaticos pendientes por registrar</h3>
                        <p class="mt-1 leading-6">
                            Este analisis tiene {{ $activeAutomaticSuggestionsCount }} regla(s) activa(s), pero solo {{ $automaticEntryCount }} costo(s) automatico(s) registrado(s).
                            Usa <span class="font-semibold">{{ $automaticSyncLabel }}</span> para recuperar los faltantes sin tocar los gastos manuales.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if($automaticSuggestions->isNotEmpty())
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach($automaticSuggestions as $suggestion)
                    <article class="rounded-2xl border {{ $suggestion['excluded'] ? 'border-amber-200 bg-amber-50/70' : 'border-emerald-200 bg-emerald-50/70' }} p-5 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $suggestion['excluded'] ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $suggestion['excluded'] ? 'Desactivada en este analisis' : 'Activa en este analisis' }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ $suggestion['trigger_label'] }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">{{ $suggestion['catalog_name'] }}</h3>
                                    <p class="text-sm text-slate-500">
                                        {{ $suggestion['catalog_sku'] ?: 'Sin SKU' }}
                                        @if($suggestion['catalog_category'])
                                            <span class="mx-2 text-slate-300">|</span>{{ $suggestion['catalog_category'] }}
                                        @endif
                                    </p>
                                </div>
                                <div class="grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                                    <p><span class="font-semibold">Disparo:</span> {{ $suggestion['trigger_reference'] ?: 'Regla general' }}</p>
                                    <p><span class="font-semibold">Cantidad:</span> {{ number_format((float) $suggestion['quantity'], 2) }} {{ $suggestion['unit'] ?: 'unidad' }}</p>
                                    <p><span class="font-semibold">Costo unitario:</span> ${{ number_format((float) $suggestion['unit_cost'], 2) }}</p>
                                    <p><span class="font-semibold">Total:</span> ${{ number_format((float) $suggestion['total_cost'], 2) }}</p>
                                </div>
                                @if($suggestion['notes'])
                                    <p class="rounded-xl bg-white/80 px-3 py-2 text-sm text-slate-600">{{ $suggestion['notes'] }}</p>
                                @endif
                            </div>

                            <div class="sm:min-w-[180px]">
                                @if($suggestion['excluded'])
                                    <form method="POST" action="{{ route('analisis-lavadora.costos.automatic.enable', ['analisislavadora' => $analisislavadora->id, 'rule' => $suggestion['rule_id']]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                            <i class="fas fa-rotate-left"></i>
                                            Reactivar costo
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('analisis-lavadora.costos.automatic.disable', ['analisislavadora' => $analisislavadora->id, 'rule' => $suggestion['rule_id']]) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700">
                                            <i class="fas fa-ban"></i>
                                            Quitar automatico
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                No se detectaron reglas automaticas aplicables para este analisis.
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                <i class="fas fa-receipt"></i>
            </span>
            <div>
                <h2 class="text-lg font-bold text-slate-900">Gastos registrados</h2>
                <p class="text-sm text-slate-500">Consulta lo que ya cuenta para este analisis y elimina solo los registros manuales cuando sea necesario.</p>
            </div>
        </div>

        @if($costEntries->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Concepto</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Origen</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Cantidad</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Costo unitario</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Total</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Notas</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($costEntries as $entry)
                            <tr>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-slate-900">{{ $entry->catalog_name_snapshot }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $entry->catalog_sku_snapshot ?: 'Sin SKU' }}
                                        @if($entry->catalog_category_snapshot)
                                            <span class="mx-2 text-slate-300">|</span>{{ $entry->catalog_category_snapshot }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $entry->isManual() ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ \App\Models\LavadoraCostEntry::originLabel($entry->source_type) }}
                                        </span>
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                            {{ \App\Models\LavadoraCostEntry::sourceLabel($entry->source_type) }}
                                        </span>
                                    </div>
                                    @if($entry->source_reference)
                                        <div class="mt-2 text-xs text-slate-500">Referencia: {{ $entry->source_reference }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top text-slate-700">
                                    {{ number_format((float) $entry->quantity, 2) }} {{ $entry->unidad_medida_snapshot ?: 'unidad' }}
                                </td>
                                <td class="px-4 py-4 align-top text-slate-700">${{ number_format((float) $entry->unit_cost, 2) }}</td>
                                <td class="px-4 py-4 align-top font-semibold text-slate-900">${{ number_format((float) $entry->total_cost, 2) }}</td>
                                <td class="px-4 py-4 align-top text-slate-600">{{ $entry->notas ?: 'Sin notas' }}</td>
                                <td class="px-4 py-4 align-top text-right">
                                    @if($entry->isManual())
                                        <form method="POST" action="{{ route('analisis-lavadora.costos.manual.destroy', ['analisislavadora' => $analisislavadora->id, 'costEntry' => $entry->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                                                <i class="fas fa-trash"></i>
                                                Quitar
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">Gestiona desde reglas automaticas</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                Todavia no hay gastos registrados para este analisis.
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <i class="fas fa-square-check"></i>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Agregar gastos manuales</h2>
                    <p class="text-sm text-slate-500">Selecciona uno o varios conceptos del catalogo para registrarlos manualmente en este analisis.</p>
                </div>
            </div>

            <div class="w-full max-w-md">
                <label for="manual-cost-search" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Buscar en catalogo</label>
                <input id="manual-cost-search" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100" placeholder="SKU, concepto, categoria o unidad">
            </div>
        </div>

        <form method="POST" action="{{ route('analisis-lavadora.costos.manual.store', ['analisislavadora' => $analisislavadora->id]) }}" class="space-y-5">
            @csrf

            <div class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="max-h-[30rem] overflow-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="sticky top-0 bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Seleccionar</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Concepto</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Costo unitario</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Cantidad</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Notas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($catalogItems as $item)
                                @php
                                    $searchText = strtolower(trim(($item->sku ?? '') . ' ' . $item->nombre . ' ' . ($item->categoria ?? '') . ' ' . $item->unidad_medida));
                                    $oldQuantity = old("items.$item->id.quantity", 1);
                                @endphp
                                <tr data-manual-cost-row data-search="{{ $searchText }}">
                                    <td class="px-4 py-4 align-top">
                                        <input type="hidden" name="items[{{ $item->id }}][catalog_item_id]" value="{{ $item->id }}">
                                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                            <input type="checkbox"
                                                   name="items[{{ $item->id }}][selected]"
                                                   value="1"
                                                   class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                                   @checked(old("items.$item->id.selected"))>
                                            Agregar
                                        </label>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="font-semibold text-slate-900">{{ $item->nombre }}</div>
                                        <div class="text-xs text-slate-500">
                                            {{ $item->sku ?: 'Sin SKU' }}
                                            @if($item->categoria)
                                                <span class="mx-2 text-slate-300">|</span>{{ $item->categoria }}
                                            @endif
                                            <span class="mx-2 text-slate-300">|</span>{{ $item->unidad_medida }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 align-top font-semibold text-slate-900">${{ number_format((float) $item->costo_unitario, 2) }}</td>
                                    <td class="px-4 py-4 align-top">
                                        <input type="number"
                                               min="0.01"
                                               step="0.01"
                                               name="items[{{ $item->id }}][quantity]"
                                               value="{{ $oldQuantity }}"
                                               class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <input type="text"
                                               name="items[{{ $item->id }}][notas]"
                                               value="{{ old("items.$item->id.notas") }}"
                                               maxlength="1000"
                                               placeholder="Observacion opcional"
                                               class="w-full min-w-[16rem] rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                    <i class="fas fa-plus"></i>
                    Registrar gastos manuales seleccionados
                </button>
            </div>
        </form>
    </section>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('manual-cost-search');
    const rows = Array.from(document.querySelectorAll('[data-manual-cost-row]'));

    if (!searchInput) {
        return;
    }

    searchInput.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();

        rows.forEach(function (row) {
            const haystack = row.dataset.search || '';
            const shouldShow = term === '' || haystack.includes(term);

            row.classList.toggle('hidden', !shouldShow);
        });
    });
});
</script>
@endsection
