# Optimización 33: Tailwind compilado en el layout autenticado

F19. Se explicó previamente la descarga/ejecución del compilador CDN y el riesgo de perder clases dinámicas. Se añadió auth-tailwind.css, tailwind.auth.config.cjs y su entrada de Vite. La configuración conserva los defaults del CDN, sin el plugin forms ni la familia Figtree de guest. Escanea Blade, PHP y JS, e incluye safelist para colores de elongaciones/reportes ensamblados en ejecución. No se instalaron/actualizaron dependencias.

Antes de activar el enlace se probó el CSS en copias del mismo HTML. Chrome, perfil temporal y fixtures sintéticos, formulario/historial/diagramas/dashboard a 1440 y 390 px: ocho comparaciones de estilos relevantes iguales; siete capturas sin diferencias de píxeles, historial de escritorio difiere en 13 píxeles. Sin errores JS, recursos fallidos ni overflow horizontal; dashboard conserva Chart. Se inspeccionó captura de dashboard. Una ejecución con una ruta de fixture incorrecta produjo 404, se descartó y se añadió validación de existencia antes de abrir Chrome.

Build local Vite aprobado: auth-tailwind-SoHZy1tb.css, 257253 bytes, gzip estimado por Vite 27,81 KB. El compilador Tailwind CDN deja de solicitarse/ejecutarse. La hoja se solicita una vez y se reutiliza en el servidor de prueba con caché explícito. No se interpreta un ResourceTiming cross-origin de cero bytes como descarga nula del CDN; no se afirma ahorro temporal ni Web Vitals de producción. La compresión real Apache se comprueba separadamente.

Tras activarlo en el layout se renderizó desde el manifiesto real y pasaron 13 pruebas de formulario/historial/estados/pasteurizadora, 153 aserciones. El enlace va al final del head para conservar la posición efectiva del CSS generado por el CDN. Guest mantiene su configuración por ahora.

Despliegue: ejecutar npm run build y entregar public/build junto con manifest.json antes de activar el código. Estos artefactos están ignorados por Git; un manifiesto antiguo no contiene la nueva entrada y provocaría error de render. README lo explicita. No se activa ningún build en producción.

Reversión: restaurar el script CDN en el layout y retirar únicamente @vite de auth-tailwind.css; puede conservarse el build sin afectar datos. Riesgo residual: clases dinámicas futuras requieren ampliar la safelist y validación de sus vistas.
