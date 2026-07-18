@php
    $permissionGroups = $permissionGroups ?? \App\Models\User::configurablePermissionGroups();
    $selectedPermissions = collect($selectedPermissions ?? old('custom_permissions', []))
        ->filter()
        ->values();
    $idSuffix = $idSuffix ?? 'user';
    $customPermissionsEnabled = (bool) ($customPermissionsEnabled ?? old('custom_permissions_enabled', false));
    $autoSaveUrl = $autoSaveUrl ?? null;
    $managedUser = $managedUser ?? null;
@endphp

<div
    class="rounded border border-slate-200 bg-slate-50 p-4"
    data-permission-panel
    @if($autoSaveUrl) data-permission-auto-save-url="{{ $autoSaveUrl }}" @endif
>
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h3 class="text-sm font-bold text-slate-900">Ajustes personalizados de acceso</h3>
            <p class="text-xs text-slate-500">Cuando esta capa esta activa, el rol se conserva y estas casillas aplican excepciones sobre vistas controladas.</p>
            @if($autoSaveUrl)
                <p data-permission-save-status class="mt-2 hidden text-xs font-semibold"></p>
            @endif
        </div>
        <label class="inline-flex items-start gap-3 rounded border border-blue-100 bg-blue-50 px-3 py-2 text-sm text-blue-900">
            <input type="hidden" name="custom_permissions_enabled" value="0">
            <input
                type="checkbox"
                name="custom_permissions_enabled"
                value="1"
                class="mt-1 rounded border-blue-300 text-blue-600 focus:ring-blue-500"
                @checked($customPermissionsEnabled)
            >
            <span>
                <span class="block font-semibold">Aplicar ajustes personalizados</span>
                <span class="block text-xs text-blue-700">Si esta apagado, el usuario conserva solamente los accesos de su rol.</span>
            </span>
        </label>
    </div>

    <div class="space-y-4">
        @foreach($permissionGroups as $groupKey => $group)
            <section class="rounded border border-slate-200 bg-white p-4">
                <div class="mb-3">
                    <h4 class="text-sm font-semibold text-slate-800">{{ $group['label'] }}</h4>
                    @if(!empty($group['description']))
                        <p class="mt-1 text-xs text-slate-500">{{ $group['description'] }}</p>
                    @endif
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    @foreach($group['permissions'] as $permissionName => $permission)
                        @php
                            $inputId = 'permission_' . md5($idSuffix . '_' . $permissionName);
                            $roleAllowsPermission = $managedUser instanceof \App\Models\User
                                ? \App\Support\AccessPermissionCatalog::roleDefaultAllows($managedUser, $permissionName)
                                : null;
                        @endphp

                        <label for="{{ $inputId }}" class="flex items-start gap-3 rounded border border-slate-100 bg-slate-50 px-3 py-3 text-sm text-slate-700">
                            <input
                                id="{{ $inputId }}"
                                type="checkbox"
                                name="custom_permissions[]"
                                value="{{ $permissionName }}"
                                class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                @checked($selectedPermissions->contains($permissionName))
                            >
                            <span>
                                <span class="block font-semibold text-slate-900">{{ $permission['label'] }}</span>
                                @if(!empty($permission['description']))
                                    <span class="mt-0.5 block text-xs leading-5 text-slate-500">{{ $permission['description'] }}</span>
                                @endif
                                @if($roleAllowsPermission === true)
                                    <span class="mt-1 block text-xs font-semibold text-amber-700">Marcado: restringe este acceso para este usuario.</span>
                                @elseif($roleAllowsPermission === false)
                                    <span class="mt-1 block text-xs font-semibold text-green-700">Marcado: concede este acceso adicional.</span>
                                @else
                                    <span class="mt-1 block text-xs font-semibold text-slate-500">Marcado: aplica una excepcion sobre el rol elegido.</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
