<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Maintenance\MaintenanceHistoryIndexer;
use Illuminate\Console\Command;
use Throwable;

class IndexMaintenanceHistory extends Command
{
    protected $signature = 'maintenance:history:index
                            {--module=lavadora : Modulo operativo a indexar}
                            {--source= : Fuente especifica del modulo seleccionado}
                            {--fresh : Elimina los chunks existentes del alcance seleccionado antes de indexar}
                            {--without-embeddings : Indexa texto y metadatos sin llamadas al proveedor IA}';

    protected $description = 'Crea o actualiza el indice semantico del historial real para la IA de mantenimiento.';

    public function __construct(
        private readonly MaintenanceHistoryIndexer $indexer
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $embeddingsEnabled = config('maintenance_ai.history_index.embeddings_enabled', true);
        if ($this->option('without-embeddings')) {
            config(['maintenance_ai.history_index.embeddings_enabled' => false]);
        }

        try {
            return $this->indexHistory();
        } finally {
            config(['maintenance_ai.history_index.embeddings_enabled' => $embeddingsEnabled]);
        }
    }

    private function indexHistory(): int
    {
        $module = trim((string) $this->option('module')) ?: User::MODULE_LAVADORA;
        $source = trim((string) $this->option('source')) ?: null;
        $fresh = (bool) $this->option('fresh');
        $availableSources = $this->indexer->sourceTypesForModule($module);

        if ($availableSources === []) {
            $this->error('No hay fuentes historicas configuradas para el modulo: '.$module);

            return self::FAILURE;
        }

        if ($source !== null && ! in_array($source, $availableSources, true)) {
            $this->error('Fuente no soportada: '.$source);
            $this->line('Fuentes disponibles: '.implode(', ', $availableSources));

            return self::FAILURE;
        }

        $this->info('Indexando historial real para IA...');
        $this->line('Modulo: '.$module);
        $this->line('Fuentes: '.($source ?: implode(', ', $availableSources)));

        if ($fresh) {
            $this->warn('Modo fresh activo: se eliminaran chunks existentes del alcance seleccionado.');
        }

        try {
            $summary = $this->indexer->indexAll($module, $source, $fresh);

            if (isset($summary['skipped'])) {
                $this->warn((string) $summary['skipped']);

                return self::FAILURE;
            }

            $this->line('Chunks eliminados: '.($summary['deleted_chunks'] ?? 0));
            $this->line('Chunks indexados: '.($summary['indexed_chunks'] ?? 0));

            foreach (($summary['sources'] ?? []) as $sourceType => $sourceSummary) {
                $this->line(sprintf(
                    '- %s: registros %d, chunks %d, errores %d',
                    $sourceType,
                    (int) ($sourceSummary['records'] ?? 0),
                    (int) ($sourceSummary['indexed_chunks'] ?? 0),
                    (int) ($sourceSummary['errors'] ?? 0)
                ));
            }

            $errors = array_sum(array_column($summary['sources'] ?? [], 'errors'));
            if ($errors > 0) {
                $this->error('La indexacion termino con errores. Revisa las fuentes indicadas.');

                return self::FAILURE;
            }

            $this->info('Indice del historial actualizado.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
