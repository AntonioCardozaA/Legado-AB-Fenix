<?php

namespace App\Exports;

use App\Models\Analisis;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AnalisisLavadoraExcel implements FromArray, WithEvents, WithColumnWidths
{
    protected $lavadora;
    protected $componentes;
    protected $analisis;

    public function __construct(string $lavadora = 'TODAS')
    {
        $this->lavadora = $lavadora;

        // Consulta segura
        $this->analisis = Analisis::with(['linea', 'componente'])
            ->when($this->lavadora !== 'TODAS', function ($query) {
                $query->whereHas('linea', function ($q) {
                    $q->where('nombre', $this->lavadora);
                });
            })
            ->get();

        // Componentes únicos presentes en los análisis
        $this->componentes = $this->analisis
            ->pluck('componente')
            ->filter() // eliminar null
            ->unique('id')
            ->values();
    }

    public function array(): array
    {
        $rows = [];

        // ===== TITULO =====
        $rows[] = ["ANÁLISIS LAVADORA {$this->lavadora}"];
        $rows[] = [];

        // ===== ENCABEZADOS =====
        $header = ['REDUCTOR'];
        foreach ($this->componentes as $componente) {
            $header[] = strtoupper($componente->nombre);
        }
        $rows[] = $header;

        // ===== DATOS =====
        foreach ($this->analisis->groupBy('reductor') as $reductor => $items) {
            $row = [$reductor];

            foreach ($this->componentes as $componente) {
                $a = $items->firstWhere('componente_id', $componente->id);

                if ($a) {
                    $fecha = $a->fecha_analisis ? $a->fecha_analisis->format('d/m/Y') : '';
                    $row[] =
                        "FECHA: {$fecha}\n" .
                        "ORDEN: {$a->numero_orden}\n" .
                        "ACTIVIDAD: {$a->actividad}";
                } else {
                    $row[] = '';
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        $cols = collect(range('B', chr(65 + max($this->componentes->count() - 1, 0))))
            ->mapWithKeys(fn($c) => [$c => 40])
            ->toArray();

        return array_merge(['A' => 12], $cols);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = chr(65 + max($this->componentes->count(), 1));

                // ===== TITULO =====
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle("A1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFC000']],
                ]);

                // ===== ENCABEZADOS =====
                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFC000']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                // ===== FILAS =====
                $lastRow = $sheet->getHighestRow();
                for ($row = 4; $row <= $lastRow; $row++) {

                    for ($col = 'B'; $col <= $lastCol; $col++) {
                        $value = $sheet->getCell("{$col}{$row}")->getValue();
                        if ($value) {
                            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '92D050']],
                                'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
                                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                            ]);
                        }
                    }

                    // Columna reductor
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFD966']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);
                }
            }
        ];
    }
}
