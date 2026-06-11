<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

$maskPath = public_path('images/Diagramas-Lavadoras/cadenas/linea4-mask.png');
echo "Mask path: $maskPath\n";
echo "File exists: " . (is_file($maskPath) ? 'YES' : 'NO') . "\n";

if (!is_file($maskPath)) exit;

$image = imagecreatefrompng($maskPath);
$w = imagesx($image);
$h = imagesy($image);
echo "Image dimensions: {$w}x{$h}\n";

// Contar píxeles permitidos
$whitePixels = 0;
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $color = imagecolorat($image, $x, $y);
        $red = ($color >> 16) & 255;
        if ($red > 100) $whitePixels++;
    }
}
echo "White/gray pixels (red > 100): $whitePixels\n";

// Encontrar línea central
$centerPixels = 0;
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $color = imagecolorat($image, $x, $y);
        $red = ($color >> 16) & 255;
        if ($red <= 100) continue;
        
        // Calcular distancia mínima a un píxel NO permitido
        $minDist = 100;
        for ($dy = -8; $dy <= 8; $dy++) {
            for ($dx = -8; $dx <= 8; $dx++) {
                $ny = $y + $dy;
                $nx = $x + $dx;
                
                if ($ny < 0 || $ny >= $h || $nx < 0 || $nx >= $w) {
                    $minDist = 0;
                    break 2;
                }
                
                $nColor = imagecolorat($image, $nx, $ny);
                $nRed = ($nColor >> 16) & 255;
                if ($nRed <= 100) {
                    $dist = max(abs($dx), abs($dy));
                    $minDist = min($minDist, $dist);
                }
            }
        }
        
        if ($minDist >= 3) {
            $centerPixels++;
        }
    }
}

echo "Center line pixels (minDist >= 3): $centerPixels\n";

imagedestroy($image);
echo "\nDone!\n";
