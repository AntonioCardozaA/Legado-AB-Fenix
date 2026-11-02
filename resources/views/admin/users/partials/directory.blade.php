@php($activeFilters = array_filter($filters, fn ($value) => $value !== ''))
<section id="userDirectory" data-directory-url="{{ route('admin.users.index') }}" tabindex="-1" aria-label="Directorio de usuarios">
    <p id="directory-status" class="sr-only" role="status" aria-live="polite"></p>
        <div>
            <form action="{{ route('admin.users.index') }}" method="GET" class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4" data-auto-filter-form data-user-directory-filter>
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
                        @if($activeFilters !== [])
                            <a href="{{ route('admin.users.index') }}" data-directory-reset class="w-full rounded border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50 sm:w-auto">
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
                        {{ $users->total() }} resultado(s)
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
                    @if($users->hasPages())
                        <div data-directory-pagination class="border-t border-slate-100 px-4 py-4">{{ $users->links() }}</div>
                    @endif
                @endif
            </div>
        </div>


</section>
