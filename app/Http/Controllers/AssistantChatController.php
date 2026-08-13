<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssistantMessageRequest;
use App\Models\AssistantMessage;
use App\Services\Maintenance\AiInteractionLogger;
use App\Services\Maintenance\OperationsAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

    public function store(
        StoreAssistantMessageRequest $request,
        OperationsAssistantService $assistant,
        AiInteractionLogger $interactionLogger
    ): JsonResponse {
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

            $interactionLogger->failure($user, 'assistant_chat', $exception, [
                'input_chars' => mb_strlen((string) $payload['message']),
                'metadata' => [
                    'page_context' => $payload['page_context'] ?? [],
                ],
            ]);

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
        $messages = AssistantMessage::query()
            ->where('user_id', $request->user()->id)
            ->get(['id', 'metadata']);

        $this->deleteArtifacts($messages);

        AssistantMessage::query()
            ->whereKey($messages->pluck('id'))
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

    private function trimHistory(int $userId): void
    {
        $maxStored = max(1, (int) config('maintenance_ai.chat.max_stored_messages', 30));
        $idsToKeep = AssistantMessage::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->limit($maxStored)
            ->pluck('id');

        $messagesToDelete = AssistantMessage::query()
            ->where('user_id', $userId)
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
