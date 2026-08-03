@extends('layouts.app')

@section('title', 'Observabilidad IA')

@section('content')
@php
    $statusClasses = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'fallback' => 'border-amber-200 bg-amber-50 text-amber-700',
        'failed' => 'border-red-200 bg-red-50 text-red-700',
    ];

    $signalClasses = [
        'healthy' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'info' => 'border-sky-200 bg-sky-50 text-sky-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        'critical' => 'border-red-200 bg-red-50 text-red-800',
    ];

    $signalIcons = [
        'healthy' => 'fa-check-circle',
        'info' => 'fa-info-circle',
        'warning' => 'fa-exclamation-triangle',
        'critical' => 'fa-fire',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-teal-700">
                <i class="fas fa-brain"></i>
                Operacion IA
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Observabilidad IA</h1>
            <p class="text-sm text-gray-500">
                {{ $filters['from']->format('d/m/Y') }} - {{ $filters['to']->format('d/m/Y') }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('assistant-chat.index') }}" class="inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                <i class="fas fa-comments text-gray-500"></i>
                Chatbot
            </a>
            <a href="{{ route('admin.ai-observability.index') }}" class="inline-flex items-center gap-2 rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                <i class="fas fa-sync-alt"></i>
                Reiniciar filtros
            </a>
        </div>
    </div>

    <form action="{{ route('admin.ai-observability.index') }}" method="GET" class="rounded bg-white p-5 shadow">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[160px,160px,180px,220px,220px,auto] xl:items-end">
            <div>
                <label for="from" class="mb-1 block text-sm font-medium text-gray-700">Desde</label>
                <input id="from" type="date" name="from" value="{{ $filters['from_date'] }}" class="w-full rounded border-gray-300 text-sm">
            </div>

            <div>
                <label for="to" class="mb-1 block text-sm font-medium text-gray-700">Hasta</label>
                <input id="to" type="date" name="to" value="{{ $filters['to_date'] }}" class="w-full rounded border-gray-300 text-sm">
            </div>

            <div>
                <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Estado</label>
                <select id="status" name="status" class="w-full rounded border-gray-300 text-sm">
                    <option value="">Todos</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="action_type" class="mb-1 block text-sm font-medium text-gray-700">Flujo IA</label>
                <select id="action_type" name="action_type" class="w-full rounded border-gray-300 text-sm">
                    <option value="">Todos</option>
                    @foreach($actionOptions as $option)
                        <option value="{{ $option['value'] }}" @selected($filters['action_type'] === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="provider" class="mb-1 block text-sm font-medium text-gray-700">Proveedor</label>
                <select id="provider" name="provider" class="w-full rounded border-gray-300 text-sm">
                    <option value="">Todos</option>
                    @foreach($providerOptions as $provider)
                        <option value="{{ $provider }}" @selected($filters['provider'] === $provider)>{{ $provider }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                <i class="fas fa-filter"></i>
                Filtrar
            </button>
        </div>
    </form>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded bg-white p-5 shadow">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Interacciones</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($metrics['total']) }}</div>
            <p class="mt-2 text-sm text-gray-500">{{ number_format($metrics['success_rate'], 1) }}% exitosas</p>
        </div>

        <div class="rounded bg-white p-5 shadow">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fallas</div>
            <div class="mt-2 text-3xl font-bold text-red-600">{{ number_format($metrics['failed']) }}</div>
            <p class="mt-2 text-sm text-gray-500">{{ number_format($metrics['failure_rate'], 1) }}% fallidas, {{ number_format($metrics['fallback']) }} fallback</p>
        </div>

        <div class="rounded bg-white p-5 shadow">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Latencia P95</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">
                {{ $metrics['p95_latency_ms'] !== null ? number_format($metrics['p95_latency_ms']) . ' ms' : 'N/D' }}
            </div>
            <p class="mt-2 text-sm text-gray-500">Promedio {{ $metrics['avg_latency_ms'] !== null ? number_format($metrics['avg_latency_ms']) . ' ms' : 'N/D' }}</p>
        </div>

        <div class="rounded bg-white p-5 shadow">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tokens</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($metrics['total_tokens']) }}</div>
            <p class="mt-2 text-sm text-gray-500">{{ number_format($metrics['prompt_tokens']) }} entrada, {{ number_format($metrics['completion_tokens']) }} salida</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded bg-white p-5 shadow">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Planes IA</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($plans['total']) }}</div>
            <p class="mt-2 text-sm text-gray-500">{{ number_format($plans['review_queue']) }} pendientes de revision</p>
        </div>

        <div class="rounded bg-white p-5 shadow">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Feedback ejecucion</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($plans['feedback_rate'], 1) }}%</div>
            <p class="mt-2 text-sm text-gray-500">{{ number_format($plans['feedback']) }} planes con retroalimentacion</p>
        </div>

        <div class="rounded bg-white p-5 shadow">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Efectividad</div>
            <div class="mt-2 text-3xl font-bold text-emerald-700">{{ number_format($plans['effective_rate'], 1) }}%</div>
            <p class="mt-2 text-sm text-gray-500">{{ number_format($plans['effective']) }} efectivos de {{ number_format($plans['evaluated']) }} evaluados</p>
        </div>

        <div class="rounded bg-white p-5 shadow">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Costo real IA</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">${{ number_format($plans['actual_cost_total'], 2) }}</div>
            <p class="mt-2 text-sm text-gray-500">{{ number_format($plans['actual_hours_total'], 1) }} horas registradas</p>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @foreach($healthSignals as $signal)
            <div class="rounded border px-4 py-3 {{ $signalClasses[$signal['level']] ?? $signalClasses['info'] }}">
                <div class="flex items-start gap-3">
                    <i class="fas {{ $signalIcons[$signal['level']] ?? $signalIcons['info'] }} mt-1"></i>
                    <div>
                        <div class="text-sm font-bold">{{ $signal['title'] }}</div>
                        <div class="mt-1 text-sm opacity-90">{{ $signal['detail'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr),minmax(320px,0.8fr)]">
        <div class="rounded bg-white shadow">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-lg font-bold text-gray-900">Actividad reciente</h2>
                <p class="text-sm text-gray-500">Ultimos dias con trafico IA</p>
            </div>

            <div class="space-y-4 p-5">
                @forelse($timeline as $day)
                    <div class="grid grid-cols-[64px,minmax(0,1fr),90px] items-center gap-3">
                        <div class="text-sm font-semibold text-gray-700">{{ $day['label'] }}</div>
                        <div class="h-3 overflow-hidden rounded bg-gray-100">
                            <div class="h-full rounded bg-blue-600" style="width: {{ $day['percent'] }}%"></div>
                        </div>
                        <div class="text-right text-sm text-gray-500">{{ number_format($day['total']) }} uso(s)</div>
                    </div>
                @empty
                    <div class="rounded border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500">
                        Sin actividad para graficar.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="rounded bg-white shadow">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-lg font-bold text-gray-900">Salud por flujo</h2>
                <p class="text-sm text-gray-500">Distribucion del periodo</p>
            </div>

            <div class="space-y-5 p-5">
                <div>
                    <div class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Estados</div>
                    <div class="flex flex-wrap gap-2">
                        @forelse($metrics['status_breakdown'] as $row)
                            <span class="rounded border px-3 py-1 text-xs font-semibold {{ $statusClasses[$row['key']] ?? 'border-gray-200 bg-gray-50 text-gray-700' }}">
                                {{ $row['label'] }}: {{ number_format($row['total']) }}
                            </span>
                        @empty
                            <span class="text-sm text-gray-500">Sin datos</span>
                        @endforelse
                    </div>
                </div>

                <div>
                    <div class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Flujos</div>
                    <div class="space-y-2">
                        @forelse($metrics['action_breakdown'] as $row)
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="truncate text-gray-700">{{ $row['label'] }}</span>
                                <span class="font-semibold text-gray-900">{{ number_format($row['total']) }}</span>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500">Sin datos</div>
                        @endforelse
                    </div>
                </div>

                <div>
                    <div class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Proveedores</div>
                    <div class="space-y-2">
                        @forelse($metrics['provider_breakdown'] as $row)
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="truncate text-gray-700">{{ $row['label'] }}</span>
                                <span class="font-semibold text-gray-900">{{ number_format($row['total']) }}</span>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500">Sin datos</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded bg-white shadow">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-lg font-bold text-gray-900">Conocimiento y RAG</h2>
                <p class="text-sm text-gray-500">Cobertura de fuentes en chatbot y planes</p>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-3">
                <div class="rounded border border-gray-200 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Uso con fuentes</div>
                    <div class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($rag['knowledge_rate'], 1) }}%</div>
                </div>
                <div class="rounded border border-gray-200 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fuentes promedio</div>
                    <div class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($rag['avg_knowledge_sources'], 1) }}</div>
                </div>
                <div class="rounded border border-gray-200 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Matches plataforma</div>
                    <div class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($rag['platform_matches_total']) }}</div>
                </div>
            </div>

            <div class="border-t border-gray-100 px-5 py-4">
                <div class="mb-3 text-xs font-bold uppercase tracking-wide text-gray-500">Modulos preguntados</div>
                <div class="flex flex-wrap gap-2">
                    @forelse($rag['module_breakdown'] as $module)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ $module['module'] }}: {{ number_format($module['total']) }}
                        </span>
                    @empty
                        <span class="text-sm text-gray-500">Sin modulo detectado</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded bg-white shadow">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-lg font-bold text-gray-900">Preguntas frecuentes</h2>
                <p class="text-sm text-gray-500">Chatbot operativo</p>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse($rag['top_questions'] as $question)
                    <div class="px-5 py-4">
                        <div class="text-sm font-semibold text-gray-900">{{ $question['question'] }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ number_format($question['total']) }} repeticion(es)</div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-500">
                        Sin preguntas registradas.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="overflow-hidden rounded bg-white shadow">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-lg font-bold text-gray-900">Errores recientes</h2>
                <p class="text-sm text-gray-500">Ultimos fallos o respuestas con error</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3">Flujo</th>
                            <th class="px-5 py-3">Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentFailures as $failure)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ optional($failure->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3 font-semibold text-gray-800">{{ $failure->action_type }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ \Illuminate\Support\Str::limit((string) $failure->error_message, 120) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center text-gray-500">Sin errores en el periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded bg-white shadow">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-lg font-bold text-gray-900">Planes por estado</h2>
                <p class="text-sm text-gray-500">Sugerencias IA en el periodo</p>
            </div>

            <div class="space-y-5 p-5">
                <div>
                    <div class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Estado operativo</div>
                    <div class="space-y-2">
                        @forelse($plans['status_breakdown'] as $row)
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="truncate text-gray-700">{{ $row['label'] }}</span>
                                <span class="font-semibold text-gray-900">{{ number_format($row['total']) }}</span>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500">Sin planes IA</div>
                        @endforelse
                    </div>
                </div>

                <div>
                    <div class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Efectividad registrada</div>
                    <div class="space-y-2">
                        @forelse($plans['effectiveness_breakdown'] as $row)
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="truncate text-gray-700">{{ $row['label'] }}</span>
                                <span class="font-semibold text-gray-900">{{ number_format($row['total']) }}</span>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500">Sin evaluaciones</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded border border-gray-200 p-4 text-sm text-gray-700">
                    <div class="flex items-center justify-between gap-3">
                        <span>Confianza promedio</span>
                        <span class="font-bold text-gray-900">{{ $plans['avg_confidence'] !== null ? number_format($plans['avg_confidence'], 1) . '%' : 'N/D' }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <span>Planes revisados</span>
                        <span class="font-bold text-gray-900">{{ number_format($plans['reviewed']) }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <span>Planes completados</span>
                        <span class="font-bold text-gray-900">{{ number_format($plans['completed']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded bg-white shadow">
        <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Bitacora IA</h2>
                <p class="text-sm text-gray-500">Interacciones filtradas</p>
            </div>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                {{ number_format($recentInteractions->total()) }} resultado(s)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Usuario</th>
                        <th class="px-5 py-3">Flujo</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3">Proveedor</th>
                        <th class="px-5 py-3 text-right">Latencia</th>
                        <th class="px-5 py-3 text-right">Tokens</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentInteractions as $interaction)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ optional($interaction->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $interaction->user?->name ?? 'Sistema' }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-800">{{ $interaction->action_type }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded border px-2 py-1 text-xs font-semibold {{ $statusClasses[$interaction->status] ?? 'border-gray-200 bg-gray-50 text-gray-700' }}">
                                    {{ $interaction->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-600">
                                <div class="font-semibold text-gray-800">{{ $interaction->provider ?: 'N/D' }}</div>
                                <div class="text-xs text-gray-500">{{ $interaction->model ?: 'Sin modelo' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right text-gray-600">
                                {{ $interaction->response_time_ms !== null ? number_format($interaction->response_time_ms) . ' ms' : 'N/D' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-gray-800">
                                {{ $interaction->total_tokens !== null ? number_format($interaction->total_tokens) : 'N/D' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-gray-500">Sin interacciones con esos filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recentInteractions->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $recentInteractions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
