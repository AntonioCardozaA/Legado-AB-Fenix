<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionExpirationRedirectTest extends TestCase
{
<<<<<<< HEAD
    use RefreshDatabase;
=======
    public function test_login_csrf_token_can_be_refreshed(): void
    {
        $response = $this->get(route('csrf-token', absolute: false));

        $response
            ->assertOk()
            ->assertJsonStructure(['token']);

        $this->assertNotEmpty($response->json('token'));
        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
    }
>>>>>>> 9cde4a464f7a2d71da55347f71970f3eb17562df

    public function test_expired_web_session_redirects_to_login(): void
    {
        Route::post('/_test-token-mismatch', static function (): void {
            throw new TokenMismatchException;
        })->middleware('web');

        $response = $this
            ->from(route('login', absolute: false))
            ->post('/_test-token-mismatch');

        $response
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionMissing('url.intended')
            ->assertSessionHas('status', 'Tu sesion expiro. Vuelve a iniciar sesion para continuar.');
    }

    public function test_user_can_login_normally_after_expired_session_redirect(): void
    {
        Route::post('/_test-expired-session-action', static function (): void {
            throw new TokenMismatchException;
        })->middleware('web');

        $user = User::factory()->create();

        $this
            ->withSession(['url.intended' => route('login')])
            ->from(route('login', absolute: false))
            ->post('/_test-expired-session-action')
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionMissing('url.intended');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionMissing('url.intended');
    }

    public function test_expired_json_session_keeps_419_response(): void
    {
        Route::post('/_test-token-mismatch-json', static function (): void {
            throw new TokenMismatchException;
        })->middleware('web');

        $response = $this->postJson('/_test-token-mismatch-json');

        $response
            ->assertStatus(419)
            ->assertJson([
                'message' => 'Tu sesion expiro. Vuelve a iniciar sesion para continuar.',
            ]);
    }
}
