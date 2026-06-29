@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-xl font-semibold text-white flex items-center">
                            <i class="fas fa-edit mr-3"></i>
                            Editar Plan de Acción
                        </h3>
                        <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index', ['linea_id' => $plan->linea_id]) }}"
                           class="responsive-action responsive-action--on-dark">
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

                            <div class="group">
                                <label for="area_pasteurizadora" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-tools mr-2 text-blue-500"></i>
                                    Parte de Pasteurizadora
                                    <span class="text-red-500 ml-1">*</span>
                                </label>
                                <select name="area_pasteurizadora" id="area_pasteurizadora"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('area_pasteurizadora') border-red-500 @enderror"
                                        required>
                                    <option value="">Seleccione una opcion</option>
                                    @foreach(($areasPasteurizadora ?? \App\Models\PlanAccion::areasPasteurizadoraOpciones()) as $areaValue => $areaLabel)
                                        <option value="{{ $areaValue }}" {{ old('area_pasteurizadora', $plan->area_pasteurizadora) === $areaValue ? 'selected' : '' }}>
                                            {{ $areaLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('area_pasteurizadora')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="group">
                                <label for="responsable_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user-check mr-2 text-blue-500"></i>
                                    Responsable
                                </label>
                                <select name="responsable_id" id="responsable_id"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('responsable_id') border-red-500 @enderror">
                                    <option value="">Sin responsable</option>
                                    @foreach(($usuariosResponsables ?? collect()) as $usuario)
                                        <option value="{{ $usuario->id }}" {{ (int) old('responsable_id', $plan->responsable_id) === $usuario->id ? 'selected' : '' }}>
                                            {{ $usuario->name }}{{ $usuario->email ? ' - ' . $usuario->email : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('responsable_id')
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

                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h6 class="text-sm font-semibold text-gray-600 mb-3 flex items-center">
                                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                                Informacion del registro
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
                                        <span class="font-medium">Fecha registro:</span>
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
                                        <span class="font-medium">Ultima modificacion:</span>
                                        {{ $plan->updated_at ? $plan->updated_at->format('d/m/Y H:i') : 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="responsive-actions">
                            <button type="button"
                                    onclick="confirmDelete()"
                                    class="responsive-action responsive-action--danger flex-1">
                                <i class="fas fa-trash mr-2"></i>
                                Eliminar
                            </button>

                            <button type="submit"
                                    class="responsive-action flex-1">
                                <i class="fas fa-save mr-2"></i>
                                Actualizar Plan
                            </button>
                            <a href="{{ route('pasteurizadora.analisis-pasteurizadora.plan-accion.index', ['linea_id' => $plan->linea_id]) }}"
                               class="responsive-action responsive-action--secondary flex-1">
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                            </a>
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
