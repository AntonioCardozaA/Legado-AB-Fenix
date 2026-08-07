<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ImageEvidenceOptimizer
{
    public const UPLOAD_MAX_KILOBYTES = 12288;

    private const MAX_DIMENSION_PIXELS = 1600;
    private const JPEG_QUALITY = 82;
    private const TARGET_MAX_BYTES = 1048576;
    private const MIN_JPEG_QUALITY = 76;

    public function store(UploadedFile $file, string $relativeDirectory, array $options = []): string
    {
        $relativeDirectory = trim(str_replace('\\', '/', $relativeDirectory), '/');
        $publicDirectory = public_path('storage/' . $relativeDirectory);
        $this->ensureDirectory($publicDirectory);

        $baseName = $this->sanitizeBaseName(
            $options['base_name'] ?? pathinfo($file->hashName(), PATHINFO_FILENAME)
        );

        try {
            $optimized = $this->optimizeToJpeg($file, $publicDirectory, $baseName);

            if ($optimized !== null) {
                $this->mirrorToStorageIfNeeded($optimized, (bool) ($options['mirror_to_storage'] ?? false));

                return $optimized;
            }
        } catch (Throwable $exception) {
            Log::warning('No se pudo optimizar la evidencia fotografica; se guardara el archivo original.', [
                'error' => $exception->getMessage(),
                'archivo' => $file->getClientOriginalName(),
            ]);
        }

        $stored = $this->storeOriginal($file, $publicDirectory, $relativeDirectory, $baseName);
        $this->mirrorToStorageIfNeeded($stored, (bool) ($options['mirror_to_storage'] ?? false));

        return $stored;
    }

    private function optimizeToJpeg(UploadedFile $file, string $publicDirectory, string $baseName): ?string
    {
        if (!$this->gdSupportsBasicImageProcessing()) {
            return null;
        }

        $sourcePath = $file->getRealPath();
        if (!$sourcePath || !is_file($sourcePath)) {
            return null;
        }

        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return null;
        }

        [$sourceWidth, $sourceHeight] = $imageInfo;
        $mimeType = strtolower((string) ($imageInfo['mime'] ?? $file->getMimeType() ?? ''));

        if ($mimeType === 'image/gif') {
            return null;
        }

        if (
            max($sourceWidth, $sourceHeight) <= self::MAX_DIMENSION_PIXELS
            && (int) $file->getSize() <= self::TARGET_MAX_BYTES
        ) {
            return null;
        }

        $source = $this->createImageResource($sourcePath, $mimeType);
        if (!$source) {
            return null;
        }

        try {
            $source = $this->applyOrientation($source, $sourcePath, $mimeType);
            $orientedWidth = imagesx($source);
            $orientedHeight = imagesy($source);
            $scale = min(1, self::MAX_DIMENSION_PIXELS / max($orientedWidth, $orientedHeight));
            $targetWidth = max(1, (int) round($orientedWidth * $scale));
            $targetHeight = max(1, (int) round($orientedHeight * $scale));

            $target = imagecreatetruecolor($targetWidth, $targetHeight);
            if (!$target) {
                return null;
            }

            try {
                $white = imagecolorallocate($target, 255, 255, 255);
                imagefill($target, 0, 0, $white);

                if (!imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $orientedWidth, $orientedHeight)) {
                    return null;
                }

                $relativePath = trim($this->relativePathFor($publicDirectory, $baseName . '.jpg'), '/');
                $absolutePath = public_path('storage/' . $relativePath);

                $quality = self::JPEG_QUALITY;
                do {
                    if (!imagejpeg($target, $absolutePath, $quality)) {
                        return null;
                    }

                    $quality -= 3;
                } while (
                    filesize($absolutePath) > self::TARGET_MAX_BYTES
                    && $quality >= self::MIN_JPEG_QUALITY
                );

                $sourceBytes = (int) $file->getSize();
                $optimizedBytes = (int) filesize($absolutePath);
                $wasResized = $targetWidth !== $orientedWidth || $targetHeight !== $orientedHeight;

                if (!$wasResized && $sourceBytes > 0 && $optimizedBytes >= $sourceBytes) {
                    @unlink($absolutePath);

                    return null;
                }

                return $relativePath;
            } finally {
                imagedestroy($target);
            }
        } finally {
            imagedestroy($source);
        }
    }

    private function gdSupportsBasicImageProcessing(): bool
    {
        return function_exists('imagecreatetruecolor')
            && function_exists('imagecopyresampled')
            && function_exists('imagejpeg')
            && function_exists('imagedestroy');
    }

    private function createImageResource(string $path, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'image/bmp', 'image/x-ms-bmp' => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($path) : false,
            default => false,
        };
    }

    private function applyOrientation($source, string $sourcePath, string $mimeType)
    {
        if ($mimeType !== 'image/jpeg') {
            return $source;
        }

        $orientation = $this->readJpegOrientation($sourcePath);

        if ($orientation < 2 || $orientation > 8) {
            return $source;
        }

        return match ($orientation) {
            2 => $this->flipImage($source, IMG_FLIP_HORIZONTAL),
            3 => $this->rotateImage($source, 180),
            4 => $this->flipImage($source, IMG_FLIP_VERTICAL),
            5 => $this->flipImage($this->rotateImage($source, 270), IMG_FLIP_HORIZONTAL),
            6 => $this->rotateImage($source, 270),
            7 => $this->flipImage($this->rotateImage($source, 90), IMG_FLIP_HORIZONTAL),
            8 => $this->rotateImage($source, 90),
            default => $source,
        };
    }

    private function rotateImage($source, int $angle)
    {
        if (!function_exists('imagerotate')) {
            return $source;
        }

        $rotated = @imagerotate($source, $angle, 0);

        if (!$rotated) {
            return $source;
        }

        imagedestroy($source);

        return $rotated;
    }

    private function flipImage($source, int $mode)
    {
        if (!function_exists('imageflip')) {
            return $source;
        }

        imageflip($source, $mode);

        return $source;
    }

    private function readJpegOrientation(string $path): int
    {
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $orientation = (int) ($exif['Orientation'] ?? 1);

            if ($orientation >= 1 && $orientation <= 8) {
                return $orientation;
            }
        }

        return $this->readJpegOrientationFromBytes($path);
    }

    private function readJpegOrientationFromBytes(string $path): int
    {
        $data = @file_get_contents($path, false, null, 0, 65536);

        if (!$data || strlen($data) < 4 || substr($data, 0, 2) !== "\xFF\xD8") {
            return 1;
        }

        $offset = 2;
        $length = strlen($data);

        while ($offset + 4 <= $length) {
            if (ord($data[$offset]) !== 0xFF) {
                break;
            }

            $marker = ord($data[$offset + 1]);
            $offset += 2;

            if ($marker === 0xDA || $marker === 0xD9) {
                break;
            }

            if ($offset + 2 > $length) {
                break;
            }

            $segmentLength = unpack('n', substr($data, $offset, 2))[1] ?? 0;
            if ($segmentLength < 2 || $offset + $segmentLength > $length) {
                break;
            }

            $segmentStart = $offset + 2;
            $segmentBytes = $segmentLength - 2;

            if ($marker === 0xE1 && substr($data, $segmentStart, 6) === "Exif\x00\x00") {
                return $this->readOrientationFromTiff($data, $segmentStart + 6, $segmentBytes - 6);
            }

            $offset += $segmentLength;
        }

        return 1;
    }

    private function readOrientationFromTiff(string $data, int $tiffStart, int $tiffLength): int
    {
        if ($tiffLength < 14) {
            return 1;
        }

        $byteOrder = substr($data, $tiffStart, 2);
        $littleEndian = $byteOrder === 'II';

        if (!$littleEndian && $byteOrder !== 'MM') {
            return 1;
        }

        if ($this->readUnsignedShort($data, $tiffStart + 2, $littleEndian) !== 42) {
            return 1;
        }

        $ifdOffset = $this->readUnsignedLong($data, $tiffStart + 4, $littleEndian);
        $ifdStart = $tiffStart + $ifdOffset;

        if ($ifdStart + 2 > strlen($data)) {
            return 1;
        }

        $entryCount = $this->readUnsignedShort($data, $ifdStart, $littleEndian);

        for ($index = 0; $index < $entryCount; $index++) {
            $entryStart = $ifdStart + 2 + ($index * 12);

            if ($entryStart + 12 > strlen($data)) {
                break;
            }

            $tag = $this->readUnsignedShort($data, $entryStart, $littleEndian);

            if ($tag !== 0x0112) {
                continue;
            }

            $orientation = $this->readUnsignedShort($data, $entryStart + 8, $littleEndian);

            return ($orientation >= 1 && $orientation <= 8) ? $orientation : 1;
        }

        return 1;
    }

    private function readUnsignedShort(string $data, int $offset, bool $littleEndian): int
    {
        $bytes = substr($data, $offset, 2);

        if (strlen($bytes) < 2) {
            return 0;
        }

        return unpack($littleEndian ? 'v' : 'n', $bytes)[1] ?? 0;
    }

    private function readUnsignedLong(string $data, int $offset, bool $littleEndian): int
    {
        $bytes = substr($data, $offset, 4);

        if (strlen($bytes) < 4) {
            return 0;
        }

        return unpack($littleEndian ? 'V' : 'N', $bytes)[1] ?? 0;
    }

    private function storeOriginal(UploadedFile $file, string $publicDirectory, string $relativeDirectory, string $baseName): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';
        $fileName = $baseName . '.' . $extension;

        $file->move($publicDirectory, $fileName);

        return $relativeDirectory . '/' . $fileName;
    }

    private function mirrorToStorageIfNeeded(string $relativePath, bool $enabled): void
    {
        if (!$enabled) {
            return;
        }

        try {
            $source = public_path('storage/' . $relativePath);
            $destination = storage_path('app/public/' . $relativePath);

            $this->ensureDirectory(dirname($destination));

            if (is_file($source)) {
                copy($source, $destination);
            }
        } catch (Throwable $exception) {
            Log::warning('No se pudo copiar evidencia a storage/app/public', [
                'error' => $exception->getMessage(),
                'path' => $relativePath,
            ]);
        }
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException("No se pudo crear el directorio {$directory}");
        }
    }

    private function sanitizeBaseName(string $baseName): string
    {
        $baseName = preg_replace('/[^A-Za-z0-9_-]/', '_', $baseName) ?: uniqid('evidencia_', true);

        return trim($baseName, '_') ?: uniqid('evidencia_', true);
    }

    private function relativePathFor(string $publicDirectory, string $fileName): string
    {
        $storagePublicRoot = str_replace('\\', '/', public_path('storage'));
        $directory = str_replace('\\', '/', $publicDirectory);

        return trim(str_replace($storagePublicRoot, '', $directory), '/') . '/' . $fileName;
    }
}
