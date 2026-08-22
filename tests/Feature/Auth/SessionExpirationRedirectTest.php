<?php

namespace Tests\Feature\Auth;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionExpirationRedirectTest extends TestCase
{
    public function test_expired_web_session_redirects_to_login(): void
    {
        Route::post('/_test-token-mismatch', static function (): void {
            throw new TokenMismatchException();
        })->middleware('web');

        $response = $this->post('/_test-token-mismatch');

        $response
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('status', 'Tu sesion expiro. Vuelve a iniciar sesion para continuar.');
    }

    public function test_expired_json_session_keeps_419_response(): void
    {
        Route::post('/_test-token-mismatch-json', static function (): void {
            throw new TokenMismatchException();
        })->middleware('web');

        $response = $this->postJson('/_test-token-mismatch-json');

        $response
            ->assertStatus(419)
            ->assertJson([
                'message' => 'Tu sesion expiro. Vuelve a iniciar sesion para continuar.',
            ]);
    }
}
