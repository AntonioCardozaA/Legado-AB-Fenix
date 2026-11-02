# Legado AB Fenix

Sistema Laravel para seguimiento operativo de lavadoras, pasteurizadoras, elongaciones de cadena, planes de accion, reportes y notificaciones.

El [seguimiento de rendimiento](docs/OPTIMIZACION_RENDIMIENTO_PROGRESO.md) registra los 33 hallazgos de la auditoría, cambios medidos y pendientes. Consulta el [cierre del código autorizado](docs/OPTIMIZACION_RENDIMIENTO_67_CIERRE_CODIGO.md) y la [propuesta de despliegue](docs/OPTIMIZACION_RENDIMIENTO_48_DESPLIEGUE.md) antes de entregar estas optimizaciones.

Este repositorio esta orientado a operacion interna. La aplicacion ya contiene modulos funcionales para registro de analisis, dashboards, historial, reportes PDF/Excel y alertas por correo/WhatsApp.

## Modulos Principales

- Lavadoras: registros de componentes por linea, reductor, lado, estado, evidencia fotografica e historial.
- Pasteurizadoras: analisis por linea, modulo, componente, nivel, lado, ciclos de revision y evidencia.
- Elongaciones: mediciones de cadena, ciclos activos, comparacion de ciclos y recordatorios programados.
- Plan de accion: actividades por equipo, fechas PCM, checklist, alertas y notificaciones.
- Reportes: vistas consolidadas, exportacion PDF y Excel.
- Usuarios y roles: admin, ingeniero de mantenimiento, supervisor y tecnico.

## Requisitos

- PHP 8.4.1 o superior dentro de la rama 8.x, de 64 bits (CLI, servidor web y workers)
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

El layout autenticado usa `resources/css/auth-tailwind.css` compilado por Vite, con clases dinámicas en `tailwind.auth.config.cjs`. Compila antes de activar una revisión y entrega también `public/build` y su manifiesto (no están versionados en Git). Un manifiesto anterior sin este entrypoint no es compatible. Al añadir clases construidas dinámicamente en PHP/JS, actualiza su safelist y comprueba la vista. CLI/PHPUnit y servidor deben usar el mismo build; no se requiere el compilador Tailwind CDN en el layout autenticado.

`npm run build` genera además sidecars `.gz` de CSS/JS. Entrégalos junto a los originales: `public/.htaccess` negocia gzip y conserva la variante sin compresión. La caché larga se limita a archivos existentes con hash. Las reglas se verificaron con Apache 2.4 local; valida también las cabeceras en el servidor de destino.

El aviso automático de elongación usa `ELONGACION_WHATSAPP_DISPATCH_MODE=after_response` por defecto en web; `sync` recupera el envío antes de responder y `queue` requiere backend/worker revisados por separado. `ELONGACION_WHATSAPP_QUEUE` elige cola (default). El job se crea después del commit, conserva mensaje/datos del evento y no añade reintentos de job. after_response ocupa PHP y no es durable: valida SAPI/buffering y entrega en staging antes de desplegar. No actives queue ni cambies retry_after en producción sin la revisión operativa correspondiente.

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
- `AI_ENABLED`, `AI_PROVIDER`, `AI_FALLBACK_PROVIDER`, `AI_QUEUE`, `AI_DISPATCH_MODE`, `AI_HISTORY_INDEX_QUEUE`: activacion, proveedor, fallback, cola prioritaria para planes IA y cola separada para indexacion historica.
- `OPENAI_API_KEY`, `OPENAI_MODEL`, `OPENAI_EMBEDDING_MODEL`: credenciales y modelos de OpenAI para chat, planes, OCR y embeddings.
- `GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_EMBEDDING_MODEL`: credenciales y modelos Gemini si se usa como proveedor principal o fallback.
- `GEMINI_CONNECT_TIMEOUT`, `GEMINI_REQUEST_TIMEOUT`, `GEMINI_MAX_RETRIES`: controlan timeout de conexion, timeout total de respuesta y reintentos HTTP/timeout de Gemini sin subir indiscriminadamente otros proveedores.
- `AI_WEB_SEARCH_ENABLED`, `AI_WEB_SEARCH_MODE`, `AI_WEB_SEARCH_PROVIDER`, `AI_WEB_SEARCH_FALLBACK_PROVIDER`: activan el modo hibrido del asistente; primero usa datos internos/base de conocimiento y solo consulta web cuando la pregunta lo requiere o falta evidencia interna.
- `OPENAI_WEB_SEARCH_MODEL`, `GEMINI_WEB_SEARCH_MODEL`, `AI_WEB_SEARCH_CONTEXT_SIZE`: modelos y tamano de contexto para busqueda web externa.
- `AI_CHAT_RATE_LIMIT_PER_MINUTE`: limite por usuario/IP para controlar costo y abuso del chat.
- `AI_KNOWLEDGE_SEMANTIC_QUERY_ENABLED`, `AI_KNOWLEDGE_CANDIDATE_LIMIT`, `AI_KNOWLEDGE_*_WEIGHT`: controlan el ranking hibrido de documentos para el chatbot y los planes IA.

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
- `docs/SOP_GUIA_USUARIO.md`
