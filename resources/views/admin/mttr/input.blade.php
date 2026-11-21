@php
    $indicador = $indicadores->get($clave);
    $campo = "minutos.{$clave}";
    $valor = old($campo, $indicador?->minutosParaMostrar());
@endphp

<input id="mttr-{{ $clave }}" name="minutos[{{ $clave }}]" value="{{ is_scalar($valor) ? $valor : '' }}"
       type="number" min="0" max="{{ \App\Models\MttrIndicador::MAXIMO_MINUTOS }}" step="0.0001" inputmode="decimal"
       placeholder="Sin registrar" aria-describedby="mttr-ayuda" @if ($errors->has($campo)) aria-invalid="true" @endif
       class="w-full max-w-xs rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-600 focus:ring-blue-600">
@if ($indicador?->updated_at)
    <p class="mt-1 text-xs text-slate-600">Actualizado el <time datetime="{{ $indicador->updated_at->toIso8601String() }}">{{ $indicador->updated_at->format('d/m/Y H:i') }}</time></p>
@endif
