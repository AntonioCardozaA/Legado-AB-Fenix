@extends('layouts.app')

@section('title', 'Reviciones de Excentricos')

@section('content')
@php
    $pasteurizadoras = $pasteurizadoras ?? collect();
    $diagramaPasteurizadora = $diagramaPasteurizadora ?? [];
    $estadoMeta = function (?string $estado): array {
        if (!$estado) {
            return [
                'label' => 'TRABAJA CON NORMALIDAD',
                'short' => 'TRABAJA CON NORMALIDAD',
                'icon' => 'fa-circle-check',
                'pill' => 'bg-green-50 text-green-700 ring-green-200',
                'tile' => 'border-green-200 bg-green-50/80 hover:border-green-300 hover:bg-green-50',
                'accent' => 'bg-green-500',
                'text' => 'text-green-700',
                'modal' => 'bg-green-800',
            ];
        }

        if (\App\Models\AnalisisPasteurizadora::esEstadoBueno($estado)) {
            return [
                'label' => $estado,
                'short' => $estado,
                'icon' => 'fa-circle-check',
                'pill' => 'bg-green-50 text-green-700 ring-green-200',
                'tile' => 'border-green-200 bg-green-50/80 hover:border-green-300 hover:bg-green-50',
                'accent' => 'bg-green-500',
                'text' => 'text-green-700',
                'modal' => 'bg-green-800',
            ];
        }

        if (\App\Models\AnalisisPasteurizadora::esEstadoCambiado($estado)) {
            return [
                'label' => $estado,
                'short' => 'Cambiado',
                'icon' => 'fa-arrows-rotate',
                'pill' => 'bg-blue-50 text-blue-700 ring-blue-200',
                'tile' => 'border-blue-200 bg-blue-50/80 hover:border-blue-300 hover:bg-blue-50',
                'accent' => 'bg-blue-500',
                'text' => 'text-blue-700',
                'modal' => 'bg-blue-800',
            ];
        }

        if (\App\Models\AnalisisPasteurizadora::esEstadoDanado($estado)) {
            return [
                'label' => $estado,
                'short' => $estado,
                'icon' => 'fa-triangle-exclamation',
                'pill' => 'bg-red-50 text-red-700 ring-red-200',
                'tile' => 'border-red-200 bg-red-50/80 hover:border-red-300 hover:bg-red-50',
                'accent' => 'bg-red-500',
                'text' => 'text-red-700',
                'modal' => 'bg-red-800',
            ];
        }

        if (\App\Models\AnalisisPasteurizadora::esEstadoRequiereRevision($estado)) {
            return [
                'label' => $estado,
                'short' => $estado,
                'icon' => 'fa-screwdriver-wrench',
                'pill' => 'bg-yellow-50 text-yellow-800 ring-yellow-200',
                'tile' => 'border-yellow-200 bg-yellow-50/90 hover:border-yellow-300 hover:bg-yellow-50',
                'accent' => 'bg-yellow-500',
                'text' => 'text-yellow-800',
                'modal' => 'bg-yellow-700',
            ];
        }

        if (\App\Models\AnalisisPasteurizadora::esEstadoDesgaste($estado)) {
            return [
                'label' => $estado,
                'short' => $estado,
                'icon' => 'fa-gauge-high',
                'pill' => 'bg-orange-50 text-orange-700 ring-orange-200',
                'tile' => 'border-orange-200 bg-orange-50/85 hover:border-orange-300 hover:bg-orange-50',
                'accent' => 'bg-orange-500',
                'text' => 'text-orange-700',
                'modal' => 'bg-orange-700',
            ];
        }

        return [
            'label' => $estado,
            'short' => $estado,
            'icon' => 'fa-circle-info',
            'pill' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'tile' => 'border-slate-200 bg-white hover:border-blue-300 hover:bg-blue-50',
            'accent' => 'bg-slate-300',
            'text' => 'text-slate-500',
            'modal' => 'bg-gray-800',
        ];
    };

    $evidenciaUrls = function ($imagenes): array {
        if (is_string($imagenes)) {
            $imagenes = json_decode($imagenes, true) ?? [];
        }

        if (!is_array($imagenes)) {
            return [];
        }

        return collect($imagenes)
            ->filter()
            ->map(fn ($foto) => asset('storage/' . ltrim(str_replace('\\', '/', $foto), '/')))
            ->values()
            ->all();
    };
@endphp

@verbatim
<style>
    .excentricos-shell {
        container-type: inline-size;
    }

    .excentricos-shell,
    .excentricos-shell * {
        min-width: 0;
    }

    .excentricos-top-panel {
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98) 56%, rgba(239, 246, 255, 0.98)),
            linear-gradient(90deg, #1e40af, #f59e0b);
    }

    .excentricos-selector-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 7rem), 1fr));
        gap: 0.45rem;
        width: 100%;
    }

    .excentricos-selector-btn {
        min-height: 2.25rem;
        min-width: 0;
        padding-inline: 0.45rem;
        font-size: 0.78rem;
        line-height: 1.15;
        text-align: center;
        overflow-wrap: anywhere;
    }

    .excentricos-machine-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 27rem), 1fr));
        gap: 1rem;
    }

    .excentricos-module-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 11.5rem), 1fr));
        gap: 0.75rem;
    }

    .excentricos-module-tile {
        min-height: clamp(12.25rem, 18vw, 15rem);
    }

    .excentricos-diagram-section {
        overflow: hidden;
    }

    .excentricos-diagram-image {
        max-height: clamp(16rem, 60vh, 32rem);
        width: 100%;
        max-width: 100%;
        object-fit: contain;
    }

    .excentricos-diagram-body {
        overflow-x: auto;
        overscroll-behavior-x: contain;
        -webkit-overflow-scrolling: touch;
    }

    .excentricos-diagram-body::-webkit-scrollbar {
        height: 10px;
    }

    .excentricos-diagram-body::-webkit-scrollbar-track {
        background: #e5e7eb;
        border-radius: 999px;
    }

    .excentricos-diagram-body::-webkit-scrollbar-thumb {
        background: #1363d3;
        border-radius: 999px;
    }

    @keyframes modalIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .animate-modalIn {
        animation: modalIn 0.3s ease-out;
    }

    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: #1363d3 #e5e7eb;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #e5e7eb;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #1363d3;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #1363d3;
    }

    .lado-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 4px;
        font-weight: 500;
        font-size: 12px;
        font-family: monospace;
    }

    .lado-badge.vapor,
    .lado-badge.pasillo {
        background-color: #f3f4f6;
        color: #1363d3;
        border: 1px solid #9ca3af;
    }

    .image-grid-enhanced {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr));
        gap: 1rem;
    }

    .image-grid-enhanced .image-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #e5e7eb;
        transition: all 0.3s ease;
        background: white;
        cursor: pointer;
    }

    .image-grid-enhanced .image-item:hover {
        border-color: #4b5563;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
    }

    .image-grid-enhanced .grid-image {
        width: 100%;
        height: 150px;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .excentricos-single-image-stage {
        max-width: 100%;
        touch-action: pan-y;
        user-select: none;
        -webkit-user-select: none;
        -webkit-user-drag: none;
        cursor: grab;
    }

    .excentricos-single-image-stage.is-swiping {
        cursor: grabbing;
    }

    .excentricos-single-image-stage img {
        touch-action: pan-y;
        user-select: none;
        -webkit-user-select: none;
        -webkit-user-drag: none;
    }

    .excentricos-image-arrow {
        display: flex;
    }

    @media (max-width: 640px) {
        .excentricos-image-arrow {
            display: none;
        }
    }

    .image-grid-enhanced .image-item:hover .grid-image {
        transform: scale(1.05);
    }

    .image-grid-enhanced .image-number {
        position: absolute;
        top: 8px;
        left: 8px;
        background: rgba(31, 41, 55, 0.9);
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 2px 8px;
        border-radius: 4px;
        z-index: 10;
        border: 1px solid #6b7280;
        font-family: monospace;
    }

    .image-grid-enhanced .image-info {
        padding: 8px;
        background: white;
        border-top: 1px solid #e5e7eb;
    }

    .image-grid-enhanced .download-image-btn {
        width: 100%;
        padding: 6px;
        background: #374151;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-family: monospace;
        transition: background 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .image-grid-enhanced .download-image-btn:hover {
        background: #1f2937;
    }

    @media (max-width: 768px) {
        .image-grid-enhanced .grid-image {
            height: 120px;
        }
    }

    @media (max-width: 640px) {
        .excentricos-shell {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .excentricos-selector-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .excentricos-machine-grid {
            gap: 0.75rem;
        }

        .excentricos-module-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.6rem;
        }

        .excentricos-module-tile {
            min-height: 12.75rem;
        }

        .excentricos-diagram-section {
            margin-left: -0.75rem;
            margin-right: -0.75rem;
            border-left-width: 0;
            border-right-width: 0;
            border-radius: 0;
        }

        .excentricos-diagram-body {
            padding: 0.35rem 0;
        }

        .excentricos-diagram-frame {
            border-left-width: 0;
            border-right-width: 0;
            border-radius: 0;
            padding: 0;
            min-width: min(48rem, 220vw);
            width: min(48rem, 220vw);
        }

        .excentricos-diagram-image {
            width: min(48rem, 220vw);
            max-width: none;
            max-height: none;
        }
    }

    @media (max-width: 380px) {
        .excentricos-selector-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .excentricos-selector-btn {
            font-size: 0.72rem;
            padding-inline: 0.35rem;
        }

        .excentricos-module-tile {
            min-height: auto;
        }
    }

    @media (max-width: 330px) {
        .excentricos-module-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endverbatim

<div
    class="excentricos-shell mx-auto w-full max-w-7xl space-y-5 px-3 sm:px-4 lg:px-6"
    x-data="{
        detalleAbierto: false,
        imagenAbierta: false,
        imagenActualIndex: 0,
        deslizImagen: null,
        detalle: {
            id: null,
            linea: '',
            modulo: '',
            componente: '',
            estado: '',
            estadoClase: 'bg-slate-100 text-slate-700 ring-slate-200',
            estadoModalClase: 'bg-gray-800',
            estadoIcono: 'fa-circle-info',
            fecha: '',
            hora: '',
            nivel: '',
            lado: '',
            responsable: '',
            actividad: '',
            avance: '',
            componentes: [],
            evidencias: [],
            capturarUrl: '#',
            creado: '',
            actualizado: ''
        },
        get imagenActual() {
            return this.detalle.evidencias[this.imagenActualIndex] || '';
        },
        abrirDetalle(data) {
            this.detalle = { ...this.detalle, ...data };
            this.imagenAbierta = false;
            this.imagenActualIndex = 0;
            this.deslizImagen = null;
            this.detalleAbierto = true;
            document.body.classList.add('overflow-hidden');
        },
        cerrarDetalle() {
            this.imagenAbierta = false;
            this.detalleAbierto = false;
            this.deslizImagen = null;
            document.body.classList.remove('overflow-hidden');
        },
        abrirImagen(index) {
            this.imagenActualIndex = index;
            this.imagenAbierta = true;
            document.body.classList.add('overflow-hidden');
        },
        cerrarImagen() {
            this.imagenAbierta = false;
            this.deslizImagen = null;
        },
        cambiarImagen(direccion) {
            if (this.detalle.evidencias.length <= 1) {
                return;
            }

            this.imagenActualIndex = (this.imagenActualIndex + direccion + this.detalle.evidencias.length) % this.detalle.evidencias.length;
        },
        obtenerPuntoDesliz(event) {
            const source = event.changedTouches?.[0] || event.touches?.[0] || event;

            return {
                x: source.clientX ?? 0,
                y: source.clientY ?? 0,
            };
        },
        iniciarDeslizImagen(event) {
            if (this.detalle.evidencias.length <= 1 || event.target?.closest('button')) {
                return;
            }

            const point = this.obtenerPuntoDesliz(event);
            this.deslizImagen = {
                startX: point.x,
                startY: point.y,
                lastX: point.x,
                lastY: point.y,
            };
            event.currentTarget?.classList.add('is-swiping');
        },
        moverDeslizImagen(event) {
            if (!this.deslizImagen) {
                return;
            }

            const point = this.obtenerPuntoDesliz(event);
            const deltaX = point.x - this.deslizImagen.startX;
            const deltaY = point.y - this.deslizImagen.startY;
            this.deslizImagen.lastX = point.x;
            this.deslizImagen.lastY = point.y;

            if (Math.abs(deltaX) > Math.abs(deltaY) && event.cancelable) {
                event.preventDefault();
            }
        },
        terminarDeslizImagen(event) {
            if (!this.deslizImagen) {
                return;
            }

            const point = this.obtenerPuntoDesliz(event);
            const endX = point.x || this.deslizImagen.lastX;
            const endY = point.y || this.deslizImagen.lastY;
            const deltaX = endX - this.deslizImagen.startX;
            const deltaY = endY - this.deslizImagen.startY;
            const isHorizontalSwipe = Math.abs(deltaX) >= 50 && Math.abs(deltaX) > Math.abs(deltaY) * 1.25;

            if (isHorizontalSwipe) {
                this.cambiarImagen(deltaX < 0 ? 1 : -1);
            }

            this.deslizImagen = null;
            event.currentTarget?.classList.remove('is-swiping');
        },
        cancelarDeslizImagen(event) {
            this.deslizImagen = null;
            event.currentTarget?.classList.remove('is-swiping');
        }
    }"
    @keydown.escape.window="imagenAbierta ? cerrarImagen() : (detalleAbierto && cerrarDetalle())"
    @keydown.window="imagenAbierta && $event.key === 'ArrowLeft' && cambiarImagen(-1); imagenAbierta && $event.key === 'ArrowRight' && cambiarImagen(1)"
>
    <section class="excentricos-top-panel overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 p-3 sm:gap-5 sm:p-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-blue-100 bg-white shadow-sm sm:h-16 sm:w-16">
                    <img
                        src="{{ asset('images/componentes-pasteurizadora/EXCENTRICOS.png') }}"
                        alt="Excentricos"
                        class="h-full w-full object-contain"
                        onerror="this.src='{{ asset('images/icono_pas.png') }}'"
                    >
                </div>
                <div class="min-w-0">
                    <h1 class="break-words text-xl font-black leading-tight text-slate-950 sm:text-3xl">
                        Excentricos de Pasteurizadoras
                    </h1>
                    <p class="mt-1 break-words text-sm font-semibold text-slate-500">
                        {{ $lineaSeleccionada?->nombre ?? 'Sin pasteurizadoras disponibles' }}
                    </p>
                </div>
            </div>

            <div class="w-full lg:max-w-2xl">
                <p class="mb-2 text-[11px] font-black uppercase tracking-wide text-slate-400 lg:text-right">
                    Pasteurizadora
                </p>
                <nav aria-label="Seleccionar pasteurizadora" class="excentricos-selector-grid">
                    @foreach ($lineas as $linea)
                        @php
                            $activa = $lineaSeleccionada?->id === $linea->id;
                        @endphp
                        <a
                            href="{{ route('pasteurizadora.analisis-pasteurizadora.excentricos.index', ['linea_id' => $linea->id]) }}"
                            @if($activa) aria-current="page" @endif
                            class="excentricos-selector-btn inline-flex items-center justify-center rounded-lg border font-black shadow-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $activa ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"
                        >
                            {{ $linea->nombre }}
                        </a>
                    @endforeach
                </nav>

                @if($lineas->isEmpty())
                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-500 shadow-sm">
                        No hay pasteurizadoras activas.
                    </div>
                @endif
            </div>
        </div>
    </section>

    @php
        $diagramaPath = data_get($diagramaPasteurizadora, 'path');
        $diagramaEsReferencia = (bool) data_get($diagramaPasteurizadora, 'is_reference', false);
        $diagramaBase = data_get($diagramaPasteurizadora, 'base', 'images/Diagramas-Pasteurizadoras');
        $tipoPasteurizadora = data_get($diagramaPasteurizadora, 'tipo');
        $totalModulosDiagrama = (int) data_get($diagramaPasteurizadora, 'total_modulos', 0);
    @endphp

    @if($lineaSeleccionada)
        <section class="excentricos-diagram-section rounded-lg border border-slate-200 bg-white shadow-sm">
            <header class="flex flex-col gap-3 border-b border-slate-100 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-4 sm:py-4">
                <div class="min-w-0">
                    <h2 class="flex min-w-0 items-start gap-2 text-base font-black text-slate-900 sm:items-center sm:text-lg">
                        <i class="fas fa-diagram-project shrink-0 text-blue-600"></i>
                        <span class="break-words">{{ $lineaSeleccionada->nombre }}</span>
                    </h2>
                </div>

                <div class="flex flex-wrap gap-2 text-xs font-black uppercase text-slate-500">
                    @if($tipoPasteurizadora)
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 sm:px-3">
                            Tipo {{ $tipoPasteurizadora }}
                        </span>
                    @endif

                    @if($totalModulosDiagrama > 0)
                        <span class="rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-blue-700 sm:px-3">
                            {{ $totalModulosDiagrama }} modulos
                        </span>
                    @endif
                </div>
            </header>

            @if($diagramaPath)
                <div class="excentricos-diagram-body bg-slate-50 px-3 py-4 sm:px-5">
                    <div class="excentricos-diagram-frame flex justify-center overflow-hidden rounded-lg border border-slate-200 bg-white p-2 shadow-sm">
                        <img
                            src="{{ asset($diagramaPath) }}"
                            alt="Diagrama Pasteurizadora {{ $lineaSeleccionada->nombre }}"
                            class="excentricos-diagram-image"
                        >
                    </div>

                    @if($diagramaEsReferencia)
                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                            No se encontro un diagrama cargado para {{ $lineaSeleccionada->nombre }}.
                            Se muestra una referencia temporal. Puedes agregar la imagen en
                            <span class="font-bold">{{ $diagramaBase }}</span>.
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-slate-50 px-3 py-4 sm:px-4 sm:py-5">
                    <div class="rounded-lg border border-dashed border-amber-300 bg-amber-50 px-4 py-5 text-sm font-semibold text-amber-800">
                        No hay un diagrama disponible para {{ $lineaSeleccionada->nombre }}.
                        Agrega una imagen en <span class="font-bold">{{ $diagramaBase }}</span>
                        para mostrarla aqui automaticamente.
                    </div>
                </div>
            @endif
        </section>
    @endif

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-800 shadow-sm">
            <i class="fas fa-circle-check mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error') || session('acceso_restringido'))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800 shadow-sm">
            <i class="fas fa-triangle-exclamation mr-2"></i>{{ session('error') ?? session('acceso_restringido') }}
        </div>
    @endif

    <div class="excentricos-machine-grid">
        @forelse ($pasteurizadoras as $pasteurizadora)
            @php
                $linea = data_get($pasteurizadora, 'linea');
                $modulos = collect(data_get($pasteurizadora, 'modulos', []));
                $totalModulos = (int) (data_get($pasteurizadora, 'total_modulos') ?? $modulos->count());
            @endphp

            @continue(!$linea)

            <section class="min-w-0 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <header class="border-b border-slate-100 bg-slate-50 px-3 py-3 sm:px-4 sm:py-4">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white shadow-sm sm:h-12 sm:w-12">
                            <i class="fas fa-temperature-half text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="break-words text-lg font-black leading-tight text-slate-950 sm:text-xl">{{ $linea->nombre }}</h2>
                            <p class="mt-1 break-words text-xs font-black uppercase text-slate-500">
                                {{ $totalModulos }} modulos disponibles
                            </p>
                        </div>
                    </div>
                </header>

                <div class="excentricos-module-grid p-3 sm:p-4">
                    @foreach ($modulos as $modulo)
                        @php
                            $ultimo = $modulo['ultimo_registro'];
                            $estado = $ultimo?->estado;
                            $meta = $estadoMeta($estado);
                            $piezas = $ultimo?->componentes_revisados_lista ?? [];
                            $piezasTexto = !empty($piezas)
                                ? collect($piezas)->map(fn ($pieza) => '#' . $pieza)->implode(', ')
                                : '0 de ' . $modulo['total_componentes'];
                            $detalleModal = null;

                            if ($ultimo) {
                                $componentesDetalle = collect($piezas)
                                    ->map(fn ($pieza) => (int) $pieza)
                                    ->values()
                                    ->all();
                                $detalleModal = [
                                    'id' => $ultimo->id,
                                    'linea' => $linea->nombre,
                                    'modulo' => 'Modulo ' . $modulo['modulo'],
                                    'componente' => $modulo['componente_nombre'] ?? 'Excentricos',
                                    'estado' => $meta['label'],
                                    'estadoClase' => $meta['pill'],
                                    'estadoModalClase' => $meta['modal'],
                                    'estadoIcono' => $meta['icon'],
                                    'fecha' => $ultimo->fecha_analisis?->format('d/m/Y') ?? 'Sin fecha',
                                    'hora' => $ultimo->created_at?->format('H:i') ?? '',
                                    'nivel' => $ultimo->nivel ?: 'Sin nivel',
                                    'lado' => $ultimo->lado ?: 'Sin lado',
                                    'responsable' => $ultimo->usuario?->name ?? $ultimo->responsable ?? 'Sin usuario asignado',
                                    'actividad' => $ultimo->actividad ?: 'Sin actividad registrada.',
                                    'avance' => count($componentesDetalle) . ' de ' . $modulo['total_componentes'],
                                    'componentes' => $componentesDetalle,
                                    'evidencias' => $evidenciaUrls($ultimo->evidencia_fotos),
                                    'capturarUrl' => $modulo['capturar_url'],
                                    'creado' => $ultimo->created_at?->format('d/m/Y H:i') ?? 'N/A',
                                    'actualizado' => $ultimo->updated_at?->format('d/m/Y H:i') ?? 'N/A',
                                ];
                            }
                        @endphp

                        <article
                            class="excentricos-module-tile group relative flex min-w-0 flex-col justify-between overflow-hidden rounded-lg border px-2.5 py-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:px-3 {{ $meta['tile'] }}"
                        >
                            <span class="absolute inset-x-0 top-0 h-1 {{ $meta['accent'] }}"></span>

                            <span class="flex items-start gap-2">
                                <span class="min-w-0">
                                    <span class="block break-words text-base font-black leading-tight text-slate-950 sm:text-xl">
                                        Modulo {{ $modulo['modulo'] }}
                                    </span>
                                    <span class="mt-0.5 block break-words text-[11px] font-black uppercase leading-tight text-slate-400">
                                        Excentricos
                                    </span>
                                </span>
                            </span>

                            <span class="mt-3 min-w-0 space-y-2">
                                <span class="block min-w-0">
                                    <span class="block text-[10px] font-black uppercase tracking-wide text-slate-400">
                                        ESTADO
                                    </span>
                                    <span class="mt-1 flex w-full items-start gap-1.5 rounded-lg px-2 py-2 text-[11px] font-black leading-tight ring-1 sm:px-2.5 sm:text-xs {{ $meta['pill'] }}" title="{{ $meta['label'] }}">
                                        <i class="fas {{ $meta['icon'] }} mt-0.5 shrink-0"></i>
                                        <span class="min-w-0 flex-1 break-words">{{ $meta['short'] }}</span>
                                    </span>
                                </span>

                                <span class="block min-w-0 text-xs font-bold text-slate-600">
                                    @if($ultimo)
                                        <span class="block truncate">{{ $ultimo->fecha_analisis?->format('d/m/Y') }}</span>
                                        <span class="mt-0.5 block truncate text-slate-400">{{ $piezasTexto }}</span>
                                    @else
                                        <span class="block text-slate-400">Sin registro</span>
                                        <span class="mt-0.5 block text-slate-400">{{ $piezasTexto }}</span>
                                    @endif
                                </span>
                            </span>

                            <div class="mt-3 grid gap-2">
                                @if($ultimo)
                                    <button
                                        type="button"
                                        class="inline-flex min-h-9 w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-2 text-xs font-black text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:px-3"
                                        @click.stop='abrirDetalle(@json($detalleModal, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP))'
                                    >
                                        <i class="fas fa-eye"></i>
                                        <span class="break-words leading-tight">Ver detalles</span>
                                    </button>
                                @endif

                                <a
                                    href="{{ $modulo['capturar_url'] }}"
                                    aria-label="Capturar Excentricos {{ $linea->nombre }} Modulo {{ $modulo['modulo'] }}"
                                    class="inline-flex min-h-9 w-full items-center justify-between gap-2 rounded-lg bg-blue-600 px-2 text-xs font-black text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:px-3"
                                >
                                    <span class="min-w-0 break-words leading-tight">Capturar revision</span>
                                    <i class="fas fa-chevron-right shrink-0 text-[10px] transition group-hover:translate-x-0.5"></i>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-6 text-center text-sm font-bold text-slate-500 shadow-sm">
                No hay modulos disponibles para capturar.
            </div>
        @endforelse
    </div>

    <div
        x-cloak
        x-show="detalleAbierto"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-2 sm:p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="excentricos-detail-title"
    >
        <button
            type="button"
            class="absolute inset-0 h-full w-full cursor-default"
            aria-label="Cerrar detalles"
            @click="cerrarDetalle()"
        ></button>

        <section
            x-show="detalleAbierto"
            x-transition
            @click.stop
            class="animate-modalIn relative z-10 flex max-h-[94svh] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-xl sm:max-h-[90vh]"
        >
            <div class="border-b border-gray-100 px-3 py-3 sm:px-6 sm:py-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100">
                            <i class="fas fa-chart-line text-sm text-gray-600"></i>
                        </div>
                        <h3 id="excentricos-detail-title" class="min-w-0 break-words font-medium text-gray-900">
                            Detalle del Analisis
                        </h3>
                    </div>
                    <button
                        type="button"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        aria-label="Cerrar modal"
                        @click="cerrarDetalle()"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="custom-scrollbar overflow-auto bg-gray-50 p-3 sm:p-5 lg:p-8" style="max-height: calc(94svh - 72px);">
                <div class="grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-lg border-l-4 border-gray-700 bg-white p-3 shadow-sm transition-all hover:shadow-md sm:p-5">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-gray-100 p-2 sm:p-3">
                                <i class="fas fa-temperature-half text-lg text-gray-700 sm:text-xl"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-mono text-xs font-semibold uppercase tracking-wider text-gray-500">Pasteurizadora</p>
                                <p class="mt-1 break-words text-base font-bold text-gray-800 sm:text-lg" x-text="detalle.linea"></p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border-l-4 border-gray-700 bg-white p-3 shadow-sm transition-all hover:shadow-md sm:p-5">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-gray-100 p-2 sm:p-3">
                                <i class="fas fa-cog text-lg text-gray-700 sm:text-xl"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-mono text-xs font-semibold uppercase tracking-wider text-gray-500">Componente</p>
                                <p class="mt-1 break-words text-base font-bold text-gray-800 sm:text-lg" x-text="detalle.componente"></p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border-l-4 border-gray-700 bg-white p-3 shadow-sm transition-all hover:shadow-md sm:p-5">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-gray-100 p-2 sm:p-3">
                                <i class="fas fa-cubes-stacked text-lg text-gray-700 sm:text-xl"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-mono text-xs font-semibold uppercase tracking-wider text-gray-500">Modulo</p>
                                <p class="mt-1 break-words text-base font-bold text-gray-800 sm:text-lg" x-text="detalle.modulo"></p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border-l-4 border-gray-700 bg-white p-3 shadow-sm transition-all hover:shadow-md sm:p-5">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-gray-100 p-2 sm:p-3">
                                <i class="fas fa-layer-group text-lg text-gray-700 sm:text-xl"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-mono text-xs font-semibold uppercase tracking-wider text-gray-500">Nivel</p>
                                <p class="mt-1 break-words text-base font-bold text-gray-800 sm:text-lg" x-text="detalle.nivel"></p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border-l-4 border-gray-700 bg-white p-3 shadow-sm transition-all hover:shadow-md sm:p-5">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-gray-100 p-2 sm:p-3">
                                <i class="fas fa-arrows-alt-h text-lg text-gray-700 sm:text-xl"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-mono text-xs font-semibold uppercase tracking-wider text-gray-500">Lado</p>
                                <p class="mt-1 break-words text-base font-bold text-gray-800 sm:text-lg" x-text="detalle.lado"></p>
                                <div class="mt-2">
                                    <span class="lado-badge" :class="detalle.lado === 'VAPOR' ? 'vapor' : 'pasillo'">
                                        <i class="fas" :class="detalle.lado === 'VAPOR' ? 'fa-wind' : 'fa-walking'"></i>
                                        <span x-text="detalle.lado"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border-l-4 border-gray-700 bg-white p-3 shadow-sm transition-all hover:shadow-md sm:p-5">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-gray-100 p-2 sm:p-3">
                                <i class="far fa-calendar-alt text-lg text-gray-700 sm:text-xl"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-mono text-xs font-semibold uppercase tracking-wider text-gray-500">Fecha</p>
                                <p class="mt-1 break-words font-mono text-base font-bold text-gray-800 sm:text-lg">
                                    <span x-text="detalle.fecha"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-blue-200 bg-white p-3 shadow-sm sm:p-5">
                        <div class="mb-3 flex items-center gap-3 sm:mb-4">
                            <div class="rounded-lg bg-blue-100 p-2">
                                <i class="fas fa-user-check text-blue-600"></i>
                            </div>
                            <h4 class="break-words border-b-2 border-blue-200 text-sm font-semibold uppercase tracking-wider text-gray-700">Responsable</h4>
                        </div>
                        <div class="flex justify-center">
                            <div class="w-full rounded-lg bg-blue-50 px-3 py-3 text-center text-sm font-semibold text-blue-700 sm:px-6">
                                Realizado por: <span x-text="detalle.responsable"></span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-green-200 bg-white p-3 shadow-sm sm:p-5">
                        <div class="mb-3 flex items-center gap-3 sm:mb-4">
                            <div class="rounded-lg bg-green-100 p-2">
                                <i class="fas fa-clipboard-check text-green-600"></i>
                            </div>
                            <h4 class="break-words border-b-2 border-green-200 text-sm font-semibold uppercase tracking-wider text-gray-700">Estado</h4>
                        </div>
                        <div class="flex justify-center">
                            <div class="w-full rounded-lg px-3 py-3 text-center font-mono text-sm tracking-wider text-white sm:px-6" :class="detalle.estadoModalClase">
                                <i class="fas mr-1" :class="detalle.estadoIcono"></i>
                                <span x-text="detalle.estado"></span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-blue-200 bg-white p-3 shadow-sm sm:p-5">
                        <div class="mb-3 flex flex-col gap-3 sm:mb-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="rounded-lg bg-blue-100 p-2">
                                    <i class="fas fa-list-check text-blue-600"></i>
                                </div>
                                <h4 class="break-words border-b-2 border-blue-200 text-sm font-semibold uppercase tracking-wider text-gray-700">Piezas revisadas</h4>
                            </div>
                            <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700" x-text="detalle.avance"></span>
                        </div>
                        <div class="flex flex-wrap gap-2" x-show="detalle.componentes.length > 0">
                            <template x-for="pieza in detalle.componentes" :key="pieza">
                                <span class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 font-mono text-sm font-bold text-gray-800">
                                    <i class="fas fa-check mr-1 text-blue-600"></i>
                                    <span x-text="'#' + pieza"></span>
                                </span>
                            </template>
                        </div>
                        <p class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600" x-show="detalle.componentes.length === 0">
                            No se registraron piezas seleccionadas.
                        </p>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm sm:p-5">
                        <div class="mb-3 flex items-center gap-3 sm:mb-4">
                            <div class="rounded-lg bg-gray-200 p-2">
                                <i class="fas fa-sticky-note text-gray-700"></i>
                            </div>
                            <h4 class="break-words font-mono text-sm font-semibold uppercase tracking-wider text-gray-700">Actividad</h4>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 sm:p-4">
                            <p class="whitespace-pre-line break-words text-sm leading-relaxed text-gray-700" x-text="detalle.actividad"></p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 sm:mt-6" x-show="detalle.evidencias.length > 0">
                    <div class="px-1 py-3 text-gray-700 sm:px-6 sm:py-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg p-2">
                                <i class="fas fa-images text-xl"></i>
                            </div>
                            <div>
                                <h4 class="break-words font-mono text-base font-bold uppercase tracking-wider sm:text-lg">Evidencia Fotografica</h4>
                            </div>
                        </div>
                    </div>
                    <div class="border-x-2 border-b-2 border-gray-200 bg-white p-3 sm:p-6">
                        <div class="image-grid-enhanced">
                            <template x-for="(foto, index) in detalle.evidencias" :key="foto">
                                <button
                                    type="button"
                                    class="image-item block w-full text-left"
                                    :aria-label="'Abrir evidencia ' + (index + 1)"
                                    @click="abrirImagen(index)"
                                >
                                    <div class="image-number" x-text="'#' + (index + 1)"></div>
                                    <img :src="foto" :alt="'Evidencia ' + (index + 1)" class="grid-image">
                                    <div class="image-info">
                                        <span class="download-image-btn">
                                            <i class="fas fa-expand"></i>
                                            Ver imagen
                                        </span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:mt-8 sm:flex-row sm:justify-end">
                    <a
                        :href="detalle.capturarUrl"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-700 bg-gray-800 px-4 py-3 text-center font-medium text-white shadow-md transition-all hover:bg-gray-900 hover:shadow-lg sm:w-auto sm:px-6"
                    >
                        <i class="fas fa-plus"></i>
                        <span class="break-words leading-tight">Capturar nueva revision</span>
                    </a>

                    <button
                        type="button"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-gray-200 px-4 py-3 font-medium text-gray-700 shadow-md transition-all hover:bg-gray-300 hover:shadow-lg sm:w-auto sm:px-6"
                        @click="cerrarDetalle()"
                    >
                        <i class="fas fa-times"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </section>
    </div>

    <div
        id="excentricosSingleImageModal"
        x-cloak
        x-show="imagenAbierta"
        x-transition.opacity
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/95 p-2 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-label="Evidencia fotografica"
        @click="cerrarImagen()"
    >
        <div class="relative flex h-full w-full max-w-6xl items-center justify-center" @click.stop>
            <button
                type="button"
                class="absolute right-2 top-2 z-20 flex h-10 w-10 items-center justify-center rounded-lg border border-white/20 bg-black/50 text-white backdrop-blur transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white sm:right-4 sm:top-4 sm:h-11 sm:w-11"
                aria-label="Cerrar imagen"
                @click="cerrarImagen()"
            >
                <i class="fas fa-times text-xl"></i>
            </button>

            <button
                type="button"
                class="excentricos-image-arrow absolute left-4 top-1/2 z-20 h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-black/50 text-white backdrop-blur transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white"
                aria-label="Imagen anterior"
                x-show="detalle.evidencias.length > 1"
                @click="cambiarImagen(-1)"
            >
                <i class="fas fa-chevron-left text-xl"></i>
            </button>

            <div
                class="excentricos-single-image-stage relative flex h-full w-full items-center justify-center"
                @click.stop
                @pointerdown="iniciarDeslizImagen($event)"
                @pointermove="moverDeslizImagen($event)"
                @pointerup="terminarDeslizImagen($event)"
                @pointercancel="cancelarDeslizImagen($event)"
                @touchstart.passive="iniciarDeslizImagen($event)"
                @touchmove="moverDeslizImagen($event)"
                @touchend="terminarDeslizImagen($event)"
                @touchcancel="cancelarDeslizImagen($event)"
            >
                <img
                    :src="imagenActual"
                    :alt="'Evidencia ' + (imagenActualIndex + 1)"
                    class="max-h-[82svh] max-w-full rounded-lg border border-white/10 object-contain shadow-2xl sm:max-h-[84vh]"
                    draggable="false"
                >
            </div>

            <button
                type="button"
                class="excentricos-image-arrow absolute right-4 top-1/2 z-20 h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-black/50 text-white backdrop-blur transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white"
                aria-label="Imagen siguiente"
                x-show="detalle.evidencias.length > 1"
                @click="cambiarImagen(1)"
            >
                <i class="fas fa-chevron-right text-xl"></i>
            </button>

            <div
                class="absolute bottom-3 left-1/2 z-20 -translate-x-1/2 rounded-lg border border-white/10 bg-black/70 px-3 py-2 font-mono text-xs text-white backdrop-blur sm:bottom-4 sm:px-4 sm:text-sm"
                x-show="detalle.evidencias.length > 0"
                x-text="(imagenActualIndex + 1) + ' / ' + detalle.evidencias.length"
            ></div>
        </div>
    </div>
</div>
@endsection
