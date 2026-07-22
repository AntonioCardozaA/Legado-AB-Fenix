@extends('layouts.app')

@php
    $structuredContent = $structuredContent ?? $plan->currentStructuredContent() ?? [];
    $recommendedActions = collect(data_get($structuredContent, 'recommended_actions', []))
        ->filter(fn ($action) => is_array($action))
        ->values();
    $detectedProblem = data_get($structuredContent, 'detected_problem', $plan->detected_problem);
    $technicalJustification = data_get($structuredContent, 'technical_justification', $plan->technical_justification);
    $riskIfNotExecuted = data_get($structuredContent, 'risk_if_not_executed', $plan->risk_if_not_executed);
    $reviewNotes = $plan->final_observations;
    $planSummaryItems = array_filter([
        filled($detectedProblem) ? 'Problema detectado: ' . $detectedProblem : null,
        filled($technicalJustification) ? 'Justificacion tecnica: ' . $technicalJustification : null,
        filled($riskIfNotExecuted) ? 'Riesgo si no se ejecuta: ' . $riskIfNotExecuted : null,
        filled($reviewNotes) ? 'Notas del revisor: ' . $reviewNotes : null,
    ]);
    $hasAiReviewDetails = $plan->isAiSuggested() && (
        filled($detectedProblem) ||
        filled($technicalJustification) ||
        filled($riskIfNotExecuted) ||
        filled($reviewNotes) ||
        $recommendedActions->isNotEmpty()
    );
@endphp

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-white flex items-center">
                            <i class="fas fa-edit mr-3"></i>
                            Editar Plan de Acción
                        </h3>
                        <a href="{{ route('plan-accion.lavadora.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-medium rounded-lg transition-all duration-200 backdrop-blur-sm">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver
                        </a>
                    </div>
                </div>
                
                <form action="{{ route('plan-accion.lavadora.update') }}" method="POST" id="editPlanForm">
                    @csrf
                    {{-- No usar @method('PUT') porque es una ruta POST --}}
                    
                    <input type="hidden" name="id" value="{{ $plan->id }}">
                    
                    <div class="p-6">
                        {{-- Alerta de validación --}}
                        @if($errors->any())
                            <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 animate-slideDown">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-red-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-red-800">Por favor, corrija los siguientes errores:</h4>
                                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <button type="button" class="ml-auto text-red-500 hover:text-red-600" onclick="this.closest('.alert').remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- Información básica --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="group">
                                <label for="tipo" class="block text-sm font-medium text-gray-700 mb-2 group-hover:text-blue-600 transition-colors">
                                    <i class="fas fa-tag mr-2 text-blue-500"></i>
                                    Tipo de Línea
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <div class="relative">
                                    <select name="tipo" id="tipo" 
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all appearance-none bg-white @error('tipo') border-red-500 @enderror">
                                        <option value="lavadora" {{ $tipo == 'lavadora' ? 'selected' : '' }}>Lavadora</option>
                                        <option value="otra" {{ $tipo != 'lavadora' ? 'selected' : '' }}>Otras Líneas</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                        <i class="fas fa-chevron-down text-gray-400"></i>
                                    </div>
                                </div>
                                @error('tipo')
                                    <p class="mt-1 text-sm text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="group">
                                <label for="linea_id" class="block text-sm font-medium text-gray-700 mb-2 group-hover:text-blue-600 transition-colors">
                                    <i class="fas fa-industry mr-2 text-blue-500"></i>
                                    Línea / Lavadora
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <select name="linea_id" id="linea_id" 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all select2 @error('linea_id') border-red-500 @enderror" 
                                        required>
                                    <option value="">Seleccione una línea</option>
                                    @foreach($lavadoras as $lavadora)
                                        <option value="{{ $lavadora->id }}" {{ $plan->linea_id == $lavadora->id ? 'selected' : '' }}>
                                            {{ $lavadora->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('linea_id')
                                    <p class="mt-1 text-sm text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="group">
                                <label for="responsable_id" class="block text-sm font-medium text-gray-700 mb-2 group-hover:text-blue-600 transition-colors">
                                    <i class="fas fa-user-check mr-2 text-blue-500"></i>
                                    Responsable
                                </label>
                                <select name="responsable_id" id="responsable_id"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all select2 @error('responsable_id') border-red-500 @enderror">
                                    <option value="">Sin responsable</option>
                                    @foreach(($usuariosResponsables ?? collect()) as $usuario)
                                        <option value="{{ $usuario->id }}" {{ (int) old('responsable_id', $plan->responsable_id) === $usuario->id ? 'selected' : '' }}>
                                            {{ $usuario->name }}{{ $usuario->email ? ' - ' . $usuario->email : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('responsable_id')
                                    <p class="mt-1 text-sm text-red-500 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        {{-- Actividad --}}
                        <div class="mb-6 group">
                            <label for="actividad" class="block text-sm font-medium text-gray-700 mb-2 group-hover:text-blue-600 transition-colors">
                                <i class="fas fa-tasks mr-2 text-blue-500"></i>
                                Actividad
                                <span class="text-red-500 ml-1">*</span>
                            </label>
                            <textarea name="actividad" 
                                      id="actividad" 
                                      rows="4" 
                                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-y @error('actividad') border-red-500 @enderror"
                                      placeholder="Describa la actividad a realizar..."
                                      required>{{ old('actividad', $plan->actividad) }}</textarea>
                            @error('actividad')
                                <p class="mt-1 text-sm text-red-500 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                            <div id="actividad_counter" class="text-right text-xs text-gray-500 mt-1"></div>
                        </div>

                        @if($hasAiReviewDetails)
                            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm">
                                <div>
                                    <h4 class="text-base font-semibold text-amber-950 flex items-center gap-2">
                                        <i class="fas fa-list-check text-amber-600"></i>
                                        Resumen del plan de accion
                                    </h4>
                                    <p class="mt-1 text-sm text-amber-800">
                                        Vista resumida con los puntos importantes del plan aprobado.
                                    </p>
                                </div>

                                <div class="mt-4 rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
                                    <div class="space-y-3 text-sm leading-6 text-gray-700">
                                        @foreach($planSummaryItems as $item)
                                            <p class="flex gap-3">
                                                <span class="mt-1 text-amber-600">-</span>
                                                <span>{{ $item }}</span>
                                            </p>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-4 rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
                                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Acciones a ejecutar</p>
                                    <div class="mt-3 space-y-3">
                                        @forelse($recommendedActions as $action)
                                            <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-4">
                                                <p class="font-semibold text-gray-900">
                                                    {{ data_get($action, 'order', $loop->iteration) }}. {{ data_get($action, 'activity', 'Sin actividad definida') }}
                                                </p>
                                                @if(filled(data_get($action, 'technical_detail')))
                                                    <p class="mt-2 text-sm leading-6 text-gray-700">{{ data_get($action, 'technical_detail') }}</p>
                                                @endif
                                            </div>
                                        @empty
                                            <p class="rounded-xl border border-dashed border-amber-200 bg-white px-4 py-5 text-sm text-gray-500">
                                                No se registraron acciones en la aprobacion.
                                            </p>
                                        @endforelse
                                    </div>
                                </div>

                                @if($plan->maintenanceEvent?->sourceUrl())
                                    <div class="mt-4">
                                        <a href="{{ $plan->maintenanceEvent->sourceUrl() }}"
                                           class="inline-flex items-center gap-2 rounded-xl border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-900 transition hover:bg-amber-100">
                                            <i class="fas fa-arrow-up-right-from-square"></i>
                                            Abrir registro original del hallazgo
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Fechas PCM --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                Fechas PCM
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                @for($i = 1; $i <= 4; $i++)
                                    <div class="group">
                                        <label for="fecha_pcm{{ $i }}" class="block text-xs font-medium text-gray-600 mb-1">
                                            PCM {{ $i }}
                                        </label>
                                        <input type="date" 
                                               name="fecha_pcm{{ $i }}" 
                                               id="fecha_pcm{{ $i }}" 
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('fecha_pcm'.$i) border-red-500 @enderror"
                                               value="{{ old('fecha_pcm'.$i, $plan->{'fecha_pcm'.$i} ? $plan->{'fecha_pcm'.$i}->format('Y-m-d') : '') }}">
                                        @error('fecha_pcm'.$i)
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endfor
                            </div>
                            <p class="text-xs text-gray-500 mt-2 flex items-center">
                                <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                Las fechas deben estar en orden cronológico (PCM1 antes que PCM2, etc.)
                            </p>
                        </div>

                        {{-- Información adicional del registro --}}
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h6 class="text-sm font-semibold text-gray-600 mb-3 flex items-center">
                                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                                Información del registro
                            </h6>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-user-check text-indigo-500"></i>
                                    <span class="text-sm text-gray-600">
                                        <span class="font-medium">Responsable:</span>
                                        {{ $plan->responsable?->name ?? 'Sin responsable' }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-user-plus text-green-500"></i>
                                    <span class="text-sm text-gray-600">
                                        <span class="font-medium">Registrado por:</span>
                                        {{ $plan->registradoPor?->name ?? 'Sin dato historico' }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-plus-circle text-green-500"></i>
                                    <span class="text-sm text-gray-600">
                                        <span class="font-medium">Creado:</span> 
                                        {{ $plan->created_at ? $plan->created_at->format('d/m/Y H:i') : 'N/A' }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-user-cog text-purple-500"></i>
                                    <span class="text-sm text-gray-600">
                                        <span class="font-medium">Ejecutado por:</span>
                                        {{ $plan->ejecutadoPor?->name ?? ($plan->completado ? 'Sin dato historico' : 'Pendiente') }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-calendar-check text-purple-500"></i>
                                    <span class="text-sm text-gray-600">
                                        <span class="font-medium">Fecha ejecucion:</span>
                                        {{ $plan->fecha_ejecucion ? $plan->fecha_ejecucion->format('d/m/Y H:i') : ($plan->completado ? 'Sin dato historico' : 'Pendiente') }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-edit text-blue-500"></i>
                                    <span class="text-sm text-gray-600">
                                        <span class="font-medium">Última modificación:</span> 
                                        {{ $plan->updated_at ? $plan->updated_at->format('d/m/Y H:i') : 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <button type="button" 
                                    onclick="confirmDelete({{ $plan->id }})"
                                    class="w-full sm:w-auto order-2 sm:order-1 px-6 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 flex items-center justify-center">
                                <i class="fas fa-trash mr-2"></i>
                                Eliminar
                            </button>
                            
                            <div class="flex w-full sm:w-auto gap-3 order-1 sm:order-2">
                                <button type="submit" 
                                        id="submitBtn"
                                        class="flex-1 sm:flex-none px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white text-sm font-medium rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i>
                                    Actualizar Plan
                                </button>
                                <a href="{{ route('plan-accion.lavadora.index') }}" 
                                   class="flex-1 sm:flex-none px-6 py-2.5 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Formulario oculto para eliminar --}}
                <form id="deleteForm" action="{{ route('plan-accion.lavadora.destroy') }}" method="POST" class="hidden">
                    @csrf
                    <input type="hidden" name="id" id="delete_id" value="">
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Animaciones personalizadas */
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-slideDown {
        animation: slideDown 0.3s ease-out;
    }
    
    /* Estilos para Select2 con Tailwind */
    .select2-container--default .select2-selection--single {
        @apply border border-gray-300 rounded-lg min-h-[42px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
    }
    
    .select2-container--default.select2-container--focus .select2-selection--single {
        @apply border-blue-500 ring-2 ring-blue-500 ring-opacity-50;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        @apply leading-[42px] px-4 text-gray-700;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        @apply h-[42px] right-2;
    }
    
    .select2-container--default .select2-search--dropdown .select2-search__field {
        @apply border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
    }
    
    .select2-dropdown {
        @apply border border-gray-300 rounded-lg shadow-lg;
    }
    
    /* Loading spinner */
    .loading-spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3b82f6;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        animation: spin 1s linear infinite;
        display: inline-block;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Scrollbar personalizado */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        @apply bg-gray-100 rounded-lg;
    }
    
    ::-webkit-scrollbar-thumb {
        @apply bg-gray-400 rounded-lg hover:bg-gray-500 transition-colors;
    }
</style>
@endpush

@push('scripts')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Moment.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<script>
$(document).ready(function() {
    // Configuración de Toast para notificaciones
    const toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // Inicializar Select2
    $('.select2').select2({
        theme: 'default',
        width: '100%',
        placeholder: 'Seleccione una opción',
        allowClear: true,
        language: {
            noResults: function() {
                return "No se encontraron resultados";
            },
            searching: function() {
                return "Buscando...";
            }
        }
    });

    // Variable para controlar el envío
    let isSubmitting = false;

    // Cambiar el tipo de línea con animación
    $('#tipo').change(function() {
        var tipo = $(this).val();
        var url = "{{ route('plan-accion.lavadora.edit') }}";
        
        Swal.fire({
            title: '¿Cambiar tipo de línea?',
            text: 'Se recargará la página para actualizar las líneas disponibles',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear un formulario para enviar el tipo y el ID
                var form = $('<form action="' + url + '" method="POST"></form>');
                form.append('@csrf');
                form.append('<input type="hidden" name="tipo" value="' + tipo + '">');
                form.append('<input type="hidden" name="id" value="{{ $plan->id }}">');
                $('body').append(form);
                form.submit();
            } else {
                $(this).val('{{ $tipo }}').trigger('change');
            }
        });
    });

    // Validar fechas PCM
    function validarFechasPCM() {
        let fechas = [];
        let fechasOrdenadas = true;
        
        for (let i = 1; i <= 4; i++) {
            let fecha = $('#fecha_pcm' + i).val();
            if (fecha) {
                fechas.push({ index: i, fecha: moment(fecha) });
            }
        }
        
        // Verificar que las fechas sean coherentes
        for (let i = 0; i < fechas.length - 1; i++) {
            if (fechas[i].fecha.isAfter(fechas[i + 1].fecha)) {
                fechasOrdenadas = false;
                break;
            }
        }
        
        if (!fechasOrdenadas && fechas.length > 1) {
            toast.fire({
                icon: 'warning',
                title: 'Las fechas PCM deben estar en orden cronológico'
            });
            
            // Resaltar campos con error
            fechas.forEach((f, idx) => {
                if (idx < fechas.length - 1 && fechas[idx].fecha.isAfter(fechas[idx + 1].fecha)) {
                    $('#fecha_pcm' + fechas[idx + 1].index).addClass('border-red-500');
                }
            });
            
            return false;
        }
        
        // Remover clases de error
        $('[id^=fecha_pcm]').removeClass('border-red-500');
        return true;
    }

    $('[id^=fecha_pcm]').on('change blur', function() {
        validarFechasPCM();
    });

    // Contador de caracteres para actividad
    $('#actividad').on('input', function() {
        let maxLength = 1000;
        let currentLength = $(this).val().length;
        let remaining = maxLength - currentLength;
        
        let counter = $('#actividad_counter');
        counter.html('');
        
        if (remaining < 100) {
            let color = remaining < 0 ? 'text-red-500' : 'text-yellow-600';
            let icon = remaining < 0 ? 'fa-exclamation-circle' : 'fa-clock';
            
            counter.html(`
                <div class="flex items-center justify-end ${color}">
                    <i class="fas ${icon} mr-1"></i>
                    <span>${remaining} caracteres restantes</span>
                </div>
            `);
        }
        
        if (remaining < 0) {
            $(this).addClass('border-red-500');
        } else {
            $(this).removeClass('border-red-500');
        }
    }).trigger('input');

    // Validar antes de enviar
    $('#editPlanForm').submit(function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return;
        }
        
        // Validar campos requeridos
        let camposRequeridos = $('#editPlanForm [required]');
        let camposVacios = [];
        
        camposRequeridos.each(function() {
            if (!$(this).val()) {
                camposVacios.push($(this).attr('name'));
                $(this).addClass('border-red-500');
            } else {
                $(this).removeClass('border-red-500');
            }
        });
        
        if (camposVacios.length > 0) {
            e.preventDefault();
            
            let mensaje = 'Complete los siguientes campos:<br>';
            camposVacios.forEach(campo => {
                let nombreCampo = campo.replace('_', ' ').toLowerCase();
                if (nombreCampo === 'linea id') nombreCampo = 'línea';
                mensaje += `- ${nombreCampo}<br>`;
            });
            
            Swal.fire({
                title: 'Campos requeridos',
                html: mensaje,
                icon: 'warning',
                confirmButtonColor: '#3b82f6'
            });
            
            // Scroll al primer campo vacío
            $('html, body').animate({
                scrollTop: $('.border-red-500').first().offset().top - 100
            }, 500);
            
            return;
        }
        
        // Validar fechas antes de enviar
        if (!validarFechasPCM()) {
            e.preventDefault();
            return;
        }
        
        e.preventDefault();
        
        Swal.fire({
            title: '¿Actualizar plan?',
            text: '¿Está seguro de que desea guardar los cambios?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Sí, actualizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                isSubmitting = true;
                let btn = $('#submitBtn');
                btn.prop('disabled', true)
                   .html('<span class="loading-spinner mr-2"></span> Actualizando...');
                $('#editPlanForm').off('submit').submit();
            }
        });
    });
});

// Función para eliminar
function confirmDelete(id) {
    Swal.fire({
        title: '¿Eliminar plan?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#3b82f6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve) => {
                $('#delete_id').val(id);
                $('#deleteForm').submit();
                resolve();
            });
        }
    });
}

// Detectar cambios antes de salir
window.addEventListener('beforeunload', function(e) {
    let formChanged = false;
    
    // Verificar si hubo cambios en el formulario
    $('#editPlanForm input:not([type=hidden]), #editPlanForm textarea, #editPlanForm select').each(function() {
        let originalValue = $(this).attr('data-original');
        let currentValue = $(this).val();
        
        if (originalValue != currentValue) {
            formChanged = true;
            return false;
        }
    });
    
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Guardar valores originales al cargar
$(document).ready(function() {
    $('#editPlanForm input:not([type=hidden]), #editPlanForm textarea, #editPlanForm select').each(function() {
        $(this).attr('data-original', $(this).val());
    });
});
</script>
@endpush
