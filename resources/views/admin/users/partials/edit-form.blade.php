@php
    $filters = $filters ?? ['search' => '', 'role' => '', 'status' => ''];
    $activeFilters = array_filter($filters, fn ($value) => $value !== '');
    $isCurrentUser = $managedUser->id === auth()->id();
    $managedName = old('name', $managedUser->name);
    $managedEmail = old('email', $managedUser->email);
    $managedCedula = old('cedula', $managedUser->cedula);
    $managedTelefono = old('telefono', $managedUser->telefono);
    $managedPuesto = old('puesto', $managedUser->puesto);
    $managedRole = old('role', $managedUser->primary_role);
    $managedActivo = old('activo', $managedUser->activo ? '1' : '0') == '1';
@endphp

<form action="{{ route('admin.users.update', array_merge(['user' => $managedUser], $activeFilters)) }}" method="POST" class="space-y-5">
    @csrf
    @method('PUT')
    <input type="hidden" name="form_context" value="update">
    <input type="hidden" name="editing_user_id" value="{{ $managedUser->id }}">

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="name_{{ $managedUser->id }}">Nombre</label>
            <input id="name_{{ $managedUser->id }}" type="text" name="name" value="{{ $managedName }}" class="w-full rounded border-gray-300 text-sm" required>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="email_{{ $managedUser->id }}">Correo</label>
            <input id="email_{{ $managedUser->id }}" type="email" name="email" value="{{ $managedEmail }}" class="w-full rounded border-gray-300 text-sm" required>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="cedula_{{ $managedUser->id }}">Cedula</label>
            <input id="cedula_{{ $managedUser->id }}" type="text" name="cedula" value="{{ $managedCedula }}" class="w-full rounded border-gray-300 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="telefono_{{ $managedUser->id }}">Telefono</label>
            <input id="telefono_{{ $managedUser->id }}" type="text" name="telefono" value="{{ $managedTelefono }}" class="w-full rounded border-gray-300 text-sm">
        </div>

        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="puesto_{{ $managedUser->id }}">Puesto</label>
            <input id="puesto_{{ $managedUser->id }}" type="text" name="puesto" value="{{ $managedPuesto }}" class="w-full rounded border-gray-300 text-sm">
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="role_{{ $managedUser->id }}">Rol</label>
            @if($isCurrentUser)
                <input type="hidden" name="role" value="{{ \App\Models\User::ROLE_ADMIN }}">
                <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                    Tu cuenta conserva el rol de administrador para evitar que pierdas acceso al panel.
                </div>
            @else
                <select id="role_{{ $managedUser->id }}" name="role" class="w-full rounded border-gray-300 text-sm" required>
                    @foreach($roleOptions as $roleName => $roleLabel)
                        <option value="{{ $roleName }}" @selected($managedRole === $roleName)>
                            {{ $roleLabel }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Estado</label>
            @if($isCurrentUser)
                <input type="hidden" name="activo" value="1">
                <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                    Tu usuario permanece activo mientras administras el sistema.
                </div>
            @else
                <input type="hidden" name="activo" value="0">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="activo" value="1" class="rounded border-gray-300 text-blue-600" @checked($managedActivo)>
                    Usuario activo
                </label>
            @endif
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="password_{{ $managedUser->id }}">Nueva contrasena</label>
            <input id="password_{{ $managedUser->id }}" type="password" name="password" class="w-full rounded border-gray-300 text-sm" autocomplete="new-password">
            <p class="mt-1 text-xs text-gray-500">Deja en blanco si no deseas cambiarla.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="password_confirmation_{{ $managedUser->id }}">Confirmar contrasena</label>
            <input id="password_confirmation_{{ $managedUser->id }}" type="password" name="password_confirmation" class="w-full rounded border-gray-300 text-sm" autocomplete="new-password">
        </div>
    </div>

    <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">
            {{ $isCurrentUser ? 'Puedes actualizar tus datos sin comprometer tu acceso de administrador.' : 'Guarda cambios.' }}
        </p>

        <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Guardar cambios
        </button>
    </div>
</form>
