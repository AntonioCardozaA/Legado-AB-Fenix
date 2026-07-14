<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LavadoraCostRuleExclusion extends Model
{
    use HasFactory;

    protected $table = 'lavadora_cost_rule_exclusions';

    protected $fillable = [
        'analisis_lavadora_id',
        'cost_automation_rule_id',
        'motivo',
        'created_by',
    ];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(AnalisisLavadora::class, 'analisis_lavadora_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CostAutomationRule::class, 'cost_automation_rule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
