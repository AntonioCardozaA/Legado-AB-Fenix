# Optimización 23: presupuesto de ejecución de colas y cron

F26, cambio de código autorizado; activación/configuración de producción reservada para revisión separada. Se explicó previamente la incompatibilidad entre lock de 55 s y jobs de al menos 120 s.

QueueExecutionBudget obtiene los timeouts de los constructores de los cuatro jobs existentes, que solo leen configuración. No ejecuta ni despacha trabajos. CronQueueController usa el máximo como timeout de worker y un lease de al menos max-time + timeout + 30 s, conservando un lease configurado que sea mayor. Esto contempla que max-time se comprueba entre trabajos y puede empezar un último job justo antes del límite.

Nuevo diagnóstico de solo configuración: `php artisan maintenance:queue:check --worker-timeout=240 --json`. No consulta tablas de jobs ni proveedores. Informa conexión durable, retry_after, mínimo requerido y soporte de señales. Un código de salida 1 significa configuración aún no preparada, no un error de procesamiento.

Pruebas: worker simulado con max-time 50 s/job 120 s requiere lease 200 s en lugar de 55 s; se comprueba timeout y liberación del lock. Con job 400 s aumenta el mínimo de retry a 430 s y se conserva lease manual de 1000 s. Dos pruebas, diez aserciones aprobadas. No se simula velocidad de guardado ni se procesa una cola real.

Diagnóstico local actual: sync, SAPI CLI Windows, sin pcntl; planes 195 s, índice 120 s, WhatsApp 45 s. Worker supervisado de 240 s requiere retry_after mínimo 270 s. El cron calcula worker 195 s y lease 275 s. **No se activa una cola real ni se cambia retry_after**. Un lease finito no garantiza exclusión si un proceso supera su timeout sin poder ser terminado; este límite es explícito y requiere supervisión adecuada.

Pendiente de aprobación de despliegue: seleccionar backend durable/worker supervisado, verificar SAPI del servidor, señales o límites externos, ajustar retry_after por encima del presupuesto y probar reintentos/idempotencia ante fallos reales. Las versiones actuales de jobs y proveedores determinan el presupuesto; volver a ejecutar el diagnóstico después de cambiar timeouts/fallbacks.

Reversión del código del cron: restaurar timeout/lease configurados anteriores. No se modifican jobs, intentos de negocio, datos ni dependencias. La corrección evita la ventana conocida bajo timeouts efectivos; no se presenta como sustituto de un worker supervisado.
