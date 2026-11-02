# Corrección 14: requisito de PHP coherente con el lock

F11. Se explicó previamente que composer.json/README permitían PHP 8.2, pero el lock y vendor/composer/platform_check.php requieren PHP >=8.4.1 y 64 bits. Symfony clock, css-selector, event-dispatcher, string, translation y yaml fijan ese mínimo en las versiones ya bloqueadas.

Se alinean el requisito raíz a ^8.4.1, platform.php del lock y la documentación. El content-hash se calcula con Composer\Package\Locker::getContentHash del Composer local. No se actualiza/resuelve ningún paquete ni se modifica vendor, configuración del servidor o PHP instalado.

Validación: composer validate --no-check-publish y composer check-platform-reqs --lock aprobados con PHP 8.4.12 y todas las extensiones requeridas. La única diferencia de composer.lock es content-hash y platform.php; los paquetes conservan versiones y referencias.

Es una corrección de compatibilidad, sin mejora de latencia atribuida. Riesgo: un despliegue con PHP anterior debe actualizar su runtime antes de instalar estas dependencias, requisito que ya existía en el lock. Revisar CLI/CGI/workers de producción por separado. Revertir la metadata no permite ejecutar el vendor actual en PHP 8.2/8.3.
