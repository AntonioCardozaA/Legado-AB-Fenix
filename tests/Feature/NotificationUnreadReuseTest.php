<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationUnreadReuseTest extends TestCase
{
    use RefreshDatabase;

    public function test_poll_counts_unread_items_outside_the_first_ten_without_querying_them_again(): void
    {
        $user = User::factory()->withRole(User::ROLE_TECNICO)->create();

        for ($i = 0; $i < 14; $i++) {
            $this->notification($user, [], $i >= 4, $i);
        }

        $this->notification($user, ['type' => 'admin_analysis_deleted']);
        $this->notification($user, [
            'record_class' => AnalisisLavadora::class,
            'record_id' => 999999999,
        ]);
        $this->notification(User::factory()->withRole(User::ROLE_TECNICO)->create());

        $this->actingAs($user);
        DB::enableQueryLog();
        $response = $this->getJson(route('notifications.unread-count'))->assertOk();
        $queries = $this->notificationSelects();
        DB::disableQueryLog();

        $response->assertJsonPath('count', 4)->assertJsonPath('notifications_count', 14);
        $this->assertCount(10, $response->json('items'));
        $this->assertTrue(collect($response->json('items'))->every(fn ($item) => $item['is_read']));
        $this->assertCount(1, $queries, 'The complete visible collection already contains the unread items.');
    }

    public function test_page_and_bell_count_unread_items_outside_the_current_page(): void
    {
        $user = User::factory()->withRole(User::ROLE_TECNICO)->create();

        for ($i = 0; $i < 25; $i++) {
            $this->notification($user, [], $i >= 4, $i);
        }

        $this->actingAs($user);
        DB::enableQueryLog();
        $response = $this->get(route('notifications.index'))->assertOk();
        $queries = $this->notificationSelects();
        DB::disableQueryLog();

        $this->assertSame(4, $response->viewData('unreadCount'));
        $this->assertSame(25, $response->viewData('notifications')->total());
        $this->assertCount(20, $response->viewData('notifications'));
        $this->assertTrue($response->viewData('notifications')->getCollection()->every(fn ($item) => $item->read_at !== null));
        $this->assertMatchesRegularExpression('/id="notification-badge"[^>]*>\s*4\s*<\/span>/s', $response->getContent());
        $this->assertCount(2, $queries, 'The page and its layout each load a collection, without a separate unread query.');
    }

    public function test_labeler_summary_counts_all_its_unread_items_before_taking_five(): void
    {
        $user = User::factory()->withRole(User::ROLE_TECNICO)->create();

        for ($i = 0; $i < 8; $i++) {
            $this->notification($user, ['tipo_equipo' => 'etiquetadora'], $i >= 2, $i);
        }

        $this->notification($user, ['tipo_equipo' => 'lavadora']);
        $this->notification($user, ['type' => 'admin_analysis_deleted', 'tipo_equipo' => 'etiquetadora']);
        $this->notification($user, [
            'tipo_equipo' => 'etiquetadora',
            'record_class' => AnalisisLavadora::class,
            'record_id' => 999999999,
        ]);

        $this->actingAs($user);
        DB::enableQueryLog();
        $response = $this->get(route('dashboard.global.etiquetadoras'))->assertOk();
        $queries = $this->notificationSelects();
        DB::disableQueryLog();

        $summary = $response->viewData('notificacionesEtiquetadora');
        $this->assertSame(8, $summary['total']);
        $this->assertSame(2, $summary['no_leidas']);
        $this->assertCount(5, $summary['ultimas']);
        $this->assertTrue($summary['ultimas']->every(fn ($item) => $item->read_at !== null));
        $this->assertCount(2, $queries, 'The summary and layout should not query unread notifications again.');
    }

    public function test_empty_feed_keeps_zero_counts(): void
    {
        $user = User::factory()->withRole(User::ROLE_TECNICO)->create();

        $this->actingAs($user)->getJson(route('notifications.unread-count'))
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('notifications_count', 0)
            ->assertJsonPath('items', []);
    }

    private function notification(User $user, array $data = [], bool $read = false, int $position = 0): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'performance-regression',
            'data' => array_merge(['type' => 'component_alert', 'message' => 'Notification fixture'], $data),
            'read_at' => $read ? now() : null,
            'created_at' => now()->subHour()->addSeconds($position),
        ]);
    }

    private function notificationSelects(): array
    {
        return array_values(array_filter(DB::getQueryLog(), function (array $query): bool {
            $sql = str_replace(['"', '`'], '', strtolower($query['query']));

            return str_starts_with($sql, 'select ') && str_contains($sql, 'from notifications');
        }));
    }
}
