# Checklist De Seguridad

Este checklist es de soporte. No cambia el comportamiento de la aplicacion.

## Entorno

- `.env` no debe versionarse.
- `.env.example` debe contener placeholders, no credenciales reales.
- `APP_DEBUG=false` en produccion.
- `APP_ENV=production` solo en servidores reales.
- Rotar cualquier credencial que haya estado en archivos versionados.

## Accesos

- Revisar que el registro publico sea intencional.
- Asignar rol al crear usuarios nuevos.
- Validar `activo` en login si el proyecto usa usuarios deshabilitados.
- Revisar rutas publicas antes de desplegar.

## Notificaciones

- Guardar tokens de UltraMsg/Twilio solo en `.env`.
- Usar destinatarios configurables, no numeros fijos en controladores.
- Registrar fallos sin exponer tokens ni datos sensibles.

## Archivos

- No versionar `backup.sql`, ZIPs, caches ni archivos temporales.
- Validar tipo y tamano de evidencias.
- Mantener `storage:link` controlado por permisos del servidor.

## Produccion

- Ejecutar `php artisan config:cache` y `php artisan route:cache` solo despues de validar `.env`.
- Ejecutar scheduler con usuario de sistema limitado.
- Hacer respaldos fuera del repositorio.
- Revisar logs periodicamente.
