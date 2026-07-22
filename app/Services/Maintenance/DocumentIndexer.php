<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use App\Models\WasherKnowledgeDocument;
use Throwable;

class DocumentIndexer
{
    public function __construct(
        private readonly PromptSafetySanitizer $sanitizer,
        private readonly AiProviderInterface $aiProvider,
        private readonly DocumentContentExtractor $contentExtractor
    ) {
    }

    public function index(WasherKnowledgeDocument $document): WasherKnowledgeDocument
    {
        $document->chunks()->delete();

        try {
            $content = $this->contentExtractor->extract($document);

            if ($content === '') {
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

            foreach ($chunks as $index => $chunk) {
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
                    ],
                    'embedding' => $embedding === [] ? null : $embedding,
                ]);
            }

            $document->update([
                'indexing_status' => 'indexed',
                'indexed_at' => now(),
                'last_index_error' => null,
                'extracted_text' => $content,
            ]);
        } catch (Throwable $exception) {
            $document->update([
                'indexing_status' => 'failed',
                'indexed_at' => null,
                'last_index_error' => $this->sanitizer->sanitizeText($exception->getMessage(), 500),
            ]);
        }

        return $document->fresh(['chunks']);
    }
    /**
     * @return array<int, string>
     */
    private function chunkText(string $content, int $chunkSize, int $chunkOverlap): array
    {
        $chunks = [];
        $start = 0;
        $length = mb_strlen($content);

        while ($start < $length) {
            $chunk = mb_substr($content, $start, $chunkSize);
            $chunk = trim($chunk);

            if ($chunk !== '') {
                $chunks[] = $chunk;
            }

            if ($start + $chunkSize >= $length) {
                break;
            }

            $start += max(1, $chunkSize - $chunkOverlap);
        }

        return $chunks;
    }
}
