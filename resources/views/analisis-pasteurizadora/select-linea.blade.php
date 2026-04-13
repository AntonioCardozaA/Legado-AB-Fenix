@extends('layouts.app')

@section('title', 'Seleccionar Pasteurizadora')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">

    {{-- HEADER --}}
    <div class="mb-8">
        <a href="{{ route('analisis-pasteurizadora.index') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 
                  bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span class="font-medium">Volver</span>
        </a>
        
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
            <img src="{{ asset('images/icono-pasteurizadora.png') }}" 
                 class="w-10 h-10 object-contain">
            Seleccionar Pasteurizadora
        </h1>
    </div>

    {{-- CONFIGURACIÓN DE LÍNEAS --}}
    @php
        $todasLasPasteurizadoras = [
            ['nombre' => 'P-03', 'modulos' => 9, 'tipo' => 'sencillo'],
            ['nombre' => 'P-04', 'modulos' => 12, 'tipo' => 'sencillo'],
            ['nombre' => 'P-05', 'modulos' => 9, 'tipo' => 'sencillo'],
            ['nombre' => 'P-06', 'modulos' => 16, 'tipo' => 'doble'],
            ['nombre' => 'P-07', 'modulos' => 16, 'tipo' => 'doble'],
            ['nombre' => 'P-08', 'modulos' => 9, 'tipo' => 'sencillo'],
            ['nombre' => 'P-09', 'modulos' => 9, 'tipo' => 'sencillo'],
            ['nombre' => 'P-10', 'modulos' => 9, 'tipo' => 'sencillo'],
            ['nombre' => 'P-11', 'modulos' => 16, 'tipo' => 'doble'],
            ['nombre' => 'P-12', 'modulos' => 9, 'tipo' => 'sencillo'],
            ['nombre' => 'P-13', 'modulos' => 9, 'tipo' => 'sencillo'],
            ['nombre' => 'P-14', 'modulos' => 9, 'tipo' => 'sencillo'],
        ];
    @endphp

    {{-- GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

        @foreach($todasLasPasteurizadoras as $config)
            @php
                $linea = $lineas[$config['nombre']];
                $lineaId = $linea->id;

                $bgGradient = $config['tipo'] == 'doble' 
                    ? 'from-purple-50 to-purple-100 border-purple-200 hover:border-purple-400' 
                    : 'from-blue-50 to-blue-100 border-blue-200 hover:border-blue-400';

                $iconColor = $config['tipo'] == 'doble' ? 'text-purple-600' : 'text-blue-600';
                $badgeColor = $config['tipo'] == 'doble' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800';
            @endphp

            <a href="{{ route('analisis-pasteurizadora.create', $lineaId) }}"
               class="group bg-gradient-to-br {{ $bgGradient }} rounded-2xl p-6 border-2 
                      transition-all duration-300 hover:shadow-xl hover:-translate-y-1 cursor-pointer">

                {{-- ICONO --}}
                <div class="flex items-start justify-between mb-4">
                    <div class="w-16 h-16 bg-white rounded-xl shadow-md flex items-center justify-center">
                        <img src="{{ asset('images/icono-pasteurizadora.png') }}" 
                             class="w-12 h-12 object-contain">
                    </div>

                    <span class="px-3 py-1 {{ $badgeColor }} rounded-full text-xs font-semibold">
                        {{ ucfirst($config['tipo']) }}
                    </span>
                </div>

                {{-- TÍTULO --}}
                <h3 class="text-2xl font-bold text-gray-800 mb-2">
                    {{ $config['nombre'] }}
                </h3>


                {{-- FOOTER --}}
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <span class="text-sm font-medium text-{{ $config['tipo'] == 'doble' ? 'purple' : 'blue' }}-600">
                        Crear análisis →
                    </span>
                </div>

            </a>
        @endforeach

    </div>

    

</div>
@endsection