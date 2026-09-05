@extends('layouts.app')

@section('title', 'Gestion de usuarios')

@section('content')
<div class="min-h-screen min-w-0 space-y-4 bg-slate-50/70 -m-4 p-4 sm:-m-6 sm:p-6 lg:-m-8 lg:p-8">
    @php
        $isCreateSubmission = old('form_context') === 'create';
        $activeFilters = array_filter($filters, fn ($value) => $value !== '');
    @endphp

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestion de usuarios</h1>
    <div class="flex min-w-0 flex-col gap-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-4">
            <div class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white sm:flex">
                <i class="fas fa-users-cog text-lg"></i>
            </div>
            <div>
                <div class="mb-1 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.18em] text-blue-600">
                    <span>Administracion</span>
                    <span class="h-1 w-1 rounded-full bg-blue-400"></span>
                    <span>Accesos</span>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">Gestion de usuarios</h1>
            </div>
        </div>

        <a href="#crear-usuario" class="create-action inline-flex w-full items-center justify-center gap-2 sm:w-auto">
            <i class="fas fa-user-plus"></i>
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

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 border-l-4 border-l-slate-900 bg-white p-3 shadow-sm sm:p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total de usuarios</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total'] }}</div>
            <div class="mt-1 text-3xl font-bold text-gray-900">{{ $stats['total'] }}</div>
            <p class="mt-1 text-sm text-gray-500">Base completa de cuentas registradas.</p>
        </div>

        <div class="rounded-xl border border-slate-200 border-l-4 border-l-emerald-500 bg-white p-3 shadow-sm sm:p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Usuarios activos</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['activos'] }}</div>
            <div class="mt-1 text-3xl font-bold text-gray-900">{{ $stats['activos'] }}</div>
            <p class="mt-1 text-sm text-gray-500">Personal habilitado para operar.</p>
        </div>

        <div class="rounded-xl border border-slate-200 border-l-4 border-l-blue-500 bg-white p-3 shadow-sm sm:p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tecnicos</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['tecnicos'] }}</div>
            <div class="mt-1 text-3xl font-bold text-gray-900">{{ $stats['tecnicos'] }}</div>
            <p class="mt-1 text-sm text-gray-500">Usuarios del equipo tecnico y operativo.</p>
        </div>

        <div class="rounded-xl border border-slate-200 border-l-4 border-l-amber-500 bg-white p-3 shadow-sm sm:p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Administradores</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['administradores'] }}</div>
            <div class="mt-1 text-3xl font-bold text-gray-900">{{ $stats['administradores'] }}</div>
            <p class="mt-1 text-sm text-gray-500">Cuentas con control total del modulo.</p>
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <form action="{{ route('admin.users.index') }}" method="GET" class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Buscar</h2>
                    </div>
                    <i class="fas fa-filter text-slate-300"></i>
                </div>
                <div class="grid gap-3 lg:grid-cols-2 xl:grid-cols-[minmax(0,1.4fr),minmax(10rem,220px),minmax(10rem,200px),auto] xl:items-end">
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

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button type="submit" class="w-full rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 sm:w-auto">
                            Filtrar
                        </button>

                        @if($activeFilters !== [])
                            <a href="{{ route('admin.users.index') }}" class="w-full rounded border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50 sm:w-auto">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex min-w-0 flex-col gap-2 border-b border-slate-100 px-4 py-3 sm:px-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Usuarios registrados</h2>
                        <h2 class="text-lg font-bold text-slate-950">Directorio del personal</h2>
                    </div>
                    <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        {{ $users->count() }} resultado(s)
                    </span>
                </div>

                @if($users->isEmpty())
                    <div class="px-5 py-10 text-center text-sm text-gray-500">
                        No hay usuarios con ese filtro.
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($users as $managedUser)
                            <a href="{{ route('admin.users.edit', array_merge(['user' => $managedUser], $activeFilters)) }}" class="group block min-w-0 px-4 py-3 transition hover:bg-slate-50 sm:px-4">
                                <div class="grid gap-3 xl:grid-cols-[minmax(0,1.4fr),minmax(0,1fr),auto] xl:items-center">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">
                                            {{ str($managedUser->name)->substr(0, 1)->upper() }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="break-words font-semibold text-slate-900 group-hover:text-blue-700 [overflow-wrap:anywhere]">{{ $managedUser->name }}</div>
                                            <div class="mt-1 break-words text-sm text-slate-500 [overflow-wrap:anywhere]">{{ $managedUser->email }}</div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3 lg:grid-cols-2">
                                        <div>
                                            <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Puesto</div>
                                            <div class="break-words text-slate-700 [overflow-wrap:anywhere]">{{ $managedUser->puesto ?: 'Sin puesto' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Rol</div>
                                            <div class="break-words font-medium text-blue-700 [overflow-wrap:anywhere]">{{ $roleOptions[$managedUser->primary_role] ?? $managedUser->role_label }}</div>
                                        </div>
                                        <div class="col-span-2 sm:col-span-1 lg:col-span-2">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $managedUser->activo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                <span class="mr-1.5">●</span>{{ $managedUser->activo ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                        @if($managedUser->hasDirectAnalysisDeletionPermission())
                                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                                Eliminar Analisis
                                            </span>
                                        @endif
                                        @if($managedUser->id === auth()->id())
                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                                Tu usuario
                                            </span>
                                        @endif
                                        <i class="fas fa-chevron-right text-xs text-slate-300 transition group-hover:translate-x-1 group-hover:text-blue-500"></i>
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
        <div id="crear-usuario" class="scroll-mt-6">
            <div class="w-full rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4 lg:p-5">
                <div class="mb-4 flex flex-col gap-3 border-b border-slate-100 pb-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-950">Crear usuario</h2>
                        </div>
                    </div>
                    <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Nueva cuenta</span>
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
