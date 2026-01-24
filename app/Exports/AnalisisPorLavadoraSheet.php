<?php

namespace App\Exports;

use App\Models\Analisis;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AnalisisLavadoraSheet implements FromArray, WithTitle, WithEvents
{
    protected $lavadora;
    protected $componentes;
    protected $analisis;

    public function __construct($lavadora)
    {
        $this->lavadora = $lavadora;

        $this->analisis = Analisis::with('componente')
            ->where('linea_id', $lavadora->id)
            ->get();

        $this->componentes = $this->analisis
            ->pluck('componente')
            ->unique('id')
            ->values();
    }

    public function title(): string
    {
        return $this->lavadora->nombre;
    }

    public function array(): array
    {
        $rows = [];

        // TITULO
        $rows[] = ["ANÁLISIS {$this->lavadora->nombre}"];
        $rows[] = [];

        // HEADERS
        $header = ['REDUCTOR'];
        foreach ($this->componentes as $c) {
            $header[] = strtoupper($c->nombre);
        }
        $rows[] = $header;

        // DATA
        foreach ($this->analisis->groupBy('reductor') as $reductor => $items) {
            $row = [$reductor];

            foreach ($this->componentes as $c) {
                $a = $items->firstWhere('componente_id', $c->id);
                $row[] = $a
                    ? "FECHA: {$a->fecha_analisis}\nORDEN: {$a->numero_orden}\nACTIVIDAD: {$a->actividad}"
                    : '';
            }
            $rows[] = $row;
        }

        // PROMEDIO
        $rows[] = [];
        $rows[] = ['PROMEDIO', '=COUNTA(B4:B100)'];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastCol = chr(65 + $this->componentes->count());
                $lastRow = $sheet->getHighestRow();

                // TITULO
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle("A1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFC000'],
                    ],
                ]);

                // ENCABEZADOS
                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFC000'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                // CUERPO
                for ($row = 4; $row <= $lastRow; $row++) {
                    for ($col = 'B'; $col <= $lastCol; $col++) {
                        if ($sheet->getCell("{$col}{$row}")->getValue()) {
                            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'color' => ['rgb' => '92D050'],
                                ],
                                'alignment' => [
                                    'wrapText' => true,
                                    'vertical' => Alignment::VERTICAL_TOP,
                                ],
                                'borders' => [
                                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                                ],
                            ]);
                        }
                    }
                }
            }
        ];
    }
}
