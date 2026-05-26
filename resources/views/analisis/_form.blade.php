@php
    $isEdit = isset($analisis) && $analisis;
    $formMethod = $formMethod ?? 'POST';
    $selectedLinea = old('linea_id', $analisis->linea_id ?? ($linea->id ?? null));
    $selectedComponente = old('componente_id', $analisis->componente_id ?? ($componente->id ?? null));
@endphp

@if($errors->any())
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <div class="font-semibold">Revisa los campos marcados.</div>
        <ul class="mt-2 list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-5 rounded bg-white p-5 shadow">
    @csrf
    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Linea</label>
            @if($isEdit)
                <select name="linea_id" class="w-full rounded border-gray-300 text-sm" required>
                    @foreach(($lineas ?? collect()) as $lineaOption)
                        <option value="{{ $lineaOption->id }}" @selected($selectedLinea == $lineaOption->id)>{{ $lineaOption->nombre }}</option>
                    @endforeach
                </select>
            @else
                <input type="hidden" name="linea_id" value="{{ $selectedLinea }}">
                <div class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ $linea->nombre ?? 'Linea seleccionada' }}</div>
            @endif
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Componente</label>
            @if($isEdit)
                <select name="componente_id" class="w-full rounded border-gray-300 text-sm" required>
                    @foreach(($componentes ?? collect()) as $componenteOption)
                        <option value="{{ $componenteOption->id }}" @selected($selectedComponente == $componenteOption->id)>{{ $componenteOption->nombre }}</option>
                    @endforeach
                </select>
            @else
                <input type="hidden" name="componente_id" value="{{ $selectedComponente }}">
                <div class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ $componente->nombre ?? 'Componente seleccionado' }}</div>
            @endif
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Fecha</label>
            <input type="date" name="fecha_analisis" value="{{ old('fecha_analisis', optional($analisis->fecha_analisis ?? null)->format('Y-m-d')) }}" class="w-full rounded border-gray-300 text-sm" required>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Numero de orden</label>
            <input type="text" name="numero_orden" value="{{ old('numero_orden', $analisis->numero_orden ?? '') }}" class="w-full rounded border-gray-300 text-sm" required>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Reductor</label>
            <input type="text" name="reductor" value="{{ old('reductor', $analisis->reductor ?? '') }}" class="w-full rounded border-gray-300 text-sm" required>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Categoria</label>
            <select name="categoria_id" class="w-full rounded border-gray-300 text-sm" required>
                <option value="">Seleccionar</option>
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected(old('categoria_id', $analisis->categoria_id ?? null) == $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Numero R</label>
            <select name="numero_r_id" class="w-full rounded border-gray-300 text-sm" required>
                <option value="">Seleccionar</option>
                @foreach($numerosR as $numeroR)
                    <option value="{{ $numeroR->id }}" @selected(old('numero_r_id', $analisis->numero_r_id ?? null) == $numeroR->id)>{{ $numeroR->codigo }} {{ $numeroR->descripcion ? '- ' . $numeroR->descripcion : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Horometro</label>
            <input type="number" min="0" name="horometro" value="{{ old('horometro', $analisis->horometro ?? '') }}" class="w-full rounded border-gray-300 text-sm">
        </div>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Actividad</label>
        <textarea name="actividad" rows="3" class="w-full rounded border-gray-300 text-sm" required>{{ old('actividad', $analisis->actividad ?? '') }}</textarea>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Observaciones</label>
        <textarea name="observaciones" rows="3" class="w-full rounded border-gray-300 text-sm">{{ old('observaciones', $analisis->observaciones ?? '') }}</textarea>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Fotos</label>
        <input type="file" name="fotos[]" multiple accept="image/*" class="w-full rounded border border-gray-300 bg-white p-2 text-sm">
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('analisis.index') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
        <button class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Guardar</button>
    </div>
</form>
