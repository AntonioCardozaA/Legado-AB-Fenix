<?php

namespace App\Http\Controllers;

use App\Exports\Lef52124SingleImportExport;
use App\Exports\Lef52124WorkbookExport;
use App\Models\Lef52124CostEntry;
use App\Models\Lef52124Import;
use App\Models\Lef52124Item;
use App\Models\Linea;
use App\Models\User;
use App\Services\Lef52124CostImportService;
use App\Services\Lef52124DataService;
use App\Services\Lef52124ImportService;
use App\Support\Lef52124Equipment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class Lef52124Controller extends Controller
{
    protected const EQUIPMENT = Lef52124Equipment::Lavadora;

    private readonly Lef52124DataService $dataService;

    public function __construct()
    {
        $this->dataService = new Lef52124DataService(static::EQUIPMENT);
    }

    public function index(Request $request): View
    {
        $equipment = static::EQUIPMENT;
        $lineas = $this->dataService->lineas();
        $requestedLineaId = $request->integer('linea_id');
        $selectedLineaId = 'all';
        if ($request->query('scope') !== 'all') {
            $belongsToEquipment = $requestedLineaId && ($lineas->contains('id', $requestedLineaId)
                || $equipment->lineas()->whereKey($requestedLineaId)->exists());
            $selectedLineaId = $belongsToEquipment ? $requestedLineaId : (int) $this->dataService->defaultLineaId($lineas);
        }
        $canManage = $this->canManage($request->user());
        $routePrefix = $equipment->routePrefix();
        $imports = $canManage
            ? Lef52124Import::with(['linea:id,nombre', 'user:id,name'])
                ->whereIn('linea_id', $equipment->lineas()->select('id'))
                ->latest()
                ->paginate(10, ['*'], 'importaciones_page')
                ->withQueryString()
                ->fragment('historial-importaciones')
            : collect();

        return view('analisis-52-12-4.index', compact('lineas', 'selectedLineaId', 'canManage', 'imports', 'equipment', 'routePrefix'));
    }

    public function periods(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => ['nullable', 'in:all'],
            'linea_id' => ['required_without:scope', 'integer', $this->equipmentLineRule()],
        ]);

        return response()->json($this->dataService->periodsPayload(
            ($validated['scope'] ?? null) === 'all' ? null : (int) $validated['linea_id']
        ));
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => ['nullable', 'in:all'],
            'linea_id' => ['nullable', 'integer', $this->equipmentLineRule()],
            'import_id' => [
                'nullable',
                'integer',
                Rule::exists('lef52124_imports', 'id')
                    ->where(fn ($query) => $query->whereIn('linea_id', static::EQUIPMENT->lineas()->select('id'))),
            ],
            'data_date' => ['nullable', 'date_format:Y-m-d'],
            'period' => ['nullable', 'in:all,52,12,4'],
            'analysis_type' => ['nullable', 'in:all,machines,parts,washer,comparison'],
        ]);

        return response()->json($this->dataService->dataPayload($validated));
    }

    public function exportExcel()
    {
        $lineas = $this->dataService->lineas();
        $lineaIds = $lineas->modelKeys();
        $startDate = Carbon::create(2025, 1, 1)->startOfDay();
        $endDate = now()->endOfDay();
        $imports = Lef52124Import::with([
            'linea:id,nombre',
            'items' => fn ($query) => $query
                ->whereIn('type', [Lef52124Item::TYPE_MACHINE, Lef52124Item::TYPE_LINE])
                ->orderBy('item_name'),
        ])
            ->whereIn('linea_id', $lineaIds)
            ->where('status', 'success')
            ->whereBetween('data_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('data_date')
            ->orderBy('created_at')
            ->get();

        if ($imports->isEmpty()) {
            return redirect()
                ->route(static::EQUIPMENT->routePrefix().'index')
                ->with('warning', 'No hay datos 52-12-4 entre 2025 y la fecha actual de 2026 para descargar.');
        }

        return Excel::download(
            new Lef52124WorkbookExport($imports, $lineas, $startDate, $endDate, static::EQUIPMENT),
            '52-12-4-todas-las-lineas-2025-a-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function download(Request $request, Lef52124Import $import)
    {
        $this->ensureAdmin($request);
        $this->ensureEquipmentImport($import);
        $import->load(['items', 'linea:id,nombre']);

        if ($import->source_path && Storage::disk('local')->exists($import->source_path)) {
            return Storage::disk('local')->download($import->source_path, $import->source_filename);
        }

        $filename = pathinfo($import->source_filename, PATHINFO_FILENAME).'.xlsx';

        return Excel::download(new Lef52124SingleImportExport($import), $filename);
    }

    public function washerMachineTrend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => ['nullable', 'in:all'],
            'linea_id' => ['required_without:scope', 'integer', $this->equipmentLineRule()],
        ]);

        return response()->json($this->dataService->washerMachineTrendPayload(
            ($validated['scope'] ?? null) === 'all' ? null : (int) $validated['linea_id']
        ));
    }

    public function costData(Request $request): JsonResponse
    {
        $request->validate([
            'linea_id' => ['nullable', 'integer', $this->equipmentLineRule()],
        ]);

        $lineaId = $request->integer('linea_id') ?: null;
        $entries = Lef52124CostEntry::query()
            ->with('linea:id,nombre')
            ->where('equipment', static::EQUIPMENT->value)
            ->whereIn('year', [2025, 2026])
            ->when($lineaId, fn ($query) => $query->where('linea_id', $lineaId))
            ->get();

        $monthLabels = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];
        $monthly = collect(range(1, 12))->map(function (int $month) use ($entries, $monthLabels): array {
            $rows = $entries->where('month', $month);

            return [
                'month' => $month,
                'label' => $monthLabels[$month],
                '2025' => round((float) $rows->where('year', 2025)->sum('amount'), 2),
                '2026' => round((float) $rows->where('year', 2026)->sum('amount'), 2),
            ];
        })->values();

        $byLine = $entries
            ->groupBy(fn (Lef52124CostEntry $entry) => $entry->linea_id ?: 'unassigned')
            ->map(function (Collection $rows): array {
                return [
                    'linea' => $rows->first()->linea?->nombre ?? 'Sin linea asignada',
                    '2025' => round((float) $rows->where('year', 2025)->sum('amount'), 2),
                    '2026' => round((float) $rows->where('year', 2026)->sum('amount'), 2),
                    'total' => round((float) $rows->sum('amount'), 2),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $total2025 = round((float) $entries->where('year', 2025)->sum('amount'), 2);
        $total2026 = round((float) $entries->where('year', 2026)->sum('amount'), 2);
        $savings = round($total2025 - $total2026, 2);

        return response()->json([
            'has_data' => $entries->isNotEmpty(),
            'linea' => $lineaId ? Linea::find($lineaId)?->nombre : 'Todas las '.static::EQUIPMENT->value.'s',
            'summary' => [
                'total' => round((float) $entries->sum('amount'), 2),
                '2025' => $total2025,
                '2026' => $total2026,
                'savings' => $savings,
                'savings_percent' => $total2025 > 0 ? round(($savings / $total2025) * 100, 2) : 0,
                'records' => $entries->count(),
            ],
            'monthly' => $monthly,
            'by_line' => $byLine,
        ]);
    }

    public function importCosts(Request $request, Lef52124CostImportService $service): RedirectResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'fallback_linea_id' => ['nullable', 'integer', $this->equipmentLineRule()],
            'archivo_costos' => ['required', 'file', 'mimes:xls,xlsx', 'max:20480'],
        ]);

        try {
            $service->import(
                $request->file('archivo_costos'),
                isset($validated['fallback_linea_id']) ? Linea::findOrFail($validated['fallback_linea_id']) : null,
                $request->user(),
                static::EQUIPMENT
            );
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['archivo_costos' => $exception->getMessage()]);
        }

        return redirect()
            ->route(static::EQUIPMENT->routePrefix().'index', array_filter(['linea_id' => $validated['fallback_linea_id'] ?? null], fn ($value) => $value !== null))
            ->with('success', 'Importacion de costos completada correctamente.');
    }

    public function store(Request $request, Lef52124ImportService $service): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'linea_id' => ['required', 'integer', $this->equipmentLineRule()],
            'data_date' => ['required', 'date_format:Y-m-d'],
            'observations' => ['nullable', 'string', 'max:1000'],
            'archivo' => ['required', 'file', 'mimes:xls,xlsx', 'max:20480'],
        ]);

        try {
            $service->import(
                $request->file('archivo'),
                Linea::findOrFail($validated['linea_id']),
                Carbon::createFromFormat('Y-m-d', $validated['data_date']),
                $validated['observations'] ?? null,
                $request->user()
            );
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'archivo' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route(static::EQUIPMENT->routePrefix().'index', ['linea_id' => $validated['linea_id']])
            ->with('success', 'Importacion 52-12-4 completada correctamente.');
    }

    public function destroy(Request $request, Lef52124Import $import): RedirectResponse
    {
        $this->ensureAdmin($request);
        $this->ensureEquipmentImport($import);
        $lineaId = $import->linea_id;
        $sourcePath = $import->source_path;
        $import->delete();

        if ($sourcePath) {
            Storage::disk('local')->delete($sourcePath);
        }

        return redirect()
            ->route(static::EQUIPMENT->routePrefix().'index', ['linea_id' => $lineaId])
            ->with('success', 'Importacion eliminada correctamente.');
    }

    private function equipmentLineRule(): Exists
    {
        return Rule::exists('lineas', 'id')
            ->where(fn ($query) => $query->where('nombre', 'like', static::EQUIPMENT->linePrefix().'-%'));
    }

    private function ensureEquipmentImport(Lef52124Import $import): void
    {
        abort_unless(static::EQUIPMENT->lineas()->whereKey($import->linea_id)->exists(), 404);
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

    private function lineas(): Collection
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

    private function defaultLineaId(Collection $lineas): ?int
    {
        return $lineas->first()?->id;
    }

    private function normalizeName(string $value): string
    {
        return Str::of(Str::ascii($value))->lower()->toString();
    }

    private function canManage(?User $user): bool
    {
        return (bool) $user?->hasRole(User::ROLE_ADMIN);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($this->canManage($request->user()), 403, 'Solo administradores pueden gestionar importaciones 52-12-4.');
    }
}
