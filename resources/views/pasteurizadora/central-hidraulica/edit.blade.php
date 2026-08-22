@extends('layouts.app')

@section('title', 'Editar Analisis - Central Hidraulica')

@section('content')
@php
    $evidencias = collect($analisis->evidencia_fotos ?? [])->filter()->values();
    $badge = $analisis->estado_badge;
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

    .pasteur-form-shell label i,
    .pasteur-form-shell p i {
        color: var(--primary-blue);
    }

    .pasteur-form-shell h1,
    .pasteur-form-shell p,
    .pasteur-form-shell span,
    .pasteur-form-shell label,
    .pasteur-form-shell a,
    .pasteur-form-shell button,
    .pasteur-form-shell input,
    .pasteur-form-shell select,
    .pasteur-form-shell textarea {
        overflow-wrap: anywhere;
    }

    @media (max-width: 640px) {
        .pasteur-form-shell {
            padding: 1.25rem 0.75rem;
        }

        .pasteur-form-shell h1 {
            font-size: 1.5rem;
            line-height: 1.25;
        }

        .pasteur-form-shell input,
        .pasteur-form-shell select,
        .pasteur-form-shell textarea {
            font-size: 16px;
        }
    }
</style>

<div class="pasteur-form-shell mx-auto max-w-4xl px-4 py-10">
    <div class="mb-8">
        <div class="mb-4 flex items-start gap-3">
            <a href="{{ $analisisRoute('show', $analisis->id) }}"
               class="mt-1 text-gray-400 transition hover:text-blue-600"
               aria-label="Volver">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div class="min-w-0">
                <h1 class="text-2xl font-bold text-gray-800 sm:text-3xl">Editar Analisis Central Hidraulica</h1>
                <p class="mt-1 text-sm text-gray-600">
                    ID: #{{ $analisis->id }}
                    @if($analisis->numero_orden)
                        <span class="mx-1">|</span> Orden: {{ $analisis->numero_orden }}
                    @endif
                </p>
            </div>
        </div>

        <div class="pasteur-context">
            <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4 lg:items-center">
                <div>
                    <p class="font-semibold text-gray-600">Linea</p>
                    <p class="text-gray-900">{{ $analisis->linea->nombre ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-600">Piso</p>
                    <p class="text-gray-900">{{ $analisis->piso_label }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-600">Componente / revision</p>
                    <p class="text-gray-900">{{ $analisis->componente_nombre }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-600">Estado actual</p>
                    <span class="mt-1 inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $badge['class'] }}">
                        {{ $analisis->estado }}
                    </span>
                </div>
            </div>
        </div>

        @if($evidencias->isNotEmpty())
            <section class="mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-500">Evidencias actuales</h2>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($evidencias as $index => $foto)
                        <div class="overflow-hidden rounded-lg border border-gray-200">
                            <img src="{{ asset('storage/' . ltrim($foto, '/')) }}" alt="Evidencia {{ $index + 1 }}" class="h-36 w-full object-cover">
                            <div class="flex items-center justify-between gap-2 p-3">
                                <span class="text-sm font-semibold text-gray-700">Evidencia {{ $index + 1 }}</span>
                                <form action="{{ $analisisRoute('delete-foto', [$analisis->id, $index]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <div class="pasteur-form-card">
        @include('pasteurizadora.central-hidraulica.partials.form', [
            'formAction' => $analisisRoute('update', $analisis->id),
        ])
    </div>
</div>
@endsection
