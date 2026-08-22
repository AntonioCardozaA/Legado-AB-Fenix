@extends('layouts.app')

@section('title', 'Registro Rapido - Central Hidraulica')

@section('content')
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

    .pasteur-context img {
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0;
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

    .pasteur-form-shell .create-actions {
        flex-wrap: wrap;
    }

    .central-check-option.is-selected {
        border-color: #3b82f6;
        background: #eff6ff;
        color: #1e40af;
    }

    @media (max-width: 640px) {
        .pasteur-form-shell {
            padding: 1.25rem 0.75rem;
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

        .pasteur-form-shell .create-action,
        .pasteur-form-shell .responsive-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="pasteur-form-shell max-w-4xl mx-auto py-10 px-4">
    <div class="mb-8">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ $analisisRoute('index', ['linea_id' => $linea->id]) }}"
                   class="text-gray-400 transition hover:text-blue-600">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-800">Registro Rapido</h1>
            </div>
        </div>

        <div class="pasteur-context">
            <div class="flex flex-col items-center gap-6 md:flex-row">
                <div class="grid flex-grow grid-cols-1 gap-4 md:grid-cols-4 md:gap-6">
                    <div class="text-center md:text-left">
                        <p class="mb-1 text-sm font-semibold text-gray-600">Linea</p>
                        <p class="font-medium text-gray-800">{{ $linea->nombre }}</p>
                    </div>

                    <div class="text-center md:text-left">
                        <p class="mb-1 text-sm font-semibold text-gray-600">
                            <i class="fas fa-layer-group mr-1"></i>
                            Piso
                        </p>
                        <p class="font-medium text-gray-800" id="summary-piso">Sin seleccionar</p>
                    </div>

                    <div class="text-center md:text-left">
                        <p class="mb-1 text-sm font-semibold text-gray-600">
                            <i class="fas fa-oil-can mr-1"></i>
                            Componente / revision
                        </p>
                        <p class="font-medium text-gray-800" id="summary-componente">Sin seleccionar</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pasteur-form-card">
        @include('pasteurizadora.central-hidraulica.partials.form', [
            'formAction' => $analisisRoute('store-quick'),
            'mostrarEvidencias' => true,
            'mostrarTextoPiezasVerificadas' => false,
            'mostrarResumenEvidencias' => false,
        ])
    </div>
</div>
@endsection
