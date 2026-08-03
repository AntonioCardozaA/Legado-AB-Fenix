<?php

namespace Tests\Feature;

use App\Contracts\AiProviderInterface;
use App\Models\AiInteractionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiInteractionLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_ai_calls_are_logged_with_usage_metadata(): void
    {
        config([
            'maintenance_ai.enabled' => true,
        ]);

        $this->app->instance(AiProviderInterface::class, new class implements AiProviderInterface
        {
            public function generateStructuredActionPlan(array $payload): array
            {
                return [
                    'data' => [
                        'answer' => 'Respuesta operativa registrada.',
                        'key_points' => [],
                        'next_steps' => [],
                        'sources' => [],
                        'confidence' => 0.82,
                    ],
                    'raw' => [],
                    'meta' => [
                        'provider' => 'fake',
                        'model' => 'logging-test-model',
                        'response_time_ms' => 123,
                        'usage' => [
                            'input_tokens' => 40,
                            'output_tokens' => 12,
                            'total_tokens' => 52,
                        ],
                    ],
                ];
            }

            public function createEmbedding(string $content): array
            {
                return [];
            }

            public function extractDocumentText(array $payload): string
            {
                return '';
            }
        });

        $user = $this->authenticatedUser();

        $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Dame un resumen del modulo de lavadora.',
            'page_context' => [
                'module' => User::MODULE_LAVADORA,
                'current_path' => '/lavadora/dashboard',
            ],
        ])->assertOk();

        $log = AiInteractionLog::query()->firstOrFail();

        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('assistant_chat', $log->action_type);
        $this->assertSame('success', $log->status);
        $this->assertSame('fake', $log->provider);
        $this->assertSame('logging-test-model', $log->model);
        $this->assertSame(123, $log->response_time_ms);
        $this->assertSame(40, $log->prompt_tokens);
        $this->assertSame(12, $log->completion_tokens);
        $this->assertSame(52, $log->total_tokens);
    }

    private function authenticatedUser(): User
    {
        Role::firstOrCreate([
            'name' => User::ROLE_TECNICO,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'activo' => true,
        ]);

        $user->assignRole(User::ROLE_TECNICO);

        return $user;
    }
}
