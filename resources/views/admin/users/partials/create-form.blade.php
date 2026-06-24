@php
    $isCreateSubmission = $isCreateSubmission ?? false;
@endphp

<form action="{{ route('admin.users.store') }}" method="POST" class="space-y-5" x-data="{ showPassword: false, showPasswordConfirmation: false, passwordPreview: '' }">
    @csrf
    <input type="hidden" name="form_context" value="create">

    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nombre completo</label>
        <input id="name" type="text" name="name" value="{{ $isCreateSubmission ? old('name') : '' }}" class="w-full rounded border-gray-300 text-sm" required>
    </div>

    <div>
        <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Correo</label>
        <input id="email" type="email" name="email" value="{{ $isCreateSubmission ? old('email') : '' }}" class="w-full rounded border-gray-300 text-sm" required>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="cedula" class="mb-1 block text-sm font-medium text-gray-700">Cedula</label>
            <input id="cedula" type="text" name="cedula" value="{{ $isCreateSubmission ? old('cedula') : '' }}" class="w-full rounded border-gray-300 text-sm">
        </div>

        <div>
            <label for="telefono" class="mb-1 block text-sm font-medium text-gray-700">Telefono</label>
            <input id="telefono" type="text" name="telefono" value="{{ $isCreateSubmission ? old('telefono') : '' }}" class="w-full rounded border-gray-300 text-sm">
        </div>
    </div>

    <div>
        <label for="puesto" class="mb-1 block text-sm font-medium text-gray-700">Puesto</label>
        <input id="puesto" type="text" name="puesto" value="{{ $isCreateSubmission ? old('puesto') : '' }}" class="w-full rounded border-gray-300 text-sm">
    </div>

    <div>
        <label for="role" class="mb-1 block text-sm font-medium text-gray-700">Rol</label>
        <select id="role" name="role" class="w-full rounded border-gray-300 text-sm" required>
            @foreach($roleOptions as $roleName => $roleLabel)
                <option value="{{ $roleName }}" @selected(($isCreateSubmission ? old('role', \App\Models\User::ROLE_TECNICO) : \App\Models\User::ROLE_TECNICO) === $roleName)>
                    {{ $roleLabel }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Contraseña</label>
        <div class="relative">
            <input
                id="password"
                x-model="passwordPreview"
                x-bind:type="showPassword ? 'text' : 'password'"
                name="password"
                class="w-full rounded border-gray-300 pr-12 text-sm"
                autocomplete="new-password"
                required
            >
            <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-3 inline-flex items-center text-slate-500 transition hover:text-slate-700"
                x-bind:aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            >
                <i class="fas" x-bind:class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
            </button>
        </div>
        <p class="mt-1 text-xs text-gray-500">Usa minimo 8 caracteres.</p>
    </div>

    <div>
        <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Confirmar contraseña</label>
        <div class="relative">
            <input
                id="password_confirmation"
                x-bind:type="showPasswordConfirmation ? 'text' : 'password'"
                name="password_confirmation"
                class="w-full rounded border-gray-300 pr-12 text-sm"
                autocomplete="new-password"
                required
            >
            <button
                type="button"
                @click="showPasswordConfirmation = !showPasswordConfirmation"
                class="absolute inset-y-0 right-3 inline-flex items-center text-slate-500 transition hover:text-slate-700"
                x-bind:aria-label="showPasswordConfirmation ? 'Ocultar confirmacion de contraseña' : 'Mostrar confirmacion de contraseña'"
            >
                <i class="fas" x-bind:class="showPasswordConfirmation ? 'fa-eye-slash' : 'fa-eye'"></i>
            </button>
        </div>
    </div>

    <div x-show="passwordPreview.length > 0" x-cloak class="rounded border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Vista previa</p>
        <p class="mt-2 break-all" x-text="passwordPreview"></p>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" value="1" class="rounded border-gray-300 text-blue-600" @checked($isCreateSubmission ? old('activo', '1') == '1' : true)>
        Usuario activo
    </label>

    <button type="submit" class="create-action w-full">
        Crear usuario
    </button>
</form>
