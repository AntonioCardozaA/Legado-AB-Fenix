<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MttrIndicador extends Model
{
    public const GENERAL = 'GENERAL';

    public const LAVADORAS = ['L-04', 'L-05', 'L-06', 'L-07', 'L-09', 'L-12', 'L-13'];

    public const MAXIMO_MINUTOS = '9999999999.9999';

    protected $table = 'mttr_indicadores';

    protected $casts = ['minutos' => 'decimal:4'];

    public function minutosParaMostrar(): ?string
    {
        return $this->minutos === null ? null : rtrim(rtrim($this->minutos, '0'), '.');
    }
}
