# Propuesta de entrega y validación operativa

Los cambios de código autorizados están en el árbol de trabajo y su evidencia está en el [seguimiento](OPTIMIZACION_RENDIMIENTO_PROGRESO.md) y el [cierre vigente](OPTIMIZACION_RENDIMIENTO_67_CIERRE_CODIGO.md). No se desplegó a producción, no se ejecutó DDL ni se activaron colas durables. Estas acciones requieren entorno de destino y revisión separada. El usuario permite dejarlas pendientes y el 2 de noviembre decidió posponer también idempotencia durable y exportaciones durables con sus migraciones.

## Entrega de código y assets

1. Preparar una revisión identificable y respaldo/reversión del código y assets actuales. La regresión local actual aprueba 462 pruebas; repetir las comprobaciones de entrega sobre la revisión exacta. Los ocho casos históricos se atendieron en 51–55 con fixtures válidos y el alcance LEF elegido por el usuario.
2. Comprobar PHP >=8.4.1 de 64 bits en CLI, SAPI web y worker; ejecutar Composer validate/check-platform-reqs con el lock. No actualizar dependencias como parte de la entrega.
3. Ejecutar npm run build en la revisión exacta a entregar. Incluir public/build/manifest.json, todos los assets y sidecars .gz, public/css/layout-app.css, public/js y los WebP de public/images/optimized. public/build está ignorado por Git: publicar solo los fuentes rompe las nuevas entradas @vite. Activar código/manifiesto/assets como una unidad; conservar los assets anteriores mientras existan documentos que los referencien.
4. Verificar las reglas .htaccess en el Apache de destino y su base de rutas. Para cada CSS/JS con hash: gzip negociado y contenido descomprimido idéntico; identity y gzip;q=0 correctos; MIME, Vary y caché larga adecuados. Privados/redirects/404 no deben heredar caché pública. Nginx u otro servidor necesita reglas equivalentes revisadas; no se aplicaron remotamente.
5. Verificar login y roles, abrir ambos dashboards/áreas, modales, filtros, formularios normal/rápido, guardar en datos de prueba, consultar todas las páginas del historial y descargar PDF/XLSX. Las rutas de producción deben responder correctamente sin depender del servidor de fixtures.
6. Medir frío/caliente con mismo usuario, datos, filtros, concurrencia y red: backend, render, bytes transferidos, LCP/INP/CLS, tareas largas y memoria. La comprobación local de fixtures no sustituye este paso. Revertir código y manifiesto/assets juntos si hay regresión.

OPcache, config/rutas/vistas precacheadas y reinicios se validarán en el SAPI de staging y en el pipeline real, con invalidación al publicar. La ausencia de OPcache CLI local no demuestra ausencia en web. No se ejecutaron comandos de caché sobre producción.

## Colas y tareas externas

Revisar un backend durable disponible, sus tablas o Redis, conectividad, credenciales, colas atendidas, permisos y almacenamiento de fallos. Esas comprobaciones y cualquier migración/activación no se sustituyen con cambiar QUEUE_CONNECTION. Preparar worker supervisado separado de HTTP y verificar soporte real de señales/timeout del sistema operativo.

El modo after_response de indexación agrupa por petición y espera commit; no es durable y mantiene ocupado PHP. El aviso de elongación también se difiere por defecto en web. Verificar entrega/flush/buffering en el SAPI de destino y fallo del proceso/proveedor. ELONGACION_WHATSAPP_DISPATCH_MODE=sync ofrece reversión al comportamiento de envío antes de responder; queue solo se activa con backend revisado. No se activó ninguna variable del entorno.

Para indexación: AI_HISTORY_INDEX_DISPATCH_MODE hereda AI_DISPATCH_MODE si no está configurado; AI_HISTORY_INDEX_QUEUE selecciona la cola. AI_QUEUE selecciona planes. ELONGACION_WHATSAPP_QUEUE selecciona avisos de elongación. Verificar que el worker consume todas las colas utilizadas; no asumir que los nombres del cron cubren una cola personalizada.

Ejecutar maintenance:queue:check --json sin procesar trabajos y contrastarlo con el comando/configuración del worker. Con la configuración local actual: worker 240 s y retry_after mínimo 270 s; cron worker 195 s y lease 275 s. Son valores calculados, no límites universales: recalcular tras cambiar timeouts. En SQS revisar visibility timeout fuera de Laravel. Probar rollback, orden, duplicación, proveedor fallido, recuperación y estado de jobs con destinatarios/proveedores de prueba. La agrupación de indexación actual cubre after_response, no promete coalescencia de múltiples jobs ya encolados.

Los exports siguen descargándose por el flujo actual; moverlos a jobs exige contrato de estado/descarga autorizada, caducidad y conservación de filtros/permisos. No se añadió una descarga pública ni se ocultó una generación lenta tras polling.

## Índices: propuesta concreta para staging, sin migración ejecutable

Conservar idx_analisis_linea_fecha de analisis_componentes. Evaluar retirar idx_analisis_componentes_linea_fecha e idx_analisis_componentes, ambos observados con linea_id/fecha_analisis. Antes, capturar definición completa/opciones/FK y confirmar dependencias y consultas externas. Medir SELECT, escrituras y bloqueo con copia representativa. Reversión: recrear ambos con sus opciones originales. No ejecutar DROP ni crear una tabla analisis vacía para ocultar la fuente heredada ausente.

## Cambios que requieren otra evaluación de diseño

Navegación parcial (F22): la continuación 66 ya implementa filtros/páginas del directorio, con URL/popstate/foco/permisos/cancelación/fallback y creación sin perder datos. Entregar también `public/js/partial-directory.js` y la nueva vista parcial; comprobar HTTP completo/fragmento en destino. No se interceptó navegación global.

Idempotencia durable (F28) y exportaciones durables (F25) quedaron pendientes por decisión explícita del usuario; no existe su implementación/migración en esta entrega. Restauración de la fuente heredada (F10), agregación adicional de metadatos/ciclos (F14) y cacheado de proveedores IA (F29) requieren evidencia/diseño y revisiones de esquema/infraestructura que apliquen. 65 mejora el Excel síncrono sin preparación descartada; 49/50/54/56 conservan metadatos necesarios para los historiales. No se redujo grounding, candidatos o calidad de imágenes para declarar completada la auditoría.
