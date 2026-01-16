// app/Exports/AnalisisExport.php
<?php

namespace App\Exports;

use App\Models\Analisis;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnalisisExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $tipo;
    
    public function __construct($tipo = 'general')
    {
        $this->tipo = $tipo;
    }
    
    public function collection()
    {
        $query = Analisis::with(['linea', 'componentes.componente']);
        
        if ($this->tipo == 'elongacion') {
            return $query->orderBy('fecha_analisis', 'desc')->get();
        }
        
        return $query->latest()->get();
    }
    
    public function headings(): array
    {
        if ($this->tipo == 'elongacion') {
            return [
                'Línea',
                'Fecha',
                'Número de Orden',
                'Elongación Promedio (mm)',
                '% Elongación',
                'Horómetro',
                'Juego de Rodaja (mm)',
                'Estado',
                'Observaciones'
            ];
        }
        
        return [
            'Línea',
            'Fecha',
            'Número de Orden',
            'Horómetro',
            'Elongación (mm)',
            'Componentes Revisados',
            'Componentes Dañados',
            'Usuario'
        ];
    }
    
    public function map($analisis): array
    {
        if ($this->tipo == 'elongacion') {
            $porcentaje = (($analisis->elongacion_promedio - 173) / 173) * 100;
            $estado = $porcentaje > 3 ? 'CRÍTICO' : ($porcentaje > 2 ? 'ATENCIÓN' : 'NORMAL');
            
            return [
                $analisis->linea->nombre,
                $analisis->fecha_analisis->format('d/m/Y'),
                $analisis->numero_orden,
                $analisis->elongacion_promedio,
                number_format($porcentaje, 2) . '%',
                $analisis->horometro,
                $analisis->juego_rodaja,
                $estado,
                $analisis->observaciones
            ];
        }
        
        $componentesRevisados = $analisis->componentes->count();
        $componentesDanados = $analisis->componentes->whereIn('estado', ['DAÑADO', 'REEMPLAZADO'])->count();
        
        return [
            $analisis->linea->nombre,
            $analisis->fecha_analisis->format('d/m/Y'),
            $analisis->numero_orden,
            $analisis->horometro,
            $analisis->elongacion_promedio,
            $componentesRevisados,
            $componentesDanados,
            $analisis->usuario->name
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}