@extends('layouts.app')

@section('title', 'Estadisticas de analisis')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Estadisticas</h1>
            <p class="text-sm text-gray-500">Resumen general de analisis registrados.</p>
        </div>
        <a href="{{ route('analisis.index') }}" class="inline-flex w-full items-center justify-center rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 sm:w-auto">Volver</a>
    </div>

    <div class="rounded bg-white p-5 shadow">
        <div class="text-sm text-gray-500">Total de analisis</div>
        <div class="mt-1 text-3xl font-bold text-gray-900 sm:text-4xl">{{ $totalAnalisis }}</div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded bg-white p-5 shadow">
            <h2 class="mb-4 font-semibold text-gray-900">Por linea</h2>
            <div class="space-y-2">
                @foreach($analisisPorLinea as $item)
                    <div class="flex flex-col gap-1 text-sm sm:flex-row sm:justify-between">
                        <span>{{ $item['linea'] }}</span>
                        <span class="font-semibold">{{ $item['total'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded bg-white p-5 shadow">
            <h2 class="mb-4 font-semibold text-gray-900">Por componente</h2>
            <div class="space-y-2">
                @foreach($analisisPorComponente as $item)
                    <div class="flex flex-col gap-1 text-sm sm:flex-row sm:justify-between sm:gap-4">
                        <span class="break-words sm:truncate">{{ $item['componente'] }}</span>
                        <span class="font-semibold">{{ $item['total'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded bg-white p-5 shadow">
            <h2 class="mb-4 font-semibold text-gray-900">Por categoria</h2>
            <div class="space-y-2">
                @foreach($analisisPorCategoria as $item)
                    <div class="flex flex-col gap-1 text-sm sm:flex-row sm:justify-between sm:gap-4">
                        <span class="break-words sm:truncate">{{ $item['categoria'] }}</span>
                        <span class="font-semibold">{{ $item['total'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
