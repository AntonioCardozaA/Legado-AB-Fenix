<script>
let currentImages = [];
let currentAnalysisData = null;
let currentImageIndex = 0;
const SEARCH_COMPONENT_CODE = @json(request('componente_id') ?: request('componente'));
const OPEN_ANALYSIS_DATA = @json($openAnalysisData ?? null);
const INDEX_URL = @json(route('analisis-etiquetadora.index'));

@php
    $evidenceBaseUrls = [
        rtrim(asset('storage'), '/'),
        rtrim(asset('public/storage'), '/'),
        rtrim(asset('storage/app/public'), '/'),
        rtrim(asset('analisis-evidencias'), '/'),
        rtrim(url('storage'), '/'),
        rtrim(url('public/storage'), '/'),
        rtrim(url('storage/app/public'), '/'),
        rtrim(url('analisis-evidencias'), '/'),
    ];
@endphp
const EVIDENCE_BASE_URLS = @json($evidenceBaseUrls);

function toggleAdvancedFilters() {
    const panel = document.getElementById('advancedFiltersPanel');
    const icon = document.getElementById('advancedFiltersIcon');

    if (!panel || !icon) {
        return;
    }

    panel.classList.toggle('show');
    icon.classList.toggle('fa-chevron-down', !panel.classList.contains('show'));
    icon.classList.toggle('fa-chevron-up', panel.classList.contains('show'));
}

function selectLinea(lineaId) {
    const input = document.getElementById('lineaInput');
    const form = document.getElementById('filterForm');

    if (!input || !form) {
        return;
    }

    input.value = lineaId;

    // Al cambiar de linea debe mostrarse la etiquetadora completa: Maquina A, B y C.
    const maquinaFilter = form.querySelector('[name="maquina"]');
    if (maquinaFilter) {
        maquinaFilter.value = '';
    }

    form.submit();
}

function selectLineaFromModal(lineaId) {
    closeLineasModal();
    selectLinea(lineaId);
}

function showAllLineas() {
    const modal = document.getElementById('lineasModal');
    if (!modal) return;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeLineasModal() {
    const modal = document.getElementById('lineasModal');
    if (!modal) return;
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

function openEstadoModal(tipo, titulo, items) {
    const modal = document.getElementById('estadoModal');
    const modalTitle = document.getElementById('estadoModalTitle');
    const modalHeader = document.getElementById('estadoModalHeader');
    const modalContent = document.getElementById('estadoModalContent');

    if (!modal || !modalTitle || !modalHeader || !modalContent) {
        return;
    }

    items = Array.isArray(items) ? items : [];

    const tipoConfig = {
        total: {
            bg: 'bg-slate-700',
            text: 'text-white',
            card: 'bg-slate-50',
            borderLeft: 'border-slate-300',
            badge: 'bg-slate-100 text-slate-800',
            icon: 'fa-chart-pie'
        },
        buen_estado: {
            bg: 'bg-emerald-500',
            text: 'text-white',
            card: 'bg-emerald-50',
            borderLeft: 'border-emerald-300',
            badge: 'bg-emerald-100 text-emerald-800',
            icon: 'fa-check-circle'
        },
        requiere_revision: {
            bg: 'bg-yellow-500',
            text: 'text-white',
            card: 'bg-yellow-50',
            borderLeft: 'border-yellow-300',
            badge: 'bg-yellow-100 text-yellow-800',
            icon: 'fa-tools'
        },
        desgaste: {
            bg: 'bg-orange-500',
            text: 'text-white',
            card: 'bg-orange-50',
            borderLeft: 'border-orange-300',
            badge: 'bg-orange-100 text-orange-800',
            icon: 'fa-exclamation-triangle'
        },
        danado: {
            bg: 'bg-red-500',
            text: 'text-white',
            card: 'bg-red-50',
            borderLeft: 'border-red-300',
            badge: 'bg-red-100 text-red-800',
            icon: 'fa-times-circle'
        },
        cambiado: {
            bg: 'bg-sky-600',
            text: 'text-white',
            card: 'bg-sky-50',
            borderLeft: 'border-sky-300',
            badge: 'bg-sky-100 text-sky-800',
            icon: 'fa-sync-alt'
        }
    };

    const config = tipoConfig[tipo] || tipoConfig.total;

    modalHeader.className = `px-6 py-4 border-b flex justify-between items-center ${config.bg}`;
    modalTitle.className = `text-xl font-bold ${config.text}`;
    modalTitle.innerHTML = `<i class="fas ${config.icon} mr-2"></i>${escapeHtml(titulo)} (${items.length})`;

    if (items.length === 0) {
        modalContent.innerHTML = `
            <div class="text-center py-12">
                <div class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <i class="fas fa-clipboard-list text-2xl"></i>
                </div>
                <p class="text-gray-600 text-lg font-semibold">Sin registros para este estado</p>
                <p class="text-gray-400 text-sm mt-1">Los filtros actuales no tienen coincidencias.</p>
            </div>
        `;
    } else {
        const grouped = {};
        items.forEach(item => {
            const lineaKey = item.linea || 'Sin linea';
            grouped[lineaKey] = grouped[lineaKey] || [];
            grouped[lineaKey].push(item);
        });

        let html = '<div class="space-y-6">';
        Object.entries(grouped).forEach(([linea, lineItems]) => {
            html += `
                <div class="rounded-3xl border border-slate-200 bg-slate-50 shadow-sm overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 bg-white border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <div class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-slate-100 text-slate-700">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Linea</p>
                                <p class="text-lg font-semibold text-slate-900">${escapeHtml(linea)}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold uppercase tracking-[0.2em]">
                            ${lineItems.length} registro${lineItems.length === 1 ? '' : 's'}
                        </span>
                    </div>
                    <div class="p-4 space-y-3">
            `;

            lineItems.forEach(item => {
                const estadoText = escapeHtml(item.estado || 'Sin estado');
                const openUrl = `${INDEX_URL}?open_analysis_id=${encodeURIComponent(item.id || '')}`;

                html += `
                    <div class="rounded-3xl border-l-4 ${config.borderLeft} ${config.card} p-4 shadow-sm hover:shadow-md transition">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                                    <span class="px-3 py-1 rounded-full bg-white text-slate-700 border border-slate-200">${escapeHtml(item.reductor || 'Sin maquina')}</span>
                                    <span class="px-3 py-1 rounded-full bg-white text-slate-700 border border-slate-200">${escapeHtml(item.fecha || 'Sin fecha')}</span>
                                </div>
                                <div class="flex items-center gap-2 text-base font-semibold text-slate-900">
                                    <p>${escapeHtml(item.componente || 'Sin componente')}</p>
                                </div>
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold ${config.badge}">
                                    <i class="fas ${config.icon}"></i>${estadoText}
                                </span>
                            </div>
                            <a href="${openUrl}" class="create-action create-action--compact" onclick="event.stopPropagation();">
                                <i class="fas fa-eye"></i>
                                Ver
                            </a>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });

        html += '</div>';
        modalContent.innerHTML = html;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeEstadoModal() {
    const modal = document.getElementById('estadoModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function showLineaPreview(lineaNombre, lineaId, estadosPreview, totalCeldas, celdasConDatos) {
    showLoading();

    document.getElementById('previewModalTitle').innerHTML = `<i class="fas fa-tags mr-2"></i>${escapeHtml(lineaNombre)} - Resumen de Estados`;

    const statsGrid = document.getElementById('previewStatsGrid');
    statsGrid.innerHTML = `
        <div class="bg-green-50 rounded-lg p-4 text-center border border-green-200">
            <i class="fas fa-check-circle text-green-600 text-2xl mb-2"></i>
            <p class="text-xs text-gray-500">Buen Estado</p>
            <p class="text-2xl font-bold text-green-600">${estadosPreview.buen_estado || 0}</p>
        </div>
        <div class="bg-yellow-50 rounded-lg p-4 text-center border border-yellow-200">
            <i class="fas fa-tools text-yellow-600 text-2xl mb-2"></i>
            <p class="text-xs text-gray-500">Requiere revision</p>
            <p class="text-2xl font-bold text-yellow-600">${estadosPreview.requiere_revision || 0}</p>
        </div>
        <div class="bg-orange-50 rounded-lg p-4 text-center border border-orange-200">
            <i class="fas fa-exclamation-triangle text-orange-600 text-2xl mb-2"></i>
            <p class="text-xs text-gray-500">Severo / Moderado</p>
            <p class="text-2xl font-bold text-orange-600">${estadosPreview.desgaste || 0}</p>
        </div>
        <div class="bg-red-50 rounded-lg p-4 text-center border border-red-200">
            <i class="fas fa-times-circle text-red-600 text-2xl mb-2"></i>
            <p class="text-xs text-gray-500">Danado</p>
            <p class="text-2xl font-bold text-red-600">${estadosPreview.danado || 0}</p>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 text-center border border-blue-200">
            <i class="fas fa-sync-alt text-blue-600 text-2xl mb-2"></i>
            <p class="text-xs text-gray-500">Cambiado</p>
            <p class="text-2xl font-bold text-blue-600">${estadosPreview.cambiado || 0}</p>
        </div>
    `;

    const porcentaje = totalCeldas > 0 ? Math.round((celdasConDatos / totalCeldas) * 100) : 0;
    document.getElementById('previewProgresoText').innerHTML = `${celdasConDatos} de ${totalCeldas} celdas analizadas (${porcentaje}%)`;
    document.getElementById('previewProgresoBar').style.width = `${porcentaje}%`;
    document.getElementById('previewProgresoBar').className = `h-3 rounded-full transition-all duration-500 ${porcentaje >= 80 ? 'bg-green-500' : (porcentaje >= 50 ? 'bg-yellow-500' : 'bg-red-500')}`;

    const detailsTable = document.getElementById('previewDetailsTable');
    const targetUrl = lineaId ? `${INDEX_URL}?linea_id=${encodeURIComponent(lineaId)}` : INDEX_URL;
    detailsTable.innerHTML = `
        <div class="bg-white rounded-lg p-5 border border-gray-200">
            <div class="text-center py-8">
                <i class="fas fa-info-circle text-gray-400 text-4xl mb-3"></i>
                <p class="text-gray-500">Detalle por componente disponible al seleccionar la linea</p>
                <a href="${targetUrl}" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-arrow-right mr-2"></i>Ver linea completa
                </a>
            </div>
        </div>
    `;

    const modal = document.getElementById('previewEstadosModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    hideLoading();
}

function closePreviewModal() {
    const modal = document.getElementById('previewEstadosModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function openAnalysisDetail(analysisData) {
    showLoading();
    currentAnalysisData = analysisData;

    document.getElementById('detail-linea').textContent = analysisData.linea || '';
    document.getElementById('detail-componente').textContent = analysisData.componente || '';
    document.getElementById('detail-componente-codigo').textContent = analysisData.componente_codigo || '';
    document.getElementById('detail-reductor').textContent = analysisData.reductor || analysisData.maquina || '';

    const piezasContainer = document.getElementById('detail-piezas-container');
    const piezasResumen = document.getElementById('detail-piezas-resumen');
    const piezasList = document.getElementById('detail-piezas');
    const totalPiezas = parseInt(analysisData.total_componentes || '0', 10) || 0;
    const piezasRevisadas = Array.isArray(analysisData.componentes_revisados)
        ? analysisData.componentes_revisados
            .map((item) => parseInt(item, 10))
            .filter((item) => item > 0)
        : [];

    if (piezasContainer && piezasResumen && piezasList && totalPiezas > 1 && piezasRevisadas.length > 0) {
        piezasContainer.classList.remove('hidden');
        piezasResumen.textContent = `${piezasRevisadas.length} de ${totalPiezas}`;
        piezasList.innerHTML = piezasRevisadas
            .map((numero) => `<span class="rounded bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 border border-indigo-100">#${numero}</span>`)
            .join('');
    } else if (piezasContainer && piezasResumen && piezasList) {
        piezasContainer.classList.add('hidden');
        piezasResumen.textContent = '';
        piezasList.innerHTML = '';
    }

    const ladoContainer = document.getElementById('detail-lado-container');
    const ladoElement = document.getElementById('detail-lado');
    const ladoBadgeContainer = document.getElementById('detail-lado-badge-container');
    const tieneLado = analysisData.lado && String(analysisData.lado).trim() !== '';

    if (tieneLado) {
        ladoContainer.classList.remove('hidden');
        const ladoTexto = analysisData.lado === 'VAPOR' ? 'Lado Vapor' : 'Lado Pasillo';
        ladoElement.textContent = ladoTexto;
        const badge = document.createElement('span');
        badge.className = `lado-badge ${analysisData.lado === 'VAPOR' ? 'vapor' : 'pasillo'}`;
        badge.innerHTML = analysisData.lado === 'VAPOR'
            ? '<i class="fas fa-wind"></i> Vapor'
            : '<i class="fas fa-walking"></i> Pasillo';
        ladoBadgeContainer.innerHTML = '';
        ladoBadgeContainer.appendChild(badge);
    } else {
        ladoContainer.classList.add('hidden');
        ladoElement.textContent = '';
        ladoBadgeContainer.innerHTML = '';
    }

    document.getElementById('detail-fecha').textContent = analysisData.fecha_analisis || '';
    document.getElementById('detail-orden').textContent = analysisData.numero_orden || '';
    document.getElementById('detail-actividad').textContent = analysisData.actividad || '';
    document.getElementById('detail-usuario').textContent = `Realizado por: ${analysisData.usuario_nombre || 'Usuario no registrado'}`;

    const estadoElement = document.getElementById('detail-estado');
    let bgClass = 'bg-gray-800';
    if (analysisData.color === 'cell-ok') {
        bgClass = 'bg-green-800';
    } else if (analysisData.color === 'cell-review') {
        bgClass = 'bg-yellow-700';
    } else if (analysisData.color === 'cell-warning') {
        bgClass = 'bg-orange-700';
    } else if (analysisData.color === 'cell-danger') {
        bgClass = 'bg-red-800';
    } else if (analysisData.color === 'cell-changed') {
        bgClass = 'bg-blue-800';
    }
    estadoElement.className = `px-6 py-3 ${bgClass} text-white rounded-lg font-mono text-sm tracking-wider w-full text-center`;
    estadoElement.textContent = analysisData.estado || '';

    document.getElementById('detail-edit-btn').href = analysisData.edit_url || '#';
    const historialBtn = document.getElementById('detail-historial-btn');
    const historialText = document.getElementById('detail-historial-text');

    if ((analysisData.total_historial || 0) > 1 && analysisData.historial_url) {
        historialBtn.classList.remove('hidden');
        historialBtn.href = analysisData.historial_url;
        historialText.innerHTML = `<i class="fas fa-history mr-2"></i>Ver Historial (${analysisData.total_historial})`;
    } else {
        historialBtn.classList.add('hidden');
    }

    const imagesSection = document.getElementById('detail-images-section');
    analysisData.imagenes = normalizeEvidenceImages(analysisData.imagenes);
    if (analysisData.imagenes.length > 0) {
        imagesSection.classList.remove('hidden');
        buildDetailImageGridEnhanced(analysisData.imagenes);
    } else {
        imagesSection.classList.add('hidden');
    }

    const modal = document.getElementById('analysisDetailModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    hideLoading();
}

function confirmDeleteAnalysis() {
    if (!currentAnalysisData || !currentAnalysisData.delete_url) {
        return;
    }

    Swal.fire({
        icon: 'warning',
        title: 'Eliminar analisis',
        text: 'Esta accion es irreversible y eliminara el registro seleccionado.',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    }).then(function(result) {
        if (result.isConfirmed) {
            const form = document.getElementById('delete-analysis-form');
            form.action = currentAnalysisData.delete_url;
            form.submit();
        }
    });
}

function normalizeEvidenceImages(imagenes) {
    if (!imagenes) {
        return [];
    }

    if (typeof imagenes === 'string') {
        const valor = imagenes.trim();
        if (!valor || valor === 'null' || valor === '[]') return [];
        if ((valor.startsWith('[') && valor.endsWith(']')) || (valor.startsWith('{') && valor.endsWith('}')) || (valor.startsWith('"') && valor.endsWith('"'))) {
            try {
                return normalizeEvidenceImages(JSON.parse(valor));
            } catch (error) {
                return [valor];
            }
        }
        return [valor];
    }

    if (typeof imagenes === 'object' && !Array.isArray(imagenes)) {
        return normalizeEvidenceImages(Object.values(imagenes));
    }

    if (!Array.isArray(imagenes)) {
        return [];
    }

    return imagenes
        .flatMap((item) => normalizeEvidenceImages(item))
        .map((item) => String(item).trim().replace(/\\/g, '/'))
        .filter((item) => item.length > 0);
}

function normalizeEvidencePath(path) {
    let normalizedPath = String(path ?? '').trim().replace(/\\/g, '/');
    if (!normalizedPath) return '';

    try {
        if (normalizedPath.startsWith(window.location.origin)) {
            normalizedPath = normalizedPath.replace(window.location.origin, '');
        }
    } catch (error) {}

    normalizedPath = normalizedPath.split('?')[0].split('#')[0];
    normalizedPath = normalizedPath.replace(/^\/+/, '');
    normalizedPath = normalizedPath.replace(/^public\//, '');
    normalizedPath = normalizedPath.replace(/^app\/public\//, '');
    normalizedPath = normalizedPath.replace(/^storage\/app\/public\//, '');
    normalizedPath = normalizedPath.replace(/^public\/storage\//, '');
    normalizedPath = normalizedPath.replace(/^storage\//, '');

    return normalizedPath;
}

function resolveEvidenceImageCandidates(path) {
    const rawPath = String(path ?? '').trim().replace(/\\/g, '/');
    if (!rawPath) return [];
    if (/^https?:\/\//i.test(rawPath)) return [rawPath];

    const cleanPath = normalizeEvidencePath(rawPath);
    if (!cleanPath) return [];

    const fileOnly = cleanPath.split('/').pop();
    const withFolder = cleanPath.startsWith('analisis-evidencias/')
        ? cleanPath
        : 'analisis-evidencias/' + cleanPath;
    const candidates = [];

    (EVIDENCE_BASE_URLS || []).forEach((base) => {
        if (!base) return;
        const cleanBase = String(base).replace(/\/+$/, '');
        candidates.push(cleanBase + '/' + cleanPath);
        candidates.push(cleanBase + '/' + withFolder);
        candidates.push(cleanBase + '/' + fileOnly);
    });

    candidates.push(
        '/storage/' + cleanPath,
        '/storage/' + withFolder,
        '/public/storage/' + cleanPath,
        '/public/storage/' + withFolder,
        '/storage/app/public/' + cleanPath,
        '/storage/app/public/' + withFolder,
        '/' + cleanPath,
        '/' + withFolder,
        '/storage/analisis-evidencias/' + fileOnly,
        '/public/storage/analisis-evidencias/' + fileOnly,
        '/storage/app/public/analisis-evidencias/' + fileOnly,
        '/analisis-evidencias/' + fileOnly
    );

    return [...new Set(candidates.filter(Boolean))];
}

function resolveEvidenceImageUrl(path) {
    const candidates = resolveEvidenceImageCandidates(path);
    return candidates.length > 0 ? candidates[0] : '';
}

function setEvidenceImageFallback(img, originalPath) {
    const candidates = resolveEvidenceImageCandidates(originalPath);
    img.dataset.fallbackIndex = '0';
    img.dataset.candidates = JSON.stringify(candidates);

    img.onerror = function () {
        let urls = [];
        try {
            urls = JSON.parse(this.dataset.candidates || '[]');
        } catch (error) {
            urls = [];
        }

        let nextIndex = parseInt(this.dataset.fallbackIndex || '0', 10) + 1;
        if (nextIndex < urls.length) {
            this.dataset.fallbackIndex = String(nextIndex);
            this.src = urls[nextIndex];
            return;
        }

        this.onerror = null;
        this.classList.add('bg-gray-100');
        this.alt = 'No se pudo cargar la imagen';
        this.closest('.image-item')?.classList.add('border-red-300');
    };
}

function buildDetailImageGridEnhanced(imagenes) {
    currentImages = normalizeEvidenceImages(imagenes);
    const grid = document.getElementById('detail-image-grid');
    grid.innerHTML = '';

    currentImages.forEach((path, index) => {
        const item = document.createElement('div');
        item.className = 'image-item';
        const safePath = String(path).replace(/'/g, "\\'");
        item.innerHTML = `
            <div class="image-number">#${index + 1}</div>
            <img src="${resolveEvidenceImageUrl(path)}" class="grid-image" onclick="openSingleImage('${safePath}', ${index})" alt="Evidencia ${index + 1}">
            <div class="image-info">
                <button class="download-image-btn" onclick="event.stopPropagation(); downloadSingleImage('${safePath}', ${index})">
                    <i class="fas fa-download"></i>
                    Descargar
                </button>
            </div>
        `;
        setEvidenceImageFallback(item.querySelector('img'), path);
        grid.appendChild(item);
    });
}

function openAllImages(imagenes, fecha, orden, estado) {
    showLoading();
    currentImages = normalizeEvidenceImages(imagenes);
    const modal = document.getElementById('allImagesModal');
    const grid = document.getElementById('imageGrid');
    const empty = document.getElementById('emptyImages');
    grid.innerHTML = '';

    if (currentImages.length === 0) {
        grid.classList.add('hidden');
        empty.classList.remove('hidden');
    } else {
        grid.classList.remove('hidden');
        empty.classList.add('hidden');

        currentImages.forEach((path, index) => {
            const item = document.createElement('div');
            item.className = 'image-item';
            const safePath = String(path).replace(/'/g, "\\'");
            item.innerHTML = `
                <div class="image-number">#${index + 1}</div>
                <img src="${resolveEvidenceImageUrl(path)}" class="grid-image" onclick="openSingleImage('${safePath}', ${index})" alt="Evidencia ${index + 1}">
                <div class="image-info">
                    <button class="download-image-btn" onclick="event.stopPropagation(); downloadSingleImage('${safePath}', ${index})">
                        <i class="fas fa-download"></i>
                        Descargar
                    </button>
                </div>
            `;
            setEvidenceImageFallback(item.querySelector('img'), path);
            grid.appendChild(item);
        });
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    hideLoading();
}

function updateSingleImageModal() {
    const img = document.getElementById('singleModalImg');
    const counter = document.getElementById('currentImageCounter');
    const prevBtn = document.getElementById('prevImageBtn');
    const nextBtn = document.getElementById('nextImageBtn');
    const imagePath = currentImages[currentImageIndex] ?? '';
    const hasMultipleImages = currentImages.length > 1;

    img.src = resolveEvidenceImageUrl(imagePath);
    setEvidenceImageFallback(img, imagePath);
    counter.textContent = currentImages.length > 0 ? `${currentImageIndex + 1} / ${currentImages.length}` : '';
    prevBtn.classList.toggle('hidden', !hasMultipleImages);
    nextBtn.classList.toggle('hidden', !hasMultipleImages);
}

function openSingleImage(imagePath, index) {
    currentImageIndex = index;
    if (currentImages.length === 0) {
        currentImages = [imagePath];
        currentImageIndex = 0;
    }

    updateSingleImageModal();
    const modal = document.getElementById('singleImageModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function changeSingleImage(direction) {
    if (currentImages.length <= 1) return;
    currentImageIndex = (currentImageIndex + direction + currentImages.length) % currentImages.length;
    updateSingleImageModal();
}

function downloadSingleImage(imagePath, index) {
    const link = document.createElement('a');
    link.href = resolveEvidenceImageUrl(imagePath);
    link.download = `imagen-${index + 1}.jpg`;
    link.click();
}

function closeAnalysisDetailModal() {
    const modal = document.getElementById('analysisDetailModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function closeAllImagesModal() {
    const modal = document.getElementById('allImagesModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function closeSingleImageModal() {
    const modal = document.getElementById('singleImageModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function showLoading() {
    document.getElementById('loadingOverlay')?.classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay')?.classList.add('hidden');
}

function focusSearchedComponent() {
    if (!SEARCH_COMPONENT_CODE) {
        return;
    }

    const targets = Array.from(document.querySelectorAll('.search-target-component'));
    if (targets.length === 0) {
        return;
    }

    targets.forEach((target) => {
        target.classList.remove('cell-highlight');
        void target.offsetWidth;
        target.classList.add('cell-highlight');
    });

    const firstTarget = document.querySelector('.search-target-cell') || targets[0];
    const lineCard = firstTarget.closest('[data-linea-card]') || firstTarget.closest('.lavadora-card');

    if (lineCard) {
        lineCard.classList.add('search-target-line');
        lineCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    window.setTimeout(() => {
        const wrapper = firstTarget.closest('.table-wrapper');
        if (wrapper) {
            const targetLeft = firstTarget.offsetLeft - (wrapper.clientWidth / 2) + (firstTarget.offsetWidth / 2);
            wrapper.scrollTo({ left: Math.max(0, targetLeft), behavior: 'smooth' });
        }
        firstTarget.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
    }, 250);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSingleImageModal();
        closeAllImagesModal();
        closeAnalysisDetailModal();
        closeLineasModal();
        closePreviewModal();
        closeEstadoModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#new') {
        const newCell = document.querySelector('.badge-new');
        if (newCell) {
            newCell.closest('.analysis-cell')?.classList.add('cell-highlight');
        }
    }

    focusSearchedComponent();

    if (OPEN_ANALYSIS_DATA) {
        openAnalysisDetail(OPEN_ANALYSIS_DATA);
    }

    const lineasModal = document.getElementById('lineasModal');
    if (lineasModal) {
        lineasModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeLineasModal();
            }
        });
    }
});
</script>
