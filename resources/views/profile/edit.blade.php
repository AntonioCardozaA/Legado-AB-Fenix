@extends('layouts.app')

@section('title', 'Perfil de usuario')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <section class="overflow-hidden rounded-3xl border border-blue-100 bg-gradient-to-r from-blue-600 via-sky-600 to-cyan-600 text-white shadow-lg">
        <div class="grid gap-6 px-6 py-8 lg:grid-cols-[minmax(0,1.2fr)_auto] lg:items-center lg:px-8">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-blue-100">Cuenta autenticada</p>
                <h1 class="mt-3 text-3xl font-bold">Perfil de usuario</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-blue-50">
                    Administra tus datos, actualiza tu contrasena y cierra sesion desde un solo lugar dentro del flujo autenticado del sistema.
                </p>
            </div>

            <div class="rounded-2xl border border-white/20 bg-white/10 px-5 py-4 backdrop-blur">
                <p class="text-xs uppercase tracking-[0.25em] text-blue-100">Usuario actual</p>
                <p class="mt-2 text-lg font-semibold">{{ $user->name }}</p>
                <p class="text-sm text-blue-50">{{ $user->email }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(280px,0.8fr)]">
        <div class="space-y-6">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-100 sm:p-8">
                <div class="max-w-none">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-100 sm:p-8">
                <div class="max-w-none">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-orange-100 bg-orange-50 p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-orange-600">Sesion</p>
                <h2 class="mt-3 text-2xl font-bold text-gray-900">Cerrar sesion</h2>
                <p class="mt-3 text-sm leading-7 text-gray-600">
                    Si terminaste tu jornada o necesitas cambiar de cuenta, puedes salir del sistema desde esta misma seccion.
                </p>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-600">
                            Cerrar sesion
                        </button>
                    </form>

                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50">
                        Volver al panel
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
