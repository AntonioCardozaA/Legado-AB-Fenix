<?php

namespace App\Exports;

use App\Models\Linea;
use App\Exports\Sheets\AnalisisPorLineaSheet;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AnalisisComponentesExport implements WithMultipleSheets
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function sheets(): array
    {
        $sheets = [];
        $lineas = Linea::orderBy('nombre')->get();

        foreach ($lineas as $linea) {
            $sheets[] = new AnalisisPorLineaSheet($linea, $this->request);
        }

        return $sheets;
    }
}
