<?php
// app/Console/Commands/SendActivityNotifications.php
namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendActivityNotifications extends Command
{
    protected $signature = 'notifications:send-activities';
    protected $description = 'Send notifications for upcoming activities';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('Iniciando envío de notificaciones...');
        
        try {
            $resultados = $this->notificationService->verificarYNotificarActividadesProximas();
            
            $this->info("Total notificaciones enviadas: {$resultados['total']}");
            
            if (!empty($resultados['errores'])) {
                $this->error("Errores encontrados: " . count($resultados['errores']));
                Log::warning('Errores en notificaciones', $resultados['errores']);
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Error en comando de notificaciones: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}