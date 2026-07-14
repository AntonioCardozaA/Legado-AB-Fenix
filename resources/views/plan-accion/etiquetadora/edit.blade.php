@extends('layouts.app')

@section('title', 'Editar Actividad | Etiquetadora')

@section('content')
@include('etiquetadora.partials.styles')

<div class="etq-page">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <header class="mb-8">
            <div class="mb-4 flex items-center gap-3">
                <a href="{{ route('plan-accion.index', ['tipo' => 'etiquetadora', 'linea_id' => $plan->linea_id]) }}"
                   class="text-gray-400 transition hover:text-blue-600">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Editar Plan de Accion</h1>
                    <p class="mt-1 text-sm text-gray-600">Actividad #{{ $plan->id }} | Etiquetadora</p>
                </div>
            </div>

            <div class="etq-context-strip">
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-4">
                    <div class="flex justify-center md:justify-start">
                        <div class="flex min-h-20 min-w-20 items-center justify-center rounded-xl bg-white p-2 shadow-sm">
                            @include('etiquetadora.partials.presentation-icons', ['linea' => $plan->linea ?? $plan->linea_id, 'size' => 'sm'])
                        </div>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-600">Equipo</p>
                        <p class="text-gray-800">Etiquetadora</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-600">Linea</p>
                        <p class="text-gray-800">{{ $plan->linea->nombre ?? 'Linea ' . $plan->linea_id }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-600">Responsable actual</p>
                        <p class="text-gray-800">{{ $plan->responsable->name ?? 'Usuario actual' }}</p>
                    </div>
                </div>
            </div>
        </header>

        <div class="etq-form-surface">
            <form action="{{ route('plan-accion.update', ['plan_accion' => $plan->id, 'tipo' => 'etiquetadora']) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')
                <input type="hidden" name="tipo" value="etiquetadora">

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        <i class="fas fa-industry mr-1 text-blue-600"></i>
                        Linea *
                    </label>
                    <select name="linea_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($lineas as $lineaItem)
                            <option value="{{ $lineaItem->id }}" @selected((int) old('linea_id', $plan->linea_id) === $lineaItem->id)>{{ $lineaItem->nombre }}</option>
                        @endforeach
                    </select>
                    @error('linea_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        <i class="fas fa-user-check mr-1 text-blue-600"></i>
                        Responsable
                    </label>
                    <select name="responsable_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Usuario actual</option>
                        @foreach(($usuariosResponsables ?? collect()) as $usuario)
                            <option value="{{ $usuario->id }}" @selected((int) old('responsable_id', $plan->responsable_id ?? auth()->id()) === $usuario->id)>
                                {{ $usuario->name }}{{ $usuario->email ? ' - ' . $usuario->email : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('responsable_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        <i class="fas fa-sticky-note mr-1 text-blue-600"></i>
                        Actividad *
                    </label>
                    <textarea name="actividad" rows="4" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('actividad', $plan->actividad) }}</textarea>
                    @error('actividad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    @foreach(['1','2','3','4'] as $n)
                        @php($field = 'fecha_pcm' . $n)
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                <i class="far fa-calendar-alt mr-1 text-blue-600"></i>
                                PCM {{ $n }}
                            </label>
                            <input type="date" name="{{ $field }}" value="{{ old($field, optional($plan->{$field})->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    @endforeach
                </div>

                <div class="create-actions border-t border-gray-200 pt-5">
                    <a href="{{ route('plan-accion.index', ['tipo' => 'etiquetadora', 'linea_id' => $plan->linea_id]) }}" class="create-action create-action--secondary flex-1">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="create-action flex-1">
                        <i class="fas fa-save"></i>
                        Actualizar Actividad
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
