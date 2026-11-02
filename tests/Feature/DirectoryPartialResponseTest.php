<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryPartialResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_fragment_matches_the_full_directory_for_filters_pages_and_empty_results(): void
    {
        $admin = User::factory()->withRole(User::ROLE_ADMIN)->create();
        for ($i = 0; $i < 32; $i++) User::factory()->withRole(User::ROLE_TECNICO)->create(['name' => sprintf('Fixture %03d', $i), 'activo' => $i % 4 !== 0]);
        $this->actingAs($admin);
        foreach ([[], ['page' => 2], ['search' => 'Fixture', 'status' => 'inactive'], ['search' => 'Absent'], ['role' => User::ROLE_TECNICO]] as $filters) {
            $url = route('admin.users.index', $filters);
            $full = $this->get($url)->assertOk()->assertSee('Crear nuevo usuario');
            $fragment = $this->get($url, ['X-Directory-Fragment' => '1'])->assertOk()
                ->assertHeader('Vary', 'X-Directory-Fragment')->assertDontSee('<script', false)->assertDontSee('Crear nuevo usuario');
            $this->assertStringContainsString('no-store', $fragment->headers->get('Cache-Control'));
            $this->assertSame($this->directory($full->getContent()), $this->directory($fragment->getContent()));
            $this->assertSame($full->viewData('users')->pluck('id')->all(), $fragment->viewData('users')->pluck('id')->all());
            $this->assertSame($full->viewData('users')->total(), $fragment->viewData('users')->total());
        }
    }

    public function test_partial_header_never_bypasses_authentication_or_current_role(): void
    {
        $headers = ['X-Directory-Fragment' => '1'];
        $this->get(route('admin.users.index'), $headers)->assertRedirect(route('login'));
        $user = User::factory()->withRole(User::ROLE_ADMIN)->create();
        $this->actingAs($user)->get(route('admin.users.index'), $headers)->assertOk();
        \Spatie\Permission\Models\Role::findOrCreate(User::ROLE_TECNICO, 'web');
        $user->syncRoles([User::ROLE_TECNICO]);
        $this->actingAs($user->fresh())->get(route('admin.users.index'), $headers)->assertForbidden();
    }

    private function directory(string $html): string
    {
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">' . $html);
        $element = (new \DOMXPath($document))->query('//*[@id="userDirectory"]')->item(0);
        $this->assertNotNull($element);
        return $document->saveHTML($element);
    }
}
