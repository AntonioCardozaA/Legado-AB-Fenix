@foreach($analisis as $lavadora => $items)
<h3 style="background:#ffc107;text-align:center">
ANÁLISIS {{ $lavadora }}
</h3>
<table width="100%" border="1">
<tr>
<th>{{ \App\Support\LavadoraCatalog::etiquetaReductor($lavadora) }}</th><th>Componente</th><th>Actividad</th>
</tr>
@foreach($items as $a)
<tr>
<td>{{ \App\Support\LavadoraCatalog::nombreReductorParaLinea($lavadora, $a->reductor) }}</td>
<td>{{ $a->componente->nombre }}</td>
<td>{{ $a->actividad }}</td>
</tr>
@endforeach
</table>
@endforeach
