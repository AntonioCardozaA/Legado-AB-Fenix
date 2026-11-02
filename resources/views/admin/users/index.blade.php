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

    <div class="space-y-4">
        @include('admin.users.partials.directory')

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

@section('scripts')
<script src="{{ asset('js/partial-directory.js') }}?v={{ filemtime(public_path('js/partial-directory.js')) }}"></script>
@endsection
