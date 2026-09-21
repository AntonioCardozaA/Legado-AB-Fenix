<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lef52124Item extends Model
{
    use HasFactory;

    public const TYPE_MACHINE = 'machine';
    public const TYPE_PART = 'part';
    public const TYPE_LINE = 'line';

    protected $table = 'lef52124_items';

    protected $fillable = [
        'lef52124_import_id',
        'linea_id',
        'type',
        'item_name',
        'value_52_weeks',
        'value_12_weeks',
        'value_4_weeks',
    ];

    protected $casts = [
        'value_52_weeks' => 'decimal:10',
        'value_12_weeks' => 'decimal:10',
        'value_4_weeks' => 'decimal:10',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Lef52124Import::class, 'lef52124_import_id');
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class);
    }
}
