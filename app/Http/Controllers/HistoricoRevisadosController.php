<?php
// app/Http/Controllers/HistoricoRevisadosController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HistoricoRevisadosController extends Controller
{
    public function index()
    {
        // Aquí puedes obtener los datos de tu base de datos
        $componentes = [
            [
                'nombre' => 'REDUCTORES CHICOS',
                'total' => 15,
                'revisadas' => 15,
                'color' => 'bg-success'
            ],
            [
                'nombre' => 'REDUCTORES GRANDES',
                'total' => 15,
                'revisadas' => 6,
                'color' => 'bg-primary'
            ],
            [
                'nombre' => 'BUJES DE BAQUELITA Y ESPIGA',
                'total' => 15,
                'revisadas' => 8,
                'color' => 'bg-info'
            ],
            [
                'nombre' => 'GUIAS SUPERIORES',
                'total' => 15,
                'revisadas' => 15,
                'color' => 'bg-success'
            ],
            [
                'nombre' => 'GUIAS INFERIORES',
                'total' => 15,
                'revisadas' => 2,
                'color' => 'bg-warning'
            ],
            [
                'nombre' => 'GUIAS DE RETORNO',
                'total' => 15,
                'revisadas' => 2,
                'color' => 'bg-warning'
            ],
            [
                'nombre' => 'CATARINAS',
                'total' => 15,
                'revisadas' => 1,
                'color' => 'bg-danger'
            ]
        ];

        return view('historico-revisados.index', compact('componentes'));
    }
}