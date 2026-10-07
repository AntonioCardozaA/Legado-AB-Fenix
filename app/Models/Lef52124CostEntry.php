<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lef52124CostEntry extends Model
{
    use HasFactory;

    protected $table = 'lef52124_cost_entries';

    protected $fillable = [
        'linea_id',
        'year',
        'month',
        'accounting_date',
        'order_number',
        'object_description',
        'material',
        'material_text',
        'quantity',
        'unit',
        'amount',
        'cost_class',
        'purchase_doc',
        'order_text',
        'source_filename',
        'source_sheet',
        'source_row',
        'imported_by',
        'sync_key',
    ];

    protected $casts = [
        'accounting_date' => 'date',
        'year' => 'integer',
        'month' => 'integer',
        'quantity' => 'float',
        'amount' => 'float',
    ];

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class);
    }
}
