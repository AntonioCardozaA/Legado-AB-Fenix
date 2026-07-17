<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);
        $users = User::query()
            ->with(['roles', 'permissions'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('cedula', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%")
                        ->orWhere('puesto', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] !== '', function ($query) use ($filters) {
                $query->whereHas('roles', function ($roleQuery) use ($filters) {
                    $roleQuery->where('name', $filters['role']);
                });
            })
            ->when($filters['status'] !== '', function ($query) use ($filters) {
                $query->where('activo', $filters['status'] === 'active');
            })
            ->orderByDesc('activo')
            ->orderBy('name')
            ->get();

        return view('admin.users.index', [
            'users' => $users,
            'roleOptions' => $this->roleOptions(),
            'permissionGroups' => User::configurablePermissionGroups(),
            'stats' => $this->stats(),
            'filters' => $filters,
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        return view('admin.users.edit', [
            'managedUser' => $user->load(['roles', 'permissions']),
            'roleOptions' => $this->roleOptions(),
            'permissionGroups' => User::configurablePermissionGroups(),
            'stats' => $this->stats(),
            'filters' => $this->filtersFromRequest($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        $user = new User();
        $this->fillUser($user, $data);
        $user->save();

        $this->syncRole($user, $data['role']);
        $this->syncSpecialPermissions($user, $data);

        return redirect()
            ->route('admin.users.edit', ['user' => $user])
            ->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validatePayload($request, $user);

        if ($this->isCurrentUser($user) && (!$data['activo'] || $data['role'] !== User::ROLE_ADMIN)) {
            return back()
                ->withErrors([
                    'self_protection' => 'Tu cuenta debe mantenerse activa con el rol de administrador.',
                ])
                ->withInput();
        }

        $this->fillUser($user, $data);
        $user->save();

        $this->syncRole($user, $data['role']);
        $this->syncSpecialPermissions($user, $data);

        return redirect()
            ->route('admin.users.edit', array_merge([
                'user' => $user,
            ], array_filter($this->filtersFromRequest($request), fn ($value) => $value !== '')))
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function updatePermissions(Request $request, User $user): JsonResponse
    {
        $data = $this->validatePermissionPayload($request);

        $this->syncSpecialPermissions($user, $data);

        return response()->json([
            'message' => 'Permisos guardados correctamente.',
            'custom_permissions_enabled' => $user->fresh()?->usesCustomPermissionAccess() ?? false,
            'custom_permissions' => $user->fresh()?->getDirectPermissions()->pluck('name')->values()->all() ?? [],
        ]);
    }

    private function validatePayload(Request $request, ?User $user = null): array
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $roleNames = $this->availableRoleNames()->all();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($user?->id),
            ],
            'cedula' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'cedula')->ignore($user?->id),
            ],
            'telefono' => ['nullable', 'string', 'max:255'],
            'puesto' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in($roleNames)],
            'activo' => ['required', 'boolean'],
            'can_delete_analysis' => ['nullable', 'boolean'],
            'can_close_lavadora_damage' => ['nullable', 'boolean'],
            'custom_permissions_enabled' => ['nullable', 'boolean'],
            'custom_permissions' => ['nullable', 'array'],
            'custom_permissions.*' => ['string', Rule::in(User::configurablePermissionNames())],
            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                Password::defaults(),
            ],
        ]);

        $data['activo'] = $request->boolean('activo');
        $data['can_delete_analysis'] = $request->boolean('can_delete_analysis');
        $data['can_close_lavadora_damage'] = $request->boolean('can_close_lavadora_damage');
        $data['custom_permissions_enabled'] = $request->boolean('custom_permissions_enabled');
        $data['custom_permissions'] = collect($request->input('custom_permissions', []))
            ->filter(fn ($permission) => is_string($permission))
            ->intersect(User::configurablePermissionNames())
            ->unique()
            ->values()
            ->all();

        if ($data['can_delete_analysis']) {
            $data['custom_permissions'][] = User::PERMISSION_DELETE_ANALYSIS;
        }

        if ($data['can_close_lavadora_damage']) {
            $data['custom_permissions'][] = User::PERMISSION_CLOSE_LAVADORA_DAMAGE;
        }

        if ($data['can_delete_analysis'] || $data['can_close_lavadora_damage']) {
            $data['custom_permissions_enabled'] = true;
        }

        $data['custom_permissions'] = array_values(array_unique($data['custom_permissions']));

        return $data;
    }

    private function validatePermissionPayload(Request $request): array
    {
        $data = $request->validate([
            'custom_permissions_enabled' => ['nullable', 'boolean'],
            'custom_permissions' => ['nullable', 'array'],
            'custom_permissions.*' => ['string', Rule::in(User::configurablePermissionNames())],
        ]);

        return [
            'custom_permissions_enabled' => $request->boolean('custom_permissions_enabled'),
            'custom_permissions' => collect($data['custom_permissions'] ?? [])
                ->filter(fn ($permission) => is_string($permission))
                ->intersect(User::configurablePermissionNames())
                ->unique()
                ->values()
                ->all(),
        ];
    }

    private function fillUser(User $user, array $data): void
    {
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'cedula' => $data['cedula'] ?: null,
            'telefono' => $data['telefono'] ?: null,
            'puesto' => $data['puesto'] ?: null,
            'activo' => $data['activo'],
        ]);

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
    }

    private function roleOptions(): array
    {
        return $this->availableRoleNames()
            ->mapWithKeys(function (string $roleName): array {
                return [
                    $roleName => User::roleLabels()[$roleName]
                        ?? str($roleName)->replace('_', ' ')->title()->toString(),
                ];
            })
            ->all();
    }

    private function stats(): array
    {
        return [
            'total' => User::count(),
            'activos' => User::where('activo', true)->count(),
            'tecnicos' => User::whereHas('roles', function ($query) {
                $query->whereIn('name', User::technicianEquivalentRoles());
            })->count(),
            'administradores' => User::whereHas('roles', function ($query) {
                $query->where('name', User::ROLE_ADMIN);
            })->count(),
        ];
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'role' => trim((string) $request->query('role', '')),
            'status' => trim((string) $request->query('status', '')),
        ];
    }

    private function availableRoleNames(): Collection
    {
        $knownRoles = collect(array_keys(User::roleLabels()));
        $configuredRoles = Role::query()
            ->where('guard_name', 'web')
            ->pluck('name');

        return $knownRoles
            ->merge($configuredRoles)
            ->filter()
            ->unique()
            ->values();
    }

    private function syncRole(User $user, string $roleName): void
    {
        Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $user->syncRoles([$roleName]);
    }

    private function syncSpecialPermissions(User $user, array $data): void
    {
        $configurablePermissionNames = User::configurablePermissionNames();
        $customAccessPermissionName = User::customAccessControlPermissionName();

        foreach ([...$configurablePermissionNames, $customAccessPermissionName] as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $requestedPermissions = collect($data['custom_permissions'] ?? [])
            ->intersect($configurablePermissionNames)
            ->unique()
            ->values();

        $preservedDirectPermissions = $user->getDirectPermissions()
            ->pluck('name')
            ->diff([...$configurablePermissionNames, $customAccessPermissionName])
            ->values();

        if ($data['custom_permissions_enabled'] ?? false) {
            $requestedPermissions->push($customAccessPermissionName);
        } else {
            $requestedPermissions = collect();
        }

        $user->syncPermissions($preservedDirectPermissions->merge($requestedPermissions)->unique()->all());
    }

    private function isCurrentUser(User $user): bool
    {
        return (int) Auth::id() === (int) $user->id;
    }
}
