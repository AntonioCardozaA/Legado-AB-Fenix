<?php

namespace App\Services\Maintenance;

use App\Models\AiInteractionLog;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AiInteractionLogger
{
    /**
     * @param  array<string, mixed>  $response
     * @param  array<string, mixed>  $attributes
     */
    public function success(?User $user, string $actionType, array $response, array $attributes = []): void
    {
        $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];
        $usage = is_array($meta['usage'] ?? null) ? $meta['usage'] : [];

        $this->write(array_merge($attributes, [
            'user_id' => $user?->id,
            'action_type' => $actionType,
            'status' => 'success',
            'provider' => $meta['provider'] ?? null,
            'model' => $meta['model'] ?? null,
            'response_time_ms' => $meta['response_time_ms'] ?? null,
            'usage' => $usage,
            'prompt_tokens' => $this->firstNumeric($usage, [
                'input_tokens',
                'prompt_tokens',
                'promptTokenCount',
            ]),
            'completion_tokens' => $this->firstNumeric($usage, [
                'output_tokens',
                'completion_tokens',
                'candidatesTokenCount',
            ]),
            'total_tokens' => $this->firstNumeric($usage, [
                'total_tokens',
                'totalTokenCount',
            ]),
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function fallback(?User $user, string $actionType, array $attributes = []): void
    {
        $this->write(array_merge($attributes, [
            'user_id' => $user?->id,
            'action_type' => $actionType,
            'status' => 'fallback',
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function failure(?User $user, string $actionType, Throwable|string $error, array $attributes = []): void
    {
        $this->write(array_merge($attributes, [
            'user_id' => $user?->id,
            'action_type' => $actionType,
            'status' => 'failed',
            'error_message' => $error instanceof Throwable ? $error->getMessage() : $error,
        ]));
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function write(array $values): void
    {
        try {
            AiInteractionLog::create([
                'user_id' => $values['user_id'] ?? null,
                'action_type' => $this->limitString($values['action_type'] ?? 'unknown', 80),
                'source_type' => $this->nullableString($values['source_type'] ?? null, 80),
                'source_id' => isset($values['source_id']) && is_numeric($values['source_id']) ? (int) $values['source_id'] : null,
                'provider' => $this->nullableString($values['provider'] ?? null, 50),
                'model' => $this->nullableString($values['model'] ?? null, 120),
                'status' => $this->limitString($values['status'] ?? 'success', 30),
                'prompt_version' => $this->nullableString($values['prompt_version'] ?? null, 80),
                'response_time_ms' => $this->nullableInteger($values['response_time_ms'] ?? null),
                'input_chars' => max(0, (int) ($values['input_chars'] ?? 0)),
                'output_chars' => max(0, (int) ($values['output_chars'] ?? 0)),
                'prompt_tokens' => $this->nullableInteger($values['prompt_tokens'] ?? null),
                'completion_tokens' => $this->nullableInteger($values['completion_tokens'] ?? null),
                'total_tokens' => $this->nullableInteger($values['total_tokens'] ?? null),
                'usage' => $this->arrayOrNull($values['usage'] ?? null),
                'metadata' => $this->arrayOrNull($values['metadata'] ?? null),
                'error_message' => $this->nullableString($values['error_message'] ?? null, 500),
            ]);
        } catch (Throwable $exception) {
            Log::warning('No fue posible registrar la interaccion IA.', [
                'action_type' => $values['action_type'] ?? 'unknown',
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $usage
     * @param  array<int, string>  $keys
     */
    private function firstNumeric(array $usage, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = Arr::get($usage, $key);

            if (is_numeric($value)) {
                return max(0, (int) $value);
            }
        }

        return null;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) ? max(0, (int) $value) : null;
    }

    private function nullableString(mixed $value, int $limit): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $this->limitString($value, $limit) : null;
    }

    private function limitString(mixed $value, int $limit): string
    {
        return Str::limit(trim((string) $value), $limit, '');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function arrayOrNull(mixed $value): ?array
    {
        return is_array($value) && $value !== [] ? $value : null;
    }
}
