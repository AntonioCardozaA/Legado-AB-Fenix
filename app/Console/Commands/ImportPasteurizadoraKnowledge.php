<?php

namespace App\Console\Commands;

use App\Models\AnalisisPasteurizadora;
use App\Models\PasteurizadoraKnowledgeDocument;
use App\Services\Maintenance\DocumentIndexer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportPasteurizadoraKnowledge extends Command
{
    protected $signature = 'pasteurizadora:knowledge:import-internal
                            {--without-embeddings : Indexar texto sin llamar al proveedor IA}';

    protected $description = 'Importa documentación interna versionada de pasteurizadoras sin duplicarla ni atribuirla al fabricante.';

    public function handle(DocumentIndexer $indexer): int
    {
        $enabled = config('maintenance_ai.enabled');
        if ($this->option('without-embeddings')) {
            config(['maintenance_ai.enabled' => false]);
        }

        try {
            foreach ([
                ['PASTEURIZADORA_BASE_CONOCIMIENTO.md', 'Procedimiento interno de operación de pasteurizadoras y central hidráulica', null],
                ['PASTEURIZADORA_NUMERACION.md', 'Numeración interna de componentes de pasteurizadoras', AnalisisPasteurizadora::AREA_MECANICA],
            ] as [$filename, $title, $area]) {
                $source = 'docs/'.$filename;
                $text = file_get_contents(base_path($source));
                if ($text === false) {
                    $this->error('No se pudo leer: '.$source);

                    return self::FAILURE;
                }
                if ($filename === 'PASTEURIZADORA_NUMERACION.md') {
                    $text = explode('## Archivos', $text, 2)[0];
                }
                $hash = hash('sha256', $text);
                $path = 'pasteurizadora-knowledge/internal/'.$filename;
                $document = PasteurizadoraKnowledgeDocument::firstOrNew(['storage_disk' => 'local', 'storage_path' => $path]);
                if ($document->exists && $document->indexing_status === 'indexed'
                    && data_get($document->metadata, 'source_sha256') === $hash
                    && $document->chunks()->exists()
                    && ($this->option('without-embeddings') || ! $document->chunks()->whereNull('embedding')->exists())) {
                    $this->line('Sin cambios: '.$title.' | chunks: '.$document->chunks()->count());

                    continue;
                }
                Storage::disk('local')->put($path, $text);
                $document->fill([
                    'title' => $title,
                    'area' => $area,
                    'document_type' => 'procedimiento interno',
                    'version' => substr($hash, 0, 12),
                    'lifecycle_status' => 'vigente',
                    'original_filename' => $filename,
                    'mime_type' => 'text/markdown',
                    'uploaded_at' => $document->uploaded_at ?: now(),
                    'metadata' => array_merge($document->metadata ?? [], [
                        'source_file' => $source,
                        'source_sha256' => $hash,
                        'origin' => 'repository_internal_documentation',
                        'manufacturer_manual' => false,
                    ]),
                    'extracted_text' => $text,
                    'indexing_status' => 'pending',
                ])->save();
                $indexed = $indexer->index($document);
                $this->line($title.' | '.$indexed->indexing_status.' | chunks: '.$indexed->chunks()->count());
                if ($indexed->indexing_status !== 'indexed') {
                    return self::FAILURE;
                }
            }

            return self::SUCCESS;
        } finally {
            config(['maintenance_ai.enabled' => $enabled]);
        }
    }
}
