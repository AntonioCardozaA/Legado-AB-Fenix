<?php

namespace App\Http\Controllers;

use App\Models\AnalisisPasteurizadora;

class AnalisisPasteurizadoraCentralHidraulicaController extends AnalisisPasteurizadoraController
{
    protected string $areaAnalisis = AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA;
    protected string $areaLabel = 'Central Hidráulica';
    protected string $routeNamePrefix = 'pasteurizadora.central-hidraulica';
    protected string $baseUrl = '/pasteurizadora/central-hidraulica';
    protected string $viewPathPrefix = 'pasteurizadora.central-hidraulica';
    protected string $historicoViewPath = 'historico-revisados.central-hidraulica.index';
    protected string $evidenciasDir = 'analisis-pasteurizadora-central-hidraulica';
    protected string $tituloAnalisis = 'Análisis Pasteurizadora Central Hidráulica';
    protected string $tituloHistorial = 'Historial de Análisis - Central Hidráulica';
    protected string $tituloHistoricoRevisados = 'Histórico de Revisados Central Hidráulica';
}
