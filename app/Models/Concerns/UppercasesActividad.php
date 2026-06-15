<?php

namespace App\Models\Concerns;

trait UppercasesActividad
{
    public function setActividadAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['actividad'] = null;
            return;
        }

        $value = (string) $value;

        $this->attributes['actividad'] = function_exists('mb_strtoupper')
            ? mb_strtoupper($value, 'UTF-8')
            : strtoupper($value);
    }
}
