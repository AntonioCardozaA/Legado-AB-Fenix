<?php

namespace App\Services\Maintenance;

use Illuminate\Support\Str;

class PromptSafetySanitizer
{
    public function sanitizeText(?string $value, int $maxLength = 4000): string
    {
        $sanitized = strip_tags((string) $value);
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $sanitized) ?? '';
        $sanitized = preg_replace('/\s+/u', ' ', $sanitized) ?? '';
        $sanitized = trim($sanitized);

        return Str::limit($sanitized, $maxLength, '...');
    }

    public function sanitizeCollection(array $items, int $maxPerItem = 1200): array
    {
        return array_values(array_filter(array_map(
            fn ($item) => $this->sanitizeText(is_scalar($item) ? (string) $item : json_encode($item), $maxPerItem),
            $items
        )));
    }
}
