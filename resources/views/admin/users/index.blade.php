@extends('layouts.app')

@section('title', 'Gestion de usuarios')

@section('content')
<div class="space-y-6">
    @php
        $isCreateSubmission = old('form_context') === 'create';
        $activeFilters = array_filter($filters, fn ($value) => $value !== '');
    @endphp

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestion de usuarios</h1>
            <p class="text-sm text-gray-500">
                Consulta el personal registrado, aplica filtros y abre una vista dedicada para editar cada usuario.
            </p>
        </div>

        <a href="#crear-usuario" class="create-action">
            Nuevo usuario
        </a>
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

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded bg-white p-5 shadow">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total de usuarios</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total'] }}</div>
            <p class="mt-2 text-sm text-gray-500">Base completa de cuentas registradas.</p>
        </div>

        <div class="rounded bg-white p-5 shadow">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Usuarios activos</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['activos'] }}</div>
            <p class="mt-2 text-sm text-gray-500">Personal habilitado para operar.</p>
        </div>

        <div class="rounded bg-white p-5 shadow">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tecnicos</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['tecnicos'] }}</div>
            <p class="mt-2 text-sm text-gray-500">Usuarios del equipo tecnico y operativo.</p>
        </div>

        <div class="rounded bg-white p-5 shadow">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Administradores</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['administradores'] }}</div>
            <p class="mt-2 text-sm text-gray-500">Cuentas con control total del modulo.</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr),380px]">
        <div class="space-y-6">
            <form action="{{ route('admin.users.index') }}" method="GET" class="rounded bg-white p-5 shadow">
                <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr),220px,200px,auto] lg:items-end">
                    <div>
                        <label for="search" class="mb-1 block text-sm font-medium text-gray-700">Buscar</label>
                        <input
                            id="search"
                            type="text"
                            name="search"
                            value="{{ $filters['search'] }}"
                            class="w-full rounded border-gray-300 text-sm"
                            placeholder="Nombre, correo, cedula, puesto o telefono"
                        >
                    </div>

                    <div>
                        <label for="role_filter" class="mb-1 block text-sm font-medium text-gray-700">Rol</label>
                        <select id="role_filter" name="role" class="w-full rounded border-gray-300 text-sm">
                            <option value="">Todos los roles</option>
                            @foreach($roleOptions as $roleName => $roleLabel)
                                <option value="{{ $roleName }}" @selected($filters['role'] === $roleName)>{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status_filter" class="mb-1 block text-sm font-medium text-gray-700">Estado</label>
                        <select id="status_filter" name="status" class="w-full rounded border-gray-300 text-sm">
                            <option value="">Todos</option>
                            <option value="active" @selected($filters['status'] === 'active')>Activos</option>
                            <option value="inactive" @selected($filters['status'] === 'inactive')>Inactivos</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Filtrar
                        </button>

                        @if($activeFilters !== [])
                            <a href="{{ route('admin.users.index') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="overflow-hidden rounded bg-white shadow">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Usuarios registrados</h2>
                        <p class="text-sm text-gray-500">Selecciona un usuario de la lista para abrir su vista de edicion.</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                        {{ $users->count() }} resultado(s)
                    </span>
                </div>

                @if($users->isEmpty())
                    <div class="px-5 py-10 text-center text-sm text-gray-500">
                        No hay usuarios con ese filtro.
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach($users as $managedUser)
                            <a href="{{ route('admin.users.edit', array_merge(['user' => $managedUser], $activeFilters)) }}" class="block px-5 py-4 hover:bg-gray-50">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="text-base font-semibold text-gray-900">{{ $managedUser->name }}</div>
                                        <div class="mt-1 break-all text-sm text-gray-500">{{ $managedUser->email }}</div>
                                        <div class="mt-3 grid gap-2 text-sm text-gray-600 sm:grid-cols-2">
                                            <div><span class="font-medium text-gray-700">Puesto:</span> {{ $managedUser->puesto ?: 'Sin puesto registrado' }}</div>
                                            <div><span class="font-medium text-gray-700">Telefono:</span> {{ $managedUser->telefono ?: 'Sin telefono registrado' }}</div>
                                            <div><span class="font-medium text-gray-700">Cedula:</span> {{ $managedUser->cedula ?: 'Sin cedula registrada' }}</div>
                                            <div><span class="font-medium text-blue-700">Abrir</span></div>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2 lg:justify-end">
                                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                            {{ $roleOptions[$managedUser->primary_role] ?? $managedUser->role_label }}
                                        </span>
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $managedUser->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $managedUser->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                        @if($managedUser->hasDirectAnalysisDeletionPermission())
                                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                Eliminar Analisis
                                            </span>
                                        @endif
                                        @if($managedUser->id === auth()->id())
                                            <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700">
                                                Tu usuario
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div id="crear-usuario" class="xl:sticky xl:top-6 xl:self-start">
            <div class="rounded bg-white p-5 shadow">
                <div class="mb-5">
                    <h2 class="text-xl font-bold text-gray-900">Crear nuevo usuario</h2>
                    <p class="text-sm text-gray-500">Registra personal nuevo sin salir del directorio.</p>
                </div>

                @include('admin.users.partials.create-form', [
                    'isCreateSubmission' => $isCreateSubmission,
                    'roleOptions' => $roleOptions,
                    'permissionGroups' => $permissionGroups,
                ])
            </div>
        </div>
    </div>
</div>
@endsection
