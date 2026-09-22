<?php

namespace App\Http\Controllers;

use App\Models\Lef52124Import;
use App\Models\Lef52124Item;
use App\Models\Linea;
use App\Models\User;
use App\Services\Lef52124ImportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class Lef52124Controller extends Controller
{
    private const INITIAL_LINES = ['L-04', 'L-05', 'L-06', 'L-07', 'L-09', 'L-12', 'L-13'];

    public function index(Request $request): View
    {
        $lineas = $this->lineas();
        $selectedLineaId = (int) ($request->integer('linea_id') ?: $this->defaultLineaId($lineas));
        $canManage = $this->canManage($request->user());
        $imports = $canManage
            ? Lef52124Import::with(['linea:id,nombre', 'user:id,name'])
                ->latest()
                ->take(25)
                ->get()
            : collect();

        return view('analisis-52-12-4.index', compact('lineas', 'selectedLineaId', 'canManage', 'imports'));
    }

    public function periods(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'linea_id' => ['required', 'integer', 'exists:lineas,id'],
        ]);

        $imports = Lef52124Import::where('linea_id', $validated['linea_id'])
            ->where('status', 'success')
            ->orderByDesc('data_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Lef52124Import $import) => $this->importPayload($import));

        return response()->json([
            'items' => $imports,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'linea_id' => ['nullable', 'integer', 'exists:lineas,id'],
            'import_id' => ['nullable', 'integer', 'exists:lef52124_imports,id'],
            'data_date' => ['nullable', 'date_format:Y-m-d'],
            'period' => ['nullable', 'in:all,52,12,4'],
            'analysis_type' => ['nullable', 'in:all,machines,parts,washer,comparison'],
        ]);

        $lineas = $this->lineas();
        $lineaId = (int) ($validated['linea_id'] ?? $this->defaultLineaId($lineas));
        $period = $validated['period'] ?? 'all';
        $analysisType = $validated['analysis_type'] ?? 'all';
        $import = $this->resolveImport($lineaId, $validated['import_id'] ?? null, $validated['data_date'] ?? null);

        if (!$import) {
            return response()->json([
                'has_data' => false,
                'message' => 'No existen datos 52-12-4 para la linea seleccionada.',
                'lineas' => $lineas->map(fn (Linea $linea) => ['id' => $linea->id, 'nombre' => $linea->nombre])->values(),
                'imports' => [],
            ]);
        }

        $items = $import->items()->get();
        $sortColumn = $this->periodColumn($period);
        $machines = $this->itemsPayload(
            $items->where('type', Lef52124Item::TYPE_MACHINE)->values(),
            $sortColumn
        );
        $parts = $this->itemsPayload($items->where('type', Lef52124Item::TYPE_PART)->values(), $sortColumn);
        $washer = $items->where('type', Lef52124Item::TYPE_LINE)->values()->first();

        return response()->json([
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
        ]);
    }

    public function store(Request $request, Lef52124ImportService $service): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'linea_id' => ['required', 'integer', 'exists:lineas,id'],
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
            ->route('lef52124.index', ['linea_id' => $validated['linea_id']])
            ->with('success', 'Importacion 52-12-4 completada correctamente.');
    }

    public function destroy(Request $request, Lef52124Import $import): RedirectResponse
    {
        $this->ensureAdmin($request);
        $lineaId = $import->linea_id;
        $import->delete();

        return redirect()
            ->route('lef52124.index', ['linea_id' => $lineaId])
            ->with('success', 'Importacion eliminada correctamente.');
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
        $latestImport = Lef52124Import::whereIn('linea_id', $lineas->pluck('id'))
            ->where('status', 'success')
            ->latest('data_date')
            ->latest()
            ->first();

        return $latestImport?->linea_id ?: $lineas->first()?->id;
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
