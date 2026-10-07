<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LefEfficiencyEntry extends Model
{
    use HasFactory;

    protected $table = 'lef_efficiency_entries';

    protected $fillable = [
        'lef_efficiency_import_id',
        'linea_id',
        'data_date',
        'machine_name',
        'value',
        'source_sheet',
        'source_row',
        'source_column',
    ];

    protected $casts = [
        'data_date' => 'date',
        'value' => 'float',
        'source_row' => 'integer',
        'source_column' => 'integer',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(LefEfficiencyImport::class, 'lef_efficiency_import_id');
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class);
    }
}
