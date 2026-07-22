<?php

namespace App\Console\Commands;

use App\Models\WasherKnowledgeDocument;
use App\Services\Maintenance\DocumentIndexer;
use App\Services\Maintenance\WasherKnowledgeBasePdfBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BuildWasherKnowledgeBasePdf extends Command
{
    protected $signature = 'washer:knowledge-base:build
                            {--title= : Titulo del documento}
                            {--path=washer-knowledge/base-conocimiento-tecnico-lavadoras.pdf : Ruta relativa dentro del disk local}';

    protected $description = 'Genera un PDF maestro de conocimiento tecnico de lavadoras y lo indexa para la IA.';

    public function __construct(
        private readonly WasherKnowledgeBasePdfBuilder $builder,
        private readonly DocumentIndexer $indexer
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Generando base de conocimiento tecnica de lavadoras...');

        try {
            $payload = $this->builder->build($this->option('title'));
            $relativePath = trim((string) $this->option('path'));

            if ($relativePath === '') {
                $relativePath = 'washer-knowledge/base-conocimiento-tecnico-lavadoras.pdf';
            }

            Storage::disk('local')->put($relativePath, $payload['pdf']);

            $document = WasherKnowledgeDocument::firstOrNew([
                'title' => $payload['title'],
            ]);

            $document->fill([
                'linea_id' => null,
                'componente_id' => null,
                'document_type' => 'manual tecnico',
                'version' => $payload['generated_at']->format('Y.m.d.Hi'),
                'effective_at' => $payload['generated_at']->toDateString(),
                'lifecycle_status' => 'vigente',
                'storage_disk' => 'local',
                'storage_path' => $relativePath,
                'original_filename' => $payload['filename'],
                'mime_type' => 'application/pdf',
                'uploaded_by' => null,
                'uploaded_at' => $payload['generated_at'],
                'metadata' => [
                    'generated_by' => 'system',
                    'generator' => self::class,
                    'sections' => array_keys($payload['data']),
                    'scope' => ['lavadora', 'cadenas', 'ia', 'conocimiento'],
                ],
                'indexing_status' => 'pending',
                'extracted_text' => $payload['text'],
                'last_index_error' => null,
                'indexed_at' => null,
            ]);
            $document->save();

            $document = $this->indexer->index($document);
            $absolutePath = Storage::disk('local')->path($relativePath);
            $summary = $payload['data']['overview'];

            $this->line('Archivo PDF: ' . $absolutePath);
            $this->line('Documento knowledge: #' . $document->id);
            $this->line('Indexacion: ' . $document->indexing_status . ' | chunks: ' . $document->chunks()->count());
            $this->line('Lineas activas: ' . $summary['lineas_activas']);
            $this->line('Analisis: ' . $summary['analisis_registrados'] . ' | eventos: ' . $summary['eventos_mantenimiento'] . ' | planes: ' . $summary['planes_accion']);
            $this->line('Elongaciones: ' . $summary['elongaciones'] . ' | ciclos activos: ' . $summary['ciclos_activos']);
            $this->line('Evidencias: ' . $summary['analisis_con_evidencia'] . ' analisis con ' . $summary['fotos_registradas'] . ' fotos');
            $this->line('Documentos base: ' . $summary['documentos_conocimiento'] . ' | chunks totales: ' . $summary['fragmentos_conocimiento']);

            if ($document->indexing_status !== 'indexed') {
                $this->warn('El PDF se genero, pero la indexacion no quedo en estado indexed.');

                if ($document->last_index_error) {
                    $this->error($document->last_index_error);
                }

                return self::FAILURE;
            }

            $this->info('Base de conocimiento generada e indexada correctamente.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
