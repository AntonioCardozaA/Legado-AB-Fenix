<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AssistantAnalyticsArraySheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(
        private readonly string $title,
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $tone = 'default'
    ) {}

    public function title(): string
    {
        $title = Str::limit(Str::ascii($this->title), 31, '');

        return $title !== '' ? $title : 'Hoja';
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return [
            $this->headings,
            ...$this->rows,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $columnCount = max(1, count($this->headings));
                $lastColumn = $this->columnName($columnCount);
                $highestRow = max(1, $sheet->getHighestRow());
                $headerColor = $this->headerColor();

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerColor]],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle("A1:{$lastColumn}{$highestRow}")->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastColumn}1");

                if ($highestRow > 1) {
                    for ($row = 2; $row <= $highestRow; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                            ]);
                        }
                    }
                }

                $this->applyConditionalStateStyles($sheet, $lastColumn, $highestRow);
                $this->applyNumberFormats($sheet, $highestRow);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
            },
        ];
    }

    private function headerColor(): string
    {
        return match ($this->tone) {
            'success' => '166534',
            'warning' => 'B45309',
            'danger' => 'B91C1C',
            'muted' => '475569',
            default => '0F172A',
        };
    }

    private function applyConditionalStateStyles($sheet, string $lastColumn, int $highestRow): void
    {
        if ($highestRow < 2 || $this->title() === 'Filtros') {
            return;
        }

        for ($row = 2; $row <= $highestRow; $row++) {
            $values = (array) ($sheet->rangeToArray("A{$row}:{$lastColumn}{$row}", null, true, false)[0] ?? []);
            $text = Str::lower(Str::ascii(implode(' ', array_map(static fn ($value): string => (string) $value, $values))));
            $fill = null;
            $font = null;

            if (str_contains($text, 'critico') || str_contains($text, 'vencido') || str_contains($text, 'danado') || str_contains($text, 'cambio / critico')) {
                $fill = 'FEE2E2';
                $font = '7F1D1D';
            } elseif (str_contains($text, 'alerta') || str_contains($text, 'revision') || str_contains($text, 'desgaste') || str_contains($text, 'comprar') || str_contains($text, 'pendiente')) {
                $fill = 'FEF3C7';
                $font = '78350F';
            } elseif (str_contains($text, 'normal') || str_contains($text, 'completado') || str_contains($text, 'buen estado')) {
                $fill = 'DCFCE7';
                $font = '14532D';
            }

            if ($fill !== null && $font !== null) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'font' => ['color' => ['rgb' => $font]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill]],
                ]);
            }
        }
    }

    private function applyNumberFormats($sheet, int $highestRow): void
    {
        if ($highestRow < 2) {
            return;
        }

        foreach ($this->headings as $index => $heading) {
            $column = $this->columnName($index + 1);
            $normalized = Str::lower(Str::ascii((string) $heading));

            if (str_contains($normalized, 'costo') || str_contains($normalized, 'mxn')) {
                $sheet->getStyle("{$column}2:{$column}{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');
            } elseif (str_contains($normalized, '%') || str_contains($normalized, 'porcentaje')) {
                $sheet->getStyle("{$column}2:{$column}{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            }
        }
    }

    private function columnName(int $columnNumber): string
    {
        $name = '';

        while ($columnNumber > 0) {
            $remainder = ($columnNumber - 1) % 26;
            $name = chr(65 + $remainder).$name;
            $columnNumber = intdiv($columnNumber - 1, 26);
        }

        return $name;
    }
}
