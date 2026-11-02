<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ViewPresentationContextTest extends TestCase
{
    use RefreshDatabase;

    private function composed(string $name): array
    {
        $view = view($name);
        app('view')->callComposer($view);
        return $view->getData();
    }

    public function test_every_existing_consumer_gets_context_but_other_partials_do_not(): void
    {
        $this->actingAs(User::factory()->withRole(User::ROLE_ADMIN)->create());
        $consumers = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (!str_ends_with($file->getFilename(), '.blade.php')) continue;
            if (!preg_match('/\$(userRoles|userRoleLabel|canAccessPasteurizadora|canSeePasteurizadora|pasteurizadoraComingSoon|canDeleteAnalysis)\b/', $file->getContents())) continue;
            $name = str_replace(['/', '\\'], '.', substr($file->getRelativePathname(), 0, -10));
            $this->assertArrayHasKey('userRoleLabel', $this->composed($name), $name . ' consumes the presentation context.');
            $consumers[] = $name;
        }
        $this->assertGreaterThan(0, count($consumers));
        $this->assertArrayNotHasKey('userRoleLabel', $this->composed('layouts.partials.site-icons'));
    }

    public function test_context_tracks_user_role_changes_and_guest_without_shared_cache(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_TECNICO] as $role) {
            $user = User::factory()->withRole($role)->create();
            $this->actingAs($user);
            $data = $this->composed('layouts.app');
            $this->assertSame($user->role_label, $data['userRoleLabel']);
            $this->assertSame($user->canDeleteAnalysis(), $data['canDeleteAnalysis']);
            $this->assertSame($user->canAccessModule(User::MODULE_PASTEURIZADORA), $data['canAccessPasteurizadora']);
        }
        $user->syncRoles([User::ROLE_ADMIN]);
        $user->unsetRelation('roles');
        $this->assertSame($user->role_label, $this->composed('layouts.app')['userRoleLabel']);
        Auth::forgetGuards();
        $this->assertArrayNotHasKey('userRoleLabel', $this->composed('layouts.app'));
    }
}
