# Evaluación 42: conservar el procesamiento actual de evidencias

F24. Se explicó previamente que se mediría antes de cambiar calidad/resolución/copias. Herramienta tools/benchmark-evidence-images.php: configuración SQLite :memory: verificada, imágenes sintéticas, public_path y storage_path redirigidos a un directorio temporal. Sin fotos operativas, DB local ni proveedores externos. Un calentamiento y tres muestras por caso, con espejo habilitado.

| Caso sintético | Entrada bytes | Salida bytes | Dimensiones de salida | Mediana ms |
|---|---:|---:|---|---:|
| Preparado por cliente | 44993 | 44993 | 1600 × 1200 | 3,25 |
| JPEG grande | 282214 | 609965 | 1600 × 1200 | 842,27 |
| PNG grande | 28420 | 432509 | 1600 × 1067 | 568,31 |

En el primer caso se conservan exactamente los bytes originales: el servidor ya evita otra recodificación bajo 1600 px/1 MB. El cliente usa 1600 px, JPEG 0,82 y mantiene la orientación; por tanto sus umbrales ya están alineados. En los otros casos se conserva la reducción de dimensiones y el formato JPEG existente. Los patrones geométricos sintéticos pueden producir más bytes al redimensionarse; no representan una estimación de compresión de fotografías reales ni justifican bajar calidad.

Todas las copias espejo coinciden byte por byte. En el entorno local public/storage es una carpeta física distinta de storage/app/public; eliminar la copia cambiaría disponibilidad y compatibilidad. No se ha cambiado ImageEvidenceOptimizer, su fallback de GIF/formatos, orientación, límites, calidad, ni copia de seguridad.

Decisión: conservar el algoritmo actual; no hay comparación que respalde reemplazarlo. La optimización 22 ya elimina la reindexación redundante tras agregar evidencias. Procesado en cola exige estado explícito de evidencia y revisión de disponibilidad inmediata; no se introduce de forma implícita. Falta perfil con un conjunto autorizado de fotos representativas y el flujo HTTP real de carga.
