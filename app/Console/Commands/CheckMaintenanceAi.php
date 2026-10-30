<?php

namespace App\Console\Commands;

use App\Contracts\AiProviderInterface;
use App\Services\Maintenance\AssistantWebSearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CheckMaintenanceAi extends Command
{
    protected $signature = 'maintenance:ai:check
                            {--probe : Comprobar una respuesta estructurada y un embedding reales con datos sinteticos}
                            {--probe-web : Comprobar la busqueda web con una consulta publica sintetica}
                            {--json : Emitir el informe en JSON sin secretos}';

    protected $description = 'Diagnostica configuración, tablas, documentos e historial de la IA sin modificar registros operativos.';

    public function handle(AiProviderInterface $provider, AssistantWebSearchService $webSearch): int
    {
        $selectedProvider = (string) config('maintenance_ai.provider');
        $checks = [
            'ai_enabled' => (bool) config('maintenance_ai.enabled'),
            'provider_key_present' => filled(config('maintenance_ai.providers.'.$selectedProvider.'.api_key')),
        ];
        $details = [
            'environment' => app()->environment(),
            'provider' => $selectedProvider,
            'model' => config('maintenance_ai.providers.'.$selectedProvider.'.model'),
            'fallback_models' => config('maintenance_ai.providers.'.$selectedProvider.'.fallback_models', []),
            'dispatch_mode' => config('maintenance_ai.dispatch_mode'),
            'queue_connection' => config('queue.default'),
            'queue' => config('maintenance_ai.queue'),
            'web_search_enabled' => (bool) config('maintenance_ai.web_search.enabled'),
            'history_auto_index' => (bool) config('maintenance_ai.history_index.auto_index'),
            'approval_requires_sources' => (bool) config('maintenance_ai.grounding.block_approval_without_sources'),
            'live_probe_requested' => (bool) $this->option('probe'),
            'live_web_probe_requested' => (bool) $this->option('probe-web'),
        ];

        try {
            foreach (['maintenance_events', 'plan_accion', 'ai_interaction_logs', 'assistant_conversations', 'assistant_messages',
                'washer_knowledge_documents', 'washer_knowledge_chunks', 'pasteurizadora_knowledge_documents',
                'pasteurizadora_knowledge_chunks', 'maintenance_history_chunks'] as $table) {
                $checks['table_'.$table] = Schema::hasTable($table);
            }
            foreach (['washer', 'pasteurizadora'] as $module) {
                $table = $module.'_knowledge_documents';
                if ($checks['table_'.$table]) {
                    $details[$module.'_indexed_documents'] = DB::table($table)->where('indexing_status', 'indexed')->where('lifecycle_status', 'vigente')->count();
                    $checks[$module.'_documents_ready'] = $details[$module.'_indexed_documents'] > 0;
                }
            }
            if ($checks['table_maintenance_history_chunks']) {
                $details['history'] = DB::table('maintenance_history_chunks')->select('module')
                    ->selectRaw('COUNT(*) AS chunks')->selectRaw('SUM(CASE WHEN embedding IS NOT NULL THEN 1 ELSE 0 END) AS embeddings')
                    ->groupBy('module')->get()->toArray();
            }
        } catch (Throwable $exception) {
            $checks['database_accessible'] = false;
            $details['database_error_type'] = $exception::class;
        }

        if ($this->option('probe') && $checks['ai_enabled'] && $checks['provider_key_present']) {
            $started = microtime(true);
            try {
                $response = $provider->generateStructuredActionPlan([
                    'flow' => 'maintenance_ai_health_check',
                    'schema_name' => 'maintenance_ai_health_check',
                    'schema' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['ok'], 'properties' => ['ok' => ['type' => 'boolean']]],
                    'system_prompt' => 'Return the requested JSON object. This is a connectivity check with synthetic data.',
                    'user_prompt' => 'Return {"ok":true}.',
                ]);
                $checks['live_structured_response'] = data_get($response, 'data.ok') === true;
                $details['live_provider'] = data_get($response, 'meta.provider');
                $details['live_model'] = data_get($response, 'meta.model');
            } catch (Throwable $exception) {
                $checks['live_structured_response'] = false;
                $details['generation_error_type'] = $exception::class;
            }
            $details['probe_latency_ms'] = (int) round((microtime(true) - $started) * 1000);
            try {
                $embedding = $provider->createEmbedding('Verificacion sintetica del indice de mantenimiento.');
                $checks['live_embedding'] = count($embedding) > 0 && count(array_filter($embedding, 'is_numeric')) === count($embedding);
                $details['embedding_dimensions'] = count($embedding);
            } catch (Throwable $exception) {
                $checks['live_embedding'] = false;
                $details['embedding_error_type'] = $exception::class;
            }
        }

        if ($this->option('probe-web')) {
            try {
                $web = $webSearch->searchIfNeeded('Busca en la web el sitio oficial de Laravel y cita la fuente.', [], [], []);
                $checks['live_web_search'] = (bool) ($web['used'] ?? false) && count($web['sources'] ?? []) > 0;
                $details['web_sources_count'] = count($web['sources'] ?? []);
                $details['web_provider'] = $web['provider'] ?? null;
                $details['web_result_reason'] = $web['reason'] ?? null;
                if (! $checks['live_web_search'] && filled($web['error'] ?? null)) {
                    $error = strtolower((string) $web['error']);
                    $details['web_error_category'] = match (true) {
                        str_contains($error, '429'), str_contains($error, 'quota') => 'quota_or_rate_limit',
                        str_contains($error, '401'), str_contains($error, '403') => 'authentication_or_permissions',
                        str_contains($error, '404') => 'endpoint_or_model_not_found',
                        default => 'provider_unavailable',
                    };
                }
            } catch (Throwable $exception) {
                $checks['live_web_search'] = false;
                $details['web_error_type'] = $exception::class;
            }
        }

        $ok = ! in_array(false, $checks, true);
        $report = ['ok' => $ok, 'checks' => $checks, 'details' => $details];
        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            foreach ($checks as $name => $passed) {
                $this->line(($passed ? 'OK ' : 'ERROR ').$name);
            }
            $this->line(json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
