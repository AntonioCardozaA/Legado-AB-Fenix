@extends('layouts.app')

@section('content')
    @php
        $defaultMessage = 'No cuenta con permisos suficientes para acceder a esta vista.';
        $exceptionMessage = isset($exception) ? trim((string) $exception->getMessage()) : '';
        $messageText = trim((string) ($message ?? $exceptionMessage));
        $detailMessage = $messageText !== '' && $messageText !== $defaultMessage ? $messageText : null;
        $homeUrl = auth()->check() ? route('dashboard') : route('welcome');
    @endphp

    <div class="flex min-h-[calc(100vh-8rem)] items-center justify-center px-4 py-10">
        <section class="w-full max-w-2xl rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                <i class="fas fa-lock text-2xl" aria-hidden="true"></i>
            </div>

            <h1 class="text-3xl font-bold text-slate-900">Acceso restringido</h1>
            <p class="mx-auto mt-4 max-w-xl text-base leading-7 text-slate-600">
                {{ $defaultMessage }}
            </p>
            @if($detailMessage)
                <p class="mx-auto mt-3 max-w-xl rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">
                    {{ $detailMessage }}
                </p>
            @endif
            <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-500">
                Si considera que requiere acceso a este contenido, solicite al administrador la asignacion del permiso correspondiente.
            </p>

            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ $homeUrl }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fas fa-house" aria-hidden="true"></i>
                    Ir al inicio
                </a>

                <button type="button"
                        onclick="window.history.length > 1 ? window.history.back() : window.location.href = @js($homeUrl)"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Regresar
                </button>
            </div>
        </section>
    </div>
@endsection
