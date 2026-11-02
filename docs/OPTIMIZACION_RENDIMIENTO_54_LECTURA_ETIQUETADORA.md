# Optimización 54: una fuente para el histórico de etiquetadora

F14/F18. El controlador construía dos consultas con los mismos filtros de línea/componente/máquina/catálogo y cargaba dos colecciones completas: una para la vista y otra para el resumen. Se explicó antes de modificar el controlador. Se reutiliza la colección ya filtrada; el cálculo de ciclos y último análisis conserva su orden interno explícito fecha/created_at/ID. No se pagina el resumen de catálogo ni se calcula desde una página; el historial individual ya tiene paginate(10).

Banco tools/benchmark-labeller-history.php, guard SQLite :memory: antes de migrar, 501 registros sintéticos, dos máquinas y múltiples ciclos, fechas empatadas, mismo usuario/reloj/sesión. Una preparación y tres muestras calientes por filtro, incluyendo preparación, hash de datos y render:

| Filtro | SQL antes/después | Mediana ms antes/después | HTML bytes |
|---|---:|---:|---:|
| Todas las máquinas | 12 / 8 | 2056,70 / 1688,04 | 168877 |
| A | 12 / 8 | 1081,12 / 878,92 | 155942 |
| B | 12 / 8 | 1197,71 / 873,62 | 155954 |

Coinciden hashes completos del payload y HTML en los tres filtros, incluidos IDs y resúmenes/ciclos. Payload: all 181b99c4a4130c24677c38333d2021712297b81331072a7cb6898f8137d25757, A 58db03dc819e3a744fb1b359687b612b7e17241791998d34669ed8c5943947ee, B 322e2cb5231e003ace15b6fea39bc60183c45c37376738d8b47b21153abad858. HTML: all 4663dd769ca8520f6d027768e895f81ccbcba6a9b994690100f87f6c1571d5f5, A e4aea245c5a33283c699cd73aa7a4c6e354b3838dea7f5f4381cfa974527569c, B e6dc640618afd2150bac3630e0163c1d4d82cacf748e867ad2d931f2101b7d34.

El pico adicional apenas cambia: todas 5009568 → 4988952 bytes; domina una fase posterior, así que no se declara reducción sustancial de memoria. Tampoco son tiempos HTTP ni percentiles de producción.

Treinta y una pruebas existentes de separación/catálogo/ciclos/checklist/filtros/dashboard pasan, 312 aserciones. No se modifica lógica de piezas ni se reduce historia. El costo del cálculo sobre colecciones grandes sigue sujeto a perfil; esta mejora elimina la segunda lectura, no promete complejidad constante. Reversión: restaurar consulta/colecta separada del histórico.
