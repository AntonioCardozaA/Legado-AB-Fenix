<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\AccessPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountAccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_without_roles_have_no_implicit_operational_permissions(): void
    {
        $user = User::factory()->create();

        foreach (['ver analisis lavadora', 'crear analisis lavadora', 'editar analisis lavadora', 'ver reportes', 'usar asistente ia', 'gestionar usuarios'] as $permission) {
            $this->assertFalse($user->canUseCustomPermission($permission), $permission);
        }

        foreach (array_keys(User::modulePermissionMap()) as $module) {
            $this->assertFalse($user->canAccessModule($module), $module);
        }

        $this->actingAs($user);

        foreach (['/dashboard', '/analisis-lavadora', '/reportes', '/asistente/chat'] as $url) {
            $this->get($url)->assertForbidden();
        }

        $this->post('/analisis-lavadora', [])->assertForbidden();
        $this->put('/analisis-lavadora/1', [])->assertForbidden();
        $this->postJson('/asistente/chat', ['message' => 'test'])->assertForbidden();
        $this->get('/profile')->assertOk();
        $this->post('/logout')->assertRedirect('/');
    }

    public function test_unmapped_routes_do_not_grant_access_to_accounts_without_roles(): void
    {
        Route::get('/_security/unmapped', fn () => 'private')
            ->middleware(['web', 'auth', 'custom.permission.access'])
            ->name('security.unmapped');

        $this->actingAs(User::factory()->create())
            ->get('/_security/unmapped')->assertForbidden();
    }

    public function test_explicit_permissions_do_not_grant_other_default_permissions(): void
    {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('ver analisis lavadora', 'web');
        $user->givePermissionTo($permission);

        $this->assertTrue($user->canUseCustomPermission('ver analisis lavadora'));
        $this->assertFalse($user->canUseCustomPermission('crear analisis lavadora'));
        $this->assertFalse($user->canUseCustomPermission('ver reportes'));

        $user->givePermissionTo(Permission::findOrCreate(User::customAccessControlPermissionName(), 'web'));

        $this->assertTrue($user->canUseCustomPermission('ver analisis lavadora'));
        $this->assertFalse($user->canUseCustomPermission('editar analisis lavadora'));
    }

    public function test_unrecognized_roles_require_explicit_permissions(): void
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('auditor_externo', 'web');
        $user->assignRole($role);

        $this->assertFalse($user->canUseCustomPermission('ver reportes'));
        $this->assertFalse($user->canAccessModule(User::MODULE_LAVADORA));

        $role->givePermissionTo(Permission::findOrCreate('ver reportes', 'web'));
        $user->unsetRelation('roles');

        $this->assertTrue($user->canUseCustomPermission('ver reportes'));
        $this->assertFalse($user->canUseCustomPermission('crear analisis lavadora'));
    }

    public function test_inactive_users_cannot_log_in_even_with_remember_me(): void
    {
        $user = $this->technician(['activo' => false]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_disabling_an_account_rotates_remember_token_and_revokes_its_session(): void
    {
        $user = $this->technician();
        $rememberToken = $user->remember_token;

        $this->actingAs($user)->withSession(['private_data' => 'secret'])
            ->get('/profile')->assertOk();

        $user->update(['activo' => false]);

        $this->assertSame(1, $user->session_version);
        $this->assertNotSame($rememberToken, $user->remember_token);

        $this->get('/profile')->assertRedirect(route('login'))
            ->assertSessionMissing('private_data')
            ->assertSessionMissing('auth_session_version');
        $this->assertGuest();
    }

    public function test_inactive_authenticated_json_requests_are_rejected_and_logged_out(): void
    {
        $user = $this->technician(['activo' => false]);

        $this->actingAs($user)->getJson('/asistente/chat')->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_reactivation_does_not_restore_an_old_session(): void
    {
        $user = $this->technician();
        $this->actingAs($user)->get('/profile')->assertOk();

        $user->update(['activo' => false]);
        $user->update(['activo' => true]);

        $this->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard')->assertSessionHas('auth_session_version', 1);
        $this->get('/profile')->assertOk();
    }

    public function test_legacy_sessions_are_rejected_after_a_revocation(): void
    {
        $user = $this->technician();
        $user->update(['activo' => false]);
        $user->update(['activo' => true]);

        $this->actingAs($user)->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_old_remember_me_cookie_cannot_restore_a_reactivated_account(): void
    {
        $user = $this->technician();
        $guard = Auth::guard('web');
        $oldCookie = implode('|', [$user->id, $user->remember_token, $guard->hashPasswordForCookie($user->password)]);
        $cookieName = $guard->getRecallerName();
        $user->update(['activo' => false]);
        $user->update(['activo' => true]);
        Auth::forgetGuards();

        $this->withCookie($cookieName, $oldCookie)->get('/profile')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_valid_remember_me_cookie_can_start_a_new_session_after_reactivation(): void
    {
        $user = $this->technician();
        $user->update(['activo' => false]);
        $user->update(['activo' => true]);
        $guard = Auth::guard('web');
        $cookie = implode('|', [$user->id, $user->remember_token, $guard->hashPasswordForCookie($user->password)]);
        $cookieName = $guard->getRecallerName();
        Auth::forgetGuards();

        $this->withCookie($cookieName, $cookie)->get('/profile')
            ->assertOk()->assertSessionHas('auth_session_version', 1);
        $this->assertAuthenticatedAs($user);
    }

    public function test_known_roles_keep_their_permissions_and_unknown_permissions_are_denied(): void
    {
        $user = $this->technician();

        $this->assertTrue($user->canUseCustomPermission('crear analisis lavadora'));
        $this->assertTrue($user->canAccessModule(User::MODULE_LAVADORA));
        $this->assertFalse($user->canUseCustomPermission('permiso inexistente'));
        $this->assertFalse(AccessPermissionCatalog::roleDefaultAllows($user, 'permiso inexistente'));
    }

    private function technician(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole(Role::findOrCreate(User::ROLE_TECNICO, 'web'));

        return $user;
    }
}
