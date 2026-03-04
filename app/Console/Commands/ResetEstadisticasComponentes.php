<?php
// app/Console/Commands/ResetEstadisticasComponentes.php

namespace App\Console\Commands;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\HistorialRestablecimiento;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetEstadisticasComponentes extends Command
{
    protected $signature = 'componentes:reset-estadisticas 
                            {--fecha= : Fecha específica para simular el reset (YYYY-MM-DD)}
                            {--simular : Solo simular sin eliminar}';

    protected $description = 'Restablece las estadísticas de componentes según su periodicidad (4 meses o anual)';

    /**
     * Configuración de periodicidad por código de componente (en meses)
     */
    private $periodicidadComponentes = [
        // Cada 4 meses
        'CATARINAS' => 4,
        'GUI_INF_TANQUE' => 4,
        'GUI_INT_TANQUE' => 4,
        'GUI_SUP_TANQUE' => 4,
        
        // Cada año (12 meses)
        'SERVO_CHICO' => 12,
        'SERVO_GRANDE' => 12,
        'BUJE_ESPIGA' => 12,
        'RV200' => 12,
        'RV200_SIN_FIN' => 12,
    ];

    /**
     * Mapeo de códigos a IDs de componentes (basado en tu seeder)
     */
    private $mapaCodigoToId = [];

    public function handle()
    {
        $this->info('🔍 Iniciando restablecimiento de estadísticas de componentes...');
        
        // Cargar el mapeo de códigos a IDs
        $this->cargarMapaComponentes();
        
        $fechaReferencia = $this->option('fecha') 
            ? Carbon::parse($this->option('fecha')) 
            : Carbon::now();
        
        $simular = $this->option('simular');
        
        if ($simular) {
            $this->warn("🔧 MODO SIMULACIÓN - No se realizarán cambios reales");
        }
        
        $this->info("📅 Fecha de referencia: " . $fechaReferencia->format('d/m/Y H:i:s'));
        
        // Obtener todos los análisis con sus relaciones
        $analisis = AnalisisLavadora::with(['linea', 'componente'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $stats = [
            'total_analisis' => $analisis->count(),
            'analisis_a_restablecer' => 0,
            'analisis_ya_restablecidos' => 0,
            'componentes_afectados' => [],
            'lineas_afectadas' => [],
            'detalles' => []
        ];
        
        $bar = $this->output->createProgressBar($analisis->count());
        $bar->start();
        
        foreach ($analisis as $item) {
            $codigoComponente = $item->componente ? $item->componente->codigo : null;
            
            if (!$codigoComponente || !isset($this->periodicidadComponentes[$codigoComponente])) {
                $bar->advance();
                continue; // Componente no configurado para restablecimiento
            }
            
            // Verificar si ya fue restablecido anteriormente
            $yaRestablecido = HistorialRestablecimiento::where('analisis_id', $item->id)->exists();
            
            if ($yaRestablecido) {
                $stats['analisis_ya_restablecidos']++;
                $bar->advance();
                continue;
            }
            
            $mesesPeriodo = $this->periodicidadComponentes[$codigoComponente];
            $fechaLimite = $fechaReferencia->copy()->subMonths($mesesPeriodo);
            
            // Si el análisis es anterior a la fecha límite, debe ser restablecido
            if (Carbon::parse($item->created_at)->lt($fechaLimite)) {
                $stats['analisis_a_restablecer']++;
                
                // Registrar para estadísticas
                if (!in_array($codigoComponente, $stats['componentes_afectados'])) {
                    $stats['componentes_afectados'][] = $codigoComponente;
                }
                
                if ($item->linea && !in_array($item->linea->nombre, $stats['lineas_afectadas'])) {
                    $stats['lineas_afectadas'][] = $item->linea->nombre;
                }
                
                $stats['detalles'][] = [
                    'id' => $item->id,
                    'linea' => $item->linea ? $item->linea->nombre : 'N/A',
                    'componente' => $codigoComponente,
                    'reductor' => $item->reductor,
                    'fecha' => $item->created_at->format('d/m/Y'),
                    'periodo' => $mesesPeriodo . ' meses'
                ];
                
                // Si no es simulación, proceder con el restablecimiento
                if (!$simular) {
                    $this->restablecerAnalisis($item, $fechaReferencia, $mesesPeriodo);
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Mostrar resultados
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['📊 Total de análisis', $stats['total_analisis']],
                ['🔄 Análisis a restablecer', $stats['analisis_a_restablecer']],
                ['✅ Análisis ya restablecidos', $stats['analisis_ya_restablecidos']],
                ['📦 Componentes afectados', implode(', ', $stats['componentes_afectados']) ?: 'Ninguno'],
                ['📏 Líneas afectadas', implode(', ', $stats['lineas_afectadas']) ?: 'Ninguna'],
            ]
        );
        
        if (!empty($stats['detalles'])) {
            $this->newLine();
            $this->info('📋 Detalle de análisis a restablecer:');
            
            $detallesTable = array_map(function($detalle) {
                return [
                    $detalle['id'],
                    $detalle['linea'],
                    $detalle['componente'],
                    $detalle['reductor'],
                    $detalle['fecha'],
                    $detalle['periodo']
                ];
            }, array_slice($stats['detalles'], 0, 20)); // Mostrar solo primeros 20
            
            $this->table(
                ['ID', 'Línea', 'Componente', 'Reductor', 'Fecha', 'Periodo'],
                $detallesTable
            );
            
            if (count($stats['detalles']) > 20) {
                $this->info("... y " . (count($stats['detalles']) - 20) . " más");
            }
        }
        
        if (!$simular && $stats['analisis_a_restablecer'] > 0) {
            $this->info('✅ Proceso de restablecimiento completado.');
            
            // Guardar registro del último reset
            DB::table('configuraciones')->updateOrInsert(
                ['clave' => 'ultimo_reset_estadisticas'],
                [
                    'valor' => $fechaReferencia->toDateTimeString(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        } elseif ($simular) {
            $this->warn('🔧 Simulación completada - No se realizaron cambios');
        } else {
            $this->info('✨ No hay análisis para restablecer en este momento');
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Cargar el mapeo de códigos de componentes a IDs
     */
    private function cargarMapaComponentes()
    {
        $componentes = Componente::whereIn('codigo', array_keys($this->periodicidadComponentes))->get();
        
        foreach ($componentes as $componente) {
            $this->mapaCodigoToId[$componente->codigo] = $componente->id;
        }
    }
    
    /**
     * Restablecer un análisis específico
     */
    private function restablecerAnalisis($analisis, $fechaReferencia, $periodoMeses)
    {
        try {
            DB::transaction(function () use ($analisis, $fechaReferencia, $periodoMeses) {
                // Crear registro en historial
                HistorialRestablecimiento::create([
                    'analisis_id' => $analisis->id,
                    'linea_id' => $analisis->linea_id,
                    'componente_id' => $analisis->componente_id,
                    'reductor' => $analisis->reductor,
                    'lado' => $analisis->lado,
                    'fecha_analisis_original' => $analisis->fecha_analisis,
                    'fecha_restablecimiento' => $fechaReferencia,
                    'motivo' => 'periodicidad',
                    'periodo_meses' => $periodoMeses,
                ]);
                
                // Opcional: Marcar el análisis como inactivo o eliminarlo
                // $analisis->delete();
                
                // O puedes agregar un campo 'restablecido' a la tabla analisis_componentes
                // y marcarlo aquí
            });
            
            Log::info("Análisis restablecido correctamente", [
                'id' => $analisis->id,
                'linea_id' => $analisis->linea_id,
                'componente_id' => $analisis->componente_id,
                'fecha_original' => $analisis->created_at,
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error al restablecer análisis {$analisis->id}: " . $e->getMessage());
        }
    }
}