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

<form method="POST" action="{{ $action }}" class="space-y-5 rounded bg-white p-5 shadow">
    @csrf
    @if(($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre', $linea->nombre ?? '') }}" class="w-full rounded border-gray-300 text-sm" required>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Descripcion</label>
        <textarea name="descripcion" rows="3" class="w-full rounded border-gray-300 text-sm">{{ old('descripcion', $linea->descripcion ?? '') }}</textarea>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" value="1" class="rounded border-gray-300 text-blue-600" @checked(old('activo', $linea->activo ?? true))>
        Linea activa
    </label>

    <div class="create-actions create-actions--end">
        <a href="{{ route('lineas.index') }}" class="create-action create-action--secondary">Cancelar</a>
        <button class="create-action">Guardar</button>
    </div>
</form>
