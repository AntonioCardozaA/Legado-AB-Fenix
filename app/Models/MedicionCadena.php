<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicionCadena extends Model
{
    protected $fillable = ['analisis_id','bombas','vapor'];

    public function analisis()
    {
        return $this->belongsTo(Analisis::class);
    }
}