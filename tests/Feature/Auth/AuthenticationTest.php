<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_authenticated_users_are_redirected_away_from_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['url.intended' => route('login')])
            ->get('/login');

        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionMissing('url.intended');
    }

    public function test_guest_is_redirected_to_login_when_visiting_protected_route(): void
    {
        $response = $this->get(route('dashboard', absolute: false));

        $response
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('url.intended', route('dashboard'));
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_are_redirected_to_the_original_protected_route_after_login(): void
    {
        $user = User::factory()->create();

        $this
            ->get(route('profile.edit', absolute: false))
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('url.intended', route('profile.edit'));

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $response
            ->assertRedirect(route('profile.edit', absolute: false))
            ->assertSessionMissing('url.intended');
    }

    #[DataProvider('authenticationIntendedUrlProvider')]
    public function test_login_ignores_authentication_intended_urls(string $intendedUrl): void
    {
        $user = User::factory()->create();

        $response = $this
            ->withSession(['url.intended' => $intendedUrl])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticated();
        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionMissing('url.intended');
    }

    public static function authenticationIntendedUrlProvider(): array
    {
        return [
            'login' => ['http://localhost/login'],
            'logout' => ['http://localhost/logout'],
            'forgot password' => ['http://localhost/forgot-password'],
            'reset password' => ['http://localhost/reset-password/token'],
            'password update' => ['http://localhost/password'],
            'verify email' => ['http://localhost/verify-email'],
        ];
    }

    public function test_login_is_not_stored_as_intended_when_guest_posts_to_protected_route(): void
    {
        Route::post('/_test-protected-post', static fn () => response()->noContent())
            ->middleware(['web', 'auth']);

        $response = $this
            ->from(route('login', absolute: false))
            ->post('/_test-protected-post');

        $response
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionMissing('url.intended');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
