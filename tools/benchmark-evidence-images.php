<?php

declare(strict_types=1);

foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'AI_ENABLED' => 'false', 'AI_HISTORY_INDEX_AUTO' => 'false', 'LOG_CHANNEL' => 'null', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'] as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
ini_set('memory_limit', '512M');
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
    throw new RuntimeException('Expected isolated SQLite configuration.');
}
$root = sys_get_temp_dir() . '/legado-evidence-' . bin2hex(random_bytes(5));
mkdir($root, 0777, true);
$app->usePublicPath($root . '/public');
$app->useStoragePath($root . '/storage');
$optimizer = new App\Services\ImageEvidenceOptimizer;
foreach (['client_ready' => [1600, 1200, 'jpg'], 'large_jpeg' => [4032, 3024, 'jpg'], 'large_png' => [3000, 2000, 'png']] as $name => [$width, $height, $extension]) {
    $image = imagecreatetruecolor($width, $height);
    for ($y = 0; $y < $height; $y += 32) for ($x = 0; $x < $width; $x += 32) {
        $color = imagecolorallocate($image, ($x * 13 + $y * 7) % 256, ($x * 3 + $y * 11) % 256, ($x + $y * 5) % 256);
        imagefilledrectangle($image, $x, $y, min($x + 31, $width - 1), min($y + 31, $height - 1), $color);
    }
    $fixture = $root . '/' . $name . '.' . $extension;
    $extension === 'png' ? imagepng($image, $fixture) : imagejpeg($image, $fixture, 85);
    imagedestroy($image);
    $samples = [];
    for ($i = 0; $i < 4; $i++) {
        $source = $root . '/' . $name . '-upload-' . $i . '.' . $extension;
        copy($fixture, $source);
        $file = new Illuminate\Http\UploadedFile($source, 'fixture.' . $extension, 'image/' . ($extension === 'jpg' ? 'jpeg' : 'png'), null, true);
        $start = hrtime(true);
        $stored = $optimizer->store($file, 'fixture', ['base_name' => $name . '-' . $i, 'mirror_to_storage' => true]);
        $elapsed = (hrtime(true) - $start) / 1e6;
        $public = public_path('storage/' . $stored);
        $mirror = storage_path('app/public/' . $stored);
        if (!is_file($mirror) || hash_file('sha256', $public) !== hash_file('sha256', $mirror)) throw new RuntimeException('Mirror differs.');
        if ($i > 0) $samples[] = round($elapsed, 2);
    }
    sort($samples);
    [$outWidth, $outHeight] = getimagesize($public);
    echo json_encode(['case' => $name, 'input_bytes' => filesize($fixture), 'output_bytes' => filesize($public), 'dimensions' => [$outWidth, $outHeight], 'median_ms' => $samples[1], 'original_bytes_preserved' => hash_file('sha256', $fixture) === hash_file('sha256', $public), 'mirror_identical' => true]) . PHP_EOL;
}
echo json_encode(['fixture_directory' => $root, 'operational_database_used' => false]) . PHP_EOL;
