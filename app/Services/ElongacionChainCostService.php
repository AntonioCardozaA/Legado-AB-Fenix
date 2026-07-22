<?php

namespace App\Services;

use App\Models\CostCatalogItem;
use App\Models\Elongacion;
use App\Models\LavadoraCostEntry;
use App\Support\LavadoraCostSupport;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ElongacionChainCostService
{
    private const REQUIREMENT_GROUPS = [
        'paso_140_reforzada' => [
            'lineas' => ['L-05', 'L-09', 'L-12', 'L-13'],
            'chain_type' => 'Cadena reforzada paso 140',
            'items' => [
                [
                    'sku' => '4051563',
                    'nombre' => 'CADENA PORTACANASTILLAS REFORZADA PASO 140',
                    'cantidad' => 280.0,
                    'descripcion' => 'Dos lados de cadena, 140 metros por lado.',
                ],
                [
                    'sku' => '4050797',
                    'nombre' => 'CANDADO PARA CADENA CANASTILLAS PASO 140',
                    'cantidad' => 2.0,
                    'descripcion' => 'Un candado por lado.',
                ],
                [
                    'sku' => '4050924',
                    'nombre' => 'MEDIO CANDADO PARA CADENA PORTACANASTILLAS PASO 140',
                    'cantidad' => 2.0,
                    'descripcion' => 'Un medio candado por lado.',
                ],
            ],
        ],
        'paso_173' => [
            'lineas' => ['L-04', 'L-06', 'L-07'],
            'chain_type' => 'Cadena paso 173',
            'items' => [
                [
                    'sku' => '4071701',
                    'nombre' => 'CADENA PORTACANASTILLAS, PASO 173',
                    'cantidad' => 346.0,
                    'descripcion' => 'Dos lados de cadena, 173 metros por lado.',
                ],
                [
                    'sku' => '4050646',
                    'nombre' => 'CANDADO PARA CADENA CANASTILLAS PASO 173.',
                    'cantidad' => 2.0,
                    'descripcion' => 'Un candado por lado.',
                ],
                [
                    'sku' => '4050647',
                    'nombre' => 'MEDIO ESLABON PARA CADENA CANASTILLAS PASO 173',
                    'cantidad' => 2.0,
                    'descripcion' => 'Un medio eslabon por lado.',
                ],
            ],
        ],
        'paso_125_reforzada' => [
            'lineas' => ['L-08'],
            'chain_type' => 'Cadena reforzada paso 125',
            'items' => [
                [
                    'sku' => '4065483',
                    'nombre' => 'CADENA REFORZADA PORTACANASTILLAS PASO 125',
                    'cantidad' => 250.0,
                    'descripcion' => 'Dos lados de cadena, 125 metros por lado.',
                ],
                [
                    'sku' => '4039789',
                    'nombre' => 'CANDADO COMPLETO PARA CADENA PORTACANASTILLAS PASO 125',
                    'cantidad' => 2.0,
                    'descripcion' => 'Un candado completo por lado.',
                ],
                [
                    'sku' => '4064202',
                    'nombre' => 'MEDIO ESLABON PARA CADENA PORTACANASTILLAS PASO 125',
                    'cantidad' => 2.0,
                    'descripcion' => 'Un medio eslabon por lado.',
                ],
            ],
        ],
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function requirementGroups(): array
    {
        return self::REQUIREMENT_GROUPS;
    }

    public function previewPayloadsForLineas(array $lineas): array
    {
        return collect($lineas)
            ->mapWithKeys(fn (string $linea) => [$linea => $this->buildPreview($linea)])
            ->all();
    }

    public function previewForLinea(string $linea): array
    {
        $preview = $this->buildPreview($linea);

        if ($preview['errors'] !== []) {
            throw ValidationException::withMessages([
                'nueva_cadena_costs' => $preview['errors'],
            ]);
        }

        return $preview;
    }

    public function syncInstallationCosts(Elongacion $elongacion): array
    {
        if (!$elongacion->linea_id) {
            throw ValidationException::withMessages([
                'linea' => 'La lavadora seleccionada no existe en el catalogo de lineas configuradas.',
            ]);
        }

        if (!$elongacion->cadena_ciclo_id) {
            throw ValidationException::withMessages([
                'nueva_cadena' => 'No fue posible relacionar el costo con el ciclo de cadena recien creado.',
            ]);
        }

        $preview = $this->previewForLinea($elongacion->linea);
        $elongacion->loadMissing(['lineaModel', 'cadenaCiclo']);

        $costDate = optional($elongacion->cadenaCiclo?->instalada_en ?? $elongacion->created_at)->toDateString()
            ?? now()->toDateString();
        $lineaNombre = $elongacion->lineaModel?->nombre ?? $elongacion->linea;
        $now = now();

        $rows = collect($preview['items'])->map(function (array $item) use ($elongacion, $preview, $costDate, $lineaNombre, $now) {
            return [
                'linea_id' => $elongacion->linea_id,
                'analisis_lavadora_id' => null,
                'elongacion_id' => $elongacion->id,
                'cadena_ciclo_id' => $elongacion->cadena_ciclo_id,
                'componente_id' => null,
                'catalog_item_id' => $item['catalog_item_id'],
                'source_type' => LavadoraCostEntry::SOURCE_CHAIN_INSTALLATION,
                'source_reference' => $elongacion->cadenaCiclo?->codigo ?? 'reinicio_ciclo',
                'cost_date' => $costDate,
                'quantity' => $item['cantidad'],
                'unit_cost' => $item['costo_unitario'],
                'total_cost' => $item['subtotal'],
                'component_snapshot' => 'Cadena de lavadora',
                'catalog_name_snapshot' => $item['nombre'],
                'catalog_sku_snapshot' => $item['sku'],
                'catalog_category_snapshot' => $item['categoria'],
                'unidad_medida_snapshot' => $item['unidad_medida'],
                'notas' => 'Instalacion nueva de cadena / reinicio de ciclo.',
                'metadata' => json_encode([
                    'linea_nombre' => $lineaNombre,
                    'linea_codigo' => $elongacion->linea,
                    'chain_type' => $preview['chain_type'],
                    'linea_group' => $preview['group_key'],
                    'elongacion_id' => $elongacion->id,
                    'cadena_ciclo_id' => $elongacion->cadena_ciclo_id,
                    'ciclo_codigo' => $elongacion->cadenaCiclo?->codigo,
                    'instalada_en' => $costDate,
                    'source' => 'elongacion',
                    'trigger' => 'Instalar nueva cadena / Reiniciar ciclo',
                ]),
                'sync_key' => sha1(implode('|', [
                    LavadoraCostEntry::SOURCE_CHAIN_INSTALLATION,
                    $elongacion->id,
                    $item['catalog_item_id'],
                ])),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        });

        LavadoraCostEntry::query()
            ->where('elongacion_id', $elongacion->id)
            ->where('source_type', LavadoraCostEntry::SOURCE_CHAIN_INSTALLATION)
            ->whereNotIn('sync_key', $rows->pluck('sync_key')->all())
            ->delete();

        LavadoraCostEntry::query()->upsert(
            $rows->all(),
            ['sync_key'],
            [
                'linea_id',
                'analisis_lavadora_id',
                'elongacion_id',
                'cadena_ciclo_id',
                'componente_id',
                'catalog_item_id',
                'source_type',
                'source_reference',
                'cost_date',
                'quantity',
                'unit_cost',
                'total_cost',
                'component_snapshot',
                'catalog_name_snapshot',
                'catalog_sku_snapshot',
                'catalog_category_snapshot',
                'unidad_medida_snapshot',
                'notas',
                'metadata',
                'updated_at',
            ]
        );

        return [
            'preview' => $preview,
            'entries' => LavadoraCostEntry::query()
                ->where('elongacion_id', $elongacion->id)
                ->where('source_type', LavadoraCostEntry::SOURCE_CHAIN_INSTALLATION)
                ->orderBy('id')
                ->get(),
        ];
    }

    private function buildPreview(string $linea): array
    {
        $group = $this->resolveGroup($linea);
        $requirements = collect($group['items']);
        $catalogItems = $this->resolveCatalogItems($requirements);
        $errors = [];

        $items = $requirements->map(function (array $requirement) use ($catalogItems, &$errors) {
            $catalogItem = $this->matchCatalogItem($catalogItems, $requirement);

            if (!$catalogItem) {
                $errors[] = 'No se encontro en el catalogo activo el material requerido: '
                    . ($requirement['sku'] ? "{$requirement['nombre']} (SKU {$requirement['sku']})" : $requirement['nombre']) . '.';

                return [
                    'catalog_item_id' => null,
                    'sku' => $requirement['sku'],
                    'nombre' => $requirement['nombre'],
                    'categoria' => 'Cadena',
                    'unidad_medida' => null,
                    'cantidad' => round((float) $requirement['cantidad'], 2),
                    'costo_unitario' => null,
                    'subtotal' => null,
                    'descripcion' => $requirement['descripcion'],
                ];
            }

            $unitCost = round((float) $catalogItem->costo_unitario, 2);

            if ($unitCost <= 0) {
                $errors[] = 'El material ' . $catalogItem->nombre . ' (SKU '
                    . ($catalogItem->sku ?: 'sin SKU') . ') no tiene un precio unitario valido.';
            }

            return [
                'catalog_item_id' => $catalogItem->id,
                'sku' => $catalogItem->sku,
                'nombre' => $catalogItem->nombre,
                'categoria' => $catalogItem->categoria,
                'unidad_medida' => $catalogItem->unidad_medida,
                'cantidad' => round((float) $requirement['cantidad'], 2),
                'costo_unitario' => $unitCost,
                'subtotal' => $unitCost > 0
                    ? round((float) $requirement['cantidad'] * $unitCost, 2)
                    : null,
                'descripcion' => $requirement['descripcion'],
            ];
        })->values();

        return [
            'linea' => $linea,
            'available' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'group_key' => $group['group_key'],
            'chain_type' => $group['chain_type'],
            'items' => $items->all(),
            'total_cost' => round((float) $items->sum(fn (array $item) => (float) ($item['subtotal'] ?? 0)), 2),
        ];
    }

    private function resolveGroup(string $linea): array
    {
        foreach (self::REQUIREMENT_GROUPS as $groupKey => $group) {
            if (in_array($linea, $group['lineas'], true)) {
                return [
                    'group_key' => $groupKey,
                    'chain_type' => $group['chain_type'],
                    'items' => $group['items'],
                ];
            }
        }

        throw ValidationException::withMessages([
            'linea' => 'La lavadora seleccionada no tiene una configuracion de cadena definida para costos.',
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $requirements
     * @return Collection<int, CostCatalogItem>
     */
    private function resolveCatalogItems(Collection $requirements): Collection
    {
        $skus = $requirements
            ->pluck('sku')
            ->filter()
            ->values()
            ->all();

        $names = $requirements
            ->pluck('nombre')
            ->filter()
            ->values()
            ->all();

        return CostCatalogItem::query()
            ->active()
            ->where(function ($query) use ($skus, $names) {
                if ($skus !== []) {
                    $query->whereIn('sku', $skus);
                }

                if ($names !== []) {
                    if ($skus !== []) {
                        $query->orWhereIn('nombre', $names);
                    } else {
                        $query->whereIn('nombre', $names);
                    }
                }
            })
            ->get();
    }

    private function matchCatalogItem(Collection $catalogItems, array $requirement): ?CostCatalogItem
    {
        $normalizedExpectedName = LavadoraCostSupport::normalizeText($requirement['nombre'] ?? null);

        return $catalogItems->first(function (CostCatalogItem $item) use ($requirement, $normalizedExpectedName) {
            if (!blank($requirement['sku']) && $item->sku === $requirement['sku']) {
                return true;
            }

            return LavadoraCostSupport::normalizeText($item->nombre) === $normalizedExpectedName;
        });
    }
};
