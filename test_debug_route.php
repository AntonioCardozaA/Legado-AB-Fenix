<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

// Configurar como si fuera L-04
$lineaNombre = 'L-04';
$referencia = [
    'mask' => 'cadenas/linea4-mask.png',
    'width' => 1502,
    'height' => 374,
];

$maskPath = public_path('images/Diagramas-Lavadoras/' . $referencia['mask']);

echo "=== DEBUG ROUTE GENERATION ===\n";
echo "Line: $lineaNombre\n";
echo "Mask: $maskPath\n";
echo "File exists: " . (is_file($maskPath) ? 'YES' : 'NO') . "\n\n";

if (!is_file($maskPath)) {
    echo "ERROR: Mask file not found!\n";
    exit;
}

// Read mask and detect center line
$image = imagecreatefrompng($maskPath);
$imgWidth = imagesx($image);
$imgHeight = imagesy($image);

echo "Image size: {$imgWidth}x{$imgHeight}\n";

// Map allowed pixels
$allowed = [];
$whiteCount = 0;
for ($y = 0; $y < $imgHeight; $y++) {
    $allowed[$y] = [];
    for ($x = 0; $x < $imgWidth; $x++) {
        $color = imagecolorat($image, $x, $y);
        $red = ($color >> 16) & 255;
        $allowed[$y][$x] = ($red > 100);
        if ($allowed[$y][$x]) $whiteCount++;
    }
}

echo "White/gray pixels: $whiteCount\n";

// Find center line pixels
$centerPixels = [];
for ($y = 0; $y < $imgHeight; $y++) {
    for ($x = 0; $x < $imgWidth; $x++) {
        if (!$allowed[$y][$x]) continue;
        
        // Calculate minimum distance to boundary
        $minDist = 100;
        for ($dy = -8; $dy <= 8; $dy++) {
            for ($dx = -8; $dx <= 8; $dx++) {
                $ny = $y + $dy;
                $nx = $x + $dx;
                
                if ($ny < 0 || $ny >= $imgHeight || $nx < 0 || $nx >= $imgWidth || !$allowed[$ny][$nx]) {
                    $dist = max(abs($dx), abs($dy));
                    $minDist = min($minDist, $dist);
                }
            }
        }
        
        if ($minDist >= 3) {
            $centerPixels[] = [$x, $y];
        }
    }
}

echo "Center pixels (minDist >= 3): " . count($centerPixels) . "\n";

if (count($centerPixels) < 50) {
    echo "ERROR: Not enough center pixels!\n";
    exit;
}

// Sort center pixels by X position
usort($centerPixels, fn($a, $b) => $a[0] <=> $b[0]);

// Group by X position
$groupedByX = [];
$groupWidth = 3;

foreach ($centerPixels as $px) {
    $groupX = (int)($px[0] / $groupWidth);
    if (!isset($groupedByX[$groupX])) {
        $groupedByX[$groupX] = [];
    }
    $groupedByX[$groupX][] = $px;
}

ksort($groupedByX);

// Create key points
$keyPoints = [];
foreach ($groupedByX as $group) {
    if (empty($group)) continue;
    $avgY = array_sum(array_map(fn($p) => $p[1], $group)) / count($group);
    $keyPoints[] = [$group[0][0], $avgY];
}

echo "Key points: " . count($keyPoints) . "\n";
echo "First 10 key points:\n";
for ($i = 0; $i < min(10, count($keyPoints)); $i++) {
    echo "  [{$keyPoints[$i][0]}, {$keyPoints[$i][1]}]\n";
}

// Generate path
if (!empty($keyPoints)) {
    $path = 'M ' . round($keyPoints[0][0], 1) . ' ' . round($keyPoints[0][1], 1);
    $lastPoint = $keyPoints[0];
    $totalLength = 0;
    $stepCount = 0;

    for ($i = 1; $i < count($keyPoints); $i++) {
        $next = $keyPoints[$i];
        
        for ($step = 1; $step <= 4; $step++) {
            $t = $step / 4;
            $tSmooth = $t * $t * (3 - 2 * $t);
            
            $x = $lastPoint[0] + ($next[0] - $lastPoint[0]) * $tSmooth;
            $y = $lastPoint[1] + ($next[1] - $lastPoint[1]) * $tSmooth;
            
            $path .= ' L ' . round($x, 1) . ' ' . round($y, 1);
            
            if ($lastPoint) {
                $totalLength += sqrt(
                    pow($x - $lastPoint[0], 2) +
                    pow($y - $lastPoint[1], 2)
                );
            }
            
            $lastPoint = [$x, $y];
            $stepCount++;
        }
    }

    // Close path
    $first = $keyPoints[0];
    for ($step = 1; $step <= 4; $step++) {
        $t = $step / 4;
        $tSmooth = $t * $t * (3 - 2 * $t);
        
        $x = $lastPoint[0] + ($first[0] - $lastPoint[0]) * $tSmooth;
        $y = $lastPoint[1] + ($first[1] - $lastPoint[1]) * $tSmooth;
        
        $path .= ' L ' . round($x, 1) . ' ' . round($y, 1);
        
        if ($lastPoint) {
            $totalLength += sqrt(
                pow($x - $lastPoint[0], 2) +
                pow($y - $lastPoint[1], 2)
            );
        }
        
        $lastPoint = [$x, $y];
        $stepCount++;
    }

    echo "\nPath generation:\n";
    echo "Total path length: " . $totalLength . " units\n";
    echo "Total steps: " . $stepCount . "\n";
    
    $linksCount = max(350, min(600, (int) ceil($totalLength / 8)));
    $duration = max(22, (int) round($totalLength / 150));
    
    echo "Calculated links: $linksCount\n";
    echo "Duration: $duration seconds\n";
    
    echo "\nPath (first 500 chars): " . substr($path, 0, 500) . "...\n";
}

imagedestroy($image);
echo "\nDone!\n";
