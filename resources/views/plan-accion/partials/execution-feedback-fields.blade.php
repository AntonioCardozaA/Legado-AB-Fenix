@php
    $effectivenessOptions = \App\Models\PlanAccion::effectivenessOptions();
    $selectedEffectiveness = old('effectiveness', $plan->effectiveness);
    $isCompleted = (bool) old('completado', $plan->completado);
@endphp

<section class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 shadow-sm">
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h4 class="flex items-center gap-2 text-base font-bold text-emerald-950">
                <i class="fas fa-clipboard-check text-emerald-600"></i>
                Cierre tecnico y aprendizaje IA
            </h4>
            <p class="mt-1 text-sm text-emerald-800">
                Captura resultado real, costo, horas y efectividad para medir el plan y mejorar futuras recomendaciones.
            </p>
        </div>
        <label class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 shadow-sm">
            <input type="hidden" name="completado" value="0">
            <input type="checkbox"
                   name="completado"
                   value="1"
                   class="rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500"
                   @checked($isCompleted)>
            Plan completado
        </label>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <label for="actual_cost_total" class="mb-1 block text-sm font-semibold text-gray-700">Costo real total</label>
            <input id="actual_cost_total"
                   name="actual_cost_total"
                   type="number"
                   min="0"
                   step="0.01"
                   value="{{ old('actual_cost_total', $plan->actual_cost_total) }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @if($plan->estimated_cost_total !== null)
                <p class="mt-1 text-xs text-gray-500">Estimado: ${{ number_format((float) $plan->estimated_cost_total, 2) }}</p>
            @endif
            @error('actual_cost_total') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="actual_hours" class="mb-1 block text-sm font-semibold text-gray-700">Horas reales</label>
            <input id="actual_hours"
                   name="actual_hours"
                   type="number"
                   min="0"
                   step="0.01"
                   value="{{ old('actual_hours', $plan->actual_hours) }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @if($plan->estimated_hours !== null)
                <p class="mt-1 text-xs text-gray-500">Estimado: {{ number_format((float) $plan->estimated_hours, 2) }} h</p>
            @endif
            @error('actual_hours') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="effectiveness" class="mb-1 block text-sm font-semibold text-gray-700">Efectividad</label>
            <select id="effectiveness"
                    name="effectiveness"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">Sin evaluar</option>
                @foreach($effectivenessOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedEffectiveness === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('effectiveness') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mt-4">
        <label for="execution_result" class="mb-1 block text-sm font-semibold text-gray-700">Resultado de ejecucion</label>
        <textarea id="execution_result"
                  name="execution_result"
                  rows="3"
                  maxlength="3000"
                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                  placeholder="Que se hizo, que se encontro y si el problema quedo resuelto.">{{ old('execution_result', $plan->execution_result) }}</textarea>
        @error('execution_result') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</section>
