<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\AnalisisLavadoraController;
use App\Http\Controllers\AnalisisPasteurizadoraController; // <-- AGREGAR
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
    | ANALISIS DE COMPONENTES - LAVADORA
    |--------------------------------------------------------------------------
    */
    Route::prefix('analisis-lavadora')
        ->name('analisis-lavadora.')
        ->group(function () {

        // ===============================
        // PRINCIPALES
        // ===============================
        Route::get('/', [AnalisisLavadoraController::class, 'index'])->name('index');
        Route::get('/seleccionar-linea', [AnalisisLavadoraController::class, 'selectLinea'])->name('select-linea');
        Route::get('/crear/{linea}', [AnalisisLavadoraController::class, 'createWithLinea'])
            ->where('linea', '[0-9]+')
            ->name('create');
        Route::get('/crear-rapido', [AnalisisLavadoraController::class, 'createQuick'])->name('create-quick');
        Route::post('/', [AnalisisLavadoraController::class, 'store'])->name('store');

        // ===============================
        // HISTORIAL
        // ===============================
        Route::get('/historial', [AnalisisLavadoraController::class, 'historial'])
            ->name('historial');

        // ===============================
        // EXPORTACIONES
        // ===============================
        Route::get('/export/excel', [AnalisisLavadoraController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [AnalisisLavadoraController::class, 'exportPdf'])->name('export.pdf');

        // ===============================
        // AJAX
        // ===============================
        Route::get('/get-componentes-por-linea', [AnalisisLavadoraController::class, 'getComponentesPorLineaAjax'])
            ->name('get-componentes-por-linea');

        Route::get('/get-reductores-por-linea', [AnalisisLavadoraController::class, 'getReductoresPorLineaPublic'])
            ->name('get-reductores-por-linea');

        // ===============================
        // ESPECÍFICAS
        // ===============================
        Route::get('/{analisislavadora}/editar', [AnalisisLavadoraController::class, 'edit'])
            ->where('analisislavadora', '[0-9]+')
            ->name('edit');

        Route::delete('/{analisislavadora}/foto/{fotoIndex}', [AnalisisLavadoraController::class, 'deleteFoto'])
            ->where('analisislavadora', '[0-9]+')
            ->where('fotoIndex', '[0-9]+')
            ->name('delete-foto');

        // ===============================
        // GENERALES
        // ===============================
        Route::put('/{analisislavadora}', [AnalisisLavadoraController::class, 'update'])
            ->where('analisislavadora', '[0-9]+')
            ->name('update');

        Route::delete('/{analisislavadora}', [AnalisisLavadoraController::class, 'destroy'])
            ->where('analisislavadora', '[0-9]+')
            ->name('destroy');

        Route::get('/{analisislavadora}', [AnalisisLavadoraController::class, 'show'])
            ->where('analisislavadora', '[0-9]+')
            ->name('show');
        Route::get('/analisis-lavadora/{id}', [AnalisisLavadoraController::class, 'show'])
    ->name('analisis-lavadora.show');

    });

    /*
    |--------------------------------------------------------------------------
    | ANALISIS DE COMPONENTES - PASTEURIZADORA (NUEVO)
    |--------------------------------------------------------------------------
    */
    Route::prefix('analisis-pasteurizadora')
        ->name('analisis-pasteurizadora.')
        ->group(function () {

        // ===============================
        // PRINCIPALES
        // ===============================
        Route::get('/', [AnalisisPasteurizadoraController::class, 'index'])->name('index');
       // Route::get('/seleccionar-linea', [AnalisisPasteurizadoraController::class, 'selectLinea'])->name('select-linea');
        //Route::get('/crear/{linea}', [AnalisisPasteurizadoraController::class, 'createWithLinea'])
           // ->where('linea', 'L-07|L-08')
         //   ->name('create');
       // Route::get('/crear-rapido', [AnalisisPasteurizadoraController::class, 'createQuick'])->name('create-quick');
       // Route::post('/', [AnalisisPasteurizadoraController::class, 'store'])->name('store');
      //  Route::post('/store-quick', [AnalisisPasteurizadoraController::class, 'storeQuick'])->name('store-quick');

        // ===============================
        // PLAN DE ACCIÓN (Basado en Excel)
        // ===============================
        //Route::get('/plan-accion', [AnalisisPasteurizadoraController::class, 'planAccion'])->name('plan-accion');
       // Route::post('/plan-accion/update', [AnalisisPasteurizadoraController::class, 'updatePlanAccion'])->name('plan-accion.update');

        // ===============================
        // ANÁLISIS 52-12-4 (Basado en Excel)
        // ===============================
       // Route::get('/analisis-52-12-4', [AnalisisPasteurizadoraController::class, 'analisis52124'])->name('analisis-52-12-4');
       // Route::post('/analisis-52-12-4/update', [AnalisisPasteurizadoraController::class, 'updateAnalisis52124'])->name('analisis-52-12-4.update');

        // ===============================
        // HISTORIAL DE REVISADOS
        // ===============================
       // Route::get('/historial', [AnalisisPasteurizadoraController::class, 'historial'])
       //     ->name('historial');
      //  Route::get('/historico-revisados', [AnalisisPasteurizadoraController::class, 'historicoRevisados'])
         //   ->name('historico-revisados');

        // ===============================
        // EXPORTACIONES
        // ===============================
       // Route::get('/export/excel', [AnalisisPasteurizadoraController::class, 'exportExcel'])->name('export.excel');
       // Route::get('/export/pdf', [AnalisisPasteurizadoraController::class, 'exportPdf'])->name('export.pdf');
       // Route::post('/export-process', [AnalisisPasteurizadoraController::class, 'exportProcess'])->name('export-process');

        // ===============================
        // AJAX - COMPONENTES ESPECÍFICOS DE PASTEURIZADORA
        // ===============================
       // Route::get('/get-componentes-por-linea', [AnalisisPasteurizadoraController::class, 'getComponentesPorLineaAjax'])
       //     ->name('get-componentes-por-linea');
       // Route::get('/get-actividades-por-modulo', [AnalisisPasteurizadoraController::class, 'getActividadesPorModulo'])
        //    ->name('get-actividades-por-modulo');
        //Route::get('/get-estadisticas-componentes', [AnalisisPasteurizadoraController::class, 'getEstadisticasComponentes'])
         //   ->name('get-estadisticas-componentes');

        // ===============================
        // ESPECÍFICAS
        // ===============================
        //Route::get('/{analisispasteurizadora}/editar', [AnalisisPasteurizadoraController::class, 'edit'])
           // ->where('analisispasteurizadora', '[0-9]+')
           // ->name('edit');

       // Route::delete('/{analisispasteurizadora}/foto/{fotoIndex}', [AnalisisPasteurizadoraController::class, 'deleteFoto'])
           // ->where('analisispasteurizadora', '[0-9]+')
          //  ->where('fotoIndex', '[0-9]+')
           // ->name('delete-foto');

        // ===============================
        // GENERALES
        // ===============================
        //Route::put('/{analisispasteurizadora}', [AnalisisPasteurizadoraController::class, 'update'])
          //  ->where('analisispasteurizadora', '[0-9]+')
          //  ->name('update');

       // Route::delete('/{analisispasteurizadora}', [AnalisisPasteurizadoraController::class, 'destroy'])
           // ->where('analisispasteurizadora', '[0-9]+')
           // ->name('destroy');

      //  Route::get('/{analisispasteurizadora}', [AnalisisPasteurizadoraController::class, 'show'])
           // ->where('analisispasteurizadora', '[0-9]+')
            //->name('show');
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
        
        // API para Pasteurizadora
        Route::prefix('pasteurizadora')->group(function () {
            Route::get('componentes/{linea}', [AnalisisPasteurizadoraController::class, 'apiGetComponentes']);
            Route::get('estadisticas/{linea}', [AnalisisPasteurizadoraController::class, 'apiGetEstadisticas']);
            Route::get('analisis-52-12-4', [AnalisisPasteurizadoraController::class, 'apiGetAnalisis52124']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | PAROS
    |--------------------------------------------------------------------------
    */
    Route::resource('paros', ParoController::class);

    /*
    |--------------------------------------------------------------------------
    | PLAN DE ACCIÓN (General)
    |--------------------------------------------------------------------------
    */
    Route::resource('plan-accion', PlanAccionController::class);

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
        Route::get('/pasteurizadora', [ReporteController::class, 'pasteurizadora'])->name('reportes.pasteurizadora'); // Nuevo
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