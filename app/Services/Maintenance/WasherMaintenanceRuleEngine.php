<?php

namespace App\Services\Maintenance;

use App\Models\AnalisisLavadora;
use App\Models\Elongacion;
use Illuminate\Support\Collection;

class WasherMaintenanceRuleEngine
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forAnalysis(AnalisisLavadora $analysis): Collection
    {
        $analysis->loadMissing(['linea', 'componente', 'costEntries']);
        $events = collect();

        if (AnalisisLavadora::esEstadoDanado($analysis->estado)) {
            $events->push($this->event(
                'component_damaged',
                'critical',
                $analysis->estado,
                null,
                'Componente danado en lavadora',
                'El componente reportado requiere cambio inmediato o evaluacion correctiva.'
            ));
        }

        if ($analysis->estado === 'Desgaste severo') {
            $events->push($this->event(
                'component_severe_wear',
                'high',
                $analysis->estado,
                null,
                'Desgaste severo detectado',
                'El componente presenta desgaste severo y amerita un plan preventivo/correctivo.'
            ));
        }

        if ($analysis->estado === 'Desgaste moderado') {
            $events->push($this->event(
                'component_moderate_wear',
                'medium',
                $analysis->estado,
                null,
                'Desgaste moderado detectado',
                'El componente presenta desgaste moderado y se recomienda seguimiento preventivo.'
            ));
        }

        if (AnalisisLavadora::esEstadoRequiereRevision($analysis->estado)) {
            $events->push($this->event(
                'component_requires_revision',
                'medium',
                $analysis->estado,
                null,
                'Componente requiere revision',
                'El analisis reporta una condicion que requiere nueva revision o inspeccion dirigida.'
            ));
        }

        $highCostThreshold = (float) config('maintenance_ai.rules.high_cost_threshold', 0);
        $currentTotal = (float) $analysis->costEntries->sum('total_cost');

        if ($highCostThreshold > 0 && $currentTotal >= $highCostThreshold) {
            $events->push($this->event(
                'high_component_cost',
                'medium',
                number_format($currentTotal, 2, '.', ''),
                number_format($highCostThreshold, 2, '.', ''),
                'Costo elevado detectado',
                'El costo relacionado con este analisis supero el umbral configurado.'
            ));
        }

        return $events;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forElongacion(Elongacion $elongacion): Collection
    {
        $events = collect();
        $warning = (float) config('maintenance_ai.rules.elongacion_warning_threshold', 1.30);
        $critical = (float) config('maintenance_ai.rules.elongacion_critical_threshold', 1.46);
        $maxValue = max((float) $elongacion->bombas_porcentaje, (float) $elongacion->vapor_porcentaje);

        if ($maxValue >= $critical) {
            $events->push($this->event(
                'elongation_above_limit',
                'critical',
                number_format($maxValue, 2, '.', ''),
                number_format($critical, 2, '.', ''),
                'Elongacion por encima del limite',
                'La medicion de elongacion supero el limite tecnico configurado.'
            ));
        } elseif ($maxValue >= $warning) {
            $events->push($this->event(
                'elongation_near_limit',
                'high',
                number_format($maxValue, 2, '.', ''),
                number_format($critical, 2, '.', ''),
                'Elongacion cercana al limite',
                'La medicion de elongacion esta en zona preventiva y requiere seguimiento.'
            ));
        }

        if (in_array($elongacion->revision_status, ['upcoming', 'due_today', 'overdue'], true)) {
            $severity = $elongacion->revision_status === 'overdue'
                ? 'high'
                : 'medium';

            $events->push($this->event(
                'elongation_revision_due',
                $severity,
                (string) $elongacion->revision_days_remaining,
                '0',
                'Revision de elongacion proxima o vencida',
                'La proxima revision de elongacion requiere atencion segun la regla de periodicidad vigente.'
            ));
        }

        $trend = $this->detectAscendingTrend($elongacion);

        if ($trend !== null) {
            $events->push($this->event(
                'elongation_ascending_trend',
                'medium',
                number_format($trend, 2, '.', ''),
                number_format((float) config('maintenance_ai.rules.elongacion_trend_min_delta', 0.05), 2, '.', ''),
                'Tendencia ascendente de elongacion',
                'Las ultimas mediciones muestran un incremento sostenido de elongacion.'
            ));
        }

        $rodajaLimit = config('maintenance_ai.rules.rodaja_max_mm');

        if ($rodajaLimit !== null) {
            $maxRodaja = max((float) $elongacion->juego_rodaja_bombas, (float) $elongacion->juego_rodaja_vapor);

            if ($maxRodaja > (float) $rodajaLimit) {
                $events->push($this->event(
                    'rodaja_out_of_tolerance',
                    'medium',
                    number_format($maxRodaja, 2, '.', ''),
                    number_format((float) $rodajaLimit, 2, '.', ''),
                    'Juego de rodaja fuera de tolerancia',
                    'El juego de rodaja rebasa el limite configurado y amerita una accion preventiva.'
                ));
            }
        }

        return $events;
    }

    /**
     * @return array<string, mixed>
     */
    private function event(
        string $type,
        string $severity,
        ?string $detectedValue,
        ?string $limitValue,
        string $title,
        string $description
    ): array {
        return [
            'event_type' => $type,
            'severity' => $severity,
            'detected_value' => $detectedValue,
            'limit_value' => $limitValue,
            'title' => $title,
            'description' => $description,
        ];
    }

    private function detectAscendingTrend(Elongacion $elongacion): ?float
    {
        $records = Elongacion::query()
            ->where('linea_id', $elongacion->linea_id)
            ->latest('created_at')
            ->limit(3)
            ->get()
            ->reverse()
            ->values();

        if ($records->count() < 3) {
            return null;
        }

        $values = $records->map(fn (Elongacion $item) => max((float) $item->bombas_porcentaje, (float) $item->vapor_porcentaje))->values();
        $delta = $values->last() - $values->first();
        $minDelta = (float) config('maintenance_ai.rules.elongacion_trend_min_delta', 0.05);

        if ($values[0] < $values[1] && $values[1] < $values[2] && $delta >= $minDelta) {
            return $delta;
        }

        return null;
    }
}
