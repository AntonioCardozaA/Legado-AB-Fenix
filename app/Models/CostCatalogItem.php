<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCatalogItem extends Model
{
    use HasFactory;

    protected $table = 'cost_catalog_items';

    protected $fillable = [
        'sku',
        'nombre',
        'categoria',
        'unidad_medida',
        'costo_unitario',
        'activo',
        'fecha_actualizacion',
        'actualizado_por',
        'observaciones',
        'aliases',
        'metadata',
    ];

    protected $casts = [
        'aliases' => 'array',
        'metadata' => 'array',
        'activo' => 'boolean',
        'fecha_actualizacion' => 'date',
        'costo_unitario' => 'float',
    ];

    public function histories(): HasMany
    {
        return $this->hasMany(CostCatalogItemHistory::class, 'cost_catalog_item_id');
    }

    public function automationRules(): HasMany
    {
        return $this->hasMany(CostAutomationRule::class, 'cost_catalog_item_id');
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(LavadoraCostEntry::class, 'catalog_item_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }
}
