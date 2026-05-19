@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4 mx-auto max-w-7xl">
    <div class="flex justify-center">
        <div class="w-full md:w-2/3">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">

                <div class="bg-green-600 px-6 py-4">
                    <h4 class="text-white text-lg font-semibold flex items-center">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Nueva Actividad - Pasteurizadora
                        @if($linea)
                            | {{ $linea->nombre }}
                        @endif
                    </h4>
                </div>

                <div class="p-6">
                    <form action="{{ route('plan-accion.store', ['tipo' => 'pasteurizadora']) }}" method="POST">
                        @csrf
                        <input type="hidden" name="tipo" value="pasteurizadora">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Línea
                            </label>

                            <div class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md flex items-center">
                                <img src="{{ asset('images/icono_pas.png') }}" class="w-10 h-8 mr-2 object-contain" alt="Ícono de pasteurizadora">
                                {{ $linea?->nombre ?? 'Pasteurizadora no seleccionada' }}
                            </div>

                            <input type="hidden" name="linea_id" value="{{ $lineaSeleccionada }}">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Actividad <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                name="actividad"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('actividad') border-red-500 @enderror"
                                rows="3"
                                required>{{ old('actividad') }}</textarea>
                            @error('actividad')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            @foreach(['1', '2', '3', '4'] as $n)
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Fecha PCM {{ $n }}
                                    </label>
                                    <input
                                        type="date"
                                        name="fecha_pcm{{ $n }}"
                                        value="{{ old('fecha_pcm' . $n) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm @error('fecha_pcm' . $n) border-red-500 @enderror">
                                    @error('fecha_pcm' . $n)
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="flex justify-between items-center">
                            <a
                                href="{{ route('plan-accion.index', ['tipo' => 'pasteurizadora', 'linea_id' => $lineaSeleccionada]) }}"
                                class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i> Cancelar
                            </a>

                            <button
                                type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center">
                                <i class="fas fa-save mr-2"></i> Guardar Actividad
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>
@endpush

@endsection
