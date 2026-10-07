<?php

namespace App\Exports;

use App\Exports\Sheets\Lef52124ImpactSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Lef52124LineTrendsExport implements WithMultipleSheets
{
    public function __construct(private readonly array $payload)
    {
    }

    public function sheets(): array
    {
        return [
            new Lef52124ImpactSheet('Lavadoras 4 SEM', $this->lineTrendRows()),
            new Lef52124ImpactSheet('Lineas completas 4 SEM', $this->monthlyRows()),
        ];
    }

    private function lineTrendRows(): array
    {
        $rows = $this->filterRows('LEF 4 semanas - Lavadoras');
        $rows[] = [];
        $rows[] = ['Lavadora', 'Año', ...($this->payload['months'] ?? [])];

        foreach (($this->payload['lines'] ?? []) as $line) {
            foreach ([2025, 2026] as $year) {
                $rows[] = [
                    $line['linea'] ?? 'Sin linea',
                    $year,
                    ...collect($line['series'][$year] ?? [])->map(fn (array $point) => $this->number($point['value'] ?? null))->all(),
                ];
            }
        }

        return $rows;
    }

    private function monthlyRows(): array
    {
        $rows = $this->filterRows('LEF 4 semanas - Lineas completas');
        $rows[] = [];
        $rows[] = ['Linea', 'Año', ...($this->payload['months'] ?? [])];

        foreach (($this->payload['line_totals'] ?? []) as $line) {
            foreach ([2025, 2026] as $year) {
                $rows[] = [
                    $line['linea'] ?? 'Sin linea',
                    $year,
                    ...collect($line['series'][$year] ?? [])->map(fn (array $point) => $this->number($point['value'] ?? null))->all(),
                ];
            }
        }

        return $rows;
    }

    private function filterRows(string $title): array
    {
        $filters = $this->payload['filters'] ?? [];

        return [
            [$title],
            ['Periodo', $filters['period'] ?? ''],
            ['Busqueda', $filters['search'] ?? ''],
            ['Resultado', $filters['status'] ?? ''],
            ['Linea enfocada', ($this->payload['focused_line_name'] ?? null) ?: 'Ninguna'],
            ['Generado', $this->payload['generated_at'] ?? ''],
        ];
    }

    private function number(mixed $value): string
    {
        return $value === null ? 'N/A' : number_format((float) $value, 2, '.', ',');
    }
}
