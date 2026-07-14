<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostAutomationRule extends Model
{
    use HasFactory;

    public const TRIGGER_ESTADO_CAMBIADO = 'estado_cambiado';
    public const TRIGGER_ACTIVIDAD_KEYWORD = 'actividad_keyword';

    protected $table = 'cost_automation_rules';

    protected $fillable = [
        'cost_catalog_item_id',
        'linea_nombre',
        'component_code',
        'trigger_type',
        'trigger_keyword',
        'quantity',
        'priority',
        'activo',
        'notas',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'float',
        'priority' => 'integer',
        'activo' => 'boolean',
        'metadata' => 'array',
    ];

    public static function triggerOptions(): array
    {
        return [
            self::TRIGGER_ESTADO_CAMBIADO => 'Estado cambiado',
            self::TRIGGER_ACTIVIDAD_KEYWORD => 'Actividad por palabra clave',
        ];
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CostCatalogItem::class, 'cost_catalog_item_id');
    }

    public function exclusions(): HasMany
    {
        return $this->hasMany(LavadoraCostRuleExclusion::class, 'cost_automation_rule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }
}
