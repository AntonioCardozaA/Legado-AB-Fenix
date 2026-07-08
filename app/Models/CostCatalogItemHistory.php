<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostCatalogItemHistory extends Model
{
    use HasFactory;

    protected $table = 'cost_catalog_item_histories';

    protected $fillable = [
        'cost_catalog_item_id',
        'tipo_cambio',
        'datos_anteriores',
        'datos_nuevos',
        'costo_anterior',
        'costo_nuevo',
        'fecha_cambio',
        'usuario_id',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'costo_anterior' => 'float',
        'costo_nuevo' => 'float',
        'fecha_cambio' => 'datetime',
    ];

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CostCatalogItem::class, 'cost_catalog_item_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
