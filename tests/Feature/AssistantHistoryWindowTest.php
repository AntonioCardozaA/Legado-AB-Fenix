<?php

namespace Tests\Feature;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\User;
use App\Services\Maintenance\OperationsAssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantHistoryWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_uses_the_current_window_in_order_without_removing_stored_messages(): void
    {
        config(['maintenance_ai.chat.history_window' => 3, 'maintenance_ai.chat.max_stored_messages' => 1000]);
        $user = User::factory()->withRole(User::ROLE_TECNICO)->create();
        $other = User::factory()->withRole(User::ROLE_TECNICO)->create();
        $conversation = AssistantConversation::create(['user_id' => $user->id, 'title' => 'Window fixture']);
        $otherConversation = AssistantConversation::create(['user_id' => $other->id, 'title' => 'Other conversation']);
        $expected = [];
        for ($i = 0; $i < 20; $i++) {
            $entry = ['role' => $i % 2 ? 'assistant' : 'user', 'content' => 'Message ' . $i];
            AssistantMessage::create($entry + ['user_id' => $user->id, 'conversation_id' => $conversation->id]);
            $expected[] = $entry;
        }
        AssistantMessage::create(['user_id' => $other->id, 'conversation_id' => $otherConversation->id, 'role' => 'user', 'content' => 'Private other history']);
        $captured = null;
        $assistant = \Mockery::mock(OperationsAssistantService::class);
        $assistant->shouldReceive('reply')->once()->andReturnUsing(function ($actor, $question, $history) use (&$captured) {
            $captured = $history;
            return ['content' => 'Window response', 'metadata' => []];
        });
        $this->app->instance(OperationsAssistantService::class, $assistant);
        $this->actingAs($user)->postJson(route('assistant-chat.store'), [
            'message' => 'Current question', 'conversation_id' => $conversation->id,
        ])->assertOk()->assertJsonPath('message.content', 'Window response');
        $this->assertSame(array_slice($expected, -3), $captured);
        $this->assertSame(22, $conversation->messages()->count());
        $this->assertSame(1, $otherConversation->messages()->count());
    }
}
