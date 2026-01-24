@extends('layouts.app')

@section('title', 'Editar Línea')

@section('content')
<div class="max-w-3xl mx-auto py-6">
    <h1 class="text-2xl font-bold mb-6">Editar Línea: {{ $linea->nombre }}</h1>

    @if($errors->any())
        <div class="bg-red-100 text-red-800 p-2 mb-4 rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('lineas.update', $linea) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block mb-1 font-semibold" for="nombre">Nombre</label>
            <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $linea->nombre) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2" required>
        </div>

        <div class="mb-4">
            <label class="block mb-1 font-semibold" for="descripcion">Descripción</label>
            <textarea name="descripcion" id="descripcion" rows="3"
                      class="w-full border border-gray-300 rounded px-3 py-2">{{ old('descripcion', $linea->descripcion) }}</textarea>
        </div>

        <div class="mb-4 flex items-center space-x-2">
            <input type="checkbox" name="activo" id="activo" value="1" {{ $linea->activo ? 'checked' : '' }}>
            <label for="activo" class="font-semibold">Activo</label>
        </div>

        <div>
            <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">
                Actualizar
            </button>
            <a href="{{ route('lineas.index') }}" class="ml-2 text-gray-600 hover:underline">Cancelar</a>
        </div>
    </form>
</div>
@endsection
