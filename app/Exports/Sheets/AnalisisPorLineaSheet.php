<?php

namespace App\Exports\Sheets;

use App\Models\AnalisisComponente;
use App\Models\Linea;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AnalisisPorLineaSheet implements FromCollection, WithTitle, WithHeadings
{
    protected $linea;
    protected $request;

    public function __construct(Linea $linea, $request)
    {
        $this->linea = $linea;
        $this->request = $request;
    }

    public function collection()
    {
        $query = AnalisisComponente::with('componente')
            ->where('linea_id', $this->linea->id)
            ->orderBy('fecha_analisis', 'desc');

        // Aplicamos filtros
        if (!empty($this->request->componente_id)) {
            $query->where('componente_id', $this->request->componente_id);
        }

        if (!empty($this->request->reductor)) {
            $query->where('reductor', $this->request->reductor);
        }

        if (!empty($this->request->fecha)) {
            $query->whereMonth('fecha_analisis', substr($this->request->fecha, 5, 2))
                  ->whereYear('fecha_analisis', substr($this->request->fecha, 0, 4));
        }

        return $query->get()->map(function ($a) {
            return [
                'Componente'     => $a->componente->nombre ?? '',
                'Reductor'       => $a->reductor,
                'Fecha análisis' => $a->fecha_analisis->format('d/m/Y'),
                'Orden'          => $a->numero_orden,
                'Actividad'      => $a->actividad,
                'Estado'         => $a->estado,
                'Observaciones'  => $a->observaciones,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Componente',
            'Reductor',
            'Fecha análisis',
            'Orden',
            'Actividad',
            'Estado',
            'Observaciones',
        ];
    }

    public function title(): string
    {
        return $this->linea->nombre;
    }
}
