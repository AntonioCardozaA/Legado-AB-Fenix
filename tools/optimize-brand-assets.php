<?php

// Deterministic lossless encoding only: originals, dimensions and visible pixels stay intact.
declare(strict_types=1);

$assetRoot = dirname(__DIR__) . '/public/images';
$destination = $assetRoot . '/optimized';
if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
    throw new RuntimeException('Cannot create optimized asset directory.');
}

foreach (range(1, 4) as $number) {
    $sourcePath = $assetRoot . '/logo' . $number . '.png';
    $original = imagecreatefrompng($sourcePath);
    imagesavealpha($original, true);
    ob_start();
    imagewebp($original, null, IMG_WEBP_LOSSLESS);
    $encoded = ob_get_clean();
    $decoded = imagecreatefromstring($encoded);
    if (!$decoded || imagesx($original) !== imagesx($decoded) || imagesy($original) !== imagesy($decoded)) {
        throw new RuntimeException('Encoded dimensions do not match.');
    }
    $hiddenColorChanges = 0;
    for ($y = 0; $y < imagesy($original); $y++) {
        for ($x = 0; $x < imagesx($original); $x++) {
            $before = imagecolorat($original, $x, $y);
            $after = imagecolorat($decoded, $x, $y);
            if ($before === $after) continue;
            if (($before >> 24) === 127 && ($after >> 24) === 127) {
                $hiddenColorChanges++;
                continue;
            }
            throw new RuntimeException('A visible pixel or its alpha changed.');
        }
    }
    if (strlen($encoded) >= filesize($sourcePath)) {
        throw new RuntimeException('No byte reduction; do not publish this variant.');
    }
    $filename = 'logo' . $number . '-' . substr(hash('sha256', $encoded), 0, 12) . '.webp';
    if (file_put_contents($destination . '/' . $filename, $encoded) !== strlen($encoded)) {
        throw new RuntimeException('Cannot write complete asset.');
    }
    echo json_encode([
        'asset' => 'images/optimized/' . $filename,
        'original_bytes' => filesize($sourcePath), 'optimized_bytes' => strlen($encoded),
        'width' => imagesx($original), 'height' => imagesy($original),
        'visible_pixels_identical' => true, 'transparent_rgb_changes' => $hiddenColorChanges,
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    imagedestroy($original);
    imagedestroy($decoded);
}
