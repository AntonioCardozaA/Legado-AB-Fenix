{{-- Modal de imagen individual --}}
<div id="imageModal" class="fixed inset-0 bg-black/95 hidden items-center justify-center z-[100] p-4 transition-all duration-300 modal-fade-in" onclick="if(event.target === this) closeImageModal()">
    <div class="relative max-w-6xl w-full h-full flex items-center justify-center">
        <button onclick="closeImageModal()" 
                class="absolute top-6 right-6 w-12 h-12 rounded-xl bg-black/50 hover:bg-black/70 text-white text-2xl flex items-center justify-center backdrop-blur-sm border border-white/20 transition-all z-10 group">
            <i class="fas fa-times group-hover:rotate-90 transition-transform"></i>
        </button>
        
        <button id="prevImageBtn" onclick="navigateImage(-1)" 
                class="absolute left-6 top-1/2 transform -translate-y-1/2 w-12 h-12 rounded-xl bg-black/50 hover:bg-black/70 text-white text-2xl flex items-center justify-center backdrop-blur-sm border border-white/20 transition-all z-10 hidden">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <button id="nextImageBtn" onclick="navigateImage(1)" 
                class="absolute right-6 top-1/2 transform -translate-y-1/2 w-12 h-12 rounded-xl bg-black/50 hover:bg-black/70 text-white text-2xl flex items-center justify-center backdrop-blur-sm border border-white/20 transition-all z-10 hidden">
            <i class="fas fa-chevron-right"></i>
        </button>

        <div class="relative">
            <img id="modalImage" class="max-w-full max-h-[85vh] object-contain rounded-xl shadow-2xl border-4 border-white/10">
            <div id="modalCaption" class="absolute bottom-6 left-1/2 transform -translate-x-1/2 bg-black/80 backdrop-blur-sm text-white px-6 py-3 rounded-xl text-sm font-medium border border-white/20 whitespace-nowrap">
                <span id="imageCounter" class="hidden">
                    <span id="currentImageIndex">1</span> / <span id="totalImages">1</span>
                </span>
            </div>
        </div>
    </div>
</div>

{{-- Modal de galería completa --}}
<div id="galleryModal" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-[100] p-4 transition-all duration-300 modal-fade-in" onclick="if(event.target === this) closeGalleryModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-8 py-5">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="bg-gray-700 p-3 rounded-xl border border-gray-600">
                        <i class="fas fa-images text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-xl">Galería de Evidencias</h3>
                        <p class="text-gray-300 text-sm">Orden #<span id="galleryOrderNumber"></span></p>
                    </div>
                </div>
                <button onclick="closeGalleryModal()" 
                        class="w-10 h-10 rounded-xl bg-gray-700 hover:bg-gray-600 transition-all flex items-center justify-center group border border-gray-600">
                    <i class="fas fa-times text-xl group-hover:rotate-90 transition-transform"></i>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-auto max-h-[calc(90vh-100px)] bg-gray-50">
            <div id="galleryGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"></div>
        </div>
    </div>
</div>