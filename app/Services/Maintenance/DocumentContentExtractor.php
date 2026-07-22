<?php

namespace App\Services\Maintenance;

use App\Contracts\AiProviderInterface;
use App\Models\WasherKnowledgeDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

class DocumentContentExtractor
{
    /**
     * @var array<int, string>
     */
    private const TEXT_LIKE_EXTENSIONS = ['txt', 'md', 'json', 'csv', 'html', 'htm', 'xml', 'log'];

    public function __construct(
        private readonly PromptSafetySanitizer $sanitizer,
        private readonly Parser $pdfParser,
        private readonly AiProviderInterface $aiProvider
    ) {
    }

    public function extract(WasherKnowledgeDocument $document): string
    {
        if (filled($document->extracted_text)) {
            return $this->sanitizer->sanitizeText($document->extracted_text, 30000);
        }

        if (!$document->storage_path || !$document->storage_disk) {
            return '';
        }

        $extension = strtolower(pathinfo((string) $document->original_filename, PATHINFO_EXTENSION));

        if (in_array($extension, self::TEXT_LIKE_EXTENSIONS, true)) {
            return $this->extractTextLikeContent($document, $extension);
        }

        if ($extension === 'pdf') {
            return $this->extractPdfContent($document);
        }

        return '';
    }

    private function extractTextLikeContent(WasherKnowledgeDocument $document, string $extension): string
    {
        $raw = Storage::disk($document->storage_disk)->get($document->storage_path);

        if (in_array($extension, ['html', 'htm', 'xml'], true)) {
            $raw = strip_tags($raw);
        }

        return $this->sanitizer->sanitizeText($raw, 30000);
    }

    private function extractPdfContent(WasherKnowledgeDocument $document): string
    {
        $raw = Storage::disk($document->storage_disk)->get($document->storage_path);
        $text = trim($this->pdfParser->parseContent($raw)->getText());

        if ($text !== '') {
            return $this->sanitizer->sanitizeText($text, 30000);
        }

        if (!$this->shouldUsePdfOcrFallback()) {
            return '';
        }

        try {
            $ocrText = trim($this->aiProvider->extractDocumentText([
                'filename' => $document->original_filename ?: 'document.pdf',
                'mime_type' => $document->mime_type ?: 'application/pdf',
                'base64_data' => base64_encode($raw),
                'detail' => (string) config('maintenance_ai.knowledge.pdf_ocr_detail', 'high'),
            ]));

            return $this->sanitizer->sanitizeText($ocrText, 30000);
        } catch (Throwable $exception) {
            Log::warning('PDF OCR fallback failed for washer knowledge document.', [
                'document_id' => $document->id,
                'filename' => $document->original_filename,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'PDF OCR fallback failed: ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    private function shouldUsePdfOcrFallback(): bool
    {
        if (!(bool) config('maintenance_ai.enabled', false)) {
            return false;
        }

        return (bool) config('maintenance_ai.knowledge.pdf_ocr_enabled', true);
    }
}
