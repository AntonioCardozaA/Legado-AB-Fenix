<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CentralHidraulicaConfiguracion extends Model
{
    use HasFactory;

    public const CODIGO_ACEITE = 'ACEITE';
    public const ACEITE_LITROS_DEFAULT = 300;

    public const PISO_SUPERIOR = 'SUPERIOR';
    public const PISO_INFERIOR = 'INFERIOR';
    public const PISOS = [
        self::PISO_SUPERIOR => 'Piso Superior',
        self::PISO_INFERIOR => 'Piso Inferior',
    ];

    public const PASTEURIZADORES_EXCEL = [
        'P-03',
        'P-04',
        'P-05',
        'P-06',
        'P-07',
        'P-09',
        'P-10',
        'P-11',
        'P-12',
        'P-13',
        'P-14',
    ];

    protected $table = 'central_hidraulica_configuraciones';

    protected $fillable = [
        'pasteurizador',
        'piso',
        'componente_id',
        'cantidad',
        'unidad',
        'detalle_excel',
        'lado_requerido',
        'activo',
        'orden',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'lado_requerido' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function componente(): BelongsTo
    {
        return $this->belongsTo(CentralHidraulicaComponente::class, 'componente_id');
    }

    public function analisis(): HasMany
    {
        return $this->hasMany(AnalisisCentralHidraulica::class, 'configuracion_id');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeParaPasteurizador($query, string $pasteurizador)
    {
        return $query->where('pasteurizador', strtoupper(trim($pasteurizador)));
    }

    public function scopeParaPiso($query, ?string $piso)
    {
        if (!$piso) {
            return $query;
        }

        return $query->where('piso', self::normalizarPiso($piso));
    }

    public static function normalizarPiso(?string $piso): ?string
    {
        $piso = strtoupper(trim((string) $piso));

        if (in_array($piso, [self::PISO_SUPERIOR, 'PISO SUPERIOR'], true)) {
            return self::PISO_SUPERIOR;
        }

        if (in_array($piso, [self::PISO_INFERIOR, 'PISO INFERIOR'], true)) {
            return self::PISO_INFERIOR;
        }

        return null;
    }

    public static function pisoLabel(?string $piso): string
    {
        $piso = self::normalizarPiso($piso);

        return $piso ? self::PISOS[$piso] : 'Sin piso';
    }

    public function getPisoLabelAttribute(): string
    {
        return self::pisoLabel($this->piso);
    }

    public function getComponenteNombreAttribute(): string
    {
        return $this->componente?->nombre_display ?? 'Componente sin catalogo';
    }

    public function getCantidadLabelAttribute(): string
    {
        if ($this->cantidad === null) {
            return 'Cantidad pendiente por definir';
        }

        return trim($this->cantidad . ' ' . ($this->unidad ?: 'pza'));
    }

    public function getEsContabilizableAttribute(): bool
    {
        if ($this->relationLoaded('componente')) {
            if ($this->componente?->codigo === self::CODIGO_ACEITE) {
                return false;
            }

            return (bool) ($this->componente?->contabilizable ?? true);
        }

        $componente = $this->componente()
            ->first(['codigo']);

        if ($componente?->codigo === self::CODIGO_ACEITE) {
            return false;
        }

        return true;
    }

    public function getEsRevisionAceiteAttribute(): bool
    {
        if ($this->relationLoaded('componente')) {
            return $this->componente?->codigo === self::CODIGO_ACEITE;
        }

        return $this->componente()->value('codigo') === self::CODIGO_ACEITE;
    }

    public function getTipoElementoLabelAttribute(): string
    {
        return $this->es_contabilizable ? 'Componente' : 'Revision';
    }

    public function getTieneCantidadDefinidaAttribute(): bool
    {
        return $this->cantidad !== null;
    }
}
