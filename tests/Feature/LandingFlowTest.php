<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_route_displays_the_welcome_view_with_login_access(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Iniciar sesion')
            ->assertSee(route('login', absolute: false), false);
    }

    public function test_authenticated_user_can_open_the_welcome_view_and_see_profile_access(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/');

        $response
            ->assertOk()
            ->assertSee(route('dashboard', absolute: false), false)
            ->assertSee(route('profile.edit', absolute: false), false)
            ->assertSee('Cerrar sesion');
    }

    public function test_guest_is_redirected_to_login_when_trying_to_open_the_profile(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_profile_page_displays_a_logout_option(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('Cerrar sesion')
            ->assertSee(route('logout', absolute: false), false);
    }
}
