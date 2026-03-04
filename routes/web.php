<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\AnalisisLavadoraController;
use App\Http\Controllers\AnalisisPasteurizadoraController;
use App\Http\Controllers\ElongacionController;
use App\Http\Controllers\ParoController;
use App\Http\Controllers\PlanAccionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\LineaController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AnalisisTendenciaMensualLavadoraController;
use App\Http\Controllers\HistoricoRevisadosController;

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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/lavadora/dashboard', [DashboardController::class, 'Lavadora'])->name('lavadora.dashboard');
    
    /*
    |--------------------------------------------------------------------------
    | HISTORICO DE REVISADOS (NUEVA SECCIÓN)
    |--------------------------------------------------------------------------
    */
    Route::prefix('historico-revisados')
        ->name('historico-revisados.')
        ->controller(HistoricoRevisadosController::class)
        ->group(function () {
            
        // Ruta principal
        Route::get('/', 'index')->name('index');
        
        // Rutas para gestión de periodicidad (solo admin/ingeniero)
        Route::middleware(['role:admin|ingeniero_mantenimiento'])->group(function () {
            Route::post('/reset-estadisticas', 'resetEstadisticas')->name('reset-estadisticas');
            Route::get('/check-reset-status', 'checkResetStatus')->name('check-reset-status');
        });
    });
    
    /*
    |--------------------------------------------------------------------------
    | ANALISIS DE COMPONENTES - LAVADORA
    |--------------------------------------------------------------------------
    */
    Route::prefix('analisis-lavadora')
        ->name('analisis-lavadora.')
        ->controller(AnalisisLavadoraController::class)
        ->group(function () {

        // ===============================
        // PRINCIPALES
        // ===============================
        Route::get('/', 'index')->name('index');
        Route::get('/seleccionar-linea', 'selectLinea')->name('select-linea');
        Route::get('/crear/{linea}', 'createWithLinea')
            ->where('linea', '[0-9]+')
            ->name('create');
        Route::get('/crear-rapido', 'createQuick')->name('create-quick');
        Route::post('/', 'store')->name('store');

        // ===============================
        // HISTORIAL
        // ===============================
        Route::get('/historial', 'historial')->name('historial');

        // ===============================
        // AJAX
        // ===============================
        Route::get('/get-componentes-por-linea', 'getComponentesPorLineaAjax')
            ->name('get-componentes-por-linea');

        Route::get('/get-reductores-por-linea', 'getReductoresPorLineaPublic')
            ->name('get-reductores-por-linea');

        // ===============================
        // ESPECÍFICAS
        // ===============================
        Route::get('/{analisislavadora}/editar', 'edit')
            ->where('analisislavadora', '[0-9]+')
            ->name('edit');

        Route::delete('/{analisislavadora}/foto/{fotoIndex}', 'deleteFoto')
            ->where('analisislavadora', '[0-9]+')
            ->where('fotoIndex', '[0-9]+')
            ->name('delete-foto');

        // ===============================
        // GENERALES
        // ===============================
        Route::put('/{analisislavadora}', 'update')
            ->where('analisislavadora', '[0-9]+')
            ->name('update');

        Route::delete('/{analisislavadora}', 'destroy')
            ->where('analisislavadora', '[0-9]+')
            ->name('destroy');

        Route::get('/{analisislavadora}', 'show')
            ->where('analisislavadora', '[0-9]+')
            ->name('show');
    });

    /*
    |--------------------------------------------------------------------------
    | ANALISIS 52-12-4
    |--------------------------------------------------------------------------
    */
    Route::prefix('analisis-52-12-4')
        ->name('analisis-52-12-4.')
        ->controller(AnalisisLavadoraController::class)
        ->group(function () {
            Route::get('/', 'analisis52124')->name('index');
        });
    
    /*
    |--------------------------------------------------------------------------
    | ANALISIS TENDENCIA MENSUAL LAVADORA
    |--------------------------------------------------------------------------
    */
    Route::prefix('analisis-tendencia-mensual-lavadora')
        ->name('analisis-tendencia-mensual-lavadora.')
        ->controller(AnalisisTendenciaMensualLavadoraController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{analisis}', 'show')->name('show');
            Route::get('/api/tendencia', 'getTendenciaApi')->name('api');
    });

    /*
    |--------------------------------------------------------------------------
    | ANALISIS DE COMPONENTES - PASTEURIZADORA
    |--------------------------------------------------------------------------
    */
    Route::prefix('analisis-pasteurizadora')
        ->name('analisis-pasteurizadora.')
        ->controller(AnalisisPasteurizadoraController::class)
        ->group(function () {

        // ===============================
        // PRINCIPALES
        // ===============================
        Route::get('/', 'index')->name('index');
        
        // NOTA: Las rutas comentadas están pendientes de implementación
        // Descomentar cuando estén listas
        /*
        Route::get('/seleccionar-linea', 'selectLinea')->name('select-linea');
        Route::get('/crear/{linea}', 'createWithLinea')
            ->where('linea', 'L-07|L-08')
            ->name('create');
        Route::get('/crear-rapido', 'createQuick')->name('create-quick');
        Route::post('/', 'store')->name('store');
        Route::post('/store-quick', 'storeQuick')->name('store-quick');
        Route::get('/plan-accion', 'planAccion')->name('plan-accion');
        Route::post('/plan-accion/update', 'updatePlanAccion')->name('plan-accion.update');
        Route::get('/analisis-52-12-4', 'analisis52124')->name('analisis-52-12-4');
        Route::post('/analisis-52-12-4/update', 'updateAnalisis52124')->name('analisis-52-12-4.update');
        Route::get('/historial', 'historial')->name('historial');
        Route::get('/historico-revisados', 'historicoRevisados')->name('historico-revisados');
        Route::get('/export/excel', 'exportExcel')->name('export.excel');
        Route::get('/export/pdf', 'exportPdf')->name('export.pdf');
        Route::post('/export-process', 'exportProcess')->name('export-process');
        Route::get('/get-componentes-por-linea', 'getComponentesPorLineaAjax')->name('get-componentes-por-linea');
        Route::get('/get-actividades-por-modulo', 'getActividadesPorModulo')->name('get-actividades-por-modulo');
        Route::get('/get-estadisticas-componentes', 'getEstadisticasComponentes')->name('get-estadisticas-componentes');
        Route::get('/{analisispasteurizadora}/editar', 'edit')
            ->where('analisispasteurizadora', '[0-9]+')
            ->name('edit');
        Route::delete('/{analisispasteurizadora}/foto/{fotoIndex}', 'deleteFoto')
            ->where('analisispasteurizadora', '[0-9]+')
            ->where('fotoIndex', '[0-9]+')
            ->name('delete-foto');
        Route::put('/{analisispasteurizadora}', 'update')
            ->where('analisispasteurizadora', '[0-9]+')
            ->name('update');
        Route::delete('/{analisispasteurizadora}', 'destroy')
            ->where('analisispasteurizadora', '[0-9]+')
            ->name('destroy');
        Route::get('/{analisispasteurizadora}', 'show')
            ->where('analisispasteurizadora', '[0-9]+')
            ->name('show');
        */
    });

    /*
    |--------------------------------------------------------------------------
    | ANALISIS ORIGINAL
    |--------------------------------------------------------------------------
    */
    Route::prefix('analisis')
        ->name('analisis.')
        ->controller(AnalisisController::class)
        ->group(function () {

        Route::get('/nuevo', 'seleccionarLinea')->name('nuevo');
        Route::get('/{linea}/seleccionar-componente', 'seleccionarComponente')->name('seleccionar-componente');
        Route::get('/{linea}/crear/{componente}', 'crear')->name('crear');

        Route::get('numeros-r/{categoria}', 'getNumerosR')->name('numeros-r');

        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{analisis}', 'show')->name('show');
        Route::get('/{analisis}/edit', 'edit')->name('edit');
        Route::put('/{analisis}', 'update')->name('update');
        Route::delete('/{analisis}', 'destroy')->name('destroy');

        Route::get('/linea/{linea}', 'porLinea')->name('porLinea');
        Route::get('/exportar/excel', 'exportarExcel')->name('exportar.excel');
        Route::get('/{analisis}/pdf', 'exportPdf')->name('exportar.pdf');
        Route::get('/analisis/exportar/lavadoras', 'exportarTodas')->name('analisis.exportar.lavadoras');
        Route::get('/estadisticas', 'estadisticas')->name('estadisticas');
        Route::post('/{analisis}/eliminar-foto', 'eliminarFoto')->name('eliminar-foto');
        Route::get('/linea/{linea}/componentes', 'getComponentes')->name('linea.componentes');
        Route::get('/componente/{componente}/reductores', 'getReductores')->name('componente.reductores');
    });

    /*
    |--------------------------------------------------------------------------
    | ELONGACIÓN (relacionada con análisis)
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
    Route::prefix('api')
        ->name('api.')
        ->group(function () {
            
        Route::get('categorias/{categoria}/numeros-r', [AnalisisController::class, 'getNumerosR']);
        Route::get('estadisticas/dashboard', [ApiController::class, 'dashboard']);
        Route::get('analisis/tendencia/{linea}', [ApiController::class, 'tendenciaLinea']);
        Route::get('analisis/danos-tendencia', [ApiController::class, 'danosTendencia']);
        
        // API para Pasteurizadora
        Route::prefix('pasteurizadora')
            ->name('pasteurizadora.')
            ->group(function () {
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
    Route::get('/plan-accion/dashboard', [PlanAccionController::class, 'dashboard'])->name('plan-accion.dashboard');
    Route::get('/plan-accion/por-lavadora/{lavadora}', [PlanAccionController::class, 'porLavadora'])->name('plan-accion.por-lavadora');
    Route::post('/plan-accion/{id}/notificar', [App\Http\Controllers\PlanAccionController::class, 'enviarNotificaciones'])
         ->name('plan-accion.notificar');

    /*
    |--------------------------------------------------------------------------
    | REPORTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('reportes')
        ->name('reportes.')
        ->controller(ReporteController::class)
        ->group(function () {
            
        Route::get('/', 'index')->name('index');
        Route::get('/elongacion', 'elongacion')->name('elongacion');
        Route::get('/componentes', 'componentes')->name('componentes');
        Route::get('/paros', 'paros')->name('paros');
        Route::get('/pasteurizadora', 'pasteurizadora')->name('pasteurizadora');
        
        // ===============================
        // EXPORTACIONES
        // ===============================
        Route::get('/export/excel', 'exportExcel')->name('export.excel');
        Route::get('/export/pdf', 'exportPdf')->name('export.pdf');
    });

    /*
    |--------------------------------------------------------------------------
    | LÍNEAS (Solo admin/ingeniero)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin|ingeniero_mantenimiento'])->group(function () {
        Route::resource('lineas', LineaController::class);
        Route::patch('/lineas/{linea}/toggle', [LineaController::class, 'toggleActivo'])
            ->name('lineas.toggle');
    });

    /*
    |--------------------------------------------------------------------------
    | NOTIFICACIONES Y CONFIGURACIÓN DE PERFIL
    |--------------------------------------------------------------------------
    */
    // Configuración de notificaciones
    Route::get('/profile/notifications', [App\Http\Controllers\ProfileController::class, 'notificationSettings'])
         ->name('profile.notifications');
    Route::put('/profile/notifications', [App\Http\Controllers\ProfileController::class, 'updateNotificationSettings'])
         ->name('profile.notifications.update');
    
    // Marcar notificaciones como leídas
    Route::post('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])
         ->name('notifications.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])
         ->name('notifications.read-all');
});

/*
|--------------------------------------------------------------------------
| ELONGACIONES (SIN AUTH)
|--------------------------------------------------------------------------
*/
Route::middleware(['web'])
    ->prefix('elongaciones')
    ->name('elongaciones.')
    ->controller(ElongacionController::class)
    ->group(function () {

    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{elongacion}', 'show')->name('show');
    Route::get('/{elongacion}/edit', 'edit')->name('edit');
    Route::put('/{elongacion}', 'update')->name('update');
    Route::delete('/{elongacion}', 'destroy')->name('destroy');
    Route::get('/reportes/elongaciones', 'reporte')->name('reporte');
    Route::get('/reportes/elongaciones/{linea}', 'reporte')->name('reporte.linea');
});

/*
|--------------------------------------------------------------------------
| RUTA PARA PRUEBAS (SOLO DESARROLLO)
|--------------------------------------------------------------------------
*/
Route::get('/test-notifications/{planId}', function($planId) {
    if (app()->environment('local')) {
        $service = app(\App\Services\NotificationService::class);
        $resultados = $service->notificarActividadManualmente($planId);
        return response()->json($resultados);
    }
    abort(404);
});

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';