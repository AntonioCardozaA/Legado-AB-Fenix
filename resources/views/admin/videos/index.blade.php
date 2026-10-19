@extends('layouts.app')

@section('title', 'Videos del administrador')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-blue-700">
                <i class="fas fa-lock"></i>
                Área privada de administración
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Videos</h1>
            <p class="mt-1 text-sm text-gray-500">Solo los usuarios con rol de administrador pueden acceder a esta pantalla y a los archivos.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">
            <i class="fas fa-shield-halved"></i>
            Acceso restringido
        </span>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <i class="fas fa-circle-check mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">No se pudo cargar el video:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                <i class="fas fa-cloud-arrow-up"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900">Subir un video</h2>
                <p class="mt-1 text-sm text-slate-500">Formatos permitidos: MP4, WebM, OGG, MOV, AVI o MKV. Tamaño máximo: 500 MB.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.videos.store') }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_auto] md:items-end">
            @csrf
            <div>
                <label for="title" class="mb-1 block text-sm font-semibold text-slate-700">Nombre del video</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" required maxlength="120" placeholder="Ej. Procedimiento de mantenimiento" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="video" class="mb-1 block text-sm font-semibold text-slate-700">Archivo de video</label>
                <input id="video" name="video" type="file" required accept="video/mp4,video/webm,video/ogg,video/quicktime,video/x-msvideo,video/x-matroska" class="block w-full rounded-xl border border-slate-300 bg-white text-sm text-slate-600 file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
            </div>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <i class="fas fa-upload"></i>
                Cargar video
            </button>
        </form>
    </section>

    <section>
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Videos cargados</h2>
                <p class="text-sm text-slate-500">{{ $videos->total() }} {{ $videos->total() === 1 ? 'archivo' : 'archivos' }}</p>
            </div>
        </div>

        @if($videos->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center text-sm text-slate-500">
                <i class="fas fa-film mb-3 text-3xl text-slate-300"></i>
                <p>Aún no hay videos cargados.</p>
            </div>
        @else
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($videos as $video)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <video class="aspect-video w-full bg-slate-950 object-contain" controls preload="metadata" src="{{ route('admin.videos.stream', $video) }}">
                            Tu navegador no puede reproducir este video.
                        </video>
                        <div class="space-y-3 p-4">
                            <div>
                                <h3 class="truncate font-bold text-slate-900" title="{{ $video->title }}">{{ $video->title }}</h3>
                                <p class="mt-1 truncate text-xs text-slate-500" title="{{ $video->original_filename }}">{{ $video->original_filename }}</p>
                            </div>
                            <div class="flex items-center justify-between gap-3 text-xs text-slate-500">
                                <span>{{ number_format(($video->file_size ?? 0) / 1048576, 1) }} MB</span>
                                <span>{{ optional($video->uploaded_at)->format('d/m/Y H:i') }}</span>
                            </div>
                            <form method="POST" action="{{ route('admin.videos.destroy', $video) }}" onsubmit="return confirm('¿Eliminar este video? Esta acción no se puede deshacer.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                    <i class="fas fa-trash"></i>
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">{{ $videos->links() }}</div>
        @endif
    </section>
</div>
@endsection
