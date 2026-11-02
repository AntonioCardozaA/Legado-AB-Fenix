# Optimización 34: caché de assets y gzip negociado

F20. Se explicó previamente la falta de compresión/frescura explícita y el riesgo de cachear contenido privado. Se modificó public/.htaccess y se añadió tools/compress-build-assets.mjs al comando npm run build. Solo archivos existentes de build/assets con hash y logos WebP optimizados con hash reciben Cache-Control public, max-age=31536000, immutable. Archivos sin hash, HTML, respuestas privadas y assets inexistentes no reciben esa política.

mod_deflate está deshabilitado en Apache local; no se cambió httpd.conf ni se reinició el servidor. Se preparan sidecars gzip durante el build. Apache los negocia únicamente para CSS/JS con hash, cuando existen ambos archivos, el cliente acepta gzip y no lo excluye con q=0. Sin mod_headers no se aplica la reescritura; sin sidecar se conserva el original. MIME y Content-Encoding se establecen explícitamente y se impide recomprimir sidecars si mod_deflate está activo. Vary separa ambas representaciones. AddOutputFilterByType queda condicionado a disponibilidad del módulo.

Validación HTTP real Apache 2.4.68 local, misma URL y contenido:

| Recurso | Antes, bytes transferidos de cuerpo | Después con gzip | Contenido descomprimido |
|---|---:|---:|---|
| auth-tailwind-SoHZy1tb.css | 257253 | 27747 | Idéntico al original |
| app-BXS-Op9n.js | 81848 | 30572 | Idéntico al original |

Se compararon los buffers completos descargados con identity/gzip y con los archivos del build, no solo longitudes. Respuestas gzip llevan Content-Encoding/MIME correctos. gzip;q=0 conserva identidad. Tres sidecars del build: 470913 → 77148 bytes. La hoja layout-app.css sin hash sigue sin caché larga; asset inexistente responde 404 sin caché larga; dashboard privado sin sesión responde 302 con Cache-Control no-cache, private. No se enviaron credenciales ni se probó escritura de negocio.

El beneficio medido es la transferencia y la frescura explícita, no un tiempo HTTP garantizado ni concurrencia. No se ha verificado el servidor de producción. Despliegue requiere npm run build y entregar originales, sidecars y manifiesto juntos; README actualizado. Reversión: recuperar .htaccess anterior y el script build anterior; los sidecars sin reglas son inertes y no afectan datos.
