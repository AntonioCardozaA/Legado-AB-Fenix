# Optimización 19: memorizar el mapeo puro de códigos

F02/F05. Se explicó antes de editar la repetición de regex/ordenación del mismo código y la solución limitada a resultados puros, sin datos de usuarios o consultas. El método original se conserva en calcularCodigoBaseComponente; codigoBaseComponente añade memoria FIFO de hasta 2048 entradas por proceso, suficiente para los 1259 códigos locales observados. Se comprueba la equivalencia de código normalizado antes de reutilizar.

No hay invalidación dependiente de datos: el resultado usa solo el código de entrada y constantes del código fuente. No se memorizan nombres, estados, permisos, existencia de registros ni archivos. Los códigos desconocidos y numéricos y la expulsión de entradas conservan resultados. La prueba compara 4224 salidas contra el algoritmo original, limita el tamaño y revisa normalización de mayúsculas/espacios.

Banco equivalente MySQL READ ONLY: 25180 llamadas por muestra, sin imprimir códigos. Antes 1101,26 / 1162,45 / 1351,42 ms; después 56,14 / 9,71 / 10,44 ms (la primera incluye llenado). Huella idéntica `7c861af363d2cc942244e28aac357905a99050f0ea7b613ac4778e0ab84c6145`.

En dashboards completos, tres muestras, mismas consultas y huellas:

| Dashboard | Mediana antes ms | Después ms | SQL estable |
|---|---:|---:|---:|
| Global | 5963,23 | 5935,97 | 81 |
| Operativo | 6604,55 | 5829,92 | 81 |

El global no muestra una mejora relevante aislada; la mayor evidencia es el trabajo puro evitado y el descenso local del operativo. No se atribuye una aceleración global proporcional al microbanco. Se conserva la mejora de CPU con memoria acotada; si el perfil de staging no la justifica, revertir el wrapper a llamar directamente al algoritmo original es suficiente.

Doce pruebas de mapeo, estados y periodicidad aprobadas durante la validación. No hay cambios de datos ni DDL.
