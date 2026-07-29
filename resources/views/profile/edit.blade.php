@extends('layouts.app')

@section('title', 'Perfil de usuario')

@section('content')
@php
    $nameParts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
    $profileInitials = collect($nameParts)
        ->filter()
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->take(2)
        ->implode('') ?: 'U';

    $profilePhotoUrl = null;
    if ($user->foto_perfil) {
        $profilePhotoPath = ltrim(str_replace('\\', '/', (string) $user->foto_perfil), '/');
        $profilePhotoUrl = \Illuminate\Support\Str::startsWith($profilePhotoPath, ['http://', 'https://'])
            ? $profilePhotoPath
            : asset('storage/' . $profilePhotoPath);
    }

    $accountIsActive = (bool) ($user->activo ?? true);
    $lastAccessLabel = $user->ultimo_acceso
        ? \Illuminate\Support\Carbon::parse($user->ultimo_acceso)->format('d/m/Y H:i')
        : 'Sin registro';
    $createdAtLabel = $user->created_at ? $user->created_at->format('d/m/Y') : 'Sin registro';
@endphp

<div class="mx-auto max-w-6xl space-y-6">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <span class="mt-1 h-10 w-1.5 shrink-0 rounded-full bg-blue-700"></span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Cuenta</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-slate-900">Perfil de usuario</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Actualiza tus datos de cuenta y seguridad.
                    </p>
                </div>
            </div>

            <a href="{{ route('dashboard') }}" class="responsive-action responsive-action--secondary">
                <i class="fas fa-arrow-left"></i>
                Volver al dashboard
            </a>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-2xl bg-blue-700 text-white shadow-sm">
                    @if($profilePhotoUrl)
                        <img src="{{ $profilePhotoUrl }}" alt="Foto de {{ $user->name }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-2xl font-black">
                            {{ $profileInitials }}
                        </div>
                    @endif
                    <span class="absolute bottom-1.5 right-1.5 h-3.5 w-3.5 rounded-full border-2 border-white {{ $accountIsActive ? 'bg-emerald-400' : 'bg-slate-400' }}"></span>
                </div>

                <div class="min-w-0">
                    <h2 class="truncate text-xl font-extrabold text-slate-900 sm:text-2xl">{{ $user->name }}</h2>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                            <i class="fas fa-user-shield"></i>
                            {{ $user->role_label }}
                        </span>
                        <span class="inline-flex max-w-full items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-700">
                            <i class="fas fa-envelope text-blue-600"></i>
                            <span class="truncate">{{ $user->email }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <span class="inline-flex w-fit items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $accountIsActive ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                <i class="fas fa-circle"></i>
                {{ $accountIsActive ? 'Activo' : 'Inactivo' }}
            </span>
        </div>

        <dl class="mt-5 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Puesto</dt>
                <dd class="mt-1 truncate text-sm font-semibold text-slate-800">{{ $user->puesto ?: 'Sin puesto registrado' }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Telefono</dt>
                <dd class="mt-1 truncate text-sm font-semibold text-slate-800">{{ $user->telefono ?: 'Sin telefono registrado' }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Cedula</dt>
                <dd class="mt-1 truncate text-sm font-semibold text-slate-800">{{ $user->cedula ?: 'Sin cedula registrada' }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Ultimo acceso</dt>
                <dd class="mt-1 truncate text-sm font-semibold text-slate-800">{{ $lastAccessLabel }}</dd>
            </div>
        </dl>
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                @include('profile.partials.update-profile-information-form')
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                @include('profile.partials.update-password-form')
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-extrabold text-slate-900">Sesion</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Cierra tu sesion cuando termines de usar el sistema.
                </p>

                <form method="POST" action="{{ route('logout') }}" class="mt-5">
                    @csrf
                    <button type="submit" class="responsive-action responsive-action--danger w-full">
                        <i class="fas fa-right-from-bracket"></i>
                        Cerrar sesion
                    </button>
                </form>

                @if($user->hasRole(\App\Models\User::ROLE_ADMIN))
                    @include('profile.partials.delete-user-form')
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Cuenta creada</h2>
                <p class="mt-1 text-lg font-extrabold text-slate-900">{{ $createdAtLabel }}</p>
            </section>
        </aside>
    </div>
</div>
@endsection
