<?php

namespace Tests\Feature;

use App\Models\AdminVideo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminVideoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_video_management_screen(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->get(route('admin.videos.index'))
            ->assertOk()
            ->assertSee('Subir un video')
            ->assertSee('Área privada de administración');
    }

    public function test_non_admin_cannot_open_video_management_screen(): void
    {
        $technician = $this->userWithRole(User::ROLE_TECNICO);

        $this->actingAs($technician)
            ->get(route('admin.videos.index'))
            ->assertForbidden();
    }

    public function test_admin_can_upload_stream_and_delete_a_video(): void
    {
        Storage::fake('local');
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $file = UploadedFile::fake()->create('procedimiento.mp4', 1024, 'video/mp4');

        $this->actingAs($admin)
            ->post(route('admin.videos.store'), [
                'title' => 'Procedimiento de prueba',
                'video' => $file,
            ])
            ->assertRedirect(route('admin.videos.index'))
            ->assertSessionHas('success', 'Video cargado correctamente.');

        $video = AdminVideo::query()->firstOrFail();

        $this->assertSame('Procedimiento de prueba', $video->title);
        Storage::disk('local')->assertExists($video->storage_path);

        $this->actingAs($admin)
            ->get(route('admin.videos.stream', $video))
            ->assertOk()
            ->assertHeader('Content-Type', 'video/mp4');

        $this->actingAs($admin)
            ->delete(route('admin.videos.destroy', $video))
            ->assertRedirect(route('admin.videos.index'));

        $this->assertDatabaseMissing('admin_videos', ['id' => $video->id]);
        Storage::disk('local')->assertMissing($video->storage_path);
    }

    private function userWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);

        $user = User::factory()->create([
            'activo' => true,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
