<section id="mttr" class="chart-card" aria-labelledby="mttr-titulo">
    <h3 id="mttr-titulo">
        <i class="fas fa-stopwatch" aria-hidden="true"></i>
        <span>MTTR – Tiempo Medio de Reparación</span>
    </h3>
    <dl class="grid grid-cols-2 gap-2 md:grid-cols-4">
        @foreach (\App\Models\MttrIndicador::LAVADORAS as $lavadora)
            @php($minutos = $indicadoresMttr->get($lavadora)?->minutosParaMostrar())
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                <dt class="text-xs font-semibold text-slate-600">{{ $lavadora }}</dt>
                <dd class="mt-1 break-words text-sm font-bold text-slate-900">
                    @if ($minutos !== null)
                        {{ $minutos }} <span class="text-xs font-medium text-slate-600">min</span>
                    @else
                        <span class="text-xs font-medium text-slate-600">Sin registrar</span>
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
</section>
