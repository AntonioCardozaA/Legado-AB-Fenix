<?php
// app/Exports/ReporteLavadoraGeneralExcel.php

namespace App\Exports;

use App\Models\AnalisisLavadora;
use App\Models\Paro;
use App\Models\Linea;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class ReporteLavadoraGeneralExcel implements FromArray, WithEvents, WithColumnWidths, WithTitle
{
    protected $fechaInicio;
    protected $fechaFin;
    protected $lineaId;
    protected $analisis;
    protected $paros;
    protected $lineas;
    
    // Líneas de lavadora
    protected $lavadoras = ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'];

    public function __construct($fechaInicio = null, $fechaFin = null, $lineaId = null)
    {
        $this->fechaInicio = $fechaInicio ? Carbon::parse($fechaInicio) : Carbon::now()->subMonth();
        $this->fechaFin = $fechaFin ? Carbon::parse($fechaFin) : Carbon::now();
        $this->lineaId = $lineaId;

        // Si hay línea específica, solo esa
        if ($lineaId) {
            $this->lineas = Linea::where('id', $lineaId)->get();
        } else {
            $this->lineas = Linea::whereIn('nombre', $this->lavadoras)->get();
        }

        // Cargar análisis
        $this->analisis = AnalisisLavadora::with(['linea', 'componente'])
            ->whereHas('linea', function($query) {
                $query->whereIn('nombre', $this->lavadoras);
                if ($this->lineaId) {
                    $query->where('id', $this->lineaId);
                }
            })
            ->whereBetween('fecha_analisis', [$this->fechaInicio, $this->fechaFin])
            ->orderBy('fecha_analisis', 'desc')
            ->get();

        // Cargar paros
        $this->paros = Paro::with(['linea', 'planesAccion'])
            ->whereHas('linea', function($query) {
                $query->whereIn('nombre', $this->lavadoras);
                if ($this->lineaId) {
                    $query->where('id', $this->lineaId);
                }
            })
            ->whereBetween('fecha_inicio', [$this->fechaInicio, $this->fechaFin])
            ->orderBy('fecha_inicio', 'desc')
            ->get();
    }

    public function title(): string
    {
        return $this->lineaId ? 'Lavadora Específica' : 'Lavadoras General';
    }

    public function array(): array
    {
        $rows = [];

        // ===== TITULO PRINCIPAL =====
        $titulo = $this->lineaId 
            ? "REPORTE DE LAVADORA - {$this->lineas->first()->nombre}"
            : "REPORTE GENERAL DE LAVADORAS";
        
        $rows[] = [$titulo];
        $rows[] = ["Período: " . $this->fechaInicio->format('d/m/Y') . " - " . $this->fechaFin->format('d/m/Y')];
        $rows[] = ["Fecha de generación: " . Carbon::now()->format('d/m/Y H:i:s')];
        $rows[] = []; // Espacio

        // ===== RESUMEN POR LÍNEA =====
        $rows[] = ["RESUMEN POR LÍNEA"];
        $rows[] = []; // Espacio

        // Encabezados resumen
        $rows[] = ['LÍNEA', 'TOTAL ANÁLISIS', 'TOTAL PAROS', 'HORAS PARO', 'COMPONENTES CRÍTICOS', 'ESTADO GENERAL'];

        foreach ($this->lineas as $linea) {
            $analisisLinea = $this->analisis->where('linea_id', $linea->id);
            $parosLinea = $this->paros->where('linea_id', $linea->id);
            
            $componentesCriticos = $analisisLinea->where('estado', 'MALO')->groupBy('componente_id')->count();
            $horasParo = $parosLinea->sum('duracion_horas');
            
            $ultimoAnalisis = $analisisLinea->first();
            $estadoGeneral = $this->determinarEstadoGeneral($ultimoAnalisis, $componentesCriticos);
            
            $rows[] = [
                $linea->nombre,
                $analisisLinea->count(),
                $parosLinea->count(),
                number_format($horasParo, 1) . ' h',
                $componentesCriticos,
                $estadoGeneral
            ];
        }

        $rows[] = []; // Espacio
        $rows[] = []; // Espacio

        // ===== ANÁLISIS DE ELONGACIÓN =====
        $rows[] = ["ANÁLISIS DE ELONGACIÓN"];
        $rows[] = []; // Espacio

        // Encabezados elongación
        $rows[] = ['LÍNEA', 'FECHA', 'COMPONENTE', 'ELONGACIÓN (mm)', 'HORÓMETRO', 'ESTADO', 'OBSERVACIONES'];

        foreach ($this->analisis as $analisis) {
            $estado = $this->determinarEstadoElongacion($analisis->elongacion_promedio);
            
            $rows[] = [
                $analisis->linea->nombre ?? 'N/A',
                $analisis->fecha_analisis ? $analisis->fecha_analisis->format('d/m/Y') : 'N/A',
                $analisis->componente->nombre ?? 'N/A',
                number_format($analisis->elongacion_promedio, 2),
                number_format($analisis->horometro),
                $estado,
                $analisis->observaciones ?? ''
            ];
        }

        $rows[] = []; // Espacio
        $rows[] = []; // Espacio

        // ===== PAROS DE MANTENIMIENTO =====
        $rows[] = ["PAROS DE MANTENIMIENTO"];
        $rows[] = []; // Espacio

        // Encabezados paros
        $rows[] = ['LÍNEA', 'FECHA INICIO', 'FECHA FIN', 'TIPO', 'DURACIÓN (h)', 'MOTIVO', 'ESTADO PLAN', 'ACCIONES'];

        foreach ($this->paros as $paro) {
            $estadoPlan = $paro->planesAccion->where('estado', 'COMPLETADA')->count() > 0 ? 'COMPLETADO' : 'EN PROCESO';
            $acciones = $paro->planesAccion->pluck('descripcion')->implode('; ');
            
            $rows[] = [
                $paro->linea->nombre ?? 'N/A',
                $paro->fecha_inicio ? $paro->fecha_inicio->format('d/m/Y H:i') : 'N/A',
                $paro->fecha_fin ? $paro->fecha_fin->format('d/m/Y H:i') : 'N/A',
                $paro->tipo,
                number_format($paro->duracion_horas, 1),
                $paro->motivo,
                $estadoPlan,
                $acciones
            ];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,  // Línea
            'B' => 15,  // Fecha
            'C' => 25,  // Componente / Tipo
            'D' => 15,  // Valor
            'E' => 15,  // Horómetro / Duración
            'F' => 20,  // Estado
            'G' => 30,  // Observaciones / Motivo
            'H' => 40,  // Acciones
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // ===== ESTILOS GENERALES =====
                $sheet->getDefaultRowDimension()->setRowHeight(20);
                $sheet->getStyle('A1:H1000')->getAlignment()->setWrapText(true);
                
                // ===== TÍTULO PRINCIPAL =====
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E3A8A'] // Azul oscuro
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                // ===== PERÍODO Y FECHA =====
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');
                $sheet->getStyle('A2:A3')->applyFromArray([
                    'font' => ['size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ===== TÍTULOS DE SECCIÓN =====
                $this->aplicarTituloSeccion($sheet, 'A5', 'RESUMEN POR LÍNEA', 'FFC000');
                
                $filaElongacion = 5 + $this->lineas->count() + 4;
                $this->aplicarTituloSeccion($sheet, 'A' . $filaElongacion, 'ANÁLISIS DE ELONGACIÓN', '4CAF50');
                
                $filaParos = $filaElongacion + $this->analisis->count() + 4;
                $this->aplicarTituloSeccion($sheet, 'A' . $filaParos, 'PAROS DE MANTENIMIENTO', 'F44336');

                // ===== ENCABEZADOS DE TABLAS =====
                $this->aplicarEncabezados($sheet, 'A7:H7', '1E3A8A'); // Resumen
                
                $filaEncabezadosElongacion = $filaElongacion + 2;
                $this->aplicarEncabezados($sheet, 'A' . $filaEncabezadosElongacion . ':G' . $filaEncabezadosElongacion, '4CAF50');
                
                $filaEncabezadosParos = $filaParos + 2;
                $this->aplicarEncabezados($sheet, 'A' . $filaEncabezadosParos . ':H' . $filaEncabezadosParos, 'F44336');

                // ===== COLORES POR ESTADO =====
                $this->aplicarColoresEstados($sheet);
                
                // ===== BORDES =====
                $this->aplicarBordes($sheet);
            },
        ];
    }

    private function aplicarTituloSeccion($sheet, $celda, $texto, $color)
    {
        $sheet->setCellValue($celda, $texto);
        $sheet->mergeCells($celda . ':H' . substr($celda, 1));
        $sheet->getStyle($celda)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $color]
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
    }

    private function aplicarEncabezados($sheet, $rango, $color)
    {
        $sheet->getStyle($rango)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $color]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
        ]);
    }

    private function aplicarColoresEstados($sheet)
    {
        $highestRow = $sheet->getHighestRow();
        
        for ($row = 8; $row <= $highestRow; $row++) {
            $estado = $sheet->getCell('F' . $row)->getValue();
            
            $colorFondo = match($estado) {
                'CRÍTICO' => 'FFCDD2', // Rojo claro
                'ATENCIÓN' => 'FFF9C4', // Amarillo claro
                'NORMAL' => 'C8E6C9',   // Verde claro
                'COMPLETADO' => 'C8E6C9', // Verde claro
                default => null
            };
            
            if ($colorFondo) {
                $sheet->getStyle('F' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $colorFondo]
                    ],
                    'font' => ['bold' => true]
                ]);
            }
        }
    }

    private function aplicarBordes($sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        
        $sheet->getStyle('A7:' . $highestColumn . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD']
                ]
            ],
        ]);
    }

    private function determinarEstadoElongacion($elongacion)
    {
        if ($elongacion > 178.19) return 'CRÍTICO';
        if ($elongacion > 176) return 'ATENCIÓN';
        return 'NORMAL';
    }

    private function determinarEstadoGeneral($ultimoAnalisis, $componentesCriticos)
    {
        if (!$ultimoAnalisis) return 'SIN DATOS';
        
        if ($ultimoAnalisis->elongacion_promedio > 178.19 || $componentesCriticos > 2) {
            return 'CRÍTICO';
        } elseif ($ultimoAnalisis->elongacion_promedio > 176 || $componentesCriticos > 0) {
            return 'ATENCIÓN';
        }
        
        return 'NORMAL';
    }
}