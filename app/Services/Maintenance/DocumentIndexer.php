<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class DocumentIndexer
{
    public function __construct(
        private readonly PromptSafetySanitizer $sanitizer,
        private readonly AiProviderInterface $aiProvider,
        private readonly DocumentContentExtractor $contentExtractor
    ) {
    }

    public function index(Model $document): Model
    {
        $auditStartedAt = now();
        $document->chunks()->delete();

        try {
            $content = $this->contentExtractor->extract($document);

            if ($content === '') {
                $this->appendIndexAudit($document, [
                    'status' => 'pending_extraction',
                    'started_at' => $auditStartedAt->toIso8601String(),
                    'finished_at' => now()->toIso8601String(),
                    'content_chars' => 0,
                    'chunk_count' => 0,
                    'embedding_count' => 0,
                ]);

                $document->update([
                    'indexing_status' => 'pending_extraction',
                    'indexed_at' => null,
                    'last_index_error' => null,
                ]);

                return $document->fresh(['chunks']);
            }

            $chunkSize = max(300, (int) config('maintenance_ai.knowledge.chunk_size', 1200));
            $chunkOverlap = max(0, min($chunkSize - 50, (int) config('maintenance_ai.knowledge.chunk_overlap', 200)));
            $chunks = $this->chunkText($content, $chunkSize, $chunkOverlap);
            $embeddingCount = 0;

            foreach ($chunks as $index => $chunkData) {
                $chunk = $chunkData['content'];
                $embedding = [];

                if ((bool) config('maintenance_ai.enabled', false)) {
                    try {
                        $embedding = $this->aiProvider->createEmbedding($chunk);
                    } catch (Throwable) {
                        $embedding = [];
                    }
                }

                $document->chunks()->create([
                    'chunk_index' => $index + 1,
                    'content' => $chunk,
                    'searchable_text' => mb_strtolower($chunk),
                    'token_count' => str_word_count($chunk),
                    'metadata' => [
                        'section' => $document->title,
                        'char_start' => $chunkData['char_start'],
                        'char_end' => $chunkData['char_end'],
                        'document_version' => $document->version,
                        'embedding_model' => $embedding === [] ? null : $this->embeddingModelName(),
                    ],
                    'embedding' => $embedding === [] ? null : $embedding,
                ]);

                if ($embedding !== []) {
                    $embeddingCount++;
                }
            }

            $this->appendIndexAudit($document, [
                'status' => 'indexed',
                'started_at' => $auditStartedAt->toIso8601String(),
                'finished_at' => now()->toIso8601String(),
                'content_chars' => mb_strlen($content),
                'chunk_count' => count($chunks),
                'embedding_count' => $embeddingCount,
                'embedding_model' => $embeddingCount > 0 ? $this->embeddingModelName() : null,
                'chunk_size' => $chunkSize,
                'chunk_overlap' => $chunkOverlap,
                'chunking_strategy' => 'semantic_paragraph_sentence',
                'lifecycle_status' => $document->lifecycle_status ?? null,
                'version' => $document->version ?? null,
            ]);

            $document->update([
                'indexing_status' => 'indexed',
                'indexed_at' => now(),
                'last_index_error' => null,
                'extracted_text' => $content,
            ]);
        } catch (Throwable $exception) {
            $this->appendIndexAudit($document, [
                'status' => 'failed',
                'started_at' => $auditStartedAt->toIso8601String(),
                'finished_at' => now()->toIso8601String(),
                'error' => $this->sanitizer->sanitizeText($exception->getMessage(), 500),
            ]);

            $document->update([
                'indexing_status' => 'failed',
                'indexed_at' => null,
                'last_index_error' => $this->sanitizer->sanitizeText($exception->getMessage(), 500),
            ]);
        }

        return $document->fresh(['chunks']);
    }
    /**
     * @return array<int, array{content: string, char_start: int, char_end: int}>
     */
    private function chunkText(string $content, int $chunkSize, int $chunkOverlap): array
    {
        $chunks = [];
        $length = mb_strlen($content);
        $segments = $this->semanticSegments($content);
        $buffer = '';
        $bufferStart = null;

        foreach ($segments as $segment) {
            $segmentText = $segment['content'];

            if (mb_strlen($segmentText) > $chunkSize) {
                if (trim($buffer) !== '') {
                    $chunks[] = $this->makeChunk($buffer, (int) $bufferStart, $length);
                    $buffer = '';
                    $bufferStart = null;
                }

                foreach ($this->fixedSizeChunks($segmentText, $segment['char_start'], $chunkSize, $chunkOverlap, $length) as $chunk) {
                    $chunks[] = $chunk;
                }

                continue;
            }

            $candidate = trim($buffer === '' ? $segmentText : $buffer."\n\n".$segmentText);

            if ($candidate !== '' && mb_strlen($candidate) > $chunkSize && $buffer !== '') {
                $chunks[] = $this->makeChunk($buffer, (int) $bufferStart, $length);
                $overlap = $chunkOverlap > 0 ? mb_substr($buffer, max(0, mb_strlen($buffer) - $chunkOverlap)) : '';
                $buffer = trim($overlap."\n\n".$segmentText);
                $bufferStart = max(0, $segment['char_start'] - mb_strlen($overlap));

                continue;
            }

            $buffer = $candidate;
            $bufferStart ??= $segment['char_start'];
        }

        if (trim($buffer) !== '') {
            $chunks[] = $this->makeChunk($buffer, (int) $bufferStart, $length);
        }

        return $chunks;
    }

    /**
     * @return array<int, array{content: string, char_start: int}>
     */
    private function semanticSegments(string $content): array
    {
        $segments = [];
        $offset = 0;
        $parts = preg_split('/(\R{2,}|(?<=[.!?;:])\s+)/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$content];

        foreach ($parts as $part) {
            $partLength = mb_strlen($part);
            $trimmed = trim($part);

            if ($trimmed !== '' && ! preg_match('/^\s+$/u', $part)) {
                $segments[] = [
                    'content' => $trimmed,
                    'char_start' => $offset + max(0, mb_strpos($part, $trimmed) ?: 0),
                ];
            }

            $offset += $partLength;
        }

        return $segments !== [] ? $segments : [['content' => trim($content), 'char_start' => 0]];
    }

    /**
     * @return array<int, array{content: string, char_start: int, char_end: int}>
     */
    private function fixedSizeChunks(string $content, int $baseStart, int $chunkSize, int $chunkOverlap, int $totalLength): array
    {
        $chunks = [];
        $start = 0;
        $length = mb_strlen($content);

        while ($start < $length) {
            $chunk = mb_substr($content, $start, $chunkSize);
            $chunk = trim($chunk);

            if ($chunk !== '') {
                $chunks[] = [
                    'content' => $chunk,
                    'char_start' => $baseStart + $start,
                    'char_end' => min($totalLength, $baseStart + $start + $chunkSize),
                ];
            }

            if ($start + $chunkSize >= $length) {
                break;
            }

            $start += max(1, $chunkSize - $chunkOverlap);
        }

        return $chunks;
    }

    /**
     * @return array{content: string, char_start: int, char_end: int}
     */
    private function makeChunk(string $content, int $charStart, int $totalLength): array
    {
        $content = trim($content);

        return [
            'content' => $content,
            'char_start' => $charStart,
            'char_end' => min($totalLength, $charStart + mb_strlen($content)),
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function appendIndexAudit(Model $document, array $entry): void
    {
        $metadata = is_array($document->metadata ?? null) ? $document->metadata : [];
        $history = array_values((array) ($metadata['reindex_history'] ?? []));
        $history[] = $entry;

        $metadata['last_index'] = $entry;
        $metadata['reindex_history'] = array_slice($history, -10);
        $document->metadata = $metadata;
    }

    private function embeddingModelName(): ?string
    {
        $provider = trim((string) config('maintenance_ai.provider', ''));

        return data_get(config('maintenance_ai'), 'providers.' . $provider . '.embedding_model')
            ?: data_get(config('maintenance_ai'), 'providers.openai.embedding_model')
            ?: data_get(config('maintenance_ai'), 'providers.gemini.embedding_model');
    }
}
