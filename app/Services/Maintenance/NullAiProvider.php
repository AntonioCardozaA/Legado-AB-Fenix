<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use RuntimeException;

class NullAiProvider implements AiProviderInterface
{
    public function generateStructuredActionPlan(array $payload): array
    {
        throw new RuntimeException('AI provider is disabled or not configured.');
    }

    public function createEmbedding(string $content): array
    {
        return [];
    }

    public function extractDocumentText(array $payload): string
    {
        return '';
    }
}
