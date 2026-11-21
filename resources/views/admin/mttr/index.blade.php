@extends('layouts.app')

@section('title', 'Administración de MTTR')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <header class="rounded-2xl bg-gradient-to-br from-slate-950 via-blue-950 to-blue-700 px-5 py-6 text-white shadow-sm sm:px-6">
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Administración de MTTR</h1>
        <p class="mt-2 text-sm text-blue-100">Última actualización
            @if ($ultimaActualizacion)
                <time class="ml-1 font-semibold" datetime="{{ $ultimaActualizacion->toIso8601String() }}">{{ $ultimaActualizacion->format('d/m/Y H:i') }}</time>
            @else
                <span class="ml-1 font-semibold">Sin registrar</span>
            @endif
        </p>
    </header>

    @if (session('mttr_status'))
        <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">
            {{ session('mttr_status') }}
        </p>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.mttr.update') }}" method="POST" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="revision" value="{{ $errors->has('revision') ? $revision : old('revision', $revision) }}">

        <p id="mttr-ayuda" class="text-sm text-slate-600">Introduce valores en minutos con hasta cuatro decimales. Los campos vacíos conservan su valor anterior.</p>

        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <label for="mttr-GENERAL" class="mb-2 block text-sm font-bold text-blue-950">MTTR GENERAL (MINUTOS)</label>
            @include('admin.mttr.input', ['clave' => \App\Models\MttrIndicador::GENERAL])
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-700">
                    <tr>
                        <th scope="col" class="px-3 py-3 sm:px-4">Lavadora</th>
                        <th scope="col" class="px-3 py-3 sm:px-4">MTTR (minutos)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach (\App\Models\MttrIndicador::LAVADORAS as $lavadora)
                        <tr>
                            <th scope="row" class="px-3 py-2 font-semibold text-slate-900 sm:px-4">
                                <label for="mttr-{{ $lavadora }}">{{ $lavadora }}</label>
                            </th>
                            <td class="px-3 py-2 sm:px-4">
                                @include('admin.mttr.input', ['clave' => $lavadora])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('dashboard.global.lavadoras') }}#mttr" class="text-sm font-semibold text-blue-700 hover:text-blue-900">
                Ver MTTR por lavadora
            </a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-800 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <i class="fas fa-save" aria-hidden="true"></i> Guardar cambios
            </button>
        </div>
    </form>
</div>
@endsection
