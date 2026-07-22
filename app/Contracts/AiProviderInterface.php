<?php

namespace App\Contracts;

interface AiProviderInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     data: array<string, mixed>,
     *     raw: array<string, mixed>,
     *     meta: array<string, mixed>
     * }
     */
    public function generateStructuredActionPlan(array $payload): array;

    /**
     * @return array<int, float>
     */
    public function createEmbedding(string $content): array;

    /**
     * @param  array{
     *     filename: string,
     *     mime_type: string,
     *     base64_data: string,
     *     prompt?: string,
     *     model?: string,
     *     detail?: string
     * }  $payload
     */
    public function extractDocumentText(array $payload): string;
}
