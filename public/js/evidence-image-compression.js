(function () {
    const MAX_INPUT_BYTES = 12 * 1024 * 1024;
    const MAX_DIMENSION = 1600;
    const JPEG_QUALITY = 0.82;
    const MIN_COMPRESS_BYTES = 900 * 1024;
    const OUTPUT_TYPE = 'image/jpeg';
    const SKIPPED_TYPES = new Set(['image/gif']);

    function canCompress() {
        return typeof document !== 'undefined'
            && typeof URL !== 'undefined'
            && typeof File !== 'undefined'
            && typeof Blob !== 'undefined';
    }

    function outputNameFor(file) {
        const baseName = (file.name || 'evidencia').replace(/\.[^.]+$/, '') || 'evidencia';

        return `${baseName}.jpg`;
    }

    function canvasToBlob(canvas, type, quality) {
        return new Promise((resolve) => {
            if (!canvas.toBlob) {
                resolve(null);
                return;
            }

            canvas.toBlob(resolve, type, quality);
        });
    }

    function loadWithImageElement(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const image = new Image();

            image.onload = () => {
                URL.revokeObjectURL(url);
                resolve({
                    width: image.naturalWidth || image.width,
                    height: image.naturalHeight || image.height,
                    draw: (context, width, height) => context.drawImage(image, 0, 0, width, height),
                    close: () => {},
                });
            };

            image.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error('No se pudo leer la imagen seleccionada.'));
            };

            image.src = url;
        });
    }

    async function decodeImage(file) {
        if (typeof createImageBitmap === 'function') {
            try {
                const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });

                return {
                    width: bitmap.width,
                    height: bitmap.height,
                    draw: (context, width, height) => context.drawImage(bitmap, 0, 0, width, height),
                    close: () => bitmap.close(),
                };
            } catch (error) {
                return loadWithImageElement(file);
            }
        }

        return loadWithImageElement(file);
    }

    async function compressImageFile(file, options = {}) {
        if (!file || !canCompress() || SKIPPED_TYPES.has(file.type)) {
            return file;
        }

        if ((file.type || '').startsWith('image/svg')) {
            return file;
        }

        const maxDimension = options.maxDimension || MAX_DIMENSION;
        const quality = options.quality || JPEG_QUALITY;

        let decoded = null;

        try {
            decoded = await decodeImage(file);
            const largestSide = Math.max(decoded.width, decoded.height);
            const scale = largestSide > maxDimension ? maxDimension / largestSide : 1;

            if (scale === 1 && file.size <= (options.minCompressBytes || MIN_COMPRESS_BYTES)) {
                return file;
            }

            const targetWidth = Math.max(1, Math.round(decoded.width * scale));
            const targetHeight = Math.max(1, Math.round(decoded.height * scale));
            const canvas = document.createElement('canvas');
            canvas.width = targetWidth;
            canvas.height = targetHeight;

            const context = canvas.getContext('2d', { alpha: false });
            if (!context) {
                return file;
            }

            context.fillStyle = '#fff';
            context.fillRect(0, 0, targetWidth, targetHeight);
            decoded.draw(context, targetWidth, targetHeight);

            const blob = await canvasToBlob(canvas, OUTPUT_TYPE, quality);
            if (!blob) {
                return file;
            }

            if (scale === 1 && blob.size >= file.size) {
                return file;
            }

            return new File([blob], outputNameFor(file), {
                type: OUTPUT_TYPE,
                lastModified: file.lastModified || Date.now(),
            });
        } catch (error) {
            return file;
        } finally {
            decoded?.close?.();
        }
    }

    window.EvidenceImageCompression = {
        MAX_INPUT_BYTES,
        MAX_DIMENSION,
        JPEG_QUALITY,
        compressImageFile,
    };
})();
