@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4 mx-auto max-w-7xl">
    <div class="flex justify-center">
        <div class="w-full md:w-2/3">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-green-600 px-6 py-4">
                    <h4 class="text-white text-lg font-semibold flex items-center">
                        <i class="fas fa-plus-circle mr-2"></i> Nueva Actividad - Plan de Acción
                    </h4>
                </div>
                <div class="p-6">
                    <form action="{{ route('plan-accion.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Línea <span class="text-red-500">*</span>
                            </label>
                            <select name="linea_id" id="linea_select"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('linea_id') border-red-500 @enderror" 
                                required>
                                <option value="">Seleccione línea</option>
                            @foreach($lineas as $linea)
                                <option value="{{ $linea->id }}" {{ old('linea_id') == $linea->id ? 'selected' : '' }}>
                                    {{ $linea->nombre }}
                                </option>
                            @endforeach
                            </select>
                            @error('linea_id')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Checklist que se muestra al seleccionar línea -->
                        <div id="checklist_container" class="mb-4 hidden">
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-md">
                                <div class="flex items-center mb-3">
                                    <i class="fas fa-clipboard-list text-blue-600 mr-2"></i>
                                    <h5 class="text-md font-semibold text-blue-800">Seleccione el tipo de máquina para la línea: <span id="linea_seleccionada_nombre" class="font-bold"></span></h5>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex items-center p-3 bg-white rounded-md border-2 border-transparent hover:border-blue-300 transition-all">
                                        <input type="checkbox" name="tipo_maquina[]" value="lavadora" id="tipo_lavadora" 
                                            class="w-5 h-5 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 focus:ring-2"
                                            {{ is_array(old('tipo_maquina')) && in_array('lavadora', old('tipo_maquina')) ? 'checked' : '' }}>
                                        <label for="tipo_lavadora" class="ml-3 flex items-center cursor-pointer">
                                            <i class="fas fa-tshirt text-blue-600 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Lavadora Industrial</span>
                                        </label>
                                    </div>
                                    
                                    <div class="flex items-center p-3 bg-white rounded-md border-2 border-transparent hover:border-blue-300 transition-all">
                                        <input type="checkbox" name="tipo_maquina[]" value="pasteurizadora" id="tipo_pasteurizadora" 
                                            class="w-5 h-5 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 focus:ring-2"
                                            {{ is_array(old('tipo_maquina')) && in_array('pasteurizadora', old('tipo_maquina')) ? 'checked' : '' }}>
                                        <label for="tipo_pasteurizadora" class="ml-3 flex items-center cursor-pointer">
                                            <i class="fas fa-temperature-high text-red-500 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Pasteurizadora</span>
                                        </label>
                                    </div>
                                    
                                    <div class="flex items-center p-3 bg-white rounded-md border-2 border-transparent hover:border-blue-300 transition-all">
                                        <input type="checkbox" name="tipo_maquina[]" value="secadora" id="tipo_secadora" 
                                            class="w-5 h-5 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 focus:ring-2"
                                            {{ is_array(old('tipo_maquina')) && in_array('secadora', old('tipo_maquina')) ? 'checked' : '' }}>
                                        <label for="tipo_secadora" class="ml-3 flex items-center cursor-pointer">
                                            <i class="fas fa-wind text-cyan-600 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Enjuagadora</span>
                                        </label>
                                    </div>
                                    
                                    
                                    <div class="flex items-center p-3 bg-white rounded-md border-2 border-transparent hover:border-blue-300 transition-all">
                                        <input type="checkbox" name="tipo_maquina[]" value="otros" id="tipo_otros" 
                                            class="w-5 h-5 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 focus:ring-2"
                                            {{ is_array(old('tipo_maquina')) && in_array('otros', old('tipo_maquina')) ? 'checked' : '' }}>
                                        <label for="tipo_otros" class="ml-3 flex items-center cursor-pointer">
                                            <i class="fas fa-cog text-gray-600 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Otros</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mt-3 text-xs text-gray-600 bg-yellow-50 p-2 rounded">
                                    <i class="fas fa-info-circle mr-1 text-yellow-600"></i>
                                    Puede seleccionar múltiples tipos de máquina según corresponda
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Actividad <span class="text-red-500">*</span>
                            </label>
                            <textarea name="actividad" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('actividad') border-red-500 @enderror" 
                                rows="3" required>{{ old('actividad') }}</textarea>
                            @error('actividad')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha PCM 1</label>
                                <input type="date" name="fecha_pcm1" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('fecha_pcm1') border-red-500 @enderror" 
                                    value="{{ old('fecha_pcm1') }}">
                                @error('fecha_pcm1')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha PCM 2</label>
                                <input type="date" name="fecha_pcm2" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('fecha_pcm2') border-red-500 @enderror" 
                                    value="{{ old('fecha_pcm2') }}">
                                @error('fecha_pcm2')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha PCM 3</label>
                                <input type="date" name="fecha_pcm3" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('fecha_pcm3') border-red-500 @enderror" 
                                    value="{{ old('fecha_pcm3') }}">
                                @error('fecha_pcm3')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha PCM 4</label>
                                <input type="date" name="fecha_pcm4" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('fecha_pcm4') border-red-500 @enderror" 
                                    value="{{ old('fecha_pcm4') }}">
                                @error('fecha_pcm4')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Estado <span class="text-red-500">*</span>
                            </label>
                            <select name="estado" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('estado') border-red-500 @enderror" 
                                required>
                                <option value="">Seleccione un estado</option>
                                <option value="pendiente" {{ old('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="en_proceso" {{ old('estado') == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                                <option value="completada" {{ old('estado') == 'completada' ? 'selected' : '' }}>Completada</option>
                                <option value="atrasada" {{ old('estado') == 'atrasada' ? 'selected' : '' }}>Atrasada</option>
                            </select>
                            @error('estado')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Responsable
                            </label>
                            <select name="responsable_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('responsable_id') border-red-500 @enderror">
                                <option value="">Seleccione un responsable</option>
                                @foreach($responsables as $responsable)
                                    <option value="{{ $responsable->id }}" {{ old('responsable_id') == $responsable->id ? 'selected' : '' }}>
                                        {{ $responsable->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('responsable_id')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Observaciones
                            </label>
                            <textarea name="observaciones" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('observaciones') border-red-500 @enderror" 
                                rows="2">{{ old('observaciones') }}</textarea>
                            @error('observaciones')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <a href="{{ route('plan-accion.index') }}" 
                                class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 transition duration-200 flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i> Cancelar
                            </a>
                            <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition duration-200 flex items-center">
                                <i class="fas fa-save mr-2"></i> Guardar Actividad
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para manejar la visualización del checklist -->
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lineaSelect = document.getElementById('linea_select');
    const checklistContainer = document.getElementById('checklist_container');
    const lineaSeleccionadaNombre = document.getElementById('linea_seleccionada_nombre');
    
    function toggleChecklist() {
        const selectedOption = lineaSelect.options[lineaSelect.selectedIndex];
        
        if (lineaSelect.value) {
            // Mostrar el nombre de la línea seleccionada
            lineaSeleccionadaNombre.textContent = selectedOption.text;
            
            // Mostrar el checklist con animación
            checklistContainer.classList.remove('hidden');
            checklistContainer.style.animation = 'fadeIn 0.3s ease-in';
        } else {
            // Ocultar el checklist
            checklistContainer.classList.add('hidden');
        }
    }
    
    // Ejecutar al cargar la página si hay un valor seleccionado
    toggleChecklist();
    
    // Ejecutar cuando cambie la selección
    lineaSelect.addEventListener('change', toggleChecklist);
});

// Agregar estilos de animación
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);
</script>
@endpush
@endsection