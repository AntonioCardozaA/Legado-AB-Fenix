<?php

namespace App\Services\Maintenance;

use Throwable;

final class GeminiRequestSupport
{
    public const CONNECTION_ERROR_MESSAGE = 'No fue posible conectar temporalmente con el servicio de inteligencia artificial. Intenta nuevamente en unos momentos.';

    /**
     * @param  array<string, mixed>  $config
     */
    public static function endpoint(array $config, string $path): string
    {
        return self::baseUrl($config) . '/' . ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function baseUrl(array $config): string
    {
        $baseUrl = trim((string) ($config['base_url'] ?? ''));

        if ($baseUrl === '') {
            throw new \RuntimeException('Gemini base URL is not configured.');
        }

        if (preg_match('/\s/', $baseUrl) === 1) {
            throw new \RuntimeException('Gemini base URL contains invalid whitespace.');
        }

        $baseUrl = rtrim($baseUrl, '/');
        $baseUrl = preg_replace('#/models(?:/.*)?$#', '', $baseUrl) ?: $baseUrl;
        $baseUrl = preg_replace('#/interactions$#', '', $baseUrl) ?: $baseUrl;

        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Gemini base URL is invalid.');
        }

        $parts = parse_url($baseUrl);

        if (!is_array($parts)
            || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            || trim((string) ($parts['host'] ?? '')) === ''
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            throw new \RuntimeException('Gemini base URL is malformed.');
        }

        return $baseUrl;
    }

    public static function normalizeModelName(string $model): string
    {
        $model = preg_replace('#^models/#', '', trim($model)) ?: trim($model);

        if ($model === '') {
            throw new \RuntimeException('Gemini model is not configured.');
        }

        if (preg_match('/\s/', $model) === 1 || str_contains($model, ':')) {
            throw new \RuntimeException('Gemini model name is malformed.');
        }

        return $model;
    }

    public static function connectionTimeout(): int
    {
        return max(1, (int) config('maintenance_ai.connect_timeout', 10));
    }

    public static function totalTimeout(): int
    {
        return max(self::connectionTimeout(), (int) config('maintenance_ai.timeout', 60));
    }

    public static function isDnsResolutionError(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'curl error 6')
            || str_contains($message, 'could not resolve host')
            || str_contains($message, 'getaddrinfo')
            || str_contains($message, 'name or service not known')
            || str_contains($message, 'temporary failure in name resolution');
    }

    public static function publicConnectionMessage(Throwable $exception): string
    {
        return self::CONNECTION_ERROR_MESSAGE;
    }

    /**
     * @return array<string, mixed>
     */
    public static function logContext(
        string $flow,
        string $model,
        string $endpoint,
        Throwable $exception,
        float $startedAt
    ): array {
        return [
            'flow' => $flow,
            'date' => now()->toIso8601String(),
            'provider' => 'gemini',
            'model' => $model,
            'endpoint' => $endpoint,
            'exception_type' => get_class($exception),
            'technical_message' => $exception->getMessage(),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'dns_resolution_error' => self::isDnsResolutionError($exception),
        ];
    }
}
