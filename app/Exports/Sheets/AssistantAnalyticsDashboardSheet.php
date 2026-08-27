<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AssistantAnalyticsDashboardSheet implements FromArray, ShouldAutoSize, WithDrawings, WithEvents, WithTitle
{
    /**
     * @param  array<string, mixed>  $dashboard
     */
    public function __construct(
        private readonly array $dashboard
    ) {}

    public function title(): string
    {
        return 'Dashboard';
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $summaryCards = array_values(array_filter((array) ($this->dashboard['summary_cards'] ?? []), fn ($item): bool => is_array($item)));
        $sideTitle = $this->text('side_panel_title');
        $sideHeadings = array_pad(array_slice(array_values((array) ($this->dashboard['side_panel_headings'] ?? [])), 0, 4), 4, '');
        $sideRows = array_values(array_filter((array) ($this->dashboard['side_panel_rows'] ?? []), fn ($item): bool => is_array($item)));
        $rows = [
            ['LEGADO AB FENIX'],
            [$this->text('title', 'Reporte operativo')],
            [$this->text('subtitle')],
            [$this->text('conclusion')],
            [],
            ['Indicador', 'Valor', 'Estado', '', '', '', '', '', '', '', $sideTitle],
        ];
        $summaryRows = collect(array_slice($summaryCards, 0, 8))
            ->map(fn (array $card): array => [
                (string) ($card['label'] ?? ''),
                (string) ($card['value'] ?? ''),
                $this->toneLabel((string) ($card['tone'] ?? 'neutral')),
            ])
            ->values()
            ->all();
        $rowCount = max(count($summaryRows), $sideRows !== [] ? count($sideRows) + 1 : 0);

        for ($index = 0; $index < $rowCount; $index++) {
            $row = array_fill(0, 14, '');

            if (isset($summaryRows[$index])) {
                [$row[0], $row[1], $row[2]] = $summaryRows[$index];
            }

            if ($sideRows !== []) {
                if ($index === 0) {
                    [$row[10], $row[11], $row[12], $row[13]] = $sideHeadings;
                } elseif (isset($sideRows[$index - 1])) {
                    $sideRow = array_pad(array_values((array) $sideRows[$index - 1]), 4, '');
                    [$row[10], $row[11], $row[12], $row[13]] = array_slice($sideRow, 0, 4);
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<int, Drawing>
     */
    public function drawings(): array
    {
        $path = (string) ($this->dashboard['chart_image_path'] ?? '');

        if ($path === '' || ! is_file($path)) {
            return [];
        }

        $drawing = new Drawing;
        $drawing->setName('Grafica del reporte');
        $drawing->setDescription($this->text('title', 'Grafica del reporte'));
        $drawing->setPath($path);
        $drawing->setHeight(340);
        $drawing->setCoordinates('E6');
        $drawing->setOffsetX(10);
        $drawing->setOffsetY(4);

        return [$drawing];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $summaryCount = min(8, count((array) ($this->dashboard['summary_cards'] ?? [])));
                $highestRow = max(1, $sheet->getHighestRow());

                foreach ([1, 2, 3, 4] as $row) {
                    $sheet->mergeCells("A{$row}:N{$row}");
                }

                $sheet->getStyle('A1:N1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:N2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A3:N4')->applyFromArray([
                    'font' => ['color' => ['rgb' => '334155']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle('A6:C6')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $hasSidePanel = trim((string) ($this->dashboard['side_panel_title'] ?? '')) !== ''
                    && ! empty($this->dashboard['side_panel_rows']);

                if ($hasSidePanel) {
                    $sheet->mergeCells('K6:N6');
                    $sheet->getStyle('K6:N6')->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle('K7:N7')->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("K6:N{$highestRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E5E7EB'],
                            ],
                        ],
                    ]);
                }

                $sheet->getStyle("A1:N{$highestRow}")->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle("A6:C{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);

                for ($row = 7; $row < 7 + $summaryCount; $row++) {
                    $tone = (string) $sheet->getCell("C{$row}")->getValue();
                    $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->toneFillColor($tone)]],
                        'font' => ['color' => ['rgb' => $this->toneFontColor($tone)]],
                    ]);
                    $sheet->getStyle("A{$row}:A{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("B{$row}:B{$row}")->getFont()->setBold(true);
                }

                foreach ([
                    'A' => 24,
                    'B' => 28,
                    'C' => 18,
                    'D' => 4,
                    'E' => 18,
                    'F' => 18,
                    'G' => 18,
                    'H' => 18,
                    'I' => 18,
                    'J' => 18,
                    'K' => 24,
                    'L' => 30,
                    'M' => 12,
                    'N' => 16,
                ] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(2)->setRowHeight(30);
                $sheet->getRowDimension(4)->setRowHeight(38);
                $sheet->freezePane('A6');
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
            },
        ];
    }

    private function text(string $key, string $fallback = ''): string
    {
        return Str::limit(Str::ascii((string) ($this->dashboard[$key] ?? $fallback)), 240, '');
    }

    private function toneLabel(string $tone): string
    {
        return match ($tone) {
            'critical' => 'Critico',
            'warning' => 'Alerta',
            'normal' => 'Normal',
            default => 'Informativo',
        };
    }

    private function toneFillColor(string $tone): string
    {
        return match (Str::lower(Str::ascii($tone))) {
            'critico' => 'FEE2E2',
            'alerta' => 'FEF3C7',
            'normal' => 'DCFCE7',
            default => 'EFF6FF',
        };
    }

    private function toneFontColor(string $tone): string
    {
        return match (Str::lower(Str::ascii($tone))) {
            'critico' => '7F1D1D',
            'alerta' => '78350F',
            'normal' => '14532D',
            default => '1E3A8A',
        };
    }
}
