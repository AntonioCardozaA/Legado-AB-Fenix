<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\ElongacionController;
use App\Http\Controllers\ParoController;
use App\Http\Controllers\PlanAccionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\LineaController;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| Ruta raíz
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('dashboard');
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Análisis - RUTAS ESPECÍFICAS (ANTES DEL RESOURCE)
    |--------------------------------------------------------------------------
    */
    Route::get('analisis/linea/{linea}', [AnalisisController::class, 'porLinea'])
        ->name('analisis.porLinea');
    
    Route::get('analisis/exportar/excel', [AnalisisController::class, 'exportarExcel'])
        ->name('analisis.exportar.excel');

    /*
    |--------------------------------------------------------------------------
    | Elongación (SEPARADO DEL CRUD DE ANALISIS)
    |--------------------------------------------------------------------------
    */
    Route::get('analisis{analisis}/elongacion', [ElongacionController::class, 'create'])
    ->name('analisis.elongacion.create');

Route::post('analisis{analisis}/elongacion', [ElongacionController::class, 'store'])
    ->name('analisis.elongacion.store');


    /*
    |--------------------------------------------------------------------------
    | CRUD de Análisis
    |--------------------------------------------------------------------------
    */
    Route::resource('analisis', AnalisisController::class);

    /*
    |--------------------------------------------------------------------------
    | Paros de Máquina
    |--------------------------------------------------------------------------
    */
    Route::resource('paros', ParoController::class);

    Route::post('paros/{paro}/plan-accion', [ParoController::class, 'agregarPlanAccion'])
        ->name('paros.agregar-plan-accion');

    /*
    |--------------------------------------------------------------------------
    | Planes de Acción
    |--------------------------------------------------------------------------
    */
    Route::put('planes-accion/{plan}/estado', [PlanAccionController::class, 'actualizarEstado'])
        ->name('planes-accion.actualizar-estado');

    /*
    |--------------------------------------------------------------------------
    | Reportes
    |--------------------------------------------------------------------------
    */
    Route::prefix('reportes')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/elongacion', [ReporteController::class, 'elongacion'])->name('reportes.elongacion');
        Route::get('/componentes', [ReporteController::class, 'componentes'])->name('reportes.componentes');
        Route::get('/paros', [ReporteController::class, 'paros'])->name('reportes.paros');
    });

    /*
    |--------------------------------------------------------------------------
    | Líneas
    |--------------------------------------------------------------------------
    */
    Route::resource('lineas', LineaController::class)
        ->middleware('role:admin,ingeniero_mantenimiento');

    /*
    |--------------------------------------------------------------------------
    | API (para gráficas)
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->group(function () {
        Route::get('estadisticas/dashboard', [ApiController::class, 'dashboard']);
        Route::get('analisis/tendencia/{linea}', [ApiController::class, 'tendenciaLinea']);
        Route::get('analisis/danos-tendencia', [ApiController::class, 'danosTendencia']);
    });

});

/*
|--------------------------------------------------------------------------
| Autenticación
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
