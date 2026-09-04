@php
    $permissionGroups = $permissionGroups ?? \App\Models\User::configurablePermissionGroups();
    $selectedPermissions = collect($selectedPermissions ?? old('custom_permissions', []))
        ->filter()
        ->values();
    $idSuffix = $idSuffix ?? 'user';
    $customPermissionsEnabled = (bool) ($customPermissionsEnabled ?? old('custom_permissions_enabled', false));
    $autoSaveUrl = $autoSaveUrl ?? null;
    $managedUser = $managedUser ?? null;
    $groupStyles = [
        'dashboards' => ['icon' => 'fa-grid-2', 'iconClass' => 'bg-blue-50 text-blue-600'],
        'analisis_lavadora' => ['icon' => 'fa-gears', 'iconClass' => 'bg-sky-50 text-sky-600'],
        'costos_lavadora' => ['icon' => 'fa-coins', 'iconClass' => 'bg-amber-50 text-amber-600'],
        'analisis_etiquetadora' => ['icon' => 'fa-tags', 'iconClass' => 'bg-rose-50 text-rose-600'],
        'pasteurizadora' => ['icon' => 'fa-temperature-half', 'iconClass' => 'bg-orange-50 text-orange-600'],
        'tendencias' => ['icon' => 'fa-chart-line', 'iconClass' => 'bg-violet-50 text-violet-600'],
        'plan_accion' => ['icon' => 'fa-list-check', 'iconClass' => 'bg-emerald-50 text-emerald-600'],
        'reportes' => ['icon' => 'fa-file-chart-column', 'iconClass' => 'bg-indigo-50 text-indigo-600'],
        'catalogos' => ['icon' => 'fa-book-open', 'iconClass' => 'bg-slate-100 text-slate-600'],
        'admin' => ['icon' => 'fa-shield-halved', 'iconClass' => 'bg-slate-100 text-slate-600'],
        'ia_lavadora' => ['icon' => 'fa-brain', 'iconClass' => 'bg-cyan-50 text-cyan-600'],
        'analisis_legacy' => ['icon' => 'fa-clock-rotate-left', 'iconClass' => 'bg-stone-100 text-stone-600'],
    ];
@endphp

<div
    class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-2 sm:p-3"
    data-permission-panel
    @if($autoSaveUrl) data-permission-auto-save-url="{{ $autoSaveUrl }}" @endif
>
    <div class="mb-3 flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h3 class="text-base font-bold text-slate-950">Permisos por vista y maquina</h3>
            @if($autoSaveUrl)
                <p data-permission-save-status class="mt-2 hidden text-xs font-semibold"></p>
            @endif
        </div>
        <label class="inline-flex w-full items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-2.5 text-sm text-blue-900 lg:w-auto">
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

    <div class="columns-1 gap-3 2xl:columns-2">
        @foreach($permissionGroups as $groupKey => $group)
            @php
                $style = $groupStyles[$groupKey] ?? ['icon' => 'fa-layer-group', 'iconClass' => 'bg-slate-100 text-slate-600'];
                $selectedCount = collect($group['permissions'])->keys()->intersect($selectedPermissions)->count();
            @endphp
            <section class="mb-3 min-w-0 break-inside-avoid overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-2 border-b border-slate-100 px-3 py-2.5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $style['iconClass'] }}">
                            <i class="fas {{ $style['icon'] }}"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">{{ $group['label'] }}</h4>
                            @if(!empty($group['description']))
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $group['description'] }}</p>
                            @endif
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-500">{{ $selectedCount }}/{{ count($group['permissions']) }}</span>
                </div>

                <div class="grid items-start gap-1.5 p-1.5 sm:grid-cols-2 2xl:grid-cols-2">
                    @foreach($group['permissions'] as $permissionName => $permission)
                        @php
                            $inputId = 'permission_' . md5($idSuffix . '_' . $permissionName);
                            $roleAllowsPermission = $managedUser instanceof \App\Models\User
                                ? \App\Support\AccessPermissionCatalog::roleDefaultAllows($managedUser, $permissionName)
                                : null;
                        @endphp

                        <label for="{{ $inputId }}" class="flex self-start cursor-pointer items-start gap-2 rounded-lg border border-transparent bg-slate-50 px-2 py-2 text-sm text-slate-700 transition hover:border-slate-200 hover:bg-white">
                            <input
                                id="{{ $inputId }}"
                                type="checkbox"
                                name="custom_permissions[]"
                                value="{{ $permissionName }}"
                                class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                @checked($selectedPermissions->contains($permissionName))
                            >
                            <span class="min-w-0 break-words [overflow-wrap:anywhere]">
                                <span class="block break-words font-semibold leading-5 text-slate-900">{{ $permission['label'] }}</span>
                                @if(!empty($permission['description']))
                                    <span class="mt-0.5 block break-words text-xs leading-5 text-slate-500">{{ $permission['description'] }}</span>
                                @endif
                                @if($roleAllowsPermission === true)
                                    <span class="mt-1 block text-xs font-semibold text-amber-700">Marcado: restringe.</span>
                                @elseif($roleAllowsPermission === false)
                                    <span class="mt-1 block text-xs font-semibold text-green-700">Marcado: concede.</span>
                                @else
                                    <span class="mt-1 block text-xs font-semibold text-slate-500">Marcado: excepcion.</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
