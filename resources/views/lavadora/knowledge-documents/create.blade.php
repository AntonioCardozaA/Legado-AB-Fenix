@extends('layouts.app')

@section('title', 'Cargar Documento de Conocimiento')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="rounded-3xl bg-slate-900 px-6 py-6 text-white shadow-xl">
        <a href="{{ route('lavadora.knowledge-documents.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white/90 transition hover:bg-white/20">
            <i class="fas fa-arrow-left"></i>
            Volver a la base
        </a>
        <h1 class="mt-4 text-3xl font-black tracking-tight">Cargar documento para la IA de lavadoras</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-300">
            Primera version segura: si el archivo es PDF o Word, agrega tambien el texto extraido para que el indexador pueda usarlo sin OCR automatico.
        </p>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
            <p class="font-bold">Hay datos por corregir.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('lavadora.knowledge-documents.store') }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="title" class="mb-2 block text-sm font-semibold text-slate-700">Titulo</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="document_type" class="mb-2 block text-sm font-semibold text-slate-700">Tipo de documento</label>
                <select id="document_type" name="document_type" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($documentTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('document_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="version" class="mb-2 block text-sm font-semibold text-slate-700">Version</label>
                <input id="version" name="version" type="text" value="{{ old('version') }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="linea_id" class="mb-2 block text-sm font-semibold text-slate-700">Linea</label>
                <select id="linea_id" name="linea_id" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">General</option>
                    @foreach($lineas as $linea)
                        <option value="{{ $linea->id }}" @selected(old('linea_id') == $linea->id)>{{ $linea->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="componente_id" class="mb-2 block text-sm font-semibold text-slate-700">Componente</label>
                <select id="componente_id" name="componente_id" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos</option>
                    @foreach($componentes as $componente)
                        <option value="{{ $componente->id }}" @selected(old('componente_id') == $componente->id)>
                            {{ $componente->nombre }}{{ $componente->codigo ? ' (' . $componente->codigo . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="lifecycle_status" class="mb-2 block text-sm font-semibold text-slate-700">Estatus de vigencia</label>
                <select id="lifecycle_status" name="lifecycle_status" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach(['vigente' => 'Vigente', 'borrador' => 'Borrador', 'obsoleto' => 'Obsoleto'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('lifecycle_status', 'vigente') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="effective_at" class="mb-2 block text-sm font-semibold text-slate-700">Fecha de vigencia</label>
                <input id="effective_at" name="effective_at" type="date" value="{{ old('effective_at') }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="md:col-span-2">
                <label for="upload" class="mb-2 block text-sm font-semibold text-slate-700">Archivo</label>
                <input id="upload" name="upload" type="file" class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                <p class="mt-2 text-xs text-slate-500">Soporta texto, markdown, csv, html, xml, pdf y documentos de Office. PDF y Word necesitan texto extraido en esta primera version.</p>
            </div>

            <div class="md:col-span-2">
                <label for="extracted_text" class="mb-2 block text-sm font-semibold text-slate-700">Texto extraido o resumen tecnico</label>
                <textarea id="extracted_text" name="extracted_text" rows="12" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('extracted_text') }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label for="metadata_notes" class="mb-2 block text-sm font-semibold text-slate-700">Notas internas</label>
                <textarea id="metadata_notes" name="metadata_notes" rows="3" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('metadata_notes') }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                <i class="fas fa-upload"></i>
                Guardar e indexar
            </button>
            <a href="{{ route('lavadora.knowledge-documents.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="fas fa-xmark"></i>
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
