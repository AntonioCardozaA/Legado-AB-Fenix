@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-white flex items-center">
                            <i class="fas fa-edit mr-3"></i>
                            Editar Plan de Acción
                        </h3>
                        <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index', ['linea_id' => $plan->linea_id]) }}"
                           class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white text-sm font-medium rounded-lg transition-all duration-200 backdrop-blur-sm">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver
                        </a>
                    </div>
                </div>

                <form action="{{ route('plan-accion.update', ['plan_accion' => $plan->id, 'tipo' => 'pasteurizadora']) }}" method="POST" id="editPlanForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tipo" value="pasteurizadora">

                    <div class="p-6">
                        @if($errors->any())
                            <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4">
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
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="group">
                                <label for="linea_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-industry mr-2 text-blue-500"></i>
                                    Línea / Pasteurizadora
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <select name="linea_id" id="linea_id"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('linea_id') border-red-500 @enderror"
                                        required>
                                    <option value="">Seleccione una línea</option>
                                    @foreach($lineas as $linea)
                                        <option value="{{ $linea->id }}" {{ (int) old('linea_id', $plan->linea_id) === $linea->id ? 'selected' : '' }}>
                                            {{ $linea->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('linea_id')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-6 group">
                            <label for="actividad" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-tasks mr-2 text-blue-500"></i>
                                Actividad
                                <span class="text-red-500 ml-1">*</span>
                            </label>
                            <textarea name="actividad"
                                      id="actividad"
                                      rows="4"
                                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-y @error('actividad') border-red-500 @enderror"
                                      required>{{ old('actividad', $plan->actividad) }}</textarea>
                            @error('actividad')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

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
                                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('fecha_pcm' . $i) border-red-500 @enderror"
                                               value="{{ old('fecha_pcm' . $i, $plan->{'fecha_pcm' . $i} ? $plan->{'fecha_pcm' . $i}->format('Y-m-d') : '') }}">
                                        @error('fecha_pcm' . $i)
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <button type="button"
                                    onclick="confirmDelete()"
                                    class="w-full sm:w-auto order-2 sm:order-1 px-6 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-all duration-200 flex items-center justify-center">
                                <i class="fas fa-trash mr-2"></i>
                                Eliminar
                            </button>

                            <div class="flex w-full sm:w-auto gap-3 order-1 sm:order-2">
                                <button type="submit"
                                        class="flex-1 sm:flex-none px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white text-sm font-medium rounded-lg transition-all duration-200 flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i>
                                    Actualizar Plan
                                </button>
                                <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index', ['linea_id' => $plan->linea_id]) }}"
                                   class="flex-1 sm:flex-none px-6 py-2.5 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-all duration-200 flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                <form id="deleteForm" action="{{ route('plan-accion.destroy', ['plan_accion' => $plan->id, 'tipo' => 'pasteurizadora']) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tipo" value="pasteurizadora">
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDelete() {
    Swal.fire({
        title: '¿Eliminar plan?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#3b82f6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>
@endpush
