<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Alias de compatibilidad para exportaciones invocadas desde controladores.
 */
class AnalisisLavadoraExport implements WithMultipleSheets
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function sheets(): array
    {
        return (new AnalisisComponentesExport($this->request))->sheets();
    }
}
