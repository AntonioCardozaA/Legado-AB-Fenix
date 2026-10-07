# API Power BI 52-12-4

## Endpoint

`GET /api/powerbi/52-12-4`

API REST de solo lectura para consumir en Power BI los datos del modulo LEF 52-12-4.

## Autenticacion

Enviar el header:

```http
X-API-KEY: MI_API_KEY
```

La clave se configura en `.env`:

```env
POWERBI_API_KEY=
```

No se debe guardar una clave real en el repositorio.

## Query Parameters

- `linea_id`: filtra por ID de linea existente.
- `import_id`: filtra por ID de importacion 52-12-4 existente.
- `data_date`: filtra por fecha de datos en formato `YYYY-MM-DD`.
- `fecha`: alias de `data_date`, tambien en formato `YYYY-MM-DD`.
- `period`: acepta `all`, `52`, `12`, `4`. Se usa para ordenar datos cuando aplica.
- `analysis_type`: acepta `all`, `machines`, `parts`, `washer`, `comparison`.

## Respuesta

```json
{
  "success": true,
  "generated_at": "2026-09-30T15:00:00-06:00",
  "filters": {
    "linea_id": null,
    "import_id": null,
    "data_date": null,
    "period": "all",
    "analysis_type": "all"
  },
  "data": {
    "lineas": [],
    "importaciones": [],
    "maquinas": [],
    "partes": [],
    "linea_general": [],
    "comparativo_lineas": [],
    "tendencia_lavadora": [],
    "historico": []
  }
}
```

Las tablas `maquinas`, `partes`, `linea_general` e `historico` usan filas planas:

```json
{
  "id": 1,
  "import_id": 10,
  "linea_id": 4,
  "linea": "L-04",
  "tipo": "maquina",
  "nombre": "Lavadora",
  "valor_52": 12.45,
  "valor_12": 8.32,
  "valor_4": 5.21,
  "fecha": "2026-09-30",
  "source_filename": "archivo.xlsx",
  "created_at": "2026-09-30T15:00:00-06:00"
}
```

Los valores `valor_52`, `valor_12` y `valor_4` son numericos JSON, sin simbolo `%`.

## Codigos HTTP

- `200 OK`: respuesta generada correctamente.
- `401 Unauthorized`: no se envio `X-API-KEY`, la clave esta vacia en el servidor o no coincide.
- `422 Unprocessable Entity`: parametros invalidos.
- `500 Internal Server Error`: error no controlado del servidor.

## PowerShell

```powershell
Invoke-RestMethod `
    -Uri "https://legadoabfenix.com/api/powerbi/52-12-4" `
    -Headers @{
        "X-API-KEY" = "MI_API_KEY"
    }
```

## curl

```bash
curl -H "X-API-KEY: MI_API_KEY" \
https://legadoabfenix.com/api/powerbi/52-12-4
```

## Power Query M

Consulta base:

```powerquery
let
    Source = Json.Document(
        Web.Contents(
            "https://legadoabfenix.com",
            [
                RelativePath = "api/powerbi/52-12-4",
                Headers = [
                    #"X-API-KEY" = "MI_API_KEY"
                ]
            ]
        )
    )
in
    Source
```

Tabla de maquinas:

```powerquery
let
    Source = Json.Document(Web.Contents("https://legadoabfenix.com", [RelativePath = "api/powerbi/52-12-4", Headers = [#"X-API-KEY" = "MI_API_KEY"]])),
    Rows = Source[data][maquinas],
    Table = Table.FromRecords(Rows)
in
    Table
```

Tabla de partes:

```powerquery
let
    Source = Json.Document(Web.Contents("https://legadoabfenix.com", [RelativePath = "api/powerbi/52-12-4", Headers = [#"X-API-KEY" = "MI_API_KEY"]])),
    Rows = Source[data][partes],
    Table = Table.FromRecords(Rows)
in
    Table
```

Tabla de linea general:

```powerquery
let
    Source = Json.Document(Web.Contents("https://legadoabfenix.com", [RelativePath = "api/powerbi/52-12-4", Headers = [#"X-API-KEY" = "MI_API_KEY"]])),
    Rows = Source[data][linea_general],
    Table = Table.FromRecords(Rows)
in
    Table
```

Tabla historica unificada:

```powerquery
let
    Source = Json.Document(Web.Contents("https://legadoabfenix.com", [RelativePath = "api/powerbi/52-12-4", Headers = [#"X-API-KEY" = "MI_API_KEY"]])),
    Rows = Source[data][historico],
    Table = Table.FromRecords(Rows)
in
    Table
```

Tabla de tendencia Lavadora:

```powerquery
let
    Source = Json.Document(Web.Contents("https://legadoabfenix.com", [RelativePath = "api/powerbi/52-12-4", Headers = [#"X-API-KEY" = "MI_API_KEY"]])),
    Rows = Source[data][tendencia_lavadora],
    Table = Table.FromRecords(Rows)
in
    Table
```
