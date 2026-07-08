<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LavadoraBudget extends Model
{
    use HasFactory;

    protected $table = 'lavadora_budgets';

    protected $fillable = [
        'linea_id',
        'year',
        'annual_budget',
        'observaciones',
        'updated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'annual_budget' => 'float',
    ];

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
