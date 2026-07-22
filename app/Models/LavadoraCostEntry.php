<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LavadoraCostEntry extends Model
{
    use HasFactory;

    public const SOURCE_MANUAL = 'manual';
<<<<<<< HEAD
    public const SOURCE_CHAIN_INSTALLATION = 'instalacion_cadena';
=======
    public const SOURCE_DAMAGE_CLOSURE = 'cierre_dano';
>>>>>>> fde0a3fde9e8c02955fd24457d21e4eaf289e991

    protected $table = 'lavadora_cost_entries';

    protected $fillable = [
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
        'sync_key',
    ];

    protected $casts = [
        'cost_date' => 'date',
        'quantity' => 'float',
        'unit_cost' => 'float',
        'total_cost' => 'float',
        'metadata' => 'array',
    ];

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public function analisisLavadora(): BelongsTo
    {
        return $this->belongsTo(AnalisisLavadora::class, 'analisis_lavadora_id');
    }

    public function elongacion(): BelongsTo
    {
        return $this->belongsTo(Elongacion::class, 'elongacion_id');
    }

    public function cadenaCiclo(): BelongsTo
    {
        return $this->belongsTo(CadenaCiclo::class, 'cadena_ciclo_id');
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CostCatalogItem::class, 'catalog_item_id');
    }

    public function isManual(): bool
    {
        return $this->source_type === self::SOURCE_MANUAL;
    }

    public function isAutomatic(): bool
    {
        return !$this->isManual();
    }

    public static function sourceLabel(?string $sourceType): string
    {
        return match ($sourceType) {
            self::SOURCE_MANUAL => 'Manual',
<<<<<<< HEAD
            self::SOURCE_CHAIN_INSTALLATION => 'Instalacion de cadena',
=======
            self::SOURCE_DAMAGE_CLOSURE => 'Cierre de dano',
>>>>>>> fde0a3fde9e8c02955fd24457d21e4eaf289e991
            CostAutomationRule::TRIGGER_ESTADO_CAMBIADO => 'Cambio completo',
            CostAutomationRule::TRIGGER_ACTIVIDAD_KEYWORD => 'Actividad',
            default => Str::headline(str_replace('_', ' ', (string) $sourceType)),
        };
    }

    public static function originLabel(?string $sourceType): string
    {
        return match ($sourceType) {
            self::SOURCE_MANUAL => 'Manual',
            self::SOURCE_DAMAGE_CLOSURE => 'Administrativo',
            default => 'Automatico',
        };
    }
}
