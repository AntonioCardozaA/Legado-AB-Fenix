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
| FLUJO DE ANÁLISIS DE COMPONENTES (CORREGIDO Y ORGANIZADO)
|--------------------------------------------------------------------------
*/
Route::prefix('analisis-componentes')->name('analisis-componentes.')->group(function () {
    
    // ============================================
    // RUTAS DE VISTA Y CREACIÓN
    // ============================================
    
    // Página principal (índice)
    Route::get('/', [AnalisisComponenteController::class, 'index'])
        ->name('index');
    
    // Selección de línea
    Route::get('/seleccionar-linea', [AnalisisComponenteController::class, 'selectLinea'])
        ->name('select-linea');
    
    // Crear con línea específica
    Route::get('/crear/{linea}', [AnalisisComponenteController::class, 'createWithLinea'])
        ->name('create')
        ->where('linea', '[0-9]+'); // Solo números para ID
    
    // Creación rápida
    Route::get('/crear-rapido', [AnalisisComponenteController::class, 'createQuick'])
        ->name('create-quick');
    
    // Almacenar nuevo análisis
    Route::post('/', [AnalisisComponenteController::class, 'store'])
        ->name('store');
    
    // ============================================
    // RUTAS ESPECÍFICAS (ANTES DE LAS GENERALES)
    // ============================================
    
    // Editar análisis (ESPECÍFICA - va primero)
    Route::get('/{analisisComponente}/editar', [AnalisisComponenteController::class, 'edit'])
        ->name('edit')
        ->where('analisisComponente', '[0-9]+'); // Solo números
    
    // Eliminar foto específica
    Route::delete('/{analisisComponente}/foto/{fotoIndex}', [AnalisisComponenteController::class, 'deleteFoto'])
        ->name('delete-foto')
        ->where('analisisComponente', '[0-9]+')
        ->where('fotoIndex', '[0-9]+');
    
    // ============================================
    // RUTAS GENERALES (DESPUÉS DE LAS ESPECÍFICAS)
    // ============================================
    
    // Actualizar análisis
    Route::put('/{analisisComponente}', [AnalisisComponenteController::class, 'update'])
        ->name('update')
        ->where('analisisComponente', '[0-9]+');
    
    // Eliminar análisis
    Route::delete('/{analisisComponente}', [AnalisisComponenteController::class, 'destroy'])
        ->name('destroy')
        ->where('analisisComponente', '[0-9]+');
    
    // Mostrar análisis (GENERAL - va al final)
    Route::get('/{analisisComponente}', [AnalisisComponenteController::class, 'show'])
        ->name('show')
        ->where('analisisComponente', '[0-9]+');
    
    // ============================================
    // RUTAS DE EXPORTACIÓN
    // ============================================
    
    // Exportar a Excel
    Route::get('/export/excel', [AnalisisComponenteController::class, 'exportExcel'])
        ->name('export.excel');
    
    // Exportar a PDF
    Route::get('/export/pdf', [AnalisisComponenteController::class, 'exportPdf'])
        ->name('export.pdf');
    
    // ============================================
    // RUTAS API/AJAX
    // ============================================
    
    // Obtener componentes por línea (AJAX)
    Route::get('/get-componentes-por-linea', [AnalisisComponenteController::class, 'getComponentesPorLineaAjax'])
        ->name('get-componentes-por-linea');
    
    // Obtener reductores por línea (Público)
    Route::get('/get-reductores-por-linea', [AnalisisComponenteController::class, 'getReductoresPorLineaPublic'])
        ->name('get-reductores-por-linea');
});
    /*
    |--------------------------------------------------------------------------
    | FLUJO DE ANÁLISIS (original)
    |--------------------------------------------------------------------------
    */
    Route::prefix('analisis')->name('analisis.')->group(function () {

        Route::get('/nuevo', [AnalisisController::class, 'seleccionarLinea'])
            ->name('nuevo');

        Route::get('/{linea}/seleccionar-componente', [AnalisisController::class, 'seleccionarComponente'])
            ->name('seleccionar-componente');

        Route::get('/{linea}/crear/{componente}', [AnalisisController::class, 'crear'])
            ->name('crear');

        Route::get('numeros-r/{categoria}', [AnalisisController::class, 'getNumerosR'])
            ->name('numeros-r');

        Route::get('/', [AnalisisController::class, 'index'])->name('index');
        Route::post('/', [AnalisisController::class, 'store'])->name('store');
        Route::get('/{analisis}', [AnalisisController::class, 'show'])->name('show');
        Route::get('/{analisis}/edit', [AnalisisController::class, 'edit'])->name('edit');
        Route::put('/{analisis}', [AnalisisController::class, 'update'])->name('update');
        Route::delete('/{analisis}', [AnalisisController::class, 'destroy'])->name('destroy');

        Route::get('/linea/{linea}', [AnalisisController::class, 'porLinea'])
            ->name('porLinea');

        Route::get('/exportar/excel', [AnalisisController::class, 'exportarExcel'])
            ->name('exportar.excel');

        Route::get('/{analisis}/pdf', [AnalisisController::class, 'exportPdf'])
            ->name('exportar.pdf');

        Route::get('/analisis/exportar/lavadoras', [AnalisisController::class, 'exportarTodas'])
            ->name('analisis.exportar.lavadoras');

        Route::get('/estadisticas', [AnalisisController::class, 'estadisticas'])
            ->name('estadisticas');

        Route::post('/{analisis}/eliminar-foto', [AnalisisController::class, 'eliminarFoto'])
            ->name('eliminar-foto');

        Route::get('/linea/{linea}/componentes', [AnalisisController::class, 'getComponentes'])
            ->name('linea.componentes');

        Route::get('/componente/{componente}/reductores', [AnalisisController::class, 'getReductores'])
            ->name('componente.reductores');
    });

    /*
    |--------------------------------------------------------------------------
    | Elongación (análisis)
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
        
        // API específica para análisis de componentes
        Route::get('analisis-componentes/componentes-por-linea', [AnalisisComponenteController::class, 'getComponentesPorLineaAjax']);
        Route::get('analisis-componentes/reductores-por-linea', [AnalisisComponenteController::class, 'getReductoresPorLineaPublic']);
    });

    /*
    |--------------------------------------------------------------------------
    | Paros
    |--------------------------------------------------------------------------
    */
    Route::resource('paros', ParoController::class);

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
    Route::middleware(['role:admin|ingeniero_mantenimiento'])->group(function () {
        Route::resource('lineas', LineaController::class);
        Route::patch('/lineas/{linea}/toggle', [LineaController::class, 'toggleActivo'])
            ->name('lineas.toggle');
    });
});

/*
|--------------------------------------------------------------------------
| RUTAS AGREGADAS DE ELONGACIONES (SIN MODIFICAR NADA)
|--------------------------------------------------------------------------
*/
Route::middleware(['web'])->group(function () {
    Route::get('/elongaciones', [ElongacionController::class, 'index'])
        ->name('elongaciones.index');
    
    Route::get('/elongaciones/create', [ElongacionController::class, 'create'])
        ->name('elongaciones.create');
    
    Route::post('/elongaciones', [ElongacionController::class, 'store'])
        ->name('elongaciones.store');
    
    Route::get('/elongaciones/{elongacion}', [ElongacionController::class, 'show'])
        ->name('elongaciones.show');
    
    Route::get('/elongaciones/{elongacion}/edit', [ElongacionController::class, 'edit'])
        ->name('elongaciones.edit');
    
    Route::put('/elongaciones/{elongacion}', [ElongacionController::class, 'update'])
            ->name('elongaciones.update');
    
    Route::delete('/elongaciones/{elongacion}', [ElongacionController::class, 'destroy'])
        ->name('elongaciones.destroy');
    
    Route::get('/reportes/elongaciones', [ElongacionController::class, 'reporte'])
        ->name('elongaciones.reporte');
    
    Route::get('/reportes/elongaciones/{linea}', [ElongacionController::class, 'reporte'])
        ->name('elongaciones.reporte.linea');
});

/*
|--------------------------------------------------------------------------
| Autenticación
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';