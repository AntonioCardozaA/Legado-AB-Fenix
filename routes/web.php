<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\AnalisisComponenteController;
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
| RUTAS PROTEGIDAS
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
    | ANALISIS DE COMPONENTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('analisis-componentes')
        ->name('analisis-componentes.')
        ->group(function () {

        // ===============================
        // PRINCIPALES
        // ===============================
        Route::get('/', [AnalisisComponenteController::class, 'index'])->name('index');
        Route::get('/seleccionar-linea', [AnalisisComponenteController::class, 'selectLinea'])->name('select-linea');
        Route::get('/crear/{linea}', [AnalisisComponenteController::class, 'createWithLinea'])
            ->where('linea', '[0-9]+')
            ->name('create');
        Route::get('/crear-rapido', [AnalisisComponenteController::class, 'createQuick'])->name('create-quick');
        Route::post('/', [AnalisisComponenteController::class, 'store'])->name('store');

        // ===============================
        // HISTORIAL (CORREGIDO)
        // ===============================
        Route::get('/historial', [AnalisisComponenteController::class, 'historial'])
            ->name('historial');

        // ===============================
        // EXPORTACIONES
        // ===============================
        Route::get('/export/excel', [AnalisisComponenteController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [AnalisisComponenteController::class, 'exportPdf'])->name('export.pdf');

        // ===============================
        // AJAX
        // ===============================
        Route::get('/get-componentes-por-linea', [AnalisisComponenteController::class, 'getComponentesPorLineaAjax'])
            ->name('get-componentes-por-linea');

        Route::get('/get-reductores-por-linea', [AnalisisComponenteController::class, 'getReductoresPorLineaPublic'])
            ->name('get-reductores-por-linea');

        // ===============================
        // ESPECÍFICAS (ANTES DE GENERALES)
        // ===============================
        Route::get('/{analisisComponente}/editar', [AnalisisComponenteController::class, 'edit'])
            ->where('analisisComponente', '[0-9]+')
            ->name('edit');

        Route::delete('/{analisisComponente}/foto/{fotoIndex}', [AnalisisComponenteController::class, 'deleteFoto'])
            ->where('analisisComponente', '[0-9]+')
            ->where('fotoIndex', '[0-9]+')
            ->name('delete-foto');

        // ===============================
        // GENERALES (AL FINAL)
        // ===============================
        Route::put('/{analisisComponente}', [AnalisisComponenteController::class, 'update'])
            ->where('analisisComponente', '[0-9]+')
            ->name('update');

        Route::delete('/{analisisComponente}', [AnalisisComponenteController::class, 'destroy'])
            ->where('analisisComponente', '[0-9]+')
            ->name('destroy');

        Route::get('/{analisisComponente}', [AnalisisComponenteController::class, 'show'])
            ->where('analisisComponente', '[0-9]+')
            ->name('show');
    });

    /*
    |--------------------------------------------------------------------------
    | ANALISIS ORIGINAL
    |--------------------------------------------------------------------------
    */
    Route::prefix('analisis')->name('analisis.')->group(function () {

        Route::get('/nuevo', [AnalisisController::class, 'seleccionarLinea'])->name('nuevo');
        Route::get('/{linea}/seleccionar-componente', [AnalisisController::class, 'seleccionarComponente'])->name('seleccionar-componente');
        Route::get('/{linea}/crear/{componente}', [AnalisisController::class, 'crear'])->name('crear');

        Route::get('numeros-r/{categoria}', [AnalisisController::class, 'getNumerosR'])->name('numeros-r');

        Route::get('/', [AnalisisController::class, 'index'])->name('index');
        Route::post('/', [AnalisisController::class, 'store'])->name('store');
        Route::get('/{analisis}', [AnalisisController::class, 'show'])->name('show');
        Route::get('/{analisis}/edit', [AnalisisController::class, 'edit'])->name('edit');
        Route::put('/{analisis}', [AnalisisController::class, 'update'])->name('update');
        Route::delete('/{analisis}', [AnalisisController::class, 'destroy'])->name('destroy');

        Route::get('/linea/{linea}', [AnalisisController::class, 'porLinea'])->name('porLinea');
        Route::get('/exportar/excel', [AnalisisController::class, 'exportarExcel'])->name('exportar.excel');
        Route::get('/{analisis}/pdf', [AnalisisController::class, 'exportPdf'])->name('exportar.pdf');
        Route::get('/analisis/exportar/lavadoras', [AnalisisController::class, 'exportarTodas'])->name('analisis.exportar.lavadoras');
        Route::get('/estadisticas', [AnalisisController::class, 'estadisticas'])->name('estadisticas');
        Route::post('/{analisis}/eliminar-foto', [AnalisisController::class, 'eliminarFoto'])->name('eliminar-foto');
        Route::get('/linea/{linea}/componentes', [AnalisisController::class, 'getComponentes'])->name('linea.componentes');
        Route::get('/componente/{componente}/reductores', [AnalisisController::class, 'getReductores'])->name('componente.reductores');
    });

    /*
    |--------------------------------------------------------------------------
    | ELONGACIÓN
    |--------------------------------------------------------------------------
    */
    Route::get('analisis/{analisis}/elongacion', [ElongacionController::class, 'create'])
        ->name('analisis.elongacion.create');

    Route::post('analisis/{analisis}/elongacion', [ElongacionController::class, 'store'])
        ->name('analisis.elongacion.store');

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->group(function () {
        Route::get('categorias/{categoria}/numeros-r', [AnalisisController::class, 'getNumerosR']);
        Route::get('categorias/{categoria}/numeros-r-componentes', [AnalisisComponenteController::class, 'getNumerosRByCategoria']);
        Route::get('estadisticas/categoria/{categoria?}', [AnalisisComponenteController::class, 'estadisticasPorCategoria']);
        Route::get('estadisticas/dashboard', [ApiController::class, 'dashboard']);
        Route::get('analisis/tendencia/{linea}', [ApiController::class, 'tendenciaLinea']);
        Route::get('analisis/danos-tendencia', [ApiController::class, 'danosTendencia']);
        Route::get('analisis-componentes/componentes-por-linea', [AnalisisComponenteController::class, 'getComponentesPorLineaAjax']);
        Route::get('analisis-componentes/reductores-por-linea', [AnalisisComponenteController::class, 'getReductoresPorLineaPublic']);
    });

    /*
    |--------------------------------------------------------------------------
    | PAROS
    |--------------------------------------------------------------------------
    */
    Route::resource('paros', ParoController::class);

    /*
    |--------------------------------------------------------------------------
    | REPORTES
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
    | LÍNEAS
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin|ingeniero_mantenimiento'])->group(function () {
        Route::resource('lineas', LineaController::class);
        Route::patch('/lineas/{linea}/toggle', [LineaController::class, 'toggleActivo'])
            ->name('lineas.toggle');
    });
});

/*
|--------------------------------------------------------------------------
| ELONGACIONES (SIN AUTH - COMO LO TENÍAS)
|--------------------------------------------------------------------------
*/
Route::middleware(['web'])->group(function () {

    Route::get('/elongaciones', [ElongacionController::class, 'index'])->name('elongaciones.index');
    Route::get('/elongaciones/create', [ElongacionController::class, 'create'])->name('elongaciones.create');
    Route::post('/elongaciones', [ElongacionController::class, 'store'])->name('elongaciones.store');
    Route::get('/elongaciones/{elongacion}', [ElongacionController::class, 'show'])->name('elongaciones.show');
    Route::get('/elongaciones/{elongacion}/edit', [ElongacionController::class, 'edit'])->name('elongaciones.edit');
    Route::put('/elongaciones/{elongacion}', [ElongacionController::class, 'update'])->name('elongaciones.update');
    Route::delete('/elongaciones/{elongacion}', [ElongacionController::class, 'destroy'])->name('elongaciones.destroy');
    Route::get('/reportes/elongaciones', [ElongacionController::class, 'reporte'])->name('elongaciones.reporte');
    Route::get('/reportes/elongaciones/{linea}', [ElongacionController::class, 'reporte'])->name('elongaciones.reporte.linea');
});

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
