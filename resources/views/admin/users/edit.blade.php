@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
<div class="min-h-screen min-w-0 space-y-5 bg-slate-50/70 -m-4 p-4 sm:space-y-6 sm:-m-6 sm:p-6 lg:-m-8 lg:p-8">
    @php
        $activeFilters = array_filter($filters, fn ($value) => $value !== '');
    @endphp

    <div>
        <a href="{{ route('admin.users.index', $activeFilters) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-blue-700">
            <i class="fas fa-arrow-left"></i>
            Volver al directorio de usuarios
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-lg font-bold text-white">
                    {{ str($managedUser->name)->substr(0, 1)->upper() }}
                </div>
                <div>
                    <div class="mb-1 text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Ficha de acceso</div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">{{ $managedUser->name }}</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Actualiza sus datos, rol, estado y permisos desde esta ficha individual.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 lg:pt-1">
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    {{ $roleOptions[$managedUser->primary_role] ?? $managedUser->role_label }}
                </span>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $managedUser->activo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                    {{ $managedUser->activo ? 'Activo' : 'Inactivo' }}
                </span>
                @if($managedUser->id === auth()->id())
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
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

    <div class="grid min-w-0 gap-4 2xl:grid-cols-[minmax(0,2fr),minmax(17rem,1fr)]">
        <div class="min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-5">
            <h2 class="text-xl font-bold text-slate-950">Datos y acceso</h2>
            <p class="mt-1 text-sm text-slate-500">Actualiza la información general y define el alcance operativo de esta cuenta.</p>
            </div>

            @include('admin.users.partials.edit-form', [
                'managedUser' => $managedUser,
                'roleOptions' => $roleOptions,
                'permissionGroups' => $permissionGroups,
                'filters' => $filters,
            ])
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600"><i class="fas fa-id-card"></i></div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Resumen del usuario</h2>
                        <p class="text-xs text-slate-500">Información registrada actualmente.</p>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Correo</div>
                        <div class="mt-1 break-all text-sm font-semibold text-gray-900">{{ $managedUser->email }}</div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Puesto</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $managedUser->puesto ?: 'Sin puesto registrado' }}</div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Telefono</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $managedUser->telefono ?: 'Sin telefono registrado' }}</div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cedula</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $managedUser->cedula ?: 'Sin cedula registrada' }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-red-100 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-950">Zona de cuenta</h2>

                @if($managedUser->id === auth()->id())
                    <p class="mt-2 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        No puedes eliminar tu propia cuenta desde Gestion de usuarios.
                    </p>
                @else
                    <p class="mt-2 text-sm leading-6 text-gray-500">
                        Elimina esta cuenta solo si ya no debe existir en el sistema.
                    </p>

                    <form
                        method="POST"
                        action="{{ route('admin.users.destroy', array_merge(['user' => $managedUser], $activeFilters)) }}"
                        class="mt-5"
                        onsubmit="return confirm(@js('Esta accion eliminara la cuenta de ' . $managedUser->name . '. Deseas continuar?'));"
                    >
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="create-action create-action--danger w-full">
                            <i class="fas fa-user-xmark"></i>
                            Eliminar usuario
                        </button>
                    </form>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
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
