@extends('layouts.app')

@section('title', 'Editar análisis - Pasteurizadora')

@section('content')
@php
    $analisisRoutePrefix = $analisisRoutePrefix ?? 'pasteurizadora.analisis-pasteurizadora';
    $analisisRoute = fn ($name, $params = []) => route($analisisRoutePrefix . '.' . $name, $params);
    $backUrl = request()->input('redirect_to') ?: $analisisRoute('show', $analisis->id);
    $cancelUrl = $backUrl;
    $componentes = \App\Models\AnalisisPasteurizadora::getComponentesPorLinea($analisis->linea->nombre ?? null);
    $totalComponentes = (int) ($componentes[$analisis->componente]['cantidad'] ?? $analisis->total_componentes ?? 0);
    $componentesRevisados = \App\Models\AnalisisPasteurizadora::normalizarComponentesRevisados(
        old('componentes_revisados', $analisis->componentes_revisados_lista)
    );
    $esBrazoTorsion = \App\Models\AnalisisPasteurizadora::esBrazoTorsion($analisis->componente);
    $esRegistroQuick = $analisis->es_registro_quick;
    $numeroComponenteSeleccionado = old('numero_componente', $analisis->numero_componente_principal);

    if (empty($componentesRevisados) && $analisis->cantidad_componentes_revisados && $totalComponentes > 0) {
        $componentesRevisados = $analisis->componentesRevisadosEfectivos();
    }

    $evidencias = $analisis->evidencia_fotos ?? [];
    $numerosComponentes = $analisis->numerosComponentesDelModulo();
    if (!is_array($evidencias)) {
        $evidencias = json_decode($evidencias, true) ?? [];
    }
    $evidenciasActualesUrls = collect($evidencias)
        ->map(function ($foto) {
            return asset('storage/' . ltrim(str_replace('\\', '/', $foto), '/'));
        })
        ->values();

    $estadoBadges = [
        'Buen estado' => 'bg-green-100 text-green-800 border-green-200',
        'Requiere revisión' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'Desgaste moderado' => 'bg-orange-100 text-orange-800 border-orange-200',
        'Desgaste severo' => 'bg-orange-100 text-orange-800 border-orange-200',
        'Cambiado' => 'bg-blue-100 text-blue-800 border-blue-200',
    ];
    $estadoBadge = $estadoBadges[$analisis->estado] ?? 'bg-red-100 text-red-800 border-red-200';
    $estadoActualLabel = \App\Models\AnalisisPasteurizadora::getEstadoOpciones()[$analisis->estado]
        ?? (str_contains((string) $analisis->estado, 'Requiere cambio') ? '❌ Dañado - Requiere cambio' : $analisis->estado);
@endphp

<style>
    .pasteur-form-shell {
        --primary-blue: #3b82f6;
        --border: #e5e7eb;
        --soft-shadow: 0 10px 15px -3px rgba(15, 23, 42, .10), 0 4px 6px -4px rgba(15, 23, 42, .10);
        width: 100%;
        max-width: min(56rem, 100%);
        overflow-x: clip;
    }

    .pasteur-edit-page {
        min-height: 100%;
        background: linear-gradient(135deg, #f8fafc 0%, #eef4ff 52%, #f8fafc 100%);
    }

    .pasteur-edit-header {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
    }

    .pasteur-edit-back {
        display: inline-flex;
        width: 2.75rem;
        height: 2.75rem;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border: 1px solid #bfdbfe;
        border-radius: .85rem;
        background: rgba(255, 255, 255, .9);
        color: #2563eb;
        box-shadow: 0 4px 12px rgba(37, 99, 235, .10);
        transition: .2s ease;
    }

    .pasteur-edit-back:hover {
        border-color: #2563eb;
        background: #eff6ff;
        transform: translateX(-2px);
    }

    .pasteur-edit-heading {
        min-width: 0;
    }

    .pasteur-edit-title {
        color: #0f172a;
        font-size: clamp(1.45rem, 3vw, 2rem);
        font-weight: 800;
        letter-spacing: -.025em;
        line-height: 1.15;
    }

    .pasteur-edit-subtitle {
        margin-top: .35rem;
        color: #64748b;
        font-size: .82rem;
        font-weight: 600;
    }

    .pasteur-context {
        background: linear-gradient(135deg, rgba(255, 255, 255, .96), rgba(239, 246, 255, .92));
        border: 1px solid #dbeafe;
        border-radius: 1rem;
        box-shadow: 0 8px 20px rgba(30, 64, 175, .08);
        padding: 1.15rem;
    }

    .pasteur-context-item {
        min-height: 4.25rem;
        padding: .75rem .85rem;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        background: rgba(255, 255, 255, .78);
    }

    .pasteur-context-label {
        color: #64748b;
        font-size: .66rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .pasteur-context-value {
        margin-top: .35rem;
        color: #0f172a;
        font-size: .9rem;
        font-weight: 700;
    }

    .pasteur-form-card {
        border: 1px solid #e2e8f0;
        border-radius: 1.15rem;
        box-shadow: 0 16px 32px rgba(15, 23, 42, .09);
        padding: clamp(1rem, 3vw, 1.75rem);
    }

    .pasteur-form-intro {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.35rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .pasteur-form-intro h2 {
        color: #0f172a;
        font-size: 1.05rem;
        font-weight: 800;
    }

    .pasteur-form-intro p {
        margin-top: .25rem;
        color: #64748b;
        font-size: .78rem;
    }

    .pasteur-evidence-card {
        overflow: hidden;
    }

    .pasteur-evidence-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        flex-wrap: nowrap;
    }

    .pasteur-evidence-actions button,
    .pasteur-evidence-actions label,
    .pasteur-evidence-actions span {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .pasteur-form-status {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        flex: 0 0 auto;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        padding: .45rem .7rem;
        font-size: .7rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .pasteur-form-card label {
        color: #334155;
        font-weight: 700;
    }

    .pasteur-form-card input:not([type="hidden"]),
    .pasteur-form-card select,
    .pasteur-form-card textarea {
        border-color: #cbd5e1;
        border-radius: .7rem;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .pasteur-form-card input:not([type="hidden"]):focus,
    .pasteur-form-card select:focus,
    .pasteur-form-card textarea:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .14);
        outline: none;
    }

    .pasteur-form-actions {
        display: flex;
        gap: .75rem;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid #e2e8f0;
    }

    .pasteur-form-shell * {
        box-sizing: border-box;
        min-width: 0;
    }

    .pasteur-form-card {
        background: #ffffff;
        border: 0;
        border-radius: 1rem;
        box-shadow: var(--soft-shadow);
        padding: clamp(1rem, 4vw, 2rem);
    }

    .pasteur-context {
        background: linear-gradient(to right, #f9fafb, #f3f4f6);
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        padding: 16px;
        overflow: hidden;
    }

    .pasteur-context img {
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0;
    }

    .pasteur-form-shell label i {
        color: var(--primary-blue);
    }

    .pasteur-form-shell h1,
    .pasteur-form-shell p,
    .pasteur-form-shell span,
    .pasteur-form-shell label,
    .pasteur-form-shell textarea {
        overflow-wrap: anywhere;
    }

    .pasteur-form-shell .responsive-actions {
        flex-wrap: wrap;
    }

    @media (max-width: 640px) {
        .pasteur-edit-page {
            padding: .5rem 0;
        }

        .pasteur-form-shell {
            padding: 1.25rem 0.75rem;
        }

        .pasteur-edit-header {
            gap: .65rem;
        }

        .pasteur-edit-back {
            width: 2.4rem;
            height: 2.4rem;
        }

        .pasteur-form-intro {
            align-items: flex-start;
            flex-direction: column;
        }

        .pasteur-form-status {
            white-space: normal;
        }

        .pasteur-form-actions {
            flex-direction: column-reverse;
        }

        .pasteur-form-shell h1 {
            font-size: 1.5rem;
            line-height: 1.25;
        }

        .pasteur-context {
            padding: 14px;
        }

        .pasteur-form-shell input,
        .pasteur-form-shell select,
        .pasteur-form-shell textarea {
            font-size: 16px;
        }

        .pasteur-form-shell .responsive-action,
        .pasteur-form-shell .create-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="pasteur-edit-page">
<div class="pasteur-form-shell max-w-5xl mx-auto py-8 px-4 sm:py-10">
    <div class="mb-8">
        <div class="pasteur-edit-header mb-5">
            <a href="{{ $backUrl }}" class="pasteur-edit-back" aria-label="Volver">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="pasteur-edit-heading">
                <h1 class="pasteur-edit-title">Editar análisis de Pasteurizadora</h1>
                <p class="pasteur-edit-subtitle">
                    Registro #{{ $analisis->id }}
                    @if($analisis->numero_orden)
                        <span class="mx-1 text-slate-300">·</span> Orden {{ $analisis->numero_orden }}
                    @endif
                </p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                Revise los campos marcados antes de guardar.
            </div>
        @endif

        <div class="pasteur-context">
            <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4 lg:items-center">
                <div class="pasteur-context-item">
                    <p class="pasteur-context-label">Línea</p>
                    <p class="pasteur-context-value">{{ $analisis->linea->nombre ?? 'N/A' }}</p>
                </div>
                <div class="pasteur-context-item">
                    <p class="pasteur-context-label">Módulo</p>
                    <p class="pasteur-context-value">Módulo {{ $analisis->modulo }}</p>
                </div>
                <div class="pasteur-context-item">
                    <p class="pasteur-context-label">Componente</p>
                    <p class="pasteur-context-value">{{ $analisis->componente_nombre }}</p>
                </div>
                <div class="pasteur-context-item">
                    <p class="pasteur-context-label">Estado actual</p>
                    <span class="mt-1 inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $estadoBadge }}">
                        {{ $estadoActualLabel }}
                    </span>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 border-t border-blue-100 pt-4 text-sm sm:grid-cols-3">
                <div class="pasteur-context-item">
                    <p class="pasteur-context-label">Nivel</p>
                    <p class="pasteur-context-value">{{ $analisis->nivel ?: 'Sin nivel' }}</p>
                </div>
                <div class="pasteur-context-item">
                    <p class="pasteur-context-label">Lado</p>
                    <p class="pasteur-context-value">{{ $analisis->lado ?: 'Sin lado' }}</p>
                </div>
                <div class="pasteur-context-item">
                    <p class="pasteur-context-label">Fecha de creación</p>
                    <p class="pasteur-context-value">{{ optional($analisis->created_at)->format('d/m/Y') ?: 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="pasteur-form-card">
        <div class="pasteur-form-intro">
            <div>
                <h2>Datos de la revisión</h2>
            </div>
            <span class="pasteur-form-status">
                <i class="fas fa-pen-to-square"></i>
                Edición activa
            </span>
        </div>
        <form method="POST"
              action="{{ $analisisRoute('update', $analisis->id) }}"
              enctype="multipart/form-data"
              id="editarAnalisisPasteurizadoraForm">
            @csrf
            @method('PUT')

            <input type="hidden" name="componente" value="{{ $analisis->componente }}">
            <input type="hidden" name="redirect_to" value="{{ $backUrl }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="modulo" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-cubes text-blue-600 mr-1"></i>
                        Modulo
                    </label>
                    <input type="number"
                           name="modulo"
                           id="modulo"
                           value="{{ old('modulo', $analisis->modulo) }}"
                           min="1"
                           readonly
                           class="w-full rounded-lg border-gray-300 bg-gray-100 shadow-sm cursor-not-allowed focus:ring-blue-500 focus:border-blue-500">
                    @error('modulo')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="fecha_analisis" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="far fa-calendar-alt text-blue-600 mr-1"></i>
                        Fecha de análisis *
                    </label>
                    <input type="date"
                           name="fecha_analisis"
                           id="fecha_analisis"
                           value="{{ old('fecha_analisis', optional($analisis->fecha_analisis)->format('Y-m-d')) }}"
                           required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('fecha_analisis') border-red-500 @enderror">
                    @error('fecha_analisis')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nivel" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-layer-group text-blue-600 mr-1"></i>
                        Nivel *
                    </label>
                    <select name="nivel" id="nivel" required class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('nivel') border-red-500 @enderror">
                        <option value="">Seleccionar nivel...</option>
                        <option value="SUPERIOR" {{ old('nivel', $analisis->nivel) === 'SUPERIOR' ? 'selected' : '' }}>Nivel Superior</option>
                        <option value="INFERIOR" {{ old('nivel', $analisis->nivel) === 'INFERIOR' ? 'selected' : '' }}>Nivel Inferior</option>
                    </select>
                    @error('nivel')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="lado" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-arrows-alt-h text-blue-600 mr-1"></i>
                        Lado *
                    </label>
                    <select name="lado" id="lado" required class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('lado') border-red-500 @enderror">
                        <option value="">Seleccionar lado...</option>
                        <option value="VAPOR" {{ old('lado', $analisis->lado) === 'VAPOR' ? 'selected' : '' }}>Lado Vapor</option>
                        <option value="PASILLO" {{ old('lado', $analisis->lado) === 'PASILLO' ? 'selected' : '' }}>Lado Pasillo</option>
                    </select>
                    @error('lado')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="numero_orden" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-hashtag text-blue-600 mr-1"></i>
                        Número de orden
                    </label>
                    <input type="text"
                           name="numero_orden"
                           id="numero_orden"
                           value="{{ old('numero_orden', $analisis->numero_orden) }}"
                           maxlength="50"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           autocomplete="off"
                           placeholder="Ej: 35221456"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('numero_orden') border-red-500 @enderror">
                    @error('numero_orden')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-clipboard-check text-blue-600 mr-1"></i>
                        Estado *
                    </label>
                    <select name="estado" id="estado" required class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('estado') border-red-500 @enderror">
                        <option value="">Seleccionar estado...</option>
                        @foreach(\App\Models\AnalisisPasteurizadora::getEstadoOpciones() as $estado => $estadoLabel)
                            <option value="{{ $estado }}" {{ old('estado', $analisis->estado) === $estado ? 'selected' : '' }}>{{ $estadoLabel }}</option>
                        @endforeach
                    </select>
                    @error('estado')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

            </div>

            @if($esRegistroQuick && $totalComponentes > 0 && !$esBrazoTorsion)
                <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-4">
                        <h3 class="text-sm font-semibold text-indigo-900">
                            <i class="fas fa-clipboard-check mr-1"></i>
                            Componentes revisados
                        </h3>
                        <p class="text-xs font-medium text-indigo-700">
                            {{ count($componentesRevisados) }} de {{ $totalComponentes }} seleccionados
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($numerosComponentes as $i)
                            <label class="flex items-center gap-3 rounded-lg border border-indigo-200 bg-white p-3 cursor-pointer transition hover:border-indigo-400 hover:shadow-sm">
                                <input type="checkbox"
                                       name="componentes_revisados[]"
                                       value="{{ $i }}"
                                       {{ in_array($i, $componentesRevisados, true) ? 'checked' : '' }}
                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-700">
                                    <i class="fas fa-cube text-indigo-500 mr-1"></i>
                                    {{ $analisis->componente }} #{{ $i }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('componentes_revisados')<p class="text-red-500 text-sm mt-3">{{ $message }}</p>@enderror
                </div>
            @elseif($esRegistroQuick && $esBrazoTorsion)
                <input type="hidden" name="componentes_revisados[]" value="1">
                <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
                    <i class="fas fa-info-circle mr-1"></i>
                    Este registro corresponde al Brazo de Torsion del modulo {{ $analisis->modulo }}.
                </div>
            @else
                <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <label for="numero_componente" class="block text-sm font-semibold text-indigo-900 mb-2">
                        <i class="fas fa-list-ol mr-1"></i>
                        Número específico del componente
                    </label>

                    @if($esBrazoTorsion)
                        <input type="hidden" name="numero_componente" value="{{ $numerosComponentes[0] ?? 1 }}">
                        <div class="rounded-lg border border-indigo-200 bg-white px-4 py-3 text-sm text-indigo-800">
                            El Brazo de Torsion utiliza un registro unico por modulo.
                        </div>
                    @elseif($totalComponentes <= 1)
                        <input type="hidden" name="numero_componente" value="{{ $numerosComponentes[0] ?? 1 }}">
                        <div class="rounded-lg border border-indigo-200 bg-white px-4 py-3 text-sm text-indigo-800">
                            Este componente solo tiene una pieza configurada en el modulo.
                        </div>
                    @else
                        <select name="numero_componente"
                                id="numero_componente"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('numero_componente') border-red-500 @enderror">
                            <option value="">Seleccionar pieza...</option>
                            @foreach($numerosComponentes as $i)
                                <option value="{{ $i }}" {{ (string) $numeroComponenteSeleccionado === (string) $i ? 'selected' : '' }}>
                                    Pieza #{{ $i }}
                                </option>
                            @endforeach
                        </select>
                        @error('numero_componente')<p class="text-red-500 text-sm mt-3">{{ $message }}</p>@enderror
                    @endif
                </div>
            @endif

            <div class="mb-6">
                <label for="actividad" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-sticky-note text-blue-600 mr-1"></i>
                    Actividad / Observaciones *
                </label>
                <textarea name="actividad"
                          id="actividad"
                          rows="4"
                          required
                          placeholder="Describa la actividad realizada, observaciones o notas adicionales..."
                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('actividad') border-red-500 @enderror">{{ old('actividad', $analisis->actividad) }}</textarea>
                @error('actividad')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            @if(!empty($evidencias))
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-images text-blue-600 mr-1"></i>
                        Evidencias actuales
                    </label>
                    <div class="pasteur-evidence-grid grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($evidencias as $index => $foto)
                            @php
                                $fotoUrl = asset('storage/' . ltrim(str_replace('\\', '/', $foto), '/'));
                            @endphp
                            <div class="pasteur-evidence-card relative group border border-gray-200 rounded-lg p-2 bg-gray-50 hover:bg-gray-100 transition">
                                <img src="{{ $fotoUrl }}"
                                     alt="Evidencia {{ $loop->iteration }}"
                                     class="w-full h-36 object-cover rounded-md border border-gray-300 mb-2 sm:h-40">

                                <div class="pasteur-evidence-actions">
                                    <button type="button"
                                       data-current-evidence-index="{{ $index }}"
                                       class="inline-flex min-h-10 shrink-0 items-center justify-center gap-1 whitespace-nowrap rounded-lg px-3 text-sm font-semibold text-blue-600 transition hover:bg-blue-50 hover:text-blue-800">
                                        <i class="fas fa-expand text-xs"></i>
                                        <span class="whitespace-nowrap">Ver</span>
                                    </button>

                                    <label class="inline-flex min-h-10 shrink-0 cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg px-3 transition hover:bg-red-50">
                                        <input type="checkbox"
                                               name="eliminar_fotos[]"
                                               value="{{ $index }}"
                                               class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        <span class="text-red-600 text-sm hover:text-red-800">
                                            <i class="fas fa-trash text-xs"></i>
                                            Eliminar
                                        </span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Las imagenes se conservaran si no marca eliminar.</p>
                </div>
            @endif

            <div class="mb-6">
                <label for="evidencia_fotos" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-camera text-blue-600 mr-1"></i>
                    Agregar nuevas evidencias
                </label>
                <input type="file" id="evidencia_fotos" name="evidencia_fotos[]" multiple accept="image/*" class="hidden">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <button type="button"
                                id="btn_evidencia_fotos_galeria"
                                class="responsive-action responsive-action--secondary w-full">
                            <i class="fas fa-images"></i>
                            Subir desde galeria
                        </button>
                        <input type="file" id="evidencia_fotos_galeria" accept="image/*" multiple class="sr-only">
                    </div>

                    <div>
                        <button type="button"
                                id="btn_evidencia_fotos_camara"
                                class="responsive-action responsive-action--success w-full">
                            <i class="fas fa-camera-retro"></i>
                            Tomar foto ahora
                        </button>
                        <input type="file" id="evidencia_fotos_camara" accept="image/*" capture="environment" multiple class="sr-only">
                    </div>
                </div>

                <p id="fotos_resumen" class="mt-3 text-sm text-gray-500">Sin imagenes seleccionadas</p>
                <div id="preview_fotos" class="mt-3 flex flex-wrap gap-2"></div>

                @error('evidencia_fotos')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                @error('evidencia_fotos.*')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="pasteur-form-actions responsive-actions">
                <a href="{{ $cancelUrl }}"
                   class="responsive-action responsive-action--secondary flex-1">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
                <button type="submit"
                        class="responsive-action flex-1">
                    <i class="fas fa-save mr-2"></i>
                    Actualizar análisis
                </button>
            </div>
        </form>
    </div>
</div>
</div>

<div id="editEvidenceModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/75 p-2 sm:p-4">
    <div class="w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <h3 id="editEvidenceTitle" class="min-w-0 break-words text-base font-bold text-gray-900">Evidencia</h3>
            <button type="button" id="editEvidenceClose" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition hover:bg-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="editEvidenceStage" class="relative bg-gray-950 p-3" style="touch-action: pan-y; user-select: none; -webkit-user-select: none;" onclick="event.stopPropagation()">
            <button type="button" id="editEvidencePrev"
                    class="absolute left-5 top-1/2 z-10 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-gray-600 bg-gray-800/60 text-2xl text-white backdrop-blur-sm transition hover:bg-gray-700/80">
                <i class="fas fa-chevron-left"></i>
            </button>
            <img id="editEvidenceImage" src="" alt="Evidencia" draggable="false" style="touch-action: pan-y; user-select: none; -webkit-user-drag: none;" class="mx-auto max-h-[70vh] w-auto rounded-lg object-contain sm:max-h-[75vh]">
            <button type="button" id="editEvidenceNext"
                    class="absolute right-5 top-1/2 z-10 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-gray-600 bg-gray-800/60 text-2xl text-white backdrop-blur-sm transition hover:bg-gray-700/80">
                <i class="fas fa-chevron-right"></i>
            </button>
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 rounded-lg border border-gray-700 bg-gray-900/80 px-4 py-2 text-sm font-mono text-white backdrop-blur-sm">
                <span id="editEvidenceCounter"></span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputFotos = document.getElementById('evidencia_fotos');
    const botonGaleria = document.getElementById('btn_evidencia_fotos_galeria');
    const botonCamara = document.getElementById('btn_evidencia_fotos_camara');
    const galeriaFotosInput = document.getElementById('evidencia_fotos_galeria');
    const camaraFotosInput = document.getElementById('evidencia_fotos_camara');
    const previewFotos = document.getElementById('preview_fotos');
    const fotosResumen = document.getElementById('fotos_resumen');
    const editarForm = document.getElementById('editarAnalisisPasteurizadoraForm');
    const maxFotoSize = 5 * 1024 * 1024;
    const soportaDataTransfer = typeof DataTransfer !== 'undefined';
    const evidenciaActualModal = document.getElementById('editEvidenceModal');
    const evidenciaActualStage = document.getElementById('editEvidenceStage');
    const evidenciaActualImage = document.getElementById('editEvidenceImage');
    const evidenciaActualTitle = document.getElementById('editEvidenceTitle');
    const evidenciaActualClose = document.getElementById('editEvidenceClose');
    const evidenciaActualPrev = document.getElementById('editEvidencePrev');
    const evidenciaActualNext = document.getElementById('editEvidenceNext');
    const evidenciaActualCounter = document.getElementById('editEvidenceCounter');
    const evidenciasActuales = @json($evidenciasActualesUrls);
    let evidenciaActualIndex = 0;
    let evidenciaActualSwipe = null;
    const evidenciaActualSwipeDistance = 50;
    const evidenciaActualSwipeVerticalTolerance = 1.25;

    function updateEvidenciaActualModal() {
        const total = evidenciasActuales.length;
        const hasMultipleImages = total > 1;

        evidenciaActualImage.src = evidenciasActuales[evidenciaActualIndex] || '';
        evidenciaActualTitle.textContent = total > 0 ? `Evidencia #${evidenciaActualIndex + 1}` : 'Evidencia';
        evidenciaActualCounter.textContent = total > 0 ? `${evidenciaActualIndex + 1} / ${total}` : '';

        evidenciaActualPrev.classList.toggle('hidden', !hasMultipleImages);
        evidenciaActualNext.classList.toggle('hidden', !hasMultipleImages);
        evidenciaActualPrev.classList.toggle('flex', hasMultipleImages);
        evidenciaActualNext.classList.toggle('flex', hasMultipleImages);
    }

    function openEvidenciaActualModal(index) {
        evidenciaActualIndex = Number.isInteger(index) ? index : 0;
        updateEvidenciaActualModal();
        evidenciaActualModal.classList.remove('hidden');
        evidenciaActualModal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function changeEvidenciaActual(direction) {
        if (evidenciasActuales.length <= 1) {
            return;
        }

        evidenciaActualIndex = (evidenciaActualIndex + direction + evidenciasActuales.length) % evidenciasActuales.length;
        updateEvidenciaActualModal();
    }

    function closeEvidenciaActualModal() {
        evidenciaActualModal.classList.add('hidden');
        evidenciaActualModal.classList.remove('flex');
        evidenciaActualImage.src = '';
        evidenciaActualSwipe = null;
        document.body.style.overflow = '';
    }

    function getEvidenciaActualSwipePoint(event) {
        const source = event.changedTouches?.[0] || event.touches?.[0] || event;

        return {
            x: source.clientX ?? 0,
            y: source.clientY ?? 0,
        };
    }

    function startEvidenciaActualSwipe(event) {
        if (event.target?.closest('button') || evidenciasActuales.length <= 1) {
            return;
        }

        if (event.pointerId !== undefined && event.currentTarget?.setPointerCapture) {
            try {
                event.currentTarget.setPointerCapture(event.pointerId);
            } catch (error) {}
        }

        const point = getEvidenciaActualSwipePoint(event);
        evidenciaActualSwipe = {
            startX: point.x,
            startY: point.y,
            lastX: point.x,
            lastY: point.y,
        };
    }

    function moveEvidenciaActualSwipe(event) {
        if (!evidenciaActualSwipe) {
            return;
        }

        const point = getEvidenciaActualSwipePoint(event);
        const deltaX = point.x - evidenciaActualSwipe.startX;
        const deltaY = point.y - evidenciaActualSwipe.startY;

        evidenciaActualSwipe.lastX = point.x;
        evidenciaActualSwipe.lastY = point.y;

        if (Math.abs(deltaX) > Math.abs(deltaY) && event.cancelable) {
            event.preventDefault();
        }
    }

    function finishEvidenciaActualSwipe(event) {
        if (!evidenciaActualSwipe) {
            return;
        }

        const point = getEvidenciaActualSwipePoint(event);
        const endX = point.x || evidenciaActualSwipe.lastX;
        const endY = point.y || evidenciaActualSwipe.lastY;
        const deltaX = endX - evidenciaActualSwipe.startX;
        const deltaY = endY - evidenciaActualSwipe.startY;
        const isHorizontalSwipe = Math.abs(deltaX) >= evidenciaActualSwipeDistance
            && Math.abs(deltaX) > Math.abs(deltaY) * evidenciaActualSwipeVerticalTolerance;

        if (isHorizontalSwipe) {
            changeEvidenciaActual(deltaX < 0 ? 1 : -1);
        }

        evidenciaActualSwipe = null;
        if (event.pointerId !== undefined && event.currentTarget?.releasePointerCapture) {
            try {
                event.currentTarget.releasePointerCapture(event.pointerId);
            } catch (error) {}
        }
    }

    function cancelEvidenciaActualSwipe(event) {
        evidenciaActualSwipe = null;
        if (event.pointerId !== undefined && event.currentTarget?.releasePointerCapture) {
            try {
                event.currentTarget.releasePointerCapture(event.pointerId);
            } catch (error) {}
        }
    }

    function actualizarResumenFotos(totalFotos) {
        fotosResumen.textContent = totalFotos
            ? `${totalFotos} imagen${totalFotos === 1 ? '' : 'es'} seleccionada${totalFotos === 1 ? '' : 's'}`
            : 'Sin imagenes seleccionadas';
    }

    function crearDataTransfer(files) {
        const dataTransfer = new DataTransfer();
        files.forEach((file) => dataTransfer.items.add(file));
        return dataTransfer;
    }

    function getFotosPrincipales() {
        return Array.from(inputFotos.files || []);
    }

    function getFotosFallback() {
        return [
            ...Array.from(galeriaFotosInput.files || []),
            ...Array.from(camaraFotosInput.files || []),
        ];
    }

    function renderPreview(files, permitirEliminar) {
        previewFotos.innerHTML = '';
        actualizarResumenFotos(files.length);

        files.forEach((file, index) => {
            if (!file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'relative group border border-gray-200 rounded-lg p-2 bg-gray-50';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-24 h-24 object-cover rounded-md border border-gray-300 mb-2';

                const fileName = document.createElement('p');
                fileName.className = 'text-xs text-gray-600 truncate w-24';
                fileName.textContent = file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name;

                imgContainer.appendChild(img);
                imgContainer.appendChild(fileName);

                if (permitirEliminar) {
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'absolute -right-2 -top-2 flex h-10 w-10 items-center justify-center rounded-full bg-red-500 text-base font-bold text-white shadow transition-opacity hover:bg-red-600 sm:opacity-0 sm:group-hover:opacity-100';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.onclick = function() {
                        const fotos = getFotosPrincipales();
                        fotos.splice(index, 1);
                        inputFotos.files = crearDataTransfer(fotos).files;
                        renderPreview(getFotosPrincipales(), true);
                    };
                    imgContainer.appendChild(removeBtn);
                }

                previewFotos.appendChild(imgContainer);
            };
            reader.readAsDataURL(file);
        });
    }

    function agregarFotos(files) {
        const fotosActuales = getFotosPrincipales();
        const firmas = new Set(fotosActuales.map((file) => `${file.name}-${file.size}-${file.lastModified}`));
        const nuevasFotos = [...fotosActuales];

        Array.from(files || []).forEach((file) => {
            if (!file.type.startsWith('image/')) {
                alert(`El archivo ${file.name} no es una imagen valida.`);
                return;
            }

            if (file.size > maxFotoSize) {
                alert(`La imagen ${file.name} supera el tamano maximo de 5MB.`);
                return;
            }

            const firma = `${file.name}-${file.size}-${file.lastModified}`;
            if (firmas.has(firma)) {
                return;
            }

            firmas.add(firma);
            nuevasFotos.push(file);
        });

        inputFotos.files = crearDataTransfer(nuevasFotos).files;
        renderPreview(getFotosPrincipales(), true);
    }

    botonGaleria.addEventListener('click', function() {
        galeriaFotosInput.click();
    });

    botonCamara.addEventListener('click', function() {
        camaraFotosInput.click();
    });

    document.querySelectorAll('[data-current-evidence-index]').forEach(function(button) {
        button.addEventListener('click', function() {
            openEvidenciaActualModal(parseInt(this.dataset.currentEvidenceIndex || '0', 10));
        });
    });

    evidenciaActualPrev.addEventListener('click', function(event) {
        event.stopPropagation();
        changeEvidenciaActual(-1);
    });

    evidenciaActualNext.addEventListener('click', function(event) {
        event.stopPropagation();
        changeEvidenciaActual(1);
    });

    evidenciaActualClose.addEventListener('click', closeEvidenciaActualModal);
    evidenciaActualModal.addEventListener('click', function(event) {
        if (event.target === evidenciaActualModal) {
            closeEvidenciaActualModal();
        }
    });

    if (window.PointerEvent) {
        evidenciaActualStage.addEventListener('pointerdown', startEvidenciaActualSwipe);
        evidenciaActualStage.addEventListener('pointermove', moveEvidenciaActualSwipe);
        evidenciaActualStage.addEventListener('pointerup', finishEvidenciaActualSwipe);
        evidenciaActualStage.addEventListener('pointercancel', cancelEvidenciaActualSwipe);
    } else {
        evidenciaActualStage.addEventListener('touchstart', startEvidenciaActualSwipe, { passive: true });
        evidenciaActualStage.addEventListener('touchmove', moveEvidenciaActualSwipe, { passive: false });
        evidenciaActualStage.addEventListener('touchend', finishEvidenciaActualSwipe);
        evidenciaActualStage.addEventListener('touchcancel', cancelEvidenciaActualSwipe);
    }

    document.addEventListener('keydown', function(event) {
        if (evidenciaActualModal.classList.contains('hidden')) {
            return;
        }

        if (event.key === 'Escape') {
            closeEvidenciaActualModal();
        }

        if (event.key === 'ArrowLeft') {
            changeEvidenciaActual(-1);
        }

        if (event.key === 'ArrowRight') {
            changeEvidenciaActual(1);
        }
    });

    inputFotos.addEventListener('change', function() {
        renderPreview(getFotosPrincipales(), true);
    });

    if (soportaDataTransfer) {
        galeriaFotosInput.addEventListener('change', function() {
            agregarFotos(this.files);
            this.value = '';
        });

        camaraFotosInput.addEventListener('change', function() {
            agregarFotos(this.files);
            this.value = '';
        });

        renderPreview(getFotosPrincipales(), true);
    } else {
        galeriaFotosInput.name = 'evidencia_fotos[]';
        camaraFotosInput.name = 'evidencia_fotos[]';
        inputFotos.disabled = true;

        const renderizarFallback = function() {
            renderPreview(getFotosFallback(), false);
        };

        galeriaFotosInput.addEventListener('change', renderizarFallback);
        camaraFotosInput.addEventListener('change', renderizarFallback);
        renderizarFallback();
    }

    editarForm.addEventListener('submit', function() {
        if (soportaDataTransfer) {
            inputFotos.disabled = getFotosPrincipales().length === 0;
        }
    });
});
</script>
@endsection
