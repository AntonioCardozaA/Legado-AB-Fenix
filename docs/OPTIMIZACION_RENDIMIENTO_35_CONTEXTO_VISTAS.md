# Optimización 35: componer contexto solo en vistas consumidoras

F31. El composer global ejecutaba los mismos seis cálculos en todos los parciales. Se midieron 50 composiciones calentadas: mediana 182,598 ms, cero SQL. Antes de editar se explicó el cambio de alcance, sus archivos y el riesgo de omitir consumidores/mezclar usuarios.

AppServiceProvider registra ahora el mismo callback para las 19 vistas que consumen esas variables. No se cachean permisos ni resultados entre composiciones o peticiones: se vuelve a consultar el usuario actual con las mismas reglas. Una prueba descubre todos los Blade consumidores para detectar futuras omisiones; otra verifica admin/técnico, cambio de rol dentro de la aplicación y posterior invitado. Ocho pruebas relacionadas pasan, 86 aserciones.

Comparación alternada, mismo proceso, fixture de historial SQLite :memory: (165 filas, 75 visibles), reloj/sesión/HTML fijos, vistas compiladas y calentamiento de ambas variantes. Se clonó únicamente el dispatcher de vistas para restablecer el composer global anterior en memoria; cinco muestras por variante:

| Render completo | Mediana ms | SQL | HTML |
|---|---:|---:|---|
| Composer global | 184,85 | 13 | 824645 bytes |
| Solo consumidores | 126,03 | 13 | Idéntico byte por byte |

La huella completa coincide en las diez muestras. No se atribuye un ahorro SQL ni se extrapola a navegador/producción. Los demás cambios permanecen iguales entre variantes. Reversión: volver a registrar el mismo callback para '*'. Los consumidores futuros deben añadirse al listado; la prueba falla si se usan esas variables en una vista no cubierta.
