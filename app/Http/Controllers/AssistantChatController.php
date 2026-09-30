<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssistantMessageRequest;
use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Services\Maintenance\AiInteractionLogger;
use App\Services\Maintenance\GeminiRequestSupport;
use App\Services\Maintenance\OperationsAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class AssistantChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $requestedConversationId = $request->query('conversation_id');

        $conversations = AssistantConversation::query()
            ->where('user_id', $user->id)
            ->latest('last_message_at')
            ->latest('id')
            ->limit(50)
            ->get();

        $activeConversation = null;

        if ($requestedConversationId !== null && $requestedConversationId !== '') {
            $activeConversation = AssistantConversation::query()
                ->where('user_id', $user->id)
                ->whereKey((int) $requestedConversationId)
                ->firstOrFail();
        } else {
            $activeConversation = $conversations->first();
        }

        $messages = $activeConversation
            ? $this->conversationMessages($activeConversation)->all()
            : [];

        return response()->json([
            'conversations' => $conversations
                ->map(fn (AssistantConversation $conversation): array => $this->serializeConversation($conversation))
                ->all(),
            'active_conversation' => $activeConversation
                ? $this->serializeConversation($activeConversation)
                : null,
            'active_conversation_id' => $activeConversation?->id,
            'messages' => $messages,
            'enabled' => (bool) config('maintenance_ai.enabled', false),
        ]);
    }

    public function show(Request $request, AssistantConversation $conversation): JsonResponse
    {
        if ((int) $conversation->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        return response()->json([
            'conversation' => $this->serializeConversation($conversation),
            'messages' => $this->conversationMessages($conversation)->all(),
            'enabled' => (bool) config('maintenance_ai.enabled', false),
        ]);
    }

    public function destroyConversation(Request $request, AssistantConversation $conversation): JsonResponse
    {
        if ((int) $conversation->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        $messages = AssistantMessage::query()
            ->where('user_id', $request->user()->id)
            ->where('conversation_id', $conversation->id)
            ->get(['id', 'metadata']);

        $this->deleteArtifacts($messages);
        $conversation->delete();

        $activeConversation = AssistantConversation::query()
            ->where('user_id', $request->user()->id)
            ->latest('last_message_at')
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'conversations' => $this->recentConversations((int) $request->user()->id),
            'active_conversation' => $activeConversation
                ? $this->serializeConversation($activeConversation)
                : null,
            'active_conversation_id' => $activeConversation?->id,
            'messages' => $activeConversation
                ? $this->conversationMessages($activeConversation)->all()
                : [],
        ]);
    }

    public function store(
        StoreAssistantMessageRequest $request,
        OperationsAssistantService $assistant,
        AiInteractionLogger $interactionLogger
    ): JsonResponse {
        $user = $request->user();
        $payload = $request->validated();
        $conversation = $this->resolveConversation($user->id, $payload);

        $userMessage = AssistantMessage::create([
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $payload['message'],
            'metadata' => [
                'page_context' => $payload['page_context'] ?? [],
            ],
        ]);

        $history = AssistantMessage::query()
            ->where('user_id', $user->id)
            ->where('conversation_id', $conversation->id)
            ->whereKeyNot($userMessage->id)
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
            $publicMessage = $exception instanceof ConnectionException
                ? GeminiRequestSupport::publicConnectionMessage($exception)
                : 'No pude responder en este momento. Intenta de nuevo en unos segundos o formula una pregunta mas especifica.';

            $interactionLogger->failure($user, 'assistant_chat', $publicMessage, [
                'input_chars' => mb_strlen((string) $payload['message']),
                'metadata' => [
                    'page_context' => $payload['page_context'] ?? [],
                    'exception_type' => get_class($exception),
                    'connection_error' => $exception instanceof ConnectionException,
                    'dns_resolution_error' => $exception instanceof ConnectionException
                        ? GeminiRequestSupport::isDnsResolutionError($exception)
                        : false,
                ],
            ]);

            Log::warning('Assistant chat reply failed.', [
                'user_id' => $user->id,
                'message_id' => $userMessage->id,
                'date' => now()->toIso8601String(),
                'exception_type' => get_class($exception),
                'error' => $exception->getMessage(),
            ]);

            $reply = [
                'content' => $publicMessage,
                'metadata' => [
                    'fallback' => true,
                    'error' => true,
                ],
            ];
        }

        $assistantMessage = AssistantMessage::create([
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => (string) $reply['content'],
            'metadata' => is_array($reply['metadata'] ?? null) ? $reply['metadata'] : [],
        ]);

        $conversation->forceFill([
            'last_message_at' => $assistantMessage->created_at ?? now(),
        ])->save();

        $this->trimHistory($conversation->id);

        return response()->json([
            'conversation' => $this->serializeConversation($conversation->refresh()),
            'conversations' => $this->recentConversations($user->id),
            'user_message' => $this->serializeMessage($userMessage),
            'message' => $this->serializeMessage($assistantMessage),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $messages = AssistantMessage::query()
            ->where('user_id', $request->user()->id)
            ->get(['id', 'metadata']);

        $this->deleteArtifacts($messages);

        AssistantConversation::query()
            ->where('user_id', $request->user()->id)
            ->delete();

        AssistantMessage::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('conversation_id')
            ->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function artifact(Request $request, AssistantMessage $message, int $artifact): BinaryFileResponse
    {
        if ((int) $message->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        $artifacts = is_array($message->metadata)
            ? (array) ($message->metadata['artifacts'] ?? [])
            : [];
        $item = $artifacts[$artifact] ?? null;

        if (! is_array($item)) {
            abort(404);
        }

        $disk = (string) ($item['disk'] ?? 'local');
        $path = (string) ($item['path'] ?? '');

        if ($disk !== 'local' || $path === '' || ! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $fileName = (string) ($item['file_name'] ?? basename($path));
        $mimeType = (string) ($item['mime_type'] ?? 'application/octet-stream');
        $absolutePath = Storage::disk($disk)->path($path);

        if ($request->boolean('download') || ($item['kind'] ?? null) === 'excel') {
            return response()->download($absolutePath, $fileName, [
                'Content-Type' => $mimeType,
            ]);
        }

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.addslashes($fileName).'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(AssistantMessage $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'role' => $message->role,
            'content' => $message->content,
            'metadata' => $this->serializeMetadata($message),
            'created_at' => $message->created_at?->toIso8601String(),
            'created_at_human' => $message->created_at?->diffForHumans(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMetadata(AssistantMessage $message): array
    {
        $metadata = is_array($message->metadata) ? $message->metadata : [];
        $artifacts = is_array($metadata['artifacts'] ?? null) ? $metadata['artifacts'] : [];

        if ($artifacts === []) {
            return $metadata;
        }

        $metadata['artifacts'] = collect($artifacts)
            ->values()
            ->map(function ($artifact, int $index) use ($message): array {
                $artifact = is_array($artifact) ? $artifact : [];

                return array_filter([
                    'kind' => $artifact['kind'] ?? null,
                    'label' => $artifact['label'] ?? null,
                    'file_name' => $artifact['file_name'] ?? null,
                    'mime_type' => $artifact['mime_type'] ?? null,
                    'size' => $artifact['size'] ?? null,
                    'url' => route('assistant-chat.artifact', [
                        'message' => $message->id,
                        'artifact' => $index,
                    ], false),
                ], static fn ($value): bool => $value !== null && $value !== '');
            })
            ->all();

        return $metadata;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveConversation(int $userId, array $payload): AssistantConversation
    {
        $conversationId = $payload['conversation_id'] ?? null;

        if ($conversationId) {
            return AssistantConversation::query()
                ->where('user_id', $userId)
                ->whereKey((int) $conversationId)
                ->firstOrFail();
        }

        return AssistantConversation::create([
            'user_id' => $userId,
            'title' => $this->titleFrom((string) $payload['message']),
            'last_message_at' => now(),
        ]);
    }

    private function titleFrom(string $content): string
    {
        $title = trim(preg_replace('/\s+/', ' ', strip_tags($content)) ?: '');

        return Str::limit($title !== '' ? $title : 'Chat operativo', 80, '');
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function conversationMessages(AssistantConversation $conversation): \Illuminate\Support\Collection
    {
        return AssistantMessage::query()
            ->where('user_id', $conversation->user_id)
            ->where('conversation_id', $conversation->id)
            ->latest('id')
            ->limit(max(1, (int) config('maintenance_ai.chat.max_stored_messages', 30)))
            ->get()
            ->sortBy('id')
            ->values()
            ->map(fn (AssistantMessage $message): array => $this->serializeMessage($message));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeConversation(AssistantConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'last_message_at' => $conversation->last_message_at?->toIso8601String()
                ?? $conversation->updated_at?->toIso8601String(),
            'last_message_at_human' => $conversation->last_message_at?->diffForHumans()
                ?? $conversation->updated_at?->diffForHumans(),
            'created_at' => $conversation->created_at?->toIso8601String(),
            'created_at_label' => $conversation->created_at?->format('d/m/Y H:i'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentConversations(int $userId): array
    {
        return AssistantConversation::query()
            ->where('user_id', $userId)
            ->latest('last_message_at')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (AssistantConversation $conversation): array => $this->serializeConversation($conversation))
            ->all();
    }

    private function trimHistory(int $conversationId): void
    {
        $maxStored = max(1, (int) config('maintenance_ai.chat.max_stored_messages', 30));
        $idsToKeep = AssistantMessage::query()
            ->where('conversation_id', $conversationId)
            ->latest('id')
            ->limit($maxStored)
            ->pluck('id');

        $messagesToDelete = AssistantMessage::query()
            ->where('conversation_id', $conversationId)
            ->when($idsToKeep->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $idsToKeep))
            ->get(['id', 'metadata']);

        $this->deleteArtifacts($messagesToDelete);

        AssistantMessage::query()
            ->whereKey($messagesToDelete->pluck('id'))
            ->delete();
    }

    /**
     * @param  iterable<int, AssistantMessage>  $messages
     */
    private function deleteArtifacts(iterable $messages): void
    {
        foreach ($messages as $message) {
            $artifacts = is_array($message->metadata)
                ? (array) ($message->metadata['artifacts'] ?? [])
                : [];

            foreach ($artifacts as $artifact) {
                if (! is_array($artifact)) {
                    continue;
                }

                $disk = (string) ($artifact['disk'] ?? 'local');
                $path = (string) ($artifact['path'] ?? '');

                if ($disk === 'local' && $path !== '' && Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            }
        }
    }
}
