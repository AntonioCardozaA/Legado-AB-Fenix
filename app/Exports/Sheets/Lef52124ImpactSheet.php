<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class Lef52124ImpactSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private readonly string $title,
        private readonly array $rows
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
                    'font' => ['name' => 'Arial', 'size' => 10],
                    'alignment' => [
                        'vertical' => 'center',
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => '1F2348']],
                ]);

                for ($row = 1; $row <= $highestRow; $row++) {
                    $value = (string) $sheet->getCell("A{$row}")->getValue();

                    if ($this->isHeaderRow($value)) {
                        $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => [
                                'fillType' => 'solid',
                                'startColor' => ['rgb' => '1F2348'],
                            ],
                        ]);
                    }
                }

                $sheet->freezePane('A4');
            },
        ];
    }

    private function isHeaderRow(string $value): bool
    {
        return in_array($value, [
            'KPI',
            'Linea',
            'Lavadora',
            'Linea / lavadora',
            'Mes',
        ], true);
    }
}
