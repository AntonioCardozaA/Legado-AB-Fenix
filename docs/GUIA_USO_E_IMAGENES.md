# Guia Rapida De Uso E Imagenes - Legado AB Fenix

Esta guia explica como debe usar el sistema un usuario operativo y donde se colocan las imagenes que aparecen en la interfaz, reportes, diagramas y evidencias.

## 1. Entrada Al Sistema

1. Abrir la URL del sistema.
2. Escribir correo y contrasena.
3. Presionar **Log In**.
4. Verificar que aparezca el dashboard principal.

Si el usuario no ve un modulo, no es problema de imagenes: normalmente falta permiso o el rol no tiene acceso.

## 2. Pantalla Principal

Al entrar, el usuario ve las tarjetas de modulos:

- **Lavadoras**
- **Pasteurizadoras**
- **Etiquetadoras**

Cada tarjeta sirve para entrar al modulo correspondiente. Tambien puede mostrar resumen, alertas, componentes revisados o pendientes.

Imagenes usadas en esta pantalla:

| Uso | Archivo |
| --- | --- |
| Fondo de bienvenida/login | `public/images/fondo.png` |
| Logo principal | `public/images/logo.png` o `public/images/logoo.png` |
| Tarjeta Lavadoras | `public/images/icono-maquina-cover.png` |
| Tarjeta Pasteurizadoras | `public/images/icono-pas-cover.png` |
| Tarjeta Etiquetadoras | `public/images/Etiquetas/Modelo especial.png` |
| Chat ABFenix.ai | `public/images/abfenix-ai-chat.png` |

Para cambiar una imagen de estas, reemplaza el archivo con el mismo nombre y extension. Despues recarga la pagina con `Ctrl + F5`.

## 3. Modulo Lavadoras

### Como lo usa el usuario

1. Entrar a **Lavadoras**.
2. Abrir **Analisis Lavadora**.
3. Elegir la linea.
4. Presionar **Crear** o entrar a captura rapida.
5. Capturar componente, reductor o servo-reductor, lado, fecha, estado, numero de orden y actividad.
6. Agregar fotografias si aplica.
7. Revisar la vista previa.
8. Guardar.

### Donde van las imagenes de lavadoras

| Tipo de imagen | Carpeta |
| --- | --- |
| Diagrama general o por linea | `public/images/Diagramas-Lavadoras/` |
| Mascaras para animacion de cadenas | `public/images/Diagramas-Lavadoras/cadenas/` |
| Iconos/fotos de componentes | `public/images/componentes-lavadora/` |
| Evidencia subida por el usuario | `public/storage/analisis-evidencias/` |
| Evidencia de correccion/cierre | `public/storage/analisis-correcciones/` |

Ejemplos:

- `public/images/Diagramas-Lavadoras/linea4.png`
- `public/images/Diagramas-Lavadoras/linea12.png`
- `public/images/componentes-lavadora/CATARINAS.png`
- `public/images/componentes-lavadora/RV200.png`

Importante: las mascaras de cadena, como `linea4-mask.png`, no son fotos decorativas. Sirven para que la animacion detecte el recorrido. Si cambias el diagrama de una linea, puede ser necesario actualizar tambien su mascara.

## 4. Modulo Pasteurizadoras

### Como lo usa el usuario

1. Entrar a **Pasteurizadoras**.
2. Abrir **Analisis Pasteurizadora**.
3. Seleccionar linea.
4. Capturar modulo, componente, lado, nivel, fecha, estado, orden y actividad.
5. Adjuntar evidencia fotografica.
6. Guardar.

Para Central Hidraulica:

1. Entrar a **Pasteurizadoras > Central Hidraulica**.
2. Seleccionar linea.
3. Capturar componente, condicion, actividad y evidencia.
4. Guardar.

### Donde van las imagenes de pasteurizadoras

| Tipo de imagen | Carpeta |
| --- | --- |
| Diagramas por linea | `public/images/Diagramas-Pasteurizadoras/` |
| Iconos/fotos de componentes | `public/images/componentes-pasteurizadora/` |
| Evidencia de analisis mecanico | `public/storage/analisis-pasteurizadora/` |
| Evidencia de Central Hidraulica | `public/storage/analisis-pasteurizadora-central-hidraulica/` |

Ejemplos:

- `public/images/Diagramas-Pasteurizadoras/linea4.png`
- `public/images/Diagramas-Pasteurizadoras/linea14.png`
- `public/images/componentes-pasteurizadora/EXCENTRICOS.png`
- `public/images/componentes-pasteurizadora/RODAJAS.png`

## 5. Modulo Etiquetadoras

### Como lo usa el usuario

1. Entrar a **Etiquetadoras**.
2. Abrir **Analisis Etiquetadora**.
3. Seleccionar linea.
4. Capturar maquina, componente, piezas revisadas, fecha, orden, estado y actividad.
5. Adjuntar evidencia fotografica.
6. Guardar.

### Donde van las imagenes de etiquetadoras

| Tipo de imagen | Carpeta |
| --- | --- |
| Etiquetas principales | `public/images/Etiquetas/` |
| Imagenes de solo etiqueta | `public/images/Etiquetas/SoloEtiquetas/` |
| Imagenes de botellas | `public/images/Etiquetas/Botellas/` |
| Evidencia subida por usuario | `public/storage/analisis-evidencias/` |

Ejemplos:

- `public/images/Etiquetas/Modelo especial.png`
- `public/images/Etiquetas/coronamega.png`
- `public/images/Etiquetas/SoloEtiquetas/linea04-corona-mega.png`
- `public/images/Etiquetas/Botellas/linea04-corona-mega-etiquetada.png`

Si agregas una presentacion nueva, no basta con subir la imagen. Tambien se debe revisar el catalogo en `app/Support/EtiquetadoraCatalog.php`, porque ahi se relaciona la linea/presentacion con el archivo.

## 6. Evidencias Fotograficas

Las evidencias son las fotos que toma o sube el usuario en los formularios de captura. El usuario no debe copiarlas manualmente a carpetas.

El flujo correcto es:

1. Abrir el formulario de analisis.
2. Presionar **Subir desde galeria** o **Tomar foto ahora**.
3. Seleccionar o tomar la foto.
4. Revisar la vista previa.
5. Guardar el analisis.

El sistema guarda automaticamente la ruta de la imagen en la base de datos y coloca el archivo en la carpeta correcta.

| Modulo | Carpeta donde se guardan |
| --- | --- |
| Lavadoras | `public/storage/analisis-evidencias/` |
| Etiquetadoras | `public/storage/analisis-evidencias/` |
| Pasteurizadora mecanica | `public/storage/analisis-pasteurizadora/` |
| Pasteurizadora Central Hidraulica | `public/storage/analisis-pasteurizadora-central-hidraulica/` |
| Correcciones de lavadora | `public/storage/analisis-correcciones/` |

Formatos aceptados: JPG, PNG, WEBP, GIF o BMP.

Tamano recomendado: usar imagenes claras, enfocadas y no muy pesadas. El sistema puede optimizar algunas fotos grandes, pero la mejor practica es subir fotos legibles y bien tomadas.

## 7. Reportes

El usuario puede generar reportes desde **Reportes**.

Procedimiento:

1. Entrar a **Reportes**.
2. Seleccionar tipo de equipo: Lavadora, Pasteurizadora o Etiquetadora.
3. Seleccionar linea o dejar todas.
4. Seleccionar rango de fechas.
5. Aplicar filtros.
6. Exportar PDF o Excel si se necesita.

Los reportes usan logos e iconos desde:

- `public/images/logo.png`
- `public/images/logoo.png`
- `public/images/icono-maquina.png`
- `public/images/icono_pas.png`
- `public/images/componentes-lavadora/`
- `public/images/componentes-pasteurizadora/`
- `public/storage/...` para evidencias.

## 8. Plan De Accion

Cuando un analisis queda en condicion critica, el usuario debe crear o actualizar un plan de accion.

Flujo recomendado:

1. Revisar analisis con dano, desgaste severo o requiere cambio.
2. Entrar a **Plan de Accion** del modulo.
3. Crear actividad.
4. Capturar fechas PCM.
5. Guardar.
6. Dar seguimiento hasta marcar como completado.

## 9. Como Debe Nombrarse Una Imagen

Para evitar errores:

- Usar nombres cortos.
- Evitar caracteres raros.
- Preferir `.png` o `.jpg`.
- Respetar mayusculas/minusculas cuando la ruta ya existe.
- En componentes, usar el codigo del componente.

Buenos ejemplos:

- `CATARINAS.png`
- `EXCENTRICOS.png`
- `linea4.png`
- `linea04-corona-mega.png`

Evitar:

- `foto nueva final final.png`
- `imagen(1).jpg`
- `pieza danada.jpg`
- `Linea 4 Nueva copia.png`

## 10. Checklist Para Administrador

Antes de entregar el sistema a usuarios:

- [ ] Confirmar que el login muestra `public/images/fondo.png`.
- [ ] Confirmar que las tarjetas del dashboard muestran sus imagenes.
- [ ] Confirmar que cada linea tiene su diagrama cuando aplica.
- [ ] Confirmar que los componentes principales tienen imagen.
- [ ] Probar un registro de lavadora con evidencia.
- [ ] Probar un registro de pasteurizadora con evidencia.
- [ ] Probar un registro de etiquetadora con evidencia.
- [ ] Verificar que las fotos aparecen en historial y reportes.
- [ ] Ejecutar `php artisan storage:link` si las evidencias no se ven.

## 11. Mapa Rapido De Carpetas De Imagenes

```text
public/images/
  fondo.png
  logo.png
  logoo.png
  icono-maquina.png
  icono-maquina-cover.png
  icono_pas.png
  icono-pas-cover.png
  abfenix-ai-chat.png

public/images/Diagramas-Lavadoras/
  linea4.png
  linea5.png
  linea6.png
  linea7.png
  linea9.png
  linea12.png
  linea13.png
  cadenas/
    linea4-mask.png
    linea5-mask.png
    ...

public/images/Diagramas-Pasteurizadoras/
  linea3.png
  linea4.png
  ...
  linea14.png

public/images/componentes-lavadora/
  CATARINAS.png
  RV200.png
  SERVO_GRANDE.png
  ...

public/images/componentes-pasteurizadora/
  EXCENTRICOS.png
  RODAJAS.png
  VIGAS_FIJAS.png
  ...

public/images/Etiquetas/
  Modelo especial.png
  coronamega.png
  SoloEtiquetas/
  Botellas/

public/storage/
  analisis-evidencias/
  analisis-pasteurizadora/
  analisis-pasteurizadora-central-hidraulica/
  analisis-correcciones/
```

## 12. Frase Simple Para Capacitar Al Usuario

Primero entra al modulo, despues selecciona la linea, registra el estado real del componente, escribe que se hizo, agrega foto si aplica y guarda. Despues revisa el historial para confirmar que el registro y la evidencia quedaron guardados.
