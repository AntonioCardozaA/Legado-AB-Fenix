<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class Lef52124WorkbookSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private readonly string $sheetTitle,
        private readonly string $reportTitle,
        private readonly string $coverage,
        private readonly array $tableRows,
        private readonly int $headerRow
    ) {
    }

    public function title(): string
    {
        return mb_substr($this->sheetTitle, 0, 31);
    }

    public function array(): array
    {
        return [
            ['ANALISIS LEF 52-12-4'],
            [$this->reportTitle],
            [$this->coverage],
            [],
            ...$this->tableRows,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = max(1, $sheet->getHighestRow());

                foreach ([1, 2, 3] as $row) {
                    $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
                }

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2348']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '475569']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A{$this->headerRow}:{$lastColumn}{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->freezePane('A'.($this->headerRow + 1));
                $sheet->setAutoFilter("A{$this->headerRow}:{$lastColumn}{$this->headerRow}");
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
            },
        ];
    }
}
