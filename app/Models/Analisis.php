<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $linea_id
 * @property int|null $componente_id
 * @property int|null $categoria_id
 * @property int|null $numero_r_id
 * @property int|null $usuario_id
 * @property Carbon|null $fecha_analisis
 * @property string|null $reductor
 * @property array<int, string>|null $fotos
 * @property Linea|null $linea
 * @property Componente|null $componente
 * @property Categoria|null $categoria
 * @property NumeroR|null $numeroR
 * @property User|null $usuario
 */
class Analisis extends Model
{
    use HasFactory;

    protected $table = 'analisis';
    
    protected $fillable = [
        'linea_id',
        'fecha_analisis',
        'numero_orden',
        'observaciones',
        'horometro',
        'actividad',
        'usuario_id',
        'categoria_id',
        'numero_r_id',
        'reductor',
        'fotos',
        'componente_id',
    ];

    protected $casts = [
        'fecha_analisis' => 'date',
        'fotos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = ['fecha_analisis'];

    /* RELACIONES */
    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class);
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(Componente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function numeroR(): BelongsTo
    {
        return $this->belongsTo(NumeroR::class, 'numero_r_id');
    }

    public function analisisComponentes(): HasMany
    {
        return $this->hasMany(AnalisisComponente::class, 'analisis_id');
    }

    // Relación mantenida del primer código
    public function mediciones(): HasMany
    {
        return $this->hasMany(MedicionCadena::class);
    }

    /* FILTROS */
    public function scopeFilter(Builder $query, mixed $request): Builder
    {
        return $query
            ->when($request->linea_id, fn($q) => $q->where('linea_id', $request->linea_id))
            ->when($request->componente_id, fn($q) => $q->where('componente_id', $request->componente_id))
            ->when($request->reductor, fn($q) => $q->where('reductor', 'like', '%'.$request->reductor.'%'))
            ->when($request->fecha, fn($q) => $q->whereMonth('fecha_analisis', substr($request->fecha, 5, 2)));
    }
}