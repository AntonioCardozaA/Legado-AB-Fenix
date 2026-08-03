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

## IA

- Mantener `AI_ENABLED=false` hasta tener llaves, colas y permisos validados.
- Guardar `OPENAI_API_KEY`, `GEMINI_API_KEY` y cualquier llave IA solo en `.env` o secretos del servidor.
- Activar limite del chat con `AI_CHAT_RATE_LIMIT_PER_MINUTE`.
- Revisar periodicamente `ai_interaction_logs` para detectar errores, latencia alta, abuso de uso o consumo inesperado de tokens.
- Evitar subir documentos de conocimiento con secretos, passwords, datos personales innecesarios o instrucciones no confiables.
- Validar que los documentos indexados tengan ciclo de vida correcto (`vigente`/obsoleto) porque el RAG prioriza fragmentos vigentes por metadata.
- Confirmar que las sugerencias IA sigan pasando por aprobacion humana antes de publicarse como plan operativo.

## Archivos

- No versionar `backup.sql`, ZIPs, caches ni archivos temporales.
- Validar tipo y tamano de evidencias.
- Mantener `storage:link` controlado por permisos del servidor.

## Produccion

- Ejecutar `php artisan config:cache` y `php artisan route:cache` solo despues de validar `.env`.
- Ejecutar scheduler con usuario de sistema limitado.
- Hacer respaldos fuera del repositorio.
- Revisar logs periodicamente.
