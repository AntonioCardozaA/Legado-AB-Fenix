<div id="estadoModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4" onclick="if(event.target === this) closeEstadoModal()">
    <div class="bg-white rounded-xl shadow-xl max-w-5xl w-full max-h-[85vh] overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center" id="estadoModalHeader">
            <h3 class="text-xl font-bold" id="estadoModalTitle">Detalle por Estado</h3>
            <button onclick="closeEstadoModal()" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center transition">
                <i class="fas fa-times text-gray-500"></i>
            </button>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(85vh-80px)]" id="estadoModalContent">
            <div class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-gray-500">Cargando...</p>
            </div>
        </div>
    </div>
</div>

<div id="previewEstadosModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4" onclick="if(event.target === this) closePreviewModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[85vh] overflow-hidden animate-modalIn">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <i class="fas fa-chart-pie text-xl"></i>
                <h3 class="font-bold text-xl" id="previewModalTitle">Resumen de Estados</h3>
            </div>
            <button onclick="closePreviewModal()" class="w-8 h-8 rounded-lg bg-gray-700 hover:bg-gray-600 transition flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(85vh-80px)] bg-gray-50" id="previewModalContent">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6" id="previewStatsGrid"></div>
            <div class="bg-white rounded-lg p-5 border border-gray-200 mb-4">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-bold text-gray-700">Progreso General</h4>
                    <span class="text-sm text-gray-500" id="previewProgresoText"></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div id="previewProgresoBar" class="h-3 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
            <div id="previewDetailsTable"></div>
            <div class="flex justify-end mt-6 pt-4 border-t border-gray-200">
                <button onclick="closePreviewModal()" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div id="analysisDetailModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4"
     onclick="if(event.target === this) closeAnalysisDetailModal()">
    <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-tags text-gray-600 text-sm"></i>
                    </div>
                    <h3 class="font-medium text-gray-900" id="detailModalTitle">Detalle del Analisis</h3>
                </div>
                <button onclick="closeAnalysisDetailModal()"
                        class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="p-8 overflow-auto max-h-[calc(90vh-100px)] bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg p-5 border-l-4 border-gray-700 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="fas fa-industry text-gray-700 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono">Linea</p>
                            <p id="detail-linea" class="font-bold text-gray-800 text-lg mt-1"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-5 border-l-4 border-gray-700 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="fas fa-cog text-gray-700 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono">Componente</p>
                            <p id="detail-componente" class="font-bold text-gray-800 text-lg mt-1"></p>
                            <p id="detail-componente-codigo" class="text-xs text-gray-500 mt-1 font-mono"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-5 border-l-4 border-gray-700 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="fas fa-tags text-gray-700 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono">Maquina</p>
                            <p id="detail-reductor" class="font-bold text-gray-800 text-lg mt-1"></p>
                        </div>
                    </div>
                </div>

                <div id="detail-lado-container" class="bg-white rounded-lg p-5 border-l-4 border-gray-700 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="fas fa-arrows-alt-h text-gray-700 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono">Lado</p>
                            <p id="detail-lado" class="font-bold text-gray-800 text-lg mt-1"></p>
                            <div id="detail-lado-badge-container" class="mt-2"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-5 border-l-4 border-gray-700 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="far fa-calendar-alt text-gray-700 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono">Fecha</p>
                            <p id="detail-fecha" class="font-bold text-gray-800 text-lg mt-1 font-mono"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-5 border-l-4 border-gray-700 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-start gap-3">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <i class="fas fa-hashtag text-gray-700 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono">Orden</p>
                            <p id="detail-orden" class="font-bold text-gray-800 text-lg mt-1 font-mono"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="bg-white rounded-lg p-5 border border-blue-200 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-blue-100 p-2 rounded-lg">
                            <i class="fas fa-user-check text-blue-600"></i>
                        </div>
                        <h4 class="font-semibold text-gray-700 border-blue-200 border-b-2 uppercase tracking-wider text-sm">Responsable</h4>
                    </div>
                    <div id="detail-usuario" class="px-6 py-3 bg-blue-50 text-blue-700 rounded-lg text-sm w-full text-center font-semibold"></div>
                </div>

                <div class="bg-white rounded-lg p-5 border border-green-200 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-green-100 p-2 rounded-lg">
                            <i class="fas fa-clipboard-check text-green-600"></i>
                        </div>
                        <h4 class="font-semibold text-gray-700 border-green-200 border-b-2 uppercase tracking-wider text-sm">Estado</h4>
                    </div>
                    <div id="detail-estado" class="px-6 py-3 bg-green-100 text-green-700 rounded-lg text-sm w-full text-center"></div>
                </div>

                <div class="bg-white rounded-lg p-5 border border-gray-200 shadow-sm md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-gray-200 p-2 rounded-lg">
                            <i class="fas fa-sticky-note text-gray-700"></i>
                        </div>
                        <h4 class="font-semibold text-gray-700 uppercase tracking-wider text-sm font-mono">Actividad</h4>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <p id="detail-actividad" class="text-gray-700 whitespace-pre-line leading-relaxed text-sm"></p>
                    </div>
                </div>
            </div>

            <div id="detail-images-section" class="mt-6 hidden">
                <div class="text-gray-700 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-images text-xl"></i>
                        <h4 class="font-bold text-lg uppercase tracking-wider font-mono">Evidencia Fotografica</h4>
                    </div>
                </div>
                <div class="bg-white p-6 border-x-2 border-b-2 border-gray-200">
                    <div id="detail-image-grid" class="image-grid-enhanced"></div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-end gap-3 mt-8 pt-4 border-t border-gray-200">
                <a id="detail-edit-btn"
                   href="#"
                   class="w-full sm:w-auto justify-center px-6 py-3 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition-all shadow-md hover:shadow-lg flex items-center gap-2 font-medium border border-gray-700">
                    <i class="fas fa-edit"></i>
                    Editar Analisis
                </a>

                <a id="detail-historial-btn"
                   href="#"
                   class="w-full sm:w-auto justify-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all shadow-md hover:shadow-lg flex items-center gap-2 font-medium hidden border border-gray-500">
                    <span id="detail-historial-text">Ver Historial</span>
                </a>

                @if($canDeleteAnalysis)
                    <button id="detail-delete-btn"
                            type="button"
                            onclick="confirmDeleteAnalysis()"
                            class="w-full sm:w-auto justify-center px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all shadow-md hover:shadow-lg flex items-center gap-2 font-medium border border-red-700">
                        <i class="fas fa-trash"></i>
                        Eliminar
                    </button>
                @endif

                <button onclick="closeAnalysisDetailModal()"
                        class="w-full sm:w-auto justify-center px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all shadow-md hover:shadow-lg flex items-center gap-2 font-medium border border-gray-300">
                    <i class="fas fa-times"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@if($canDeleteAnalysis)
    <form id="delete-analysis-form" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endif

<div id="allImagesModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-all duration-300"
     onclick="closeAllImagesModal()">
    <div class="bg-white rounded-lg shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden transform transition-all duration-300 scale-100 border border-gray-200"
         onclick="event.stopPropagation()">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-8 py-5">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="bg-gray-700 p-3 rounded-lg border border-gray-600">
                        <i class="fas fa-images text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-xl uppercase tracking-wider font-mono">
                            <span id="modalTitle">Galeria Industrial</span>
                        </h3>
                        <p class="text-gray-300 text-sm">Evidencia fotografica del analisis</p>
                    </div>
                </div>
                <button onclick="closeAllImagesModal()"
                        class="w-10 h-10 rounded-lg bg-gray-700 hover:bg-gray-600 transition-all flex items-center justify-center group border border-gray-600">
                    <i class="fas fa-times text-xl group-hover:rotate-90 transition-transform"></i>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(90vh-100px)] bg-gray-50">
            <div id="imageGrid" class="image-grid-enhanced"></div>
            <div id="emptyImages" class="hidden">
                <div class="text-center py-16">
                    <div class="bg-gray-200 w-24 h-24 rounded-lg flex items-center justify-center mx-auto mb-4 border border-gray-300">
                        <i class="fas fa-image text-4xl text-gray-500"></i>
                    </div>
                    <p class="text-gray-600 text-lg font-mono">No hay imagenes disponibles</p>
                    <p class="text-gray-400 text-sm mt-2">Este analisis no cuenta con evidencia fotografica</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="singleImageModal" class="fixed inset-0 bg-black/95 hidden items-center justify-center z-[60] p-4 transition-all duration-300"
     onclick="closeSingleImageModal()">
    <div class="relative max-w-6xl w-full h-full flex items-center justify-center">
        <button onclick="closeSingleImageModal()"
                class="absolute top-6 right-6 w-12 h-12 rounded-lg bg-gray-800/50 hover:bg-gray-700/70 text-white text-2xl flex items-center justify-center backdrop-blur-sm border border-gray-600 transition-all z-10 group">
            <i class="fas fa-times group-hover:rotate-90 transition-transform"></i>
        </button>
        <div class="relative" onclick="event.stopPropagation()">
            <button id="prevImageBtn"
                    onclick="event.stopPropagation(); changeSingleImage(-1)"
                    class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-gray-800/60 hover:bg-gray-700/80 text-white text-2xl flex items-center justify-center backdrop-blur-sm border border-gray-600 transition-all z-10 hidden">
                <i class="fas fa-chevron-left"></i>
            </button>
            <img id="singleModalImg" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl border-4 border-gray-700" alt="Evidencia">
            <button id="nextImageBtn"
                    onclick="event.stopPropagation(); changeSingleImage(1)"
                    class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-gray-800/60 hover:bg-gray-700/80 text-white text-2xl flex items-center justify-center backdrop-blur-sm border border-gray-600 transition-all z-10 hidden">
                <i class="fas fa-chevron-right"></i>
            </button>
            <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 bg-gray-900/80 backdrop-blur-sm text-white px-4 py-2 rounded-lg text-sm font-mono border border-gray-700">
                <span id="currentImageCounter"></span>
            </div>
        </div>
    </div>
</div>

<div id="loadingOverlay" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100]">
    <div class="bg-white rounded-lg p-8 shadow-2xl">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-700">Cargando...</p>
    </div>
</div>
