<?php

namespace App\Exports\Concerns;

trait MapsPasteurizadoraAnalysis
{
    public function headings(): array
    {
        return [
            'Fecha',
            'Pasteurizadora',
            'Modulo',
            'Nivel',
            'Lado',
            'Componente',
            'Estado',
            'Orden',
            'Actividad',
            'Componentes revisados',
            'Total componentes',
            'Numeros de componentes',
        ];
    }

    public function map($item): array
    {
        return [
            $item->fecha_analisis?->format('d/m/Y'),
            $item->linea->nombre ?? '',
            $item->modulo,
            $item->nivel,
            $item->lado,
            $item->componente_nombre,
            $item->estado,
            $item->numero_orden,
            $item->actividad,
            $item->cantidad_componentes_revisados,
            $item->total_componentes,
            implode(', ', $item->componentes_revisados_lista),
        ];
    }
}
