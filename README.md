# Legado AB Fenix

Sistema Laravel para seguimiento operativo de lavadoras, pasteurizadoras, elongaciones de cadena, planes de accion, reportes y notificaciones.

Este repositorio esta orientado a operacion interna. La aplicacion ya contiene modulos funcionales para registro de analisis, dashboards, historial, reportes PDF/Excel y alertas por correo/WhatsApp.

## Modulos Principales

- Lavadoras: registros de componentes por linea, reductor, lado, estado, evidencia fotografica e historial.
- Pasteurizadoras: analisis por linea, modulo, componente, nivel, lado, ciclos de revision y evidencia.
- Elongaciones: mediciones de cadena, ciclos activos, comparacion de ciclos y recordatorios programados.
- Plan de accion: actividades por equipo, fechas PCM, checklist, alertas y notificaciones.
- Reportes: vistas consolidadas, exportacion PDF y Excel.
- Usuarios y roles: admin, ingeniero de mantenimiento, supervisor y tecnico.

## Requisitos

- PHP 8.2 o superior
- Composer
- Node.js y npm
- MySQL o MariaDB
- Extension PHP para ZIP, GD/Imagick, PDO MySQL y OpenSSL

## Instalacion Local

1. Instalar dependencias PHP:

```bash
composer install
```

2. Instalar dependencias frontend:

```bash
npm install
```

3. Crear el archivo de entorno:

```bash
cp .env.example .env
```

En Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

4. Generar la llave de Laravel:

```bash
php artisan key:generate
```

5. Configurar la base de datos en `.env` y ejecutar migraciones:

```bash
php artisan migrate --seed
```

6. Crear el enlace publico de storage para evidencias:

```bash
php artisan storage:link
```

7. Compilar assets:

```bash
npm run build
```

8. Levantar el servidor local:

```bash
php artisan serve
```

Tambien puedes usar el script integrado:

```bash
composer run dev
```

## Comandos Utiles

```bash
php artisan test
composer run test
npm run dev
npm run build
php artisan route:list --except-vendor
php artisan elongaciones:send-reminders --dry-run
php artisan notifications:send-activities
php artisan componentes:reset-estadisticas --simular
```

## Tareas Programadas

El scheduler ejecuta recordatorios de elongacion desde `routes/console.php`.

Para produccion se debe configurar un cron que llame al scheduler cada minuto:

```bash
* * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

En Windows se puede usar el Programador de tareas apuntando a:

```powershell
php artisan schedule:run
```

## Variables de Entorno Importantes

- `APP_URL`: URL base de la aplicacion.
- `DB_*`: conexion a base de datos.
- `MAIL_*`: configuracion de correo.
- `ULTRAMSG_*`: envio de WhatsApp por UltraMsg.
- `TWILIO_*`: envio SMS si se usa Twilio.
- `ELONGACION_ALERT_*`: horario, zona horaria y destinatarios de recordatorios.
- `QUEUE_CONNECTION`: usar `database` o un driver de colas real en produccion si las notificaciones deben procesarse en segundo plano.

Nunca guardes credenciales reales en `.env.example`, README, seeders publicos o archivos versionados.

## Pruebas

La configuracion de PHPUnit usa SQLite en memoria. Para ejecutar la suite:

```bash
php artisan test
```

O mediante Composer:

```bash
composer run test
```

## Estructura Relevante

- `app/Http/Controllers`: controladores de modulos operativos.
- `app/Models`: modelos principales del dominio.
- `app/Services`: servicios de notificaciones y WhatsApp.
- `app/Console/Commands`: comandos programados o manuales.
- `database/migrations`: estructura de base de datos.
- `database/seeders`: datos base de roles, lineas y usuarios iniciales.
- `resources/views`: pantallas Blade.
- `routes/web.php`: rutas web autenticadas y rutas internas.
- `routes/console.php`: agenda de comandos.
- `tests`: pruebas feature/unit.

## Seguridad Operativa

- Mantener `.env` fuera de Git.
- Rotar cualquier token o password que haya sido compartido en plantillas o respaldos.
- Usar `APP_DEBUG=false` en produccion.
- Evitar credenciales fijas en seeders de produccion.
- Revisar permisos por rol antes de exponer rutas nuevas.
- Mantener respaldos SQL y ZIP fuera del repositorio.

## Documentacion Interna

Tambien se incluyen notas de soporte en:

- `docs/PROJECT_MAP.md`
- `docs/OPERATIONS.md`
- `docs/SECURITY_CHECKLIST.md`
