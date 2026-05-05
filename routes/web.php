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
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Ruta raíz
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (AUTH)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'technician.access'])->group(function () {

    // ===========================================================
    // DASHBOARDS (ESTRUCTURA COMPLETA Y LIMPIA)
    // PREFIJO: /dashboard
    // ===========================================================
    Route::prefix('dashboard')->group(function () {

        // Dashboard principal - Selector de módulos
        Route::get('/', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/tecnico', [DashboardController::class, 'tecnico'])
            ->name('tecnico.dashboard');

        // =======================================================
        // DASHBOARDS GLOBALES (VISTAS PRINCIPALES)
        // =======================================================
        Route::get('/lavadoras', [DashboardController::class, 'lavadoraGlobal'])
            ->name('dashboard.global.lavadoras');

        Route::get('/pasteurizadoras', [DashboardController::class, 'pasteurizadoraGlobal'])
            ->name('dashboard.global.pasteurizadoras');

        // =======================================================
        // DASHBOARDS OPERATIVOS (CON DATOS)
        // =======================================================
        Route::get('/lavadora/operativo', [DashboardController::class, 'lavadoraOperativo'])
            ->name('dashboard.operativo.lavadora');

        Route::get('/pasteurizadora/operativo', [DashboardController::class, 'pasteurizadoraOperativo'])
            ->name('dashboard.operativo.pasteurizadora');

        // =======================================================
        // MÉTODOS DE COMPATIBILIDAD (BACKWARD COMPATIBILITY)
        // Mantienen las rutas anteriores para no romper código existente
        // =======================================================
        Route::get('/lavadora', [DashboardController::class, 'lavadora'])
            ->name('dashboard_lavadora');

        Route::get('/pasteurizadora', [DashboardController::class, 'pasteurizadora'])
            ->name('dashboard_pasteurizadora');
    });

    // ===========================================================
    // DASHBOARDS POR MÓDULO (VISTAS ANIDADAS)
    // Prefijo: /lavadora, /pasteurizadora
    // ===========================================================
    
    // Dashboard operativo de lavadora (vista anidada)
    Route::prefix('lavadora')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'lavadora'])
            ->name('lavadora.dashboard');
    });

    // Dashboard operativo de pasteurizadora (vista anidada)
    Route::prefix('pasteurizadora')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'pasteurizadora'])
            ->name('pasteurizadora.dashboard');
    });

    /*
    |--------------------------------------------------------------------------
    | ALIAS de compatibilidad
    |--------------------------------------------------------------------------
    | Para referencias antiguas a route('dashboard')
    */
    Route::get('/dashboard-alias', [DashboardController::class, 'index'])
        ->name('dashboard.alias');

    /*
    |--------------------------------------------------------------------------
    | API daños tendencia
    |--------------------------------------------------------------------------
    */
    Route::get('/api/danos-tendencia', [DashboardController::class, 'getDanosTendenciaApi'])
        ->name('api.danos-tendencia');

    /*
    |--------------------------------------------------------------------------
    | Perfil de usuario
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
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
    }); // 👈 Cierra el grupo de ANALISIS LAVADORA

    /*
    |--------------------------------------------------------------------------
    | ANALISIS TENDENCIA MENSUAL LAVADORA
    |--------------------------------------------------------------------------
    */
    Route::prefix('analisis-tendencia-mensual')
        ->name('analisis-tendencia-mensual.lavadora.')
        ->controller(AnalisisTendenciaMensualLavadoraController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{analisis}', 'show')->name('show');
        });

    /*
    |--------------------------------------------------------------------------
    | PASTEURIZADORA (GRUPO PRINCIPAL)
    |--------------------------------------------------------------------------
    */
    Route::prefix('pasteurizadora')->name('pasteurizadora.')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | ANALISIS DE COMPONENTES
        |--------------------------------------------------------------------------
        */
        Route::prefix('analisis-pasteurizadora')
            ->name('analisis-pasteurizadora.')
            ->controller(AnalisisPasteurizadoraController::class)
            ->group(function () {

            // INDEX
            Route::get('/', 'index')->name('index');

            // SELECCIONAR LINEA
            Route::get('/seleccionar-linea', 'selectLinea')->name('select-linea');

            // CREAR
            Route::get('/crear/{linea}', 'createWithLinea')
                ->whereNumber('linea')
                ->name('create');

            Route::get('/crear', 'create')->name('create-legacy');
            Route::get('/crear-rapido', 'createQuick')->name('create-quick');

            Route::post('/', 'store')->name('store');
            Route::post('/store-quick', 'storeQuick')->name('store-quick');

            // HISTORIAL
            Route::get('/historial', 'historial')->name('historial');

            // HISTORICO REVISADOS
            Route::get('/historico-revisados', 'historicoRevisados')->name('historico-revisados');

            // PLAN DE ACCION
            Route::get('/plan-accion', 'planAccion')->name('plan-accion.index');
            Route::get('/plan-accion/create', 'createPlanAccion')->name('plan-accion.create');
            Route::post('/plan-accion/update', 'updatePlanAccion')->name('plan-accion.update');

            // EXPORTACIONES
            Route::get('/export/excel', 'exportExcel')->name('export.excel');
            Route::get('/export/pdf', 'exportPdf')->name('export.pdf');
            Route::post('/export-process', 'exportProcess')->name('export-process');

            // AJAX
            Route::get('/ajax/componentes', 'getComponentesPorLineaAjax')->name('ajax.componentes');
            Route::post('/ajax/remaining-components', 'getRemainingComponentsAjax')->name('ajax.remaining-components');
            Route::post('/ajax/revision-context', 'getRevisionContextAjax')->name('ajax.revision-context');
            Route::post('/ajax/piezas-disponibles', 'getPiezasDisponiblesAjax')->name('ajax.piezas-disponibles');
            Route::get('/ajax/actividades', 'getActividadesPorModulo')->name('ajax.actividades');
            Route::get('/ajax/estadisticas', 'getEstadisticasComponentesAjax')->name('ajax.estadisticas');

            // CRUD
            Route::get('/{analisispasteurizadora}', 'show')
                ->whereNumber('analisispasteurizadora')
                ->name('show');

            Route::get('/{analisispasteurizadora}/editar', 'edit')
                ->whereNumber('analisispasteurizadora')
                ->name('edit');

            Route::put('/{analisispasteurizadora}', 'update')
                ->whereNumber('analisispasteurizadora')
                ->name('update');

            Route::delete('/{analisispasteurizadora}', 'destroy')
                ->whereNumber('analisispasteurizadora')
                ->name('destroy');

            // CREAR LINEAS
            Route::post('/crear-lineas', 'crearLineasPasteurizadora')
                ->name('crear-lineas');

            // FOTOS
            Route::delete('/{analisispasteurizadora}/foto/{fotoIndex}', 'deleteFoto')
                ->whereNumber('analisispasteurizadora')
                ->whereNumber('fotoIndex')
                ->name('delete-foto');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | ANALISIS TENDENCIA MENSUAL (pasteurizadora)
    |--------------------------------------------------------------------------
    */
    Route::prefix('analisis-tendencia-mensual/pasteurizadora')
        ->name('analisis-tendencia-mensual.pasteurizadora.')
        ->controller(AnalisisPasteurizadoraController::class)
        ->group(function () {
            Route::get('/', 'analisis52124')->name('index');
            Route::post('/update', 'updateAnalisis52124')->name('update');
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
    | PLAN DE ACCIÓN
    |--------------------------------------------------------------------------
    */

    // Dashboard
    Route::get('/plan-accion/dashboard', [PlanAccionController::class, 'dashboard'])
        ->name('plan-accion.dashboard');

    // Filtrar por lavadora
    Route::get('/plan-accion/por-lavadora/{lavadora}', [PlanAccionController::class, 'porLavadora'])
        ->name('plan-accion.por-lavadora');

    // Notificaciones
    Route::post('/plan-accion/{id}/notificar', [PlanAccionController::class, 'notificar'])
        ->name('plan-accion.notificar');

    // Index especial para lavadoras
    Route::get('/plan-accion/lavadora', [PlanAccionController::class, 'planAccion'])
        ->name('plan-accion.lavadora.index');

    // Editar plan acción lavadora
    Route::post('/plan-accion/lavadora/edit', [PlanAccionController::class, 'editarPlanAccion'])
        ->name('plan-accion.lavadora.edit');

    // Actualización manual
    Route::post('/plan-accion/lavadora/update', [PlanAccionController::class, 'updatePlanAccion'])
        ->name('plan-accion.lavadora.update');

    Route::post('/plan-accion/lavadora/destroy', [PlanAccionController::class, 'destroy'])
        ->name('plan-accion.lavadora.destroy');
    
    Route::post('/plan-accion/{id}/checklist',[PlanAccionController::class,'checklist']);

    // CRUD completo
    Route::resource('plan-accion', PlanAccionController::class);

    /*
    |--------------------------------------------------------------------------
    | NOTIFICACIONES
    |--------------------------------------------------------------------------
    */
    Route::get('/notificaciones/configuracion', [NotificationSettingsController::class, 'index'])
        ->name('notificaciones.configuracion');
    
    Route::put('/notificaciones/configuracion', [NotificationSettingsController::class, 'update'])
        ->name('notificaciones.configuracion.update');
    
    Route::post('/notificaciones/verify-phone', [NotificationSettingsController::class, 'verifyPhone'])
        ->name('notificaciones.verify.phone');

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

        // NUEVAS RUTAS
        Route::get('/show', 'show')->name('show');
        Route::get('/show/{lineaId}', 'show')->name('show.linea');

        Route::get('/elongacion', 'elongacion')->name('elongacion');
        Route::get('/componentes', 'componentes')->name('componentes');
        Route::get('/paros', 'paros')->name('paros');
        Route::get('/pasteurizadora', 'pasteurizadora')->name('pasteurizadora');
        
        // ===============================
        // EXPORTACIONES
        // ===============================
        Route::get('/export/pdf', [ReporteController::class, 'exportar'])
            ->name('export-pdf');

        Route::get('/export/excel', [ReporteController::class, 'exportar'])
            ->name('export-excel');
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
    Route::get('/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount'])
         ->name('notifications.unread-count');
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
    Route::get('/comparacion-ciclos', 'comparacionCiclos')->name('ciclos.comparacion');
    Route::get('/ciclos/{ciclo}', 'showCiclo')->name('ciclos.show');
    Route::get('/ultima-lectura/{linea}', 'ultimaLectura')->name('ultima-lectura');
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
