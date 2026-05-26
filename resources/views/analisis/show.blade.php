@extends('layouts.app')

@section('title', 'Detalle de analisis')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Analisis #{{ $analisis->id }}</h1>
            <p class="text-sm text-gray-500">{{ optional($analisis->fecha_analisis)->format('d/m/Y') ?? 'Sin fecha' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('analisis.edit', $analisis) }}" class="rounded bg-yellow-500 px-4 py-2 text-sm font-semibold text-white hover:bg-yellow-600">Editar</a>
            <a href="{{ route('analisis.exportar.pdf', $analisis) }}" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">PDF</a>
            <a href="{{ route('analisis.index') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Volver</a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="rounded bg-white p-5 shadow">
        <dl class="grid gap-4 md:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase text-gray-500">Linea</dt>
                <dd class="text-gray-900">{{ optional($analisis->linea)->nombre ?? 'Sin linea' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-gray-500">Componente</dt>
                <dd class="text-gray-900">{{ optional($analisis->componente)->nombre ?? 'Sin componente' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-gray-500">Categoria</dt>
                <dd class="text-gray-900">{{ optional($analisis->categoria)->nombre ?? 'Sin categoria' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-gray-500">Numero R</dt>
                <dd class="text-gray-900">{{ optional($analisis->numeroR)->codigo ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-gray-500">Reductor</dt>
                <dd class="text-gray-900">{{ $analisis->reductor ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-gray-500">Orden</dt>
                <dd class="text-gray-900">{{ $analisis->numero_orden ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-gray-500">Horometro</dt>
                <dd class="text-gray-900">{{ $analisis->horometro ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-gray-500">Usuario</dt>
                <dd class="text-gray-900">{{ optional($analisis->usuario)->name ?? 'Sin usuario' }}</dd>
            </div>
        </dl>

        <div class="mt-5 border-t pt-5">
            <h2 class="font-semibold text-gray-900">Actividad</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $analisis->actividad }}</p>
        </div>

        @if($analisis->observaciones)
            <div class="mt-5 border-t pt-5">
                <h2 class="font-semibold text-gray-900">Observaciones</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $analisis->observaciones }}</p>
            </div>
        @endif
    </div>

    @if(!empty($analisis->fotos))
        <div class="rounded bg-white p-5 shadow">
            <h2 class="mb-4 font-semibold text-gray-900">Evidencias</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($analisis->fotos as $index => $foto)
                    <div class="overflow-hidden rounded border border-gray-200">
                        <img src="{{ asset('storage/' . $foto) }}" alt="Evidencia {{ $index + 1 }}" class="h-48 w-full object-cover">
                        <form method="POST" action="{{ route('analisis.eliminar-foto', $analisis) }}" class="border-t p-3">
                            @csrf
                            <input type="hidden" name="foto_index" value="{{ $index }}">
                            <button class="text-sm font-semibold text-red-600 hover:text-red-800" onclick="return confirm('Eliminar esta foto?')">Eliminar foto</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('analisis.destroy', $analisis) }}" class="rounded border border-red-200 bg-red-50 p-4">
        @csrf
        @method('DELETE')
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-red-700">Esta accion elimina el analisis y sus fotos asociadas.</p>
            <button class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700" onclick="return confirm('Eliminar este analisis?')">Eliminar</button>
        </div>
    </form>
</div>
@endsection
