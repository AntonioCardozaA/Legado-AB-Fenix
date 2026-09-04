<?php

namespace App\Services\Maintenance;

use App\Models\AnalisisCentralHidraulica;
use App\Models\AnalisisPasteurizadora;
use Illuminate\Support\Collection;

class PasteurizadoraMaintenanceRuleEngine
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forAnalysis(AnalisisPasteurizadora $analysis): Collection
    {
        $analysis->loadMissing(['linea']);

        return $this->eventsForState(
            $analysis->estado,
            'Componente danado en pasteurizadora',
            'El componente reportado en pasteurizadora requiere cambio inmediato o evaluacion correctiva.',
            'El componente de pasteurizadora presenta desgaste severo y amerita un plan preventivo/correctivo.',
            'El componente de pasteurizadora presenta desgaste moderado y se recomienda seguimiento preventivo.',
            'El analisis de pasteurizadora reporta una condicion que requiere nueva revision o inspeccion dirigida.'
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forCentralAnalysis(AnalisisCentralHidraulica $analysis): Collection
    {
        $analysis->loadMissing(['linea', 'componente', 'configuracion']);

        return $this->eventsForState(
            $analysis->estado,
            'Componente danado en central hidraulica',
            'El componente reportado en central hidraulica de pasteurizadora requiere cambio inmediato o evaluacion correctiva.',
            'El componente de central hidraulica presenta desgaste severo y amerita un plan preventivo/correctivo.',
            'El componente de central hidraulica presenta desgaste moderado y se recomienda seguimiento preventivo.',
            'El analisis de central hidraulica reporta una condicion que requiere nueva revision o inspeccion dirigida.'
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function eventsForState(
        ?string $state,
        string $damagedTitle,
        string $damagedDescription,
        string $severeWearDescription,
        string $moderateWearDescription,
        string $requiresRevisionDescription
    ): Collection {
        $events = collect();

        if (AnalisisPasteurizadora::esEstadoDanado($state)) {
            $events->push($this->event(
                'component_damaged',
                'critical',
                $state,
                null,
                $damagedTitle,
                $damagedDescription
            ));
        }

        if ($state === 'Desgaste severo') {
            $events->push($this->event(
                'component_severe_wear',
                'high',
                $state,
                null,
                'Desgaste severo detectado',
                $severeWearDescription
            ));
        }

        if ($state === 'Desgaste moderado') {
            $events->push($this->event(
                'component_moderate_wear',
                'medium',
                $state,
                null,
                'Desgaste moderado detectado',
                $moderateWearDescription
            ));
        }

        if (AnalisisPasteurizadora::esEstadoRequiereRevision($state)) {
            $events->push($this->event(
                'component_requires_revision',
                'medium',
                $state,
                null,
                'Componente requiere revision',
                $requiresRevisionDescription
            ));
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
}
