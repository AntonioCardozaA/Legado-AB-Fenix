<?php

namespace App\Services;

use App\Models\AnalysisDeletionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AnalysisDeletionLogger
{
    public function log(User $user, Model $analysis, string $analysisType, string $tipoAnalisis, array $metadata = []): AnalysisDeletionLog
    {
        $linea = method_exists($analysis, 'linea') ? $analysis->linea : null;

        return AnalysisDeletionLog::create([
            'user_id' => $user->id,
            'analysis_type' => $analysisType,
            'analysis_model' => $analysis::class,
            'analysis_table' => $analysis->getTable(),
            'deleted_record_id' => $analysis->getKey(),
            'linea_id' => $analysis->linea_id ?? null,
            'linea_nombre' => $linea?->nombre,
            'tipo_analisis' => $tipoAnalisis,
            'deleted_at' => now(),
            'metadata' => $metadata,
        ]);
    }
}
