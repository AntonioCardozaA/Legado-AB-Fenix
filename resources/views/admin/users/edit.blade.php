@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
<div class="space-y-6">
    @php
        $activeFilters = array_filter($filters, fn ($value) => $value !== '');
    @endphp

    <div>
        <a href="{{ route('admin.users.index', $activeFilters) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left"></i>
            Volver al directorio de usuarios
        </a>
    </div>

    <div class="rounded bg-white p-6 shadow">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $managedUser->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Actualiza los datos del usuario, cambia su rol, ajusta su estado y renueva la contrasena desde esta ficha individual.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                    {{ $roleOptions[$managedUser->primary_role] ?? $managedUser->role_label }}
                </span>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $managedUser->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $managedUser->activo ? 'Activo' : 'Inactivo' }}
                </span>
                @if($managedUser->id === auth()->id())
                    <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700">
                        Tu usuario
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <div class="font-semibold">Revisa los campos marcados.</div>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr),minmax(280px,1fr)]">
        <div class="rounded bg-white p-5 shadow">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-gray-900">Editar datos del usuario</h2>
                <p class="text-sm text-gray-500">Guarda los cambios para actualizar su acceso dentro del sistema.</p>
            </div>

            @include('admin.users.partials.edit-form', [
                'managedUser' => $managedUser,
                'roleOptions' => $roleOptions,
                'filters' => $filters,
            ])
        </div>

        <div class="space-y-6">
            <div class="rounded bg-white p-5 shadow">
                <h2 class="text-xl font-bold text-gray-900">Resumen del usuario</h2>
                <p class="mt-1 text-sm text-gray-500">Vista rapida del registro actual.</p>

                <div class="mt-5 space-y-3">
                    <div class="rounded border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Correo</div>
                        <div class="mt-1 break-all text-sm font-semibold text-gray-900">{{ $managedUser->email }}</div>
                    </div>

                    <div class="rounded border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Puesto</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $managedUser->puesto ?: 'Sin puesto registrado' }}</div>
                    </div>

                    <div class="rounded border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Telefono</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $managedUser->telefono ?: 'Sin telefono registrado' }}</div>
                    </div>

                    <div class="rounded border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cedula</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $managedUser->cedula ?: 'Sin cedula registrada' }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded bg-white p-5 shadow">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Usuarios activos</div>
                <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['activos'] }}</div>
                <p class="mt-2 text-sm text-gray-500">Personal habilitado actualmente en el sistema.</p>

                <a href="{{ route('admin.users.index', $activeFilters) }}" class="mt-5 inline-flex rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Volver a la lista
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
