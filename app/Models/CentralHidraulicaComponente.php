<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CentralHidraulicaComponente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'central_hidraulica_componentes';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'unidad',
        'requiere_lado',
        'cantidad_editable',
        'contabilizable',
        'activo',
        'orden',
    ];

    protected $casts = [
        'requiere_lado' => 'boolean',
        'cantidad_editable' => 'boolean',
        'contabilizable' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function configuraciones(): HasMany
    {
        return $this->hasMany(CentralHidraulicaConfiguracion::class, 'componente_id');
    }

    public function analisis(): HasMany
    {
        return $this->hasMany(AnalisisCentralHidraulica::class, 'componente_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function getNombreDisplayAttribute(): string
    {
        return self::normalizarNombreParaMostrar($this->attributes['nombre'] ?? null);
    }

    public static function normalizarNombreParaMostrar(?string $nombre, string $fallback = 'Componente sin catalogo'): string
    {
        $nombre = trim(preg_replace('/\s+/u', ' ', (string) $nombre) ?? '');

        if ($nombre === '') {
            return $fallback;
        }

        $partes = preg_split('/\s+-\s+/u', $nombre) ?: [];
        $partes = array_values(array_filter(
            array_map(fn (string $parte) => trim($parte), $partes),
            fn (string $parte) => $parte !== ''
        ));

        if (count($partes) > 1 && self::partesRepetidas($partes)) {
            return $partes[0];
        }

        $tokens = preg_split('/\s+/u', $nombre) ?: [];

        if (count($tokens) > 1 && count($tokens) % 2 === 0) {
            $mitad = (int) (count($tokens) / 2);
            $primerNombre = implode(' ', array_slice($tokens, 0, $mitad));
            $segundoNombre = implode(' ', array_slice($tokens, $mitad));
            $primerComparable = self::normalizarNombreParaComparar($primerNombre);
            $segundoComparable = self::normalizarNombreParaComparar($segundoNombre);

            if ($primerComparable !== '' && $primerComparable === $segundoComparable) {
                return $primerNombre;
            }
        }

        return $nombre;
    }

    private static function partesRepetidas(array $partes): bool
    {
        $comparables = collect($partes)
            ->map(fn (string $parte) => self::normalizarNombreParaComparar($parte))
            ->filter()
            ->unique()
            ->values();

        return $comparables->count() === 1;
    }

    private static function normalizarNombreParaComparar(string $nombre): string
    {
        $nombre = Str::ascii(Str::lower(trim($nombre)));
        $nombre = preg_replace('/[^a-z0-9]+/', ' ', $nombre) ?? '';

        return trim(preg_replace('/\s+/', ' ', $nombre) ?? '');
    }
}
