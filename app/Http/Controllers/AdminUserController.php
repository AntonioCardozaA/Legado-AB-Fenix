<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);
        $users = User::query()
            ->with('roles')
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
            'stats' => $this->stats(),
            'filters' => $filters,
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        return view('admin.users.edit', [
            'managedUser' => $user->load('roles'),
            'roleOptions' => $this->roleOptions(),
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

        return redirect()
            ->route('admin.users.edit', array_merge([
                'user' => $user,
            ], array_filter($this->filtersFromRequest($request), fn ($value) => $value !== '')))
            ->with('success', 'Usuario actualizado correctamente.');
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
            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                Password::defaults(),
            ],
        ]);

        $data['activo'] = $request->boolean('activo');

        return $data;
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

    private function isCurrentUser(User $user): bool
    {
        return (int) Auth::id() === (int) $user->id;
    }
}
