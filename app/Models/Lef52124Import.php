<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lef52124Import extends Model
{
    use HasFactory;

    protected $table = 'lef52124_imports';

    protected $fillable = [
        'linea_id',
        'data_date',
        'source_filename',
        'observations',
        'status',
        'machines_count',
        'parts_count',
        'line_items_count',
        'imported_by',
    ];

    protected $casts = [
        'data_date' => 'date',
        'machines_count' => 'integer',
        'parts_count' => 'integer',
        'line_items_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Lef52124Item::class, 'lef52124_import_id');
    }
}
