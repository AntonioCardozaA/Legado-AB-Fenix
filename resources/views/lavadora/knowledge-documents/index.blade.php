@extends('layouts.app')

@section('title', 'Base de Conocimiento Lavadoras')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="rounded-3xl bg-slate-900 px-6 py-6 text-white shadow-xl">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <a href="{{ route('plan-accion.ai.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white/90 transition hover:bg-white/20">
                    <i class="fas fa-arrow-left"></i>
                    Volver a revision IA
                </a>
                <h1 class="mt-4 text-3xl font-black tracking-tight">Base de conocimiento de lavadoras</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-300">
                    Manuales, procedimientos y referencias historicas que alimentan el contexto del generador de planes.
                </p>
            </div>

            <a href="{{ route('lavadora.knowledge-documents.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-4 py-3 text-sm font-bold text-slate-950 transition hover:bg-amber-300">
                <i class="fas fa-file-circle-plus"></i>
                Cargar documento
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Linea</label>
                <select name="linea_id" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas</option>
                    @foreach($lineas as $linea)
                        <option value="{{ $linea->id }}" @selected(request('linea_id') == $linea->id)>{{ $linea->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Componente</label>
                <select name="componente_id" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos</option>
                    @foreach($componentes as $componente)
                        <option value="{{ $componente->id }}" @selected(request('componente_id') == $componente->id)>
                            {{ $componente->nombre }}{{ $componente->codigo ? ' (' . $componente->codigo . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Indexacion</label>
                <select name="indexing_status" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas</option>
                    @foreach(['pending' => 'Pendiente', 'indexed' => 'Indexado', 'pending_extraction' => 'Pendiente de extraccion', 'failed' => 'Fallido'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('indexing_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                    <i class="fas fa-filter"></i>
                    Filtrar
                </button>
                <a href="{{ route('lavadora.knowledge-documents.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    <i class="fas fa-rotate-left"></i>
                    Limpiar
                </a>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Documento</th>
                        <th class="px-4 py-3">Cobertura</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Chunks</th>
                        <th class="px-4 py-3">Subido por</th>
                        <th class="px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($documents as $document)
                        @php
                            $statusClasses = match ($document->indexing_status) {
                                'indexed' => 'bg-emerald-100 text-emerald-800',
                                'failed' => 'bg-rose-100 text-rose-800',
                                'pending_extraction' => 'bg-orange-100 text-orange-800',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-4 align-top">
                                <p class="font-semibold text-slate-950">{{ $document->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $document->document_type }}{{ $document->version ? ' · v' . $document->version : '' }}</p>
                                @if($document->original_filename)
                                    <p class="mt-1 text-xs text-slate-500">{{ $document->original_filename }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 align-top text-slate-600">
                                <p>{{ $document->linea?->nombre ?? 'General' }}</p>
                                <p class="mt-1 text-xs">{{ $document->componente?->nombre ?? 'Todos los componentes' }}</p>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $statusClasses }}">
                                    {{ str_replace('_', ' ', (string) $document->indexing_status) }}
                                </span>
                                <p class="mt-2 text-xs text-slate-500">{{ $document->lifecycle_status }}</p>
                                @if($document->last_index_error)
                                    <p class="mt-2 text-xs text-rose-600">{{ $document->last_index_error }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 align-top text-slate-600">
                                <p class="font-semibold text-slate-950">{{ $document->chunks_count }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ optional($document->indexed_at)->format('d/m/Y H:i') ?? 'Sin indexar' }}</p>
                            </td>
                            <td class="px-4 py-4 align-top text-slate-600">
                                <p>{{ $document->uploadedBy?->name ?? 'Sin usuario' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ optional($document->uploaded_at)->format('d/m/Y H:i') ?? 'N/A' }}</p>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <form method="POST" action="{{ route('lavadora.knowledge-documents.reindex', ['document' => $document->id]) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                        <i class="fas fa-rotate"></i>
                                        Reindexar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center text-sm text-slate-500">
                                Aun no hay documentos registrados para el asistente de mantenimiento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $documents->links() }}
    </div>
</div>
@endsection
