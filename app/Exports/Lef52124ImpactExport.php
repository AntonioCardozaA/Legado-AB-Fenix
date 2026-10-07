<?php

namespace App\Exports;

use App\Exports\Sheets\Lef52124ImpactSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Lef52124ImpactExport implements WithMultipleSheets
{
    public function __construct(private readonly array $payload)
    {
    }

    public function sheets(): array
    {
        return [
            new Lef52124ImpactSheet('Resumen Ejecutivo', $this->summaryRows()),
            new Lef52124ImpactSheet('Tendencias Lavadoras', $this->washerTrendRows()),
            new Lef52124ImpactSheet('Analisis Costos', $this->costRows()),
            new Lef52124ImpactSheet('Analisis Eficiencia', $this->efficiencyRows()),
        ];
    }

    private function summaryRows(): array
    {
        $filters = $this->payload['filters'] ?? [];
        $costs = $this->payload['costs']['kpis'] ?? [];
        $efficiency = $this->payload['efficiency']['kpis'] ?? [];
        $architecture = $this->payload['import_architecture'] ?? [];

        return [
            ['Reporte Ejecutivo Tendencias 2025 vs 2026'],
            ['Generado', $filters['generated_at'] ?? ''],
            ['Periodo', $filters['month_label'] ?? 'Anual'],
            ['Linea / lavadora', $filters['linea_label'] ?? 'Todas las lineas'],
            ['Equipo', $filters['equipment_label'] ?? 'Todos los equipos'],
            [],
            ['KPI', 'Valor'],
            ['Costo 2025', $this->money($costs['total_2025'] ?? 0)],
            ['Costo 2026', $this->money($costs['total_2026'] ?? 0)],
            ['Ahorro detectado', $this->money($costs['savings'] ?? 0)],
            ['Incremento detectado', $this->money($costs['increase'] ?? 0)],
            ['Reduccion / aumento', $this->percent($costs['reduction_percent'] ?? null)],
            ['Eficiencia promedio 2025', $this->percent($efficiency['average_2025'] ?? null)],
            ['Eficiencia promedio 2026', $this->percent($efficiency['average_2026'] ?? null)],
            ['Cambio eficiencia', $this->points($efficiency['delta_points'] ?? null)],
            ['Lineas con mejora', $efficiency['improved_lines'] ?? 0],
            ['Lineas con disminucion', $efficiency['worse_lines'] ?? 0],
            [],
            ['Arquitectura importacion Excel'],
            [$architecture['message'] ?? 'Pendiente de archivos muestra.'],
        ];
    }

    private function washerTrendRows(): array
    {
        $payload = $this->payload['washer_trends'] ?? [];
        $rows = [
            ['Tendencias de Lavadoras 52-12-4'],
            ['Periodo', $payload['period_label'] ?? '4 semanas'],
            [],
            ['Linea', 'Mes referencia', 'Valor 2025', 'Valor 2026', 'Variacion', 'Variacion %', 'Resultado'],
        ];

        foreach (($payload['lines'] ?? []) as $line) {
            $rows[] = [
                $line['linea'] ?? '',
                $payload['summary']['latest_month'] ?? '',
                $this->number($line['same_month_2025']['value'] ?? null),
                $this->number($line['latest_2026']['value'] ?? null),
                $this->number($line['variation']['delta'] ?? null),
                $this->percent($line['variation']['percent'] ?? null),
                $this->statusLabel($line['status'] ?? ''),
            ];
        }

        return $rows;
    }

    private function costRows(): array
    {
        $rows = [
            ['Analisis de Costos 2025 vs 2026'],
            [],
            ['Mes', 'Costo 2025', 'Costo 2026', 'Diferencia', 'Ahorro', 'Incremento', 'Reduccion %', 'Resultado'],
        ];

        foreach (($this->payload['costs']['rows'] ?? []) as $row) {
            $rows[] = [
                $row['month_label'] ?? '',
                $this->money($row['cost_2025'] ?? 0),
                $this->money($row['cost_2026'] ?? 0),
                $this->money($row['difference'] ?? 0),
                $this->money($row['savings'] ?? 0),
                $this->money($row['increase'] ?? 0),
                $this->percent($row['reduction_percent'] ?? null),
                $this->statusLabel($row['status'] ?? ''),
            ];
        }

        $rows[] = [];
        $rows[] = ['Costos por lavadora'];
        $rows[] = ['Lavadora', 'Costo 2025', 'Costo 2026', 'Diferencia', 'Reduccion %', 'Resultado'];

        foreach (($this->payload['costs']['by_line'] ?? []) as $line) {
            $rows[] = [
                $line['linea'] ?? 'Lavadora sin nombre',
                $this->money($line['cost_2025'] ?? 0),
                $this->money($line['cost_2026'] ?? 0),
                $this->money($line['difference'] ?? 0),
                $this->percent($line['reduction_percent'] ?? null),
                $this->statusLabel($line['status'] ?? ''),
            ];
        }

        return $rows;
    }

    private function efficiencyRows(): array
    {
        $rows = [
            ['Analisis de Eficiencia 2025 vs 2026'],
            [],
            ['Linea', 'Promedio 2025', 'Promedio 2026', 'Cambio puntos', 'Variacion %', 'Resultado'],
        ];

        foreach (($this->payload['efficiency']['lines'] ?? []) as $line) {
            $rows[] = [
                $line['linea'] ?? '',
                $this->percent($line['average_2025'] ?? null),
                $this->percent($line['average_2026'] ?? null),
                $this->points($line['delta_points'] ?? null),
                $this->percent($line['variation_percent'] ?? null),
                $this->statusLabel($line['status'] ?? ''),
            ];
        }

        $rows[] = [];
        $rows[] = ['Eficiencia mensual general'];
        $rows[] = ['Mes', 'Eficiencia 2025', 'Eficiencia 2026', 'Revisiones 2025', 'Revisiones 2026'];

        foreach (($this->payload['months'] ?? []) as $month) {
            $monthNumber = $month['number'] ?? null;
            $point2025 = collect($this->payload['efficiency']['general'][2025] ?? [])->firstWhere('month', $monthNumber);
            $point2026 = collect($this->payload['efficiency']['general'][2026] ?? [])->firstWhere('month', $monthNumber);
            $rows[] = [
                $month['label'] ?? '',
                $this->percent($point2025['value'] ?? null),
                $this->percent($point2026['value'] ?? null),
                $point2025['total'] ?? 0,
                $point2026['total'] ?? 0,
            ];
        }

        return $rows;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'saving', 'better' => 'Mejora',
            'increase', 'worse' => 'Revisar',
            'stable' => 'Sin cambio',
            default => 'Sin dato',
        };
    }

    private function money(mixed $value): string
    {
        return '$'.number_format((float) $value, 2, '.', ',');
    }

    private function percent(mixed $value): string
    {
        return $value === null ? 'N/A' : number_format((float) $value, 2, '.', ',').'%';
    }

    private function points(mixed $value): string
    {
        return $value === null ? 'N/A' : (($value > 0 ? '+' : '').number_format((float) $value, 2, '.', ',').' pp');
    }

    private function number(mixed $value): string
    {
        return $value === null ? 'N/A' : number_format((float) $value, 2, '.', ',');
    }
}
