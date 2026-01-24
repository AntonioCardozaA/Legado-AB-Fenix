<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }

    public function componente()
    {
        return $this->belongsTo(Componente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function numeroR()
    {
        return $this->belongsTo(NumeroR::class, 'numero_r_id');
    }

    public function analisisComponentes()
    {
        return $this->hasMany(AnalisisComponente::class, 'analisis_id');
    }

    // Relación mantenida del primer código
    public function mediciones()
    {
        return $this->hasMany(MedicionCadena::class);
    }

    /* FILTROS */
    public function scopeFilter($query, $request)
    {
        return $query
            ->when($request->linea_id, fn($q) => $q->where('linea_id', $request->linea_id))
            ->when($request->componente_id, fn($q) => $q->where('componente_id', $request->componente_id))
            ->when($request->reductor, fn($q) => $q->where('reductor', 'like', '%'.$request->reductor.'%'))
            ->when($request->fecha, fn($q) => $q->whereMonth('fecha_analisis', substr($request->fecha, 5, 2)));
    }
}