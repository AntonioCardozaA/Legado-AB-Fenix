<?php

namespace App\Exports;

use App\Exports\Sheets\Lef52124WorkbookSheet;
use App\Models\Lef52124Import;
use App\Models\Lef52124Item;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Lef52124WorkbookExport implements WithMultipleSheets
{
    private const MONTHS = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    private const WINDOWS = [
        '52' => ['label' => '52 semanas', 'column' => 'value_52_weeks'],
        '12' => ['label' => '12 semanas', 'column' => 'value_12_weeks'],
        '4' => ['label' => '4 semanas', 'column' => 'value_4_weeks'],
    ];

    public function __construct(
        private readonly Collection $imports,
        private readonly Collection $lineas,
        private readonly CarbonInterface $startDate,
        private readonly CarbonInterface $endDate
    ) {
    }

    public function sheets(): array
    {
        $washerByLineAndMonth = $this->washerByLineAndMonth();
        $coverage = 'Cobertura: '.$this->startDate->format('d/m/Y').' al '.$this->endDate->format('d/m/Y').' | Todas las lineas';
        $sheets = [];

        foreach (self::WINDOWS as $key => $window) {
            $rows = [['Linea', 'Anio', 'Mes', 'Fecha de corte', 'LEF de Lavadora ('.$window['label'].')']];

            foreach ($this->monthsInRange() as $month) {
                foreach ($this->lineas as $linea) {
                    $import = $washerByLineAndMonth[$linea->id][$month->format('Y-m')] ?? null;
                    $washer = $import?->items->first(
                        fn (Lef52124Item $item) => $item->type === Lef52124Item::TYPE_MACHINE
                            && Str::contains(Str::ascii(Str::lower($item->item_name)), 'lavadora')
                    );

                    $rows[] = [
                        $linea->nombre,
                        (int) $month->format('Y'),
                        self::MONTHS[(int) $month->format('n')],
                        $import?->data_date?->format('Y-m-d'),
                        $washer ? (float) $washer->{$window['column']} : null,
                    ];
                }
            }

            $sheets[] = new Lef52124WorkbookSheet(
                'Lavadora '.$key.' semanas',
                'Tendencia de Lavadora - '.$window['label'],
                $coverage,
                $rows,
                5
            );
        }

        $sheets[] = new Lef52124WorkbookSheet(
            'Todas las maquinas',
            'Todas las maquinas - comparativo 52-12-4',
            $coverage,
            $this->allMachineRows(),
            5
        );

        return $sheets;
    }

    private function washerByLineAndMonth(): array
    {
        $latest = [];

        foreach ($this->imports as $import) {
            $hasWasher = $import->items->contains(
                fn (Lef52124Item $item) => $item->type === Lef52124Item::TYPE_MACHINE
                    && Str::contains(Str::ascii(Str::lower($item->item_name)), 'lavadora')
            );

            if ($hasWasher && $import->data_date) {
                $latest[$import->linea_id][$import->data_date->format('Y-m')] = $import;
            }
        }

        return $latest;
    }

    private function monthsInRange(): array
    {
        $months = [];
        $month = $this->startDate->copy()->startOfMonth();
        $lastMonth = $this->endDate->copy()->startOfMonth();

        while ($month->lessThanOrEqualTo($lastMonth)) {
            $months[] = $month->copy();
            $month->addMonth();
        }

        return $months;
    }

    private function allMachineRows(): array
    {
        $rows = [['Linea', 'Fecha de datos', 'Posicion', 'Maquina', 'LEF 52 semanas', 'LEF 12 semanas', 'LEF 4 semanas']];
        $lineOrder = $this->lineas->values()->mapWithKeys(fn ($linea, $index) => [$linea->id => $index]);

        $imports = $this->imports
            ->sortBy(fn (Lef52124Import $import) => [
                $lineOrder->get($import->linea_id, PHP_INT_MAX),
                $import->data_date?->format('Y-m-d') ?? '',
                $import->created_at?->format('Y-m-d H:i:s') ?? '',
            ]);

        foreach ($imports as $import) {
            $machines = $import->items
                ->where('type', Lef52124Item::TYPE_MACHINE)
                ->sortByDesc(fn (Lef52124Item $item) => (float) $item->value_52_weeks)
                ->values();

            foreach ($machines as $index => $machine) {
                $rows[] = [
                    $import->linea?->nombre ?? 'Sin linea',
                    $import->data_date?->format('Y-m-d'),
                    $index + 1,
                    $machine->item_name,
                    (float) $machine->value_52_weeks,
                    (float) $machine->value_12_weeks,
                    (float) $machine->value_4_weeks,
                ];
            }
        }

        return $rows;
    }
}
