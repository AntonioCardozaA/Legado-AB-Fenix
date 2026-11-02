# Optimización 40: directorio administrativo paginado

F30. Se explicó previamente la carga completa de usuarios, archivos a tocar y riesgo de perder filtros/registros. Volumen local observado: 49 usuarios, sin modificar sus datos. AdminUserController pagina a 25, conservando filtro de búsqueda/rol/estado, relaciones y orden activo/nombre. Se añade ID para resolver nombres idénticos de forma estable entre páginas. El contador usa el total filtrado completo y los enlaces preservan parámetros.

Once pruebas del módulo pasan, 66 aserciones: gestión existente, restricción de no-admin y prueba nueva con 58 técnicos, nombres empatados y estados mixtos; los 46 que cumplen filtros se recorren exactamente una vez en dos páginas, total y URL conservados. No se cambian permisos, creación/edición/borrado, ni campos mostrados.

Banco tools/benchmark-admin-list.php: SQLite :memory: verificado antes de migrar/crear, 165 usuarios sintéticos, mismo reloj/rol/sesión, calentamiento y tres muestras por variante:

| Métrica | Antes | Después |
|---|---:|---:|
| Usuarios hidratados/visibles | 165 | 25 |
| HTML bytes | 685891 | 293859 |
| SQL | 5 | 6 |
| Mediana render+datos ms | 132,71 | 117,07 |

Beneficio principal: 57 % menos HTML y menos modelos. COUNT añade una consulta; la reducción temporal es moderada y no se extrapola a producción. Las direcciones sintéticas se generan con Faker en cada proceso, por lo que no se afirma igualdad de bytes del contenido entre bancos: se comprueba completitud/orden/filtros en la prueba. La vista conserva el formulario de alta completo, que explica parte del HTML restante.

Reversión: recuperar get(), count() y quitar enlaces/orden de desempate. Sin cambios en registros o esquema. Los enlaces de edición conservan los filtros existentes; volver al directorio puede abrir su primera página, como navegación previa sin paginación.
