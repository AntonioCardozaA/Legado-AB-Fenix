@extends('layouts.app')

@section('title', 'Gestion de usuarios')

@section('content')
<style>
    .admin-users-shell {
        --admin-ink: #0f172a;
        --admin-subtle: #64748b;
        --admin-line: #dbe3ef;
        --admin-blue: #1d4ed8;
        --admin-blue-soft: rgba(29, 78, 216, 0.08);
        --admin-green: #047857;
        --admin-green-soft: rgba(4, 120, 87, 0.1);
        --admin-amber: #b45309;
        --admin-amber-soft: rgba(245, 158, 11, 0.14);
        --admin-slate-soft: rgba(15, 23, 42, 0.06);
    }

    .admin-hero {
        background:
            radial-gradient(circle at top right, rgba(56, 189, 248, 0.18), transparent 34%),
            linear-gradient(135deg, #0f172a 0%, #123a7a 55%, #1d4ed8 100%);
        color: white;
        border-radius: 28px;
        padding: 28px;
        box-shadow: 0 24px 44px -28px rgba(15, 23, 42, 0.55);
    }

    .admin-hero h1 {
        font-size: clamp(1.7rem, 2.7vw, 2.35rem);
        font-weight: 800;
        letter-spacing: -0.03em;
        margin-bottom: 10px;
    }

    .admin-hero p {
        max-width: 760px;
        color: rgba(255, 255, 255, 0.82);
        line-height: 1.6;
    }

    .admin-panel {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid var(--admin-line);
        border-radius: 24px;
        box-shadow: 0 18px 40px -34px rgba(15, 23, 42, 0.38);
    }

    .admin-stat {
        padding: 18px 20px;
        position: relative;
        overflow: hidden;
    }

    .admin-stat::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: linear-gradient(180deg, #38bdf8 0%, #1d4ed8 100%);
    }

    .admin-stat-label {
        font-size: 0.74rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--admin-subtle);
        font-weight: 700;
        margin-bottom: 8px;
    }

    .admin-stat-value {
        font-size: 2rem;
        line-height: 1;
        font-weight: 800;
        color: var(--admin-ink);
    }

    .admin-stat-copy {
        margin-top: 8px;
        color: var(--admin-subtle);
        font-size: 0.92rem;
    }

    .field-label {
        display: block;
        margin-bottom: 8px;
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--admin-ink);
    }

    .field-help {
        color: var(--admin-subtle);
        font-size: 0.8rem;
        margin-top: 6px;
    }

    .admin-input,
    .admin-select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        background: #fff;
        color: var(--admin-ink);
        padding: 0.82rem 0.95rem;
        transition: 0.2s ease;
    }

    .admin-input:focus,
    .admin-select:focus {
        outline: none;
        border-color: rgba(29, 78, 216, 0.48);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
    }

    .admin-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        border-radius: 999px;
        padding: 0.9rem 1.3rem;
        font-weight: 700;
        transition: 0.2s ease;
    }

    .admin-button-primary {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
        color: #fff;
        box-shadow: 0 18px 26px -20px rgba(29, 78, 216, 0.8);
    }

    .admin-button-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 20px 30px -22px rgba(29, 78, 216, 0.8);
    }

    .status-chip,
    .role-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 0.42rem 0.78rem;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .role-chip {
        background: var(--admin-blue-soft);
        color: var(--admin-blue);
    }

    .status-chip.status-active {
        background: var(--admin-green-soft);
        color: var(--admin-green);
    }

    .status-chip.status-inactive {
        background: var(--admin-amber-soft);
        color: var(--admin-amber);
    }

    .account-chip {
        background: var(--admin-slate-soft);
        color: var(--admin-ink);
    }

    .user-card {
        padding: 22px;
    }

    .user-card-header {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
        padding-bottom: 16px;
        margin-bottom: 18px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.22);
    }

    .user-card-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--admin-ink);
        margin-bottom: 4px;
    }

    .user-card-subtitle {
        color: var(--admin-subtle);
        font-size: 0.92rem;
    }

    .inline-note {
        border-radius: 16px;
        background: rgba(248, 250, 252, 0.95);
        border: 1px solid rgba(203, 213, 225, 0.9);
        color: var(--admin-subtle);
        padding: 12px 14px;
        font-size: 0.88rem;
    }

    .toggle-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        border-radius: 16px;
        background: rgba(248, 250, 252, 0.9);
        border: 1px solid rgba(203, 213, 225, 0.88);
        color: var(--admin-ink);
        min-height: 56px;
    }

    .alert-box {
        border-radius: 18px;
        padding: 16px 18px;
        border: 1px solid transparent;
    }

    .alert-success {
        background: rgba(220, 252, 231, 0.8);
        border-color: rgba(74, 222, 128, 0.45);
        color: #166534;
    }

    .alert-error {
        background: rgba(254, 226, 226, 0.82);
        border-color: rgba(248, 113, 113, 0.42);
        color: #991b1b;
    }

    @media (max-width: 768px) {
        .admin-hero,
        .user-card {
            padding: 20px;
        }

        .user-card-header {
            flex-direction: column;
        }
    }
</style>

<div class="admin-users-shell space-y-6">
    @php
        $isCreateSubmission = old('form_context') === 'create';
    @endphp

    <section class="admin-hero">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-white/80">
                    <i class="fas fa-user-shield"></i>
                    Panel administrador
                </div>
                <h1>Gestion de usuarios y Roles.</h1>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3">
                    <div class="text-xs uppercase tracking-[0.18em] text-white/65">Roles disponibles</div>
                    <div class="mt-2 text-lg font-bold">{{ count($roleOptions) }}</div>
                </div>
                <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3">
                    <div class="text-xs uppercase tracking-[0.18em] text-white/65">Cuentas registradas</div>
                    <div class="mt-2 text-lg font-bold">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert-box alert-success">
            <div class="flex items-center gap-3">
                <i class="fas fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert-box alert-error">
            <div class="flex items-start gap-3">
                <i class="fas fa-triangle-exclamation mt-1"></i>
                <div>
                    <p class="font-semibold">No se pudo guardar la informacion.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="admin-panel admin-stat">
            <div class="admin-stat-label">Total de usuarios</div>
            <div class="admin-stat-value">{{ $stats['total'] }}</div>
            <p class="admin-stat-copy">Base completa de cuentas registradas.</p>
        </article>

        <article class="admin-panel admin-stat">
            <div class="admin-stat-label">Usuarios activos</div>
            <div class="admin-stat-value">{{ $stats['activos'] }}</div>
            <p class="admin-stat-copy">Personal marcado como habilitado en el sistema.</p>
        </article>

        <article class="admin-panel admin-stat">
            <div class="admin-stat-label">Tecnicos</div>
            <div class="admin-stat-value">{{ $stats['tecnicos'] }}</div>
            <p class="admin-stat-copy">Operativos y tecnicos con acceso asignado.</p>
        </article>

        <article class="admin-panel admin-stat">
            <div class="admin-stat-label">Administradores</div>
            <div class="admin-stat-value">{{ $stats['administradores'] }}</div>
            <p class="admin-stat-copy">Usuarios con control total sobre la plataforma.</p>
        </article>
    </section>

    <div class="grid gap-6 xl:grid-cols-[380px,minmax(0,1fr)]">
        <section class="admin-panel p-6 xl:sticky xl:top-6 xl:self-start">
            <div class="mb-5">
                <h2 class="text-xl font-extrabold text-slate-900">Crear nuevo usuario</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Registra personal nuevo y deja su rol listo desde el alta inicial.
                </p>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4" x-data="{ showPassword: false, showPasswordConfirmation: false, passwordPreview: '' }">
                @csrf
                <input type="hidden" name="form_context" value="create">

                <div>
                    <label for="name" class="field-label">Nombre completo</label>
                    <input id="name" type="text" name="name" value="{{ $isCreateSubmission ? old('name') : '' }}" class="admin-input" required>
                </div>

                <div>
                    <label for="email" class="field-label">Correo</label>
                    <input id="email" type="email" name="email" value="{{ $isCreateSubmission ? old('email') : '' }}" class="admin-input" required>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="cedula" class="field-label">Cedula</label>
                        <input id="cedula" type="text" name="cedula" value="{{ $isCreateSubmission ? old('cedula') : '' }}" class="admin-input">
                    </div>

                    <div>
                        <label for="telefono" class="field-label">Telefono</label>
                        <input id="telefono" type="text" name="telefono" value="{{ $isCreateSubmission ? old('telefono') : '' }}" class="admin-input">
                    </div>
                </div>

                <div>
                    <label for="puesto" class="field-label">Puesto</label>
                    <input id="puesto" type="text" name="puesto" value="{{ $isCreateSubmission ? old('puesto') : '' }}" class="admin-input">
                </div>

                <div>
                    <label for="role" class="field-label">Rol</label>
                    <select id="role" name="role" class="admin-select" required>
                        @foreach($roleOptions as $roleName => $roleLabel)
                            <option value="{{ $roleName }}" @selected(($isCreateSubmission ? old('role', \App\Models\User::ROLE_TECNICO) : \App\Models\User::ROLE_TECNICO) === $roleName)>
                                {{ $roleLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="password" class="field-label">Contrasena</label>
                    <div class="relative">
                        <input
                            id="password"
                            x-model="passwordPreview"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            name="password"
                            class="admin-input pr-12"
                            autocomplete="new-password"
                            required
                        >
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-3 inline-flex items-center text-slate-500 transition hover:text-slate-700"
                            x-bind:aria-label="showPassword ? 'Ocultar contrasena' : 'Mostrar contrasena'"
                        >
                            <i class="fas" x-bind:class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <p class="field-help">Usa minimo 8 caracteres.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="field-label">Confirmar contrasena</label>
                    <div class="relative">
                        <input
                            id="password_confirmation"
                            x-bind:type="showPasswordConfirmation ? 'text' : 'password'"
                            name="password_confirmation"
                            class="admin-input pr-12"
                            autocomplete="new-password"
                            required
                        >
                        <button
                            type="button"
                            @click="showPasswordConfirmation = !showPasswordConfirmation"
                            class="absolute inset-y-0 right-3 inline-flex items-center text-slate-500 transition hover:text-slate-700"
                            x-bind:aria-label="showPasswordConfirmation ? 'Ocultar confirmacion de contrasena' : 'Mostrar confirmacion de contrasena'"
                        >
                            <i class="fas" x-bind:class="showPasswordConfirmation ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <div x-show="passwordPreview.length > 0" x-cloak class="inline-note">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Vista previa</p>
                    <p class="mt-2 break-all font-mono text-sm text-slate-800" x-text="passwordPreview"></p>
                </div>

                <div>
                    <input type="hidden" name="activo" value="0">
                    <label class="toggle-wrap">
                        <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-blue-600" @checked($isCreateSubmission ? old('activo', '1') == '1' : true)>
                        <span class="text-sm font-semibold">Usuario activo</span>
                    </label>
                </div>

                <button type="submit" class="admin-button admin-button-primary w-full">
                    <i class="fas fa-user-plus"></i>
                    Crear usuario
                </button>
            </form>
        </section>

        <section class="space-y-4">
            <div class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white/80 px-5 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Personal registrado</h2>
                    <p class="mt-1 text-sm text-slate-500">Edita datos, cambia roles y renueva contrasenas sin salir de esta pantalla.</p>
                </div>

                <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                    <i class="fas fa-users"></i>
                    {{ $users->count() }} usuarios
                </div>
            </div>

            <div class="grid gap-4 2xl:grid-cols-2">
                @foreach($users as $managedUser)
                    @php
                        $isEditingThisUser = old('form_context') === 'update'
                            && (string) old('editing_user_id') === (string) $managedUser->id;
                        $managedName = $isEditingThisUser ? old('name', $managedUser->name) : $managedUser->name;
                        $managedEmail = $isEditingThisUser ? old('email', $managedUser->email) : $managedUser->email;
                        $managedCedula = $isEditingThisUser ? old('cedula', $managedUser->cedula) : $managedUser->cedula;
                        $managedTelefono = $isEditingThisUser ? old('telefono', $managedUser->telefono) : $managedUser->telefono;
                        $managedPuesto = $isEditingThisUser ? old('puesto', $managedUser->puesto) : $managedUser->puesto;
                        $managedRole = $isEditingThisUser ? old('role', $managedUser->primary_role) : $managedUser->primary_role;
                        $managedActivo = $isEditingThisUser
                            ? old('activo', $managedUser->activo ? '1' : '0') == '1'
                            : (bool) $managedUser->activo;
                    @endphp

                    <form action="{{ route('admin.users.update', $managedUser) }}" method="POST" class="admin-panel user-card {{ $managedUser->id === auth()->id() ? 'ring-2 ring-blue-200' : '' }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="form_context" value="update">
                        <input type="hidden" name="editing_user_id" value="{{ $managedUser->id }}">

                        <div class="user-card-header">
                            <div>
                                <h3 class="user-card-title">{{ $managedUser->name }}</h3>
                                <p class="user-card-subtitle">{{ $managedUser->email }}</p>
                            </div>

                            <div class="flex flex-wrap justify-end gap-2">
                                <span class="role-chip">
                                    <i class="fas fa-id-badge"></i>
                                    {{ $roleOptions[$managedUser->primary_role] ?? $managedUser->role_label }}
                                </span>

                                <span class="status-chip {{ $managedUser->activo ? 'status-active' : 'status-inactive' }}">
                                    <i class="fas {{ $managedUser->activo ? 'fa-circle-check' : 'fa-circle-pause' }}"></i>
                                    {{ $managedUser->activo ? 'Activo' : 'Inactivo' }}
                                </span>

                                @if($managedUser->id === auth()->id())
                                    <span class="status-chip account-chip">
                                        <i class="fas fa-key"></i>
                                        Tu usuario
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="field-label" for="name_{{ $managedUser->id }}">Nombre</label>
                                <input id="name_{{ $managedUser->id }}" type="text" name="name" value="{{ $managedName }}" class="admin-input" required>
                            </div>

                            <div>
                                <label class="field-label" for="email_{{ $managedUser->id }}">Correo</label>
                                <input id="email_{{ $managedUser->id }}" type="email" name="email" value="{{ $managedEmail }}" class="admin-input" required>
                            </div>

                            <div>
                                <label class="field-label" for="cedula_{{ $managedUser->id }}">Cedula</label>
                                <input id="cedula_{{ $managedUser->id }}" type="text" name="cedula" value="{{ $managedCedula }}" class="admin-input">
                            </div>

                            <div>
                                <label class="field-label" for="telefono_{{ $managedUser->id }}">Telefono</label>
                                <input id="telefono_{{ $managedUser->id }}" type="text" name="telefono" value="{{ $managedTelefono }}" class="admin-input">
                            </div>

                            <div class="md:col-span-2">
                                <label class="field-label" for="puesto_{{ $managedUser->id }}">Puesto</label>
                                <input id="puesto_{{ $managedUser->id }}" type="text" name="puesto" value="{{ $managedPuesto }}" class="admin-input">
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div>
                                <label class="field-label" for="role_{{ $managedUser->id }}">Rol</label>
                                @if($managedUser->id === auth()->id())
                                    <input type="hidden" name="role" value="{{ \App\Models\User::ROLE_ADMIN }}">
                                    <div class="inline-note">
                                        Tu cuenta conserva el rol de administrador para evitar que pierdas acceso al panel.
                                    </div>
                                @else
                                    <select id="role_{{ $managedUser->id }}" name="role" class="admin-select" required>
                                        @foreach($roleOptions as $roleName => $roleLabel)
                                            <option value="{{ $roleName }}" @selected($managedRole === $roleName)>
                                                {{ $roleLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <div>
                                <label class="field-label">Estado</label>
                                @if($managedUser->id === auth()->id())
                                    <input type="hidden" name="activo" value="1">
                                    <div class="inline-note">
                                        Tu usuario permanece activo mientras administras el sistema.
                                    </div>
                                @else
                                    <input type="hidden" name="activo" value="0">
                                    <label class="toggle-wrap">
                                        <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-blue-600" @checked($managedActivo)>
                                        <span class="text-sm font-semibold">Usuario activo</span>
                                    </label>
                                @endif
                            </div>

                            <div>
                                <label class="field-label" for="password_{{ $managedUser->id }}">Nueva contrasena</label>
                                <input id="password_{{ $managedUser->id }}" type="password" name="password" class="admin-input" autocomplete="new-password">
                                <p class="field-help">Deja en blanco si no deseas cambiarla.</p>
                            </div>

                            <div>
                                <label class="field-label" for="password_confirmation_{{ $managedUser->id }}">Confirmar contrasena</label>
                                <input id="password_confirmation_{{ $managedUser->id }}" type="password" name="password_confirmation" class="admin-input" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-slate-500">
                                {{ $managedUser->id === auth()->id() ? 'Puedes actualizar tus datos sin comprometer tu acceso de administrador.' : 'Guarda cambios despues de ajustar datos, rol o estado.' }}
                            </p>

                            <button type="submit" class="admin-button admin-button-primary">
                                <i class="fas fa-floppy-disk"></i>
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
