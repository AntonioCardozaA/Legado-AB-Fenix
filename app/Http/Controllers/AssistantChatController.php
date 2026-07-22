<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssistantMessageRequest;
use App\Models\AssistantMessage;
use App\Services\Maintenance\OperationsAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssistantChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $messages = AssistantMessage::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->limit(max(1, (int) config('maintenance_ai.chat.max_stored_messages', 30)))
            ->get()
            ->sortBy('id')
            ->values()
            ->map(fn (AssistantMessage $message): array => $this->serializeMessage($message))
            ->all();

        return response()->json([
            'messages' => $messages,
            'enabled' => (bool) config('maintenance_ai.enabled', false),
        ]);
    }

    public function store(StoreAssistantMessageRequest $request, OperationsAssistantService $assistant): JsonResponse
    {
        $user = $request->user();
        $payload = $request->validated();

        $userMessage = AssistantMessage::create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $payload['message'],
            'metadata' => [
                'page_context' => $payload['page_context'] ?? [],
            ],
        ]);

        $history = AssistantMessage::query()
            ->where('user_id', $user->id)
            ->oldest('id')
            ->get(['role', 'content'])
            ->map(fn (AssistantMessage $message): array => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->all();

        try {
            $reply = $assistant->reply(
                $user,
                (string) $payload['message'],
                $history,
                is_array($payload['page_context'] ?? null) ? $payload['page_context'] : []
            );
        } catch (Throwable $exception) {
            report($exception);

            Log::warning('Assistant chat reply failed.', [
                'user_id' => $user->id,
                'message_id' => $userMessage->id,
                'error' => $exception->getMessage(),
            ]);

            $reply = [
                'content' => 'No pude responder en este momento. Intenta de nuevo en unos segundos o formula una pregunta mas especifica.',
                'metadata' => [
                    'fallback' => true,
                    'error' => true,
                ],
            ];
        }

        $assistantMessage = AssistantMessage::create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => (string) $reply['content'],
            'metadata' => is_array($reply['metadata'] ?? null) ? $reply['metadata'] : [],
        ]);

        $this->trimHistory($user->id);

        return response()->json([
            'user_message' => $this->serializeMessage($userMessage),
            'message' => $this->serializeMessage($assistantMessage),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        AssistantMessage::query()
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(AssistantMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'metadata' => $message->metadata ?? [],
            'created_at' => $message->created_at?->toIso8601String(),
            'created_at_human' => $message->created_at?->diffForHumans(),
        ];
    }

    private function trimHistory(int $userId): void
    {
        $maxStored = max(1, (int) config('maintenance_ai.chat.max_stored_messages', 30));
        $idsToKeep = AssistantMessage::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->limit($maxStored)
            ->pluck('id');

        AssistantMessage::query()
            ->where('user_id', $userId)
            ->when($idsToKeep->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $idsToKeep))
            ->delete();
    }
}
