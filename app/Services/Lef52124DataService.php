<?php

namespace App\Services;

use App\Models\Lef52124Import;
use App\Models\Lef52124Item;
use App\Models\Linea;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Lef52124DataService
{
    private const INITIAL_LINES = ['L-04', 'L-05', 'L-06', 'L-07', 'L-09', 'L-12', 'L-13'];

    public function lineas(): Collection
    {
        $lineas = Linea::whereIn('nombre', self::INITIAL_LINES)
            ->where('activo', true)
            ->get()
            ->sortBy(fn (Linea $linea) => array_search($linea->nombre, self::INITIAL_LINES, true))
            ->values();

        if ($lineas->isNotEmpty()) {
            return $lineas;
        }

        return Linea::where('activo', true)
            ->where('nombre', 'like', 'L-%')
            ->orderBy('nombre')
            ->get();
    }

    public function defaultLineaId(Collection $lineas): ?int
    {
        return $lineas->first()?->id;
    }

    public function periodsPayload(int $lineaId): array
    {
        return [
            'items' => Lef52124Import::where('linea_id', $lineaId)
                ->where('status', 'success')
                ->orderByDesc('data_date')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Lef52124Import $import) => $this->importPayload($import))
                ->values(),
        ];
    }

    public function dataPayload(array $filters): array
    {
        $lineas = $this->lineas();
        $lineaId = (int) ($filters['linea_id'] ?? $this->defaultLineaId($lineas));
        $period = $filters['period'] ?? 'all';
        $import = $this->resolveImport($lineaId, $filters['import_id'] ?? null, $filters['data_date'] ?? null);

        if (!$import) {
            return [
                'has_data' => false,
                'message' => 'No existen datos 52-12-4 para la linea seleccionada.',
                'lineas' => $lineas->map(fn (Linea $linea) => ['id' => $linea->id, 'nombre' => $linea->nombre])->values(),
                'imports' => [],
            ];
        }

        $items = $import->items()->get();
        $sortColumn = $this->periodColumn($period);
        $machines = $this->itemsPayload(
            $items->where('type', Lef52124Item::TYPE_MACHINE)->values(),
            $sortColumn
        );
        $parts = $this->itemsPayload($items->where('type', Lef52124Item::TYPE_PART)->values(), $sortColumn);
        $washer = $items->where('type', Lef52124Item::TYPE_LINE)->values()->first();

        return [
            'has_data' => true,
            'linea' => [
                'id' => $import->linea_id,
                'nombre' => $import->linea?->nombre,
            ],
            'import' => $this->importPayload($import),
            'imports' => Lef52124Import::where('linea_id', $import->linea_id)
                ->where('status', 'success')
                ->orderByDesc('data_date')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Lef52124Import $item) => $this->importPayload($item))
                ->values(),
            'summary' => $this->summaryPayload($import, $items),
            'machines' => $machines,
            'parts' => $parts,
            'washer' => $washer ? $this->itemPayload($washer) : null,
            'comparison' => $this->comparisonPayload($lineas, $import->data_date),
        ];
    }

    public function washerMachineTrendPayload(int $lineaId): array
    {
        $years = [2025, 2026];
        $imports = Lef52124Import::with([
            'linea:id,nombre',
            'items' => fn ($query) => $query->where('type', Lef52124Item::TYPE_MACHINE),
        ])
            ->where('linea_id', $lineaId)
            ->where('status', 'success')
            ->whereBetween('data_date', ['2025-01-01', '2026-12-31'])
            ->orderByDesc('data_date')
            ->orderByDesc('created_at')
            ->get();

        $machineName = null;
        $series = collect($years)
            ->mapWithKeys(function (int $year) use ($imports, &$machineName) {
                $months = collect(range(1, 12))
                    ->map(function (int $month) use ($imports, $year, &$machineName) {
                        $monthImports = $imports
                            ->filter(fn (Lef52124Import $item) => (int) $item->data_date->year === $year
                                && (int) $item->data_date->month === $month);
                        $import = null;
                        $machine = null;

                        foreach ($monthImports as $candidateImport) {
                            $candidateMachine = $candidateImport->items
                                ->first(fn (Lef52124Item $item) => str_contains($this->normalizeName($item->item_name), 'lavadora'));

                            if ($candidateMachine) {
                                $import = $candidateImport;
                                $machine = $candidateMachine;
                                break;
                            }
                        }

                        if ($machine && !$machineName) {
                            $machineName = $machine->item_name;
                        }

                        return [
                            'month' => $month,
                            'import_id' => $import?->id,
                            'data_date' => $import?->data_date?->format('Y-m-d'),
                            'value_52_weeks' => $machine ? (float) $machine->value_52_weeks : null,
                            'value_12_weeks' => $machine ? (float) $machine->value_12_weeks : null,
                            'value_4_weeks' => $machine ? (float) $machine->value_4_weeks : null,
                        ];
                    })
                    ->values();

                return [$year => $months];
            })
            ->all();

        $hasData = collect($series)
            ->flatten(1)
            ->contains(fn (array $month) => $month['value_52_weeks'] !== null
                || $month['value_12_weeks'] !== null
                || $month['value_4_weeks'] !== null);

        return [
            'has_data' => $hasData,
            'linea' => $imports->first()?->linea?->nombre ?: Linea::find($lineaId)?->nombre,
            'machine_name' => $machineName ?: 'LAVADORA',
            'years' => $years,
            'months' => [
                'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic',
            ],
            'series' => $series,
        ];
    }

    public function powerBiPayload(array $filters): array
    {
        $lineas = $this->lineas();
        $lineaIds = $lineas->pluck('id');

        $query = Lef52124Import::query()
            ->select([
                'id',
                'linea_id',
                'data_date',
                'source_filename',
                'status',
                'machines_count',
                'parts_count',
                'line_items_count',
                'created_at',
                'updated_at',
            ])
            ->with([
                'linea:id,nombre',
                'items:id,lef52124_import_id,linea_id,type,item_name,value_52_weeks,value_12_weeks,value_4_weeks,created_at,updated_at',
            ])
            ->where('status', 'success')
            ->when($lineaIds->isNotEmpty(), fn ($builder) => $builder->whereIn('linea_id', $lineaIds))
            ->when(isset($filters['linea_id']), fn ($builder) => $builder->where('linea_id', $filters['linea_id']))
            ->when(isset($filters['import_id']), fn ($builder) => $builder->whereKey($filters['import_id']))
            ->when(isset($filters['data_date']), fn ($builder) => $builder->whereDate('data_date', $filters['data_date']))
            ->orderByDesc('data_date')
            ->orderByDesc('created_at');

        $imports = $query->get();
        $period = $filters['period'] ?? 'all';
        $analysisType = $filters['analysis_type'] ?? 'all';

        $historico = $imports
            ->flatMap(fn (Lef52124Import $import) => $import->items->map(fn (Lef52124Item $item) => $this->powerBiItemPayload($import, $item)))
            ->filter(fn (array $row) => $this->passesAnalysisType($row['tipo'], $analysisType))
            ->sortByDesc(fn (array $row) => [$row['fecha'], $row['import_id'], $row[$this->powerBiValueKey($period)] ?? $row['valor_52']])
            ->values();

        $targetDate = $filters['data_date'] ?? $imports->max(fn (Lef52124Import $import) => $import->data_date?->format('Y-m-d'));
        $comparativo = $targetDate
            ? $this->powerBiComparisonPayload($lineas, Carbon::createFromFormat('Y-m-d', $targetDate))
            : [];

        $trendLineas = isset($filters['linea_id'])
            ? $lineas->where('id', (int) $filters['linea_id'])->values()
            : $lineas;

        return [
            'lineas' => $lineas
                ->map(fn (Linea $linea) => [
                    'linea_id' => $linea->id,
                    'linea' => $linea->nombre,
                ])
                ->values(),
            'importaciones' => $imports
                ->map(fn (Lef52124Import $import) => $this->powerBiImportPayload($import))
                ->values(),
            'maquinas' => $historico->where('tipo', 'maquina')->values(),
            'partes' => $historico->where('tipo', 'parte')->values(),
            'linea_general' => $historico->where('tipo', 'linea')->values(),
            'comparativo_lineas' => $comparativo,
            'tendencia_lavadora' => $trendLineas
                ->flatMap(fn (Linea $linea) => $this->powerBiTrendRows($linea))
                ->values(),
            'historico' => $historico,
        ];
    }

    private function resolveImport(int $lineaId, ?int $importId, ?string $dataDate = null): ?Lef52124Import
    {
        $query = Lef52124Import::with(['linea:id,nombre'])
            ->where('linea_id', $lineaId)
            ->where('status', 'success');

        if ($importId) {
            return (clone $query)->whereKey($importId)->first();
        }

        if ($dataDate) {
            return (clone $query)
                ->whereDate('data_date', $dataDate)
                ->orderByDesc('created_at')
                ->first();
        }

        return $query
            ->orderByDesc('data_date')
            ->orderByDesc('created_at')
            ->first();
    }

    private function summaryPayload(Lef52124Import $import, Collection $items): array
    {
        $machines = $items->where('type', Lef52124Item::TYPE_MACHINE)->values();
        $washer = $items->where('type', Lef52124Item::TYPE_LINE)->values()->first();

        return [
            'linea' => $import->linea?->nombre,
            'top_52' => $this->topItem($machines, 'value_52_weeks'),
            'top_12' => $this->topItem($machines, 'value_12_weeks'),
            'top_4' => $this->topItem($machines, 'value_4_weeks'),
            'washer_52' => $washer ? $this->formatNumber($washer->value_52_weeks) : null,
            'washer_12' => $washer ? $this->formatNumber($washer->value_12_weeks) : null,
            'washer_4' => $washer ? $this->formatNumber($washer->value_4_weeks) : null,
            'updated_at' => $import->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function topItem(Collection $items, string $column): ?array
    {
        $item = $items->sortByDesc(fn (Lef52124Item $row) => (float) $row->{$column})->first();

        if (!$item) {
            return null;
        }

        return [
            'name' => $item->item_name,
            'value' => $this->formatNumber($item->{$column}),
        ];
    }

    private function itemsPayload(Collection $items, string $sortColumn, bool $highlightWasher = false): array
    {
        return $items
            ->sortByDesc(fn (Lef52124Item $item) => (float) $item->{$sortColumn})
            ->values()
            ->map(fn (Lef52124Item $item) => array_merge($this->itemPayload($item), [
                'highlight' => $highlightWasher && str_contains($this->normalizeName($item->item_name), 'lavadora de botella'),
            ]))
            ->all();
    }

    private function itemPayload(Lef52124Item $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->item_name,
            'value_52_weeks' => (float) $item->value_52_weeks,
            'value_12_weeks' => (float) $item->value_12_weeks,
            'value_4_weeks' => (float) $item->value_4_weeks,
            'formatted_52' => $this->formatNumber($item->value_52_weeks),
            'formatted_12' => $this->formatNumber($item->value_12_weeks),
            'formatted_4' => $this->formatNumber($item->value_4_weeks),
        ];
    }

    private function comparisonPayload(Collection $lineas, Carbon $targetDate): array
    {
        $lineaIds = $lineas->pluck('id');
        $imports = Lef52124Import::with(['linea:id,nombre', 'items' => fn ($query) => $query->where('type', Lef52124Item::TYPE_LINE)])
            ->whereIn('linea_id', $lineaIds)
            ->where('status', 'success')
            ->whereDate('data_date', '<=', $targetDate->toDateString())
            ->orderByDesc('data_date')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('linea_id')
            ->map(fn (Collection $group) => $group->first());

        return $lineas
            ->map(function (Linea $linea) use ($imports) {
                $import = $imports->get($linea->id);
                $washer = $import?->items->first();

                return [
                    'linea_id' => $linea->id,
                    'linea' => $linea->nombre,
                    'import_id' => $import?->id,
                    'data_date' => $import?->data_date?->format('Y-m-d'),
                    'value_52_weeks' => $washer ? (float) $washer->value_52_weeks : null,
                    'value_12_weeks' => $washer ? (float) $washer->value_12_weeks : null,
                    'value_4_weeks' => $washer ? (float) $washer->value_4_weeks : null,
                ];
            })
            ->values()
            ->all();
    }

    private function importPayload(Lef52124Import $import): array
    {
        return [
            'id' => $import->id,
            'data_date' => $import->data_date?->format('Y-m-d'),
            'data_date_label' => $import->data_date?->format('d/m/Y'),
            'source_filename' => $import->source_filename,
            'created_at' => $import->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function powerBiItemPayload(Lef52124Import $import, Lef52124Item $item): array
    {
        return [
            'id' => $item->id,
            'import_id' => $import->id,
            'linea_id' => $import->linea_id,
            'linea' => $import->linea?->nombre,
            'tipo' => $this->powerBiType($item->type),
            'nombre' => $item->item_name,
            'valor_52' => (float) $item->value_52_weeks,
            'valor_12' => (float) $item->value_12_weeks,
            'valor_4' => (float) $item->value_4_weeks,
            'fecha' => $import->data_date?->format('Y-m-d'),
            'source_filename' => $import->source_filename,
            'created_at' => $import->created_at?->toIso8601String(),
        ];
    }

    private function powerBiImportPayload(Lef52124Import $import): array
    {
        return [
            'import_id' => $import->id,
            'linea_id' => $import->linea_id,
            'linea' => $import->linea?->nombre,
            'fecha' => $import->data_date?->format('Y-m-d'),
            'source_filename' => $import->source_filename,
            'machines_count' => (int) $import->machines_count,
            'parts_count' => (int) $import->parts_count,
            'line_items_count' => (int) $import->line_items_count,
            'created_at' => $import->created_at?->toIso8601String(),
        ];
    }

    private function powerBiComparisonPayload(Collection $lineas, Carbon $targetDate): array
    {
        return collect($this->comparisonPayload($lineas, $targetDate))
            ->map(fn (array $row) => [
                'linea_id' => $row['linea_id'],
                'linea' => $row['linea'],
                'tipo' => 'comparativo_linea',
                'import_id' => $row['import_id'],
                'valor_52' => $row['value_52_weeks'],
                'valor_12' => $row['value_12_weeks'],
                'valor_4' => $row['value_4_weeks'],
                'fecha' => $row['data_date'],
            ])
            ->values()
            ->all();
    }

    private function powerBiTrendRows(Linea $linea): Collection
    {
        $payload = $this->washerMachineTrendPayload((int) $linea->id);

        return collect($payload['series'])
            ->flatMap(function (Collection|array $months, int|string $year) use ($linea, $payload) {
                return collect($months)->map(fn (array $month) => [
                    'linea_id' => $linea->id,
                    'linea' => $payload['linea'] ?: $linea->nombre,
                    'maquina' => $payload['machine_name'],
                    'anio' => (int) $year,
                    'mes' => (int) $month['month'],
                    'import_id' => $month['import_id'],
                    'fecha' => $month['data_date'],
                    'valor_52' => $month['value_52_weeks'],
                    'valor_12' => $month['value_12_weeks'],
                    'valor_4' => $month['value_4_weeks'],
                ]);
            })
            ->values();
    }

    private function passesAnalysisType(string $tipo, string $analysisType): bool
    {
        return match ($analysisType) {
            'machines' => $tipo === 'maquina',
            'parts' => $tipo === 'parte',
            'washer' => $tipo === 'linea',
            'comparison' => false,
            default => true,
        };
    }

    private function powerBiType(string $type): string
    {
        return match ($type) {
            Lef52124Item::TYPE_MACHINE => 'maquina',
            Lef52124Item::TYPE_PART => 'parte',
            Lef52124Item::TYPE_LINE => 'linea',
            default => $type,
        };
    }

    private function powerBiValueKey(string $period): string
    {
        return match ($period) {
            '12' => 'valor_12',
            '4' => 'valor_4',
            default => 'valor_52',
        };
    }

    private function periodColumn(string $period): string
    {
        return match ($period) {
            '12' => 'value_12_weeks',
            '4' => 'value_4_weeks',
            default => 'value_52_weeks',
        };
    }

    private function formatNumber(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function normalizeName(string $value): string
    {
        return Str::of(Str::ascii($value))->lower()->toString();
    }
}
