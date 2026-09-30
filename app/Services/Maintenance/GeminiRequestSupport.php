<?php

namespace App\Services\Maintenance;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Throwable;

final class GeminiRequestSupport
{
    public const CONNECTION_ERROR_MESSAGE = 'No fue posible conectar temporalmente con el servicio de inteligencia artificial. Intenta nuevamente en unos momentos.';
    public const TIMEOUT_MESSAGE = 'La inteligencia artificial tardó más de lo esperado en responder. Intenta nuevamente.';
    public const HIGH_DEMAND_MESSAGE = 'ABFenix.AI está experimentando alta demanda en este momento. Intenta nuevamente en unos momentos.';
    public const TRANSIENT_HTTP_STATUSES = [408, 429, 500, 502, 503, 504];

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
        return max(1, (int) config(
            'maintenance_ai.providers.gemini.connect_timeout',
            config('maintenance_ai.connect_timeout', 10)
        ));
    }

    public static function totalTimeout(): int
    {
        return max(self::connectionTimeout(), (int) config(
            'maintenance_ai.providers.gemini.request_timeout',
            config('maintenance_ai.timeout', 60)
        ));
    }

    public static function isTimeoutException(Throwable $exception): bool
    {
        if ($exception instanceof RequestException) {
            $status = $exception->response?->status();

            if ($status === 408 || $status === 504) {
                return true;
            }
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'curl error 28')
            || str_contains($message, 'operation timed out')
            || str_contains($message, 'timed out after')
            || str_contains($message, 'connection timed out')
            || str_contains($message, 'request timed out')
            || str_contains($message, 'timeout was reached');
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
        if (self::isTimeoutException($exception)) {
            return self::TIMEOUT_MESSAGE;
        }

        return self::CONNECTION_ERROR_MESSAGE;
    }

    public static function publicFailureMessage(Throwable $exception): ?string
    {
        if (self::isTimeoutException($exception)) {
            return self::TIMEOUT_MESSAGE;
        }

        if (self::isTransientHttpException($exception)) {
            return self::HIGH_DEMAND_MESSAGE;
        }

        if ($exception instanceof ConnectionException) {
            return self::CONNECTION_ERROR_MESSAGE;
        }

        return null;
    }

    public static function isTransientHttpStatus(?int $status): bool
    {
        return $status !== null && in_array($status, self::TRANSIENT_HTTP_STATUSES, true);
    }

    public static function isTransientHttpException(Throwable $exception): bool
    {
        return $exception instanceof RequestException
            && self::isTransientHttpStatus($exception->response?->status());
    }

    public static function responseMessage(?Response $response): ?string
    {
        if ($response === null) {
            return null;
        }

        $json = $response->json();

        if (is_array($json)) {
            $message = data_get($json, 'error.message')
                ?? data_get($json, 'message')
                ?? data_get($json, 'candidates.0.finishReason')
                ?? data_get($json, 'promptFeedback.blockReason');

            if (is_scalar($message) && trim((string) $message) !== '') {
                return self::truncateLogValue((string) $message);
            }
        }

        $body = trim($response->body());

        return $body !== '' ? self::truncateLogValue($body) : null;
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
            'connect_timeout_seconds' => self::connectionTimeout(),
            'request_timeout_seconds' => self::totalTimeout(),
            'timeout_error' => self::isTimeoutException($exception),
            'dns_resolution_error' => self::isDnsResolutionError($exception),
            'exception' => $exception,
        ];
    }

    private static function truncateLogValue(string $value, int $limit = 500): string
    {
        $value = trim($value);

        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit) : $value;
    }
}
