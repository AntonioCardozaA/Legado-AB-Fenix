<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AnalisisPasteurizadoraExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $analisis)
    {
    }

    public function collection(): Collection
    {
        return $this->analisis;
    }

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
        ];
    }
}
