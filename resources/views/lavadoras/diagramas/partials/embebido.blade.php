@php
    $lineaNombre = $lineaNombre ?? null;
    $grupo = $grupo ?? null;
    $monitorAlertas = $monitorAlertas ?? [];
    $paneles = [];
    $catarinas = [];
    $cadenaPaths = [];
    $flechas = [];
    $textos = [];
    $ondas = [];
    $catarinasExactasPorLinea = [];
    $viewBox = '0 0 1468 382';
    $svgWidth = 1468;
    $svgHeight = 382;
    $baseWidth = 1468;
    $baseHeight = 382;
    $planoBorde = '';
    $tituloGrupo = 'Diagrama';
    $isWide = false;
    $shapePath = null;
    $centerPaths = [];
    $chainTransform = null;
    $exactBaseImage = null;
    $useExactBaseImage = false;
    $lineaMaskKey = match ($lineaNombre) {
        'L-04' => 'linea4',
        'L-05' => 'linea5',
        'L-06' => 'linea6',
        'L-07' => 'linea7',
        'L-09' => 'linea9',
        'L-12' => 'linea12',
        'L-13' => 'linea13',
        default => null,
    };
    $maskConfigPath = resource_path('configs/diagrams/lavadora-chain-masks.php');
    $chainMasks = is_file($maskConfigPath) ? include $maskConfigPath : [];
    $chainMask = $lineaMaskKey ? ($chainMasks[$lineaMaskKey] ?? null) : null;

    if ($grupo === 'l05-l12-l13') {
        $viewBox = '0 0 1468 382';
        $svgWidth = 1468;
        $svgHeight = 382;
        $baseWidth = 1468;
        $baseHeight = 382;
        $tituloGrupo = 'L-05 / L-12 / L-13';
        $planoBorde = '28,36 196,36 206,47 1023,47 1033,36 1150,36 1150,182 1450,182 1450,359 28,359 28,36';

        $paneles = [
            ['type' => 'entrada', 'label' => 'RED 02', 'x' => 28, 'y' => 36, 'w' => 168, 'h' => 323, 'fill' => '#dedede', 'bottomLabel' => 'RED 1', 'bottomLabelX' => 100, 'bottomLabelY' => 341],
            ['type' => 'ancha', 'label' => 'RED 03', 'x' => 196, 'y' => 36, 'w' => 126, 'h' => 323],
            ['type' => 'estrecha', 'label' => 'RED 04', 'x' => 322, 'y' => 36, 'w' => 82, 'h' => 323],
            ['type' => 'ancha', 'label' => 'RED 05', 'x' => 404, 'y' => 36, 'w' => 135, 'h' => 323],
            ['type' => 'estrecha', 'label' => 'RED 06', 'x' => 539, 'y' => 36, 'w' => 75, 'h' => 323],
            ['type' => 'ancha', 'label' => 'RED 07', 'x' => 614, 'y' => 36, 'w' => 128, 'h' => 323],
            ['type' => 'estrecha', 'label' => 'RED 08', 'x' => 742, 'y' => 36, 'w' => 77, 'h' => 323],
            ['type' => 'ancha', 'label' => 'RED 09', 'x' => 819, 'y' => 36, 'w' => 130, 'h' => 323],
            ['type' => 'estrecha', 'label' => 'RED 10', 'x' => 949, 'y' => 36, 'w' => 74, 'h' => 323],
            ['type' => 'salida', 'label' => 'RED 11', 'x' => 1023, 'y' => 36, 'w' => 127, 'h' => 323, 'fill' => '#dedede'],
            ['type' => 'loca', 'label' => 'RED 12', 'labelX' => 1402, 'labelY' => 341, 'x' => 1130, 'y' => 182, 'w' => 320, 'h' => 177, 'fill' => '#dedede'],
        ];

        $catarinas = [
            ['x' => 88, 'y' => 68, 'r' => 18, 'label' => 'RED 02'],
            ['x' => 57, 'y' => 325, 'r' => 18, 'label' => 'RED 1', 'sentido' => 'inversa'],
            ['x' => 234, 'y' => 68, 'r' => 18, 'label' => 'RED 03'],
            ['x' => 356, 'y' => 68, 'r' => 18, 'label' => 'RED 04'],
            ['x' => 420, 'y' => 68, 'r' => 18, 'label' => 'RED 05'],
            ['x' => 550, 'y' => 68, 'r' => 18, 'label' => 'RED 06'],
            ['x' => 614, 'y' => 68, 'r' => 18, 'label' => 'RED 07'],
            ['x' => 760, 'y' => 68, 'r' => 18, 'label' => 'RED 08'],
            ['x' => 820, 'y' => 68, 'r' => 18, 'label' => 'RED 09'],
            ['x' => 950, 'y' => 68, 'r' => 18, 'label' => 'RED 10'],
            ['x' => 1017, 'y' => 68, 'r' => 18, 'label' => 'RED 11'],
            ['x' => 1135, 'y' => 204, 'r' => 18, 'label' => 'LOCA'],
            ['x' => 1416, 'y' => 317, 'r' => 18, 'label' => 'RED 12', 'sentido' => 'inversa'],
        ];

        $catarinasExactasPorLinea = [
            'linea5' => [
                ['x' => 89, 'y' => 69, 'r' => 18, 'label' => 'RED 02'],
                ['x' => 58, 'y' => 325, 'r' => 18, 'label' => 'RED 1', 'sentido' => 'inversa'],
                ['x' => 230, 'y' => 68, 'r' => 18, 'label' => 'RED 03'],
                ['x' => 354, 'y' => 68, 'r' => 18, 'label' => 'RED 04'],
                ['x' => 414, 'y' => 69, 'r' => 18, 'label' => 'RED 05'],
                ['x' => 556, 'y' => 69, 'r' => 18, 'label' => 'RED 06'],
                ['x' => 615, 'y' => 69, 'r' => 18, 'label' => 'RED 07'],
                ['x' => 758, 'y' => 70, 'r' => 18, 'label' => 'RED 08'],
                ['x' => 818, 'y' => 69, 'r' => 18, 'label' => 'RED 09'],
                ['x' => 950, 'y' => 69, 'r' => 18, 'label' => 'RED 10'],
                ['x' => 1017, 'y' => 69, 'r' => 18, 'label' => 'RED 11'],
                ['x' => 1131, 'y' => 209, 'r' => 18, 'label' => 'LOCA'],
                ['x' => 1414, 'y' => 290, 'r' => 18, 'label' => 'RED 12', 'sentido' => 'inversa'],
            ],
            'linea12' => [
                ['x' => 96, 'y' => 73, 'r' => 18, 'label' => 'RED 02'],
                ['x' => 65, 'y' => 335, 'r' => 18, 'label' => 'RED 1', 'sentido' => 'inversa'],
                ['x' => 235, 'y' => 72, 'r' => 18, 'label' => 'RED 03'],
                ['x' => 357, 'y' => 72, 'r' => 18, 'label' => 'RED 04'],
                ['x' => 416, 'y' => 73, 'r' => 18, 'label' => 'RED 05'],
                ['x' => 557, 'y' => 73, 'r' => 18, 'label' => 'RED 06'],
                ['x' => 614, 'y' => 74, 'r' => 18, 'label' => 'RED 07'],
                ['x' => 756, 'y' => 74, 'r' => 18, 'label' => 'RED 08'],
                ['x' => 816, 'y' => 73, 'r' => 18, 'label' => 'RED 09'],
                ['x' => 945, 'y' => 73, 'r' => 18, 'label' => 'RED 10'],
                ['x' => 1012, 'y' => 73, 'r' => 18, 'label' => 'RED 11'],
                ['x' => 1124, 'y' => 216, 'r' => 18, 'label' => 'LOCA'],
                ['x' => 1404, 'y' => 300, 'r' => 18, 'label' => 'RED 12', 'sentido' => 'inversa'],
            ],
            'linea13' => [
                ['x' => 96, 'y' => 70, 'r' => 18, 'label' => 'RED 02'],
                ['x' => 65, 'y' => 327, 'r' => 18, 'label' => 'RED 1', 'sentido' => 'inversa'],
                ['x' => 234, 'y' => 69, 'r' => 18, 'label' => 'RED 03'],
                ['x' => 355, 'y' => 69, 'r' => 18, 'label' => 'RED 04'],
                ['x' => 414, 'y' => 70, 'r' => 18, 'label' => 'RED 05'],
                ['x' => 553, 'y' => 70, 'r' => 18, 'label' => 'RED 06'],
                ['x' => 610, 'y' => 71, 'r' => 18, 'label' => 'RED 07'],
                ['x' => 750, 'y' => 71, 'r' => 18, 'label' => 'RED 08'],
                ['x' => 810, 'y' => 70, 'r' => 18, 'label' => 'RED 09'],
                ['x' => 938, 'y' => 70, 'r' => 18, 'label' => 'RED 10'],
                ['x' => 1004, 'y' => 70, 'r' => 18, 'label' => 'RED 11'],
                ['x' => 1116, 'y' => 211, 'r' => 18, 'label' => 'LOCA'],
                ['x' => 1393, 'y' => 292, 'r' => 18, 'label' => 'RED 12', 'sentido' => 'inversa'],
            ],
        ];

        $loopAncha = fn ($x, $y, $w, $h) => 'M ' . ($x + 22) . ' ' . ($y + $h - 38) .
            ' L ' . ($x + 36) . ' ' . ($y + 28) .
            ' L ' . ($x + $w - 18) . ' ' . ($y + 28) .
            ' L ' . ($x + $w - 4) . ' ' . ($y + 48) .
            ' L ' . ($x + $w - 34) . ' ' . ($y + 190) .
            ' L ' . ($x + $w - 8) . ' ' . ($y + $h - 58) .
            ' L ' . ($x + $w - 30) . ' ' . ($y + $h - 28) .
            ' L ' . ($x + 58) . ' ' . ($y + $h - 28) .
            ' L ' . ($x + 24) . ' ' . ($y + $h - 64);

        $loopEstrecha = fn ($x, $y, $w, $h) => 'M ' . ($x + 18) . ' ' . ($y + $h - 42) .
            ' L ' . ($x + 36) . ' ' . ($y + 27) .
            ' L ' . ($x + $w - 18) . ' ' . ($y + 27) .
            ' L ' . ($x + $w - 6) . ' ' . ($y + 42) .
            ' L ' . ($x + $w - 25) . ' ' . ($y + $h - 60) .
            ' L ' . ($x + $w - 44) . ' ' . ($y + $h - 28) .
            ' L ' . ($x + 30) . ' ' . ($y + $h - 28) .
            ' L ' . ($x + 8) . ' ' . ($y + $h - 64);

        $cadenaPaths = [
            ['d' => 'M 55 326 L 78 65 Q 81 52 96 52 L 188 52 Q 196 54 197 66 L 170 303 Q 168 319 150 326 L 55 326'],
            ['d' => $loopAncha(196, 36, 126, 323)],
            ['d' => $loopEstrecha(322, 36, 82, 323)],
            ['d' => $loopAncha(404, 36, 135, 323)],
            ['d' => $loopEstrecha(539, 36, 75, 323)],
            ['d' => $loopAncha(614, 36, 128, 323)],
            ['d' => $loopEstrecha(742, 36, 77, 323)],
            ['d' => $loopAncha(819, 36, 130, 323)],
            ['d' => $loopEstrecha(949, 36, 74, 323)],
            ['d' => 'M 1045 322 L 1125 56 L 1145 48 L 1148 74 L 1115 194 L 1097 250 L 1128 300 L 1100 323 L 1068 323 L 1043 300'],
            ['d' => 'M 1138 197 L 1438 197 Q 1450 198 1450 214 L 1450 295 Q 1448 312 1430 318 L 1360 320 L 1288 356 L 95 356 L 58 350 L 38 333'],
        ];

        $flechas = [
            ['x1' => 322, 'y1' => 358, 'x2' => 356, 'y2' => 84],
            ['x1' => 985, 'y1' => 354, 'x2' => 803, 'y2' => 354],
            ['x1' => 1290, 'y1' => 64, 'x2' => 1231, 'y2' => 199],
        ];

        $textos = [
            ['class' => 'texto-loca', 'x' => 1144, 'y' => 176, 'text' => 'LOCA'],
            ['class' => 'texto-espreado', 'x' => 1180, 'y' => 64, 'text' => 'Espreador'],
        ];
    } elseif ($grupo === 'l06-l07') {
        $viewBox = '0 0 1687 333';
        $svgWidth = 1687;
        $svgHeight = 333;
        $baseWidth = 1687;
        $baseHeight = 333;
        $tituloGrupo = 'L-06 / L-07';
        $isWide = true;
        $planoBorde = '22,30 190,30 200,41 1205,41 1215,30 1355,30 1355,158 1667,158 1667,315 22,315 22,30';

        $paneles = [
            ['type' => 'entrada', 'label' => 'RED 9', 'x' => 22, 'y' => 30, 'w' => 168, 'h' => 285, 'fill' => '#dedede', 'bottomLabel' => 'RED 1', 'bottomLabelX' => 92, 'bottomLabelY' => 300],
            ['type' => 'ancha', 'label' => 'RED 10', 'x' => 190, 'y' => 30, 'w' => 128, 'h' => 285],
            ['type' => 'estrecha', 'label' => 'RED 11', 'x' => 318, 'y' => 30, 'w' => 72, 'h' => 285],
            ['type' => 'ancha', 'label' => 'RED 12', 'x' => 390, 'y' => 30, 'w' => 142, 'h' => 285],
            ['type' => 'estrecha', 'label' => 'RED 13', 'x' => 532, 'y' => 30, 'w' => 60, 'h' => 285],
            ['type' => 'ancha', 'label' => 'RED 14', 'x' => 592, 'y' => 30, 'w' => 140, 'h' => 285],
            ['type' => 'estrecha', 'label' => 'RED 15', 'x' => 732, 'y' => 30, 'w' => 70, 'h' => 285],
            ['type' => 'ancha', 'label' => 'RED 16', 'x' => 802, 'y' => 30, 'w' => 140, 'h' => 285],
            ['type' => 'estrecha', 'label' => 'RED 17', 'x' => 942, 'y' => 30, 'w' => 60, 'h' => 285],
            ['type' => 'ancha', 'label' => 'RED 18', 'x' => 1002, 'y' => 30, 'w' => 138, 'h' => 285],
            ['type' => 'estrecha', 'label' => 'RED 19', 'x' => 1140, 'y' => 30, 'w' => 65, 'h' => 285],
            ['type' => 'salida', 'label' => 'RED 20', 'x' => 1205, 'y' => 30, 'w' => 150, 'h' => 285, 'fill' => '#dedede'],
            ['type' => 'loca', 'label' => 'RED 21', 'labelX' => 1380, 'labelY' => 151, 'labelAnchor' => 'start', 'x' => 1352, 'y' => 158, 'w' => 315, 'h' => 157, 'fill' => '#dedede', 'bottomLabel' => 'RED 22', 'bottomLabelX' => 1618, 'bottomLabelY' => 302],
        ];

        $catarinas = [
            ['x' => 80, 'y' => 62, 'r' => 17, 'label' => 'RED 9'],
            ['x' => 46, 'y' => 292, 'r' => 17, 'label' => 'RED 1', 'sentido' => 'inversa'],
            ['x' => 235, 'y' => 63, 'r' => 17, 'label' => 'RED 10'],
            ['x' => 350, 'y' => 63, 'r' => 17, 'label' => 'RED 11'],
            ['x' => 412, 'y' => 63, 'r' => 17, 'label' => 'RED 12'],
            ['x' => 555, 'y' => 63, 'r' => 17, 'label' => 'RED 13'],
            ['x' => 625, 'y' => 63, 'r' => 17, 'label' => 'RED 14'],
            ['x' => 766, 'y' => 63, 'r' => 17, 'label' => 'RED 15'],
            ['x' => 825, 'y' => 63, 'r' => 17, 'label' => 'RED 16'],
            ['x' => 965, 'y' => 63, 'r' => 17, 'label' => 'RED 17'],
            ['x' => 1028, 'y' => 63, 'r' => 17, 'label' => 'RED 18'],
            ['x' => 1159, 'y' => 63, 'r' => 17, 'label' => 'RED 19'],
            ['x' => 1240, 'y' => 63, 'r' => 17, 'label' => 'RED 20'],
            ['x' => 1368, 'y' => 181, 'r' => 17, 'label' => 'RED 21'],
            ['x' => 1638, 'y' => 292, 'r' => 17, 'label' => 'RED 22', 'sentido' => 'inversa'],
        ];

        $catarinasExactasPorLinea = [
            'linea6' => [
                ['x' => 84, 'y' => 59, 'r' => 17, 'label' => 'RED 9'],
                ['x' => 52, 'y' => 285, 'r' => 17, 'label' => 'RED 1', 'sentido' => 'inversa'],
                ['x' => 226, 'y' => 58, 'r' => 17, 'label' => 'RED 10'],
                ['x' => 351, 'y' => 58, 'r' => 17, 'label' => 'RED 11'],
                ['x' => 411, 'y' => 58, 'r' => 17, 'label' => 'RED 12'],
                ['x' => 555, 'y' => 58, 'r' => 17, 'label' => 'RED 13'],
                ['x' => 618, 'y' => 59, 'r' => 17, 'label' => 'RED 14'],
                ['x' => 761, 'y' => 59, 'r' => 17, 'label' => 'RED 15'],
                ['x' => 821, 'y' => 59, 'r' => 17, 'label' => 'RED 16'],
                ['x' => 966, 'y' => 59, 'r' => 17, 'label' => 'RED 17'],
                ['x' => 1027, 'y' => 59, 'r' => 17, 'label' => 'RED 18'],
                ['x' => 1160, 'y' => 59, 'r' => 17, 'label' => 'RED 19'],
                ['x' => 1228, 'y' => 59, 'r' => 17, 'label' => 'RED 20'],
                ['x' => 1344, 'y' => 183, 'r' => 17, 'label' => 'RED 21'],
                ['x' => 1630, 'y' => 254, 'r' => 17, 'label' => 'RED 22', 'sentido' => 'inversa'],
            ],
            'linea7' => [
                ['x' => 70, 'y' => 63, 'r' => 17, 'label' => 'RED 9'],
                ['x' => 38, 'y' => 286, 'r' => 17, 'label' => 'RED 1', 'sentido' => 'inversa'],
                ['x' => 214, 'y' => 62, 'r' => 17, 'label' => 'RED 10'],
                ['x' => 340, 'y' => 62, 'r' => 17, 'label' => 'RED 11'],
                ['x' => 402, 'y' => 63, 'r' => 17, 'label' => 'RED 12'],
                ['x' => 547, 'y' => 63, 'r' => 17, 'label' => 'RED 13'],
                ['x' => 611, 'y' => 63, 'r' => 17, 'label' => 'RED 14'],
                ['x' => 756, 'y' => 63, 'r' => 17, 'label' => 'RED 15'],
                ['x' => 817, 'y' => 64, 'r' => 17, 'label' => 'RED 16'],
                ['x' => 963, 'y' => 64, 'r' => 17, 'label' => 'RED 17'],
                ['x' => 1025, 'y' => 63, 'r' => 17, 'label' => 'RED 18'],
                ['x' => 1160, 'y' => 63, 'r' => 17, 'label' => 'RED 19'],
                ['x' => 1229, 'y' => 63, 'r' => 17, 'label' => 'RED 20'],
                ['x' => 1346, 'y' => 185, 'r' => 17, 'label' => 'RED 21'],
                ['x' => 1636, 'y' => 256, 'r' => 17, 'label' => 'RED 22', 'sentido' => 'inversa'],
            ],
        ];

        $loopAncha = fn ($x, $y, $w, $h) => 'M ' . ($x + 21) . ' ' . ($y + $h - 35) .
            ' L ' . ($x + 34) . ' ' . ($y + 25) .
            ' L ' . ($x + $w - 18) . ' ' . ($y + 25) .
            ' L ' . ($x + $w - 5) . ' ' . ($y + 44) .
            ' L ' . ($x + $w - 34) . ' ' . ($y + 174) .
            ' L ' . ($x + $w - 8) . ' ' . ($y + $h - 52) .
            ' L ' . ($x + $w - 30) . ' ' . ($y + $h - 25) .
            ' L ' . ($x + 56) . ' ' . ($y + $h - 25) .
            ' L ' . ($x + 24) . ' ' . ($y + $h - 58);

        $loopEstrecha = fn ($x, $y, $w, $h) => 'M ' . ($x + 17) . ' ' . ($y + $h - 38) .
            ' L ' . ($x + 34) . ' ' . ($y + 24) .
            ' L ' . ($x + $w - 17) . ' ' . ($y + 24) .
            ' L ' . ($x + $w - 5) . ' ' . ($y + 39) .
            ' L ' . ($x + $w - 24) . ' ' . ($y + $h - 54) .
            ' L ' . ($x + $w - 42) . ' ' . ($y + $h - 25) .
            ' L ' . ($x + 28) . ' ' . ($y + $h - 25) .
            ' L ' . ($x + 8) . ' ' . ($y + $h - 58);

        $cadenaPaths = [
            ['d' => 'M 46 293 L 67 58 Q 72 43 88 43 L 180 43 Q 190 45 190 58 L 166 266 Q 162 288 144 296 L 46 293'],
            ['d' => $loopAncha(190, 30, 128, 285)],
            ['d' => $loopEstrecha(318, 30, 72, 285)],
            ['d' => $loopAncha(390, 30, 142, 285)],
            ['d' => $loopEstrecha(532, 30, 60, 285)],
            ['d' => $loopAncha(592, 30, 140, 285)],
            ['d' => $loopEstrecha(732, 30, 70, 285)],
            ['d' => $loopAncha(802, 30, 140, 285)],
            ['d' => $loopEstrecha(942, 30, 60, 285)],
            ['d' => $loopAncha(1002, 30, 138, 285)],
            ['d' => $loopEstrecha(1140, 30, 65, 285)],
            ['d' => 'M 1228 285 L 1262 45 L 1340 41 Q 1352 43 1354 58 L 1320 181 L 1302 236 L 1330 279 L 1302 299 L 1265 299 L 1230 273'],
            ['d' => 'M 1356 171 L 1649 171 Q 1665 173 1665 190 L 1665 270 Q 1660 287 1643 293 L 1575 294 L 1512 306 L 68 306 L 38 299'],
        ];

        $flechas = [
            ['x1' => 320, 'y1' => 315, 'x2' => 350, 'y2' => 70],
            ['x1' => 1116, 'y1' => 306, 'x2' => 1005, 'y2' => 306],
            ['x1' => 1512, 'y1' => 56, 'x2' => 1454, 'y2' => 175],
        ];

        $textos = [
            ['class' => 'texto-espreado', 'x' => 1408, 'y' => 54, 'text' => 'Espreador'],
        ];

        $ondas = [
            ['x' => 11, 'y' => 297, 'w' => 55, 'h' => 18, 'path' => 'M 18 299 C 26 315, 33 315, 40 299 S 54 283, 62 299'],
        ];
    } elseif ($grupo === 'l04-l09') {
        $viewBox = '0 0 1502 374';
        $svgWidth = 1502;
        $svgHeight = 374;
        $baseWidth = 1502;
        $baseHeight = 374;
        $tituloGrupo = 'L-04 / L-09';
        $planoBorde = '34,35 204,35 214,46 1067,46 1077,35 1179,35 1179,183 1470,183 1470,360 34,360 34,35';

        $paneles = [
            ['type' => 'entrada', 'label' => 'RED 09', 'x' => 34, 'y' => 35, 'w' => 170, 'h' => 325, 'fill' => '#dedede', 'bottomLabel' => 'RED 1', 'bottomLabelX' => 72, 'bottomLabelY' => 350],
            ['type' => 'ancha', 'label' => 'RED 10', 'x' => 204, 'y' => 35, 'w' => 126, 'h' => 325],
            ['type' => 'estrecha', 'label' => 'RED 11', 'x' => 330, 'y' => 35, 'w' => 76, 'h' => 325],
            ['type' => 'ancha', 'label' => 'RED 12', 'x' => 406, 'y' => 35, 'w' => 146, 'h' => 325],
            ['type' => 'estrecha', 'label' => 'RED 13', 'x' => 552, 'y' => 35, 'w' => 76, 'h' => 325],
            ['type' => 'ancha', 'label' => 'RED 14', 'x' => 628, 'y' => 35, 'w' => 150, 'h' => 325],
            ['type' => 'estrecha', 'label' => 'RED 15', 'x' => 778, 'y' => 35, 'w' => 77, 'h' => 325],
            ['type' => 'ancha', 'label' => 'RED 16', 'x' => 855, 'y' => 35, 'w' => 140, 'h' => 325],
            ['type' => 'estrecha', 'label' => 'RED 17', 'x' => 995, 'y' => 35, 'w' => 72, 'h' => 325],
            ['type' => 'salida', 'label' => 'RED 18', 'x' => 1067, 'y' => 35, 'w' => 112, 'h' => 325, 'fill' => '#dedede'],
            ['type' => 'loca', 'label' => 'RED 19', 'labelX' => 1494, 'labelY' => 356, 'labelAnchor' => 'end', 'x' => 1150, 'y' => 183, 'w' => 320, 'h' => 177, 'fill' => '#dedede'],
        ];

        $catarinas = [
            ['x' => 96, 'y' => 66, 'r' => 18, 'label' => 'RED 09'],
            ['x' => 64, 'y' => 328, 'r' => 18, 'label' => 'RED 1', 'sentido' => 'inversa'],
            ['x' => 236, 'y' => 67, 'r' => 18, 'label' => 'RED 10'],
            ['x' => 366, 'y' => 67, 'r' => 18, 'label' => 'RED 11'],
            ['x' => 428, 'y' => 67, 'r' => 18, 'label' => 'RED 12'],
            ['x' => 590, 'y' => 67, 'r' => 18, 'label' => 'RED 13'],
            ['x' => 650, 'y' => 67, 'r' => 18, 'label' => 'RED 14'],
            ['x' => 806, 'y' => 67, 'r' => 18, 'label' => 'RED 15'],
            ['x' => 865, 'y' => 67, 'r' => 18, 'label' => 'RED 16'],
            ['x' => 1016, 'y' => 67, 'r' => 18, 'label' => 'RED 17'],
            ['x' => 1068, 'y' => 67, 'r' => 18, 'label' => 'RED 18'],
            ['x' => 1172, 'y' => 205, 'r' => 18, 'label' => 'LOCA'],
            ['x' => 1438, 'y' => 320, 'r' => 18, 'label' => 'RED 19', 'sentido' => 'inversa'],
        ];

        $catarinasExactasPorLinea = [
            'linea4' => [
                ['x' => 95, 'y' => 68, 'r' => 18, 'label' => 'RED 09'],
                ['x' => 63, 'y' => 324, 'r' => 18, 'label' => 'RED 1', 'sentido' => 'inversa'],
                ['x' => 236, 'y' => 67, 'r' => 18, 'label' => 'RED 10'],
                ['x' => 360, 'y' => 67, 'r' => 18, 'label' => 'RED 11'],
                ['x' => 420, 'y' => 68, 'r' => 18, 'label' => 'RED 12'],
                ['x' => 562, 'y' => 68, 'r' => 18, 'label' => 'RED 13'],
                ['x' => 621, 'y' => 69, 'r' => 18, 'label' => 'RED 14'],
                ['x' => 764, 'y' => 69, 'r' => 18, 'label' => 'RED 15'],
                ['x' => 825, 'y' => 68, 'r' => 18, 'label' => 'RED 16'],
                ['x' => 956, 'y' => 68, 'r' => 18, 'label' => 'RED 17'],
                ['x' => 1024, 'y' => 68, 'r' => 18, 'label' => 'RED 18'],
                ['x' => 1138, 'y' => 208, 'r' => 18, 'label' => 'LOCA'],
                ['x' => 1421, 'y' => 290, 'r' => 18, 'label' => 'RED 19', 'sentido' => 'inversa'],
            ],
            'linea9' => [
                ['x' => 91, 'y' => 70, 'r' => 18, 'label' => 'RED 09'],
                ['x' => 59, 'y' => 327, 'r' => 18, 'label' => 'RED 1', 'sentido' => 'inversa'],
                ['x' => 231, 'y' => 69, 'r' => 18, 'label' => 'RED 10'],
                ['x' => 356, 'y' => 69, 'r' => 18, 'label' => 'RED 11'],
                ['x' => 416, 'y' => 70, 'r' => 18, 'label' => 'RED 12'],
                ['x' => 559, 'y' => 70, 'r' => 18, 'label' => 'RED 13'],
                ['x' => 618, 'y' => 71, 'r' => 18, 'label' => 'RED 14'],
                ['x' => 762, 'y' => 71, 'r' => 18, 'label' => 'RED 15'],
                ['x' => 822, 'y' => 70, 'r' => 18, 'label' => 'RED 16'],
                ['x' => 954, 'y' => 70, 'r' => 18, 'label' => 'RED 17'],
                ['x' => 1022, 'y' => 70, 'r' => 18, 'label' => 'RED 18'],
                ['x' => 1137, 'y' => 211, 'r' => 18, 'label' => 'LOCA'],
                ['x' => 1420, 'y' => 293, 'r' => 18, 'label' => 'RED 19', 'sentido' => 'inversa'],
            ],
        ];

        $loopAncha = fn ($x, $y, $w, $h) => 'M ' . ($x + 22) . ' ' . ($y + $h - 38) .
            ' L ' . ($x + 36) . ' ' . ($y + 28) .
            ' L ' . ($x + $w - 18) . ' ' . ($y + 28) .
            ' L ' . ($x + $w - 4) . ' ' . ($y + 48) .
            ' L ' . ($x + $w - 34) . ' ' . ($y + 190) .
            ' L ' . ($x + $w - 8) . ' ' . ($y + $h - 58) .
            ' L ' . ($x + $w - 30) . ' ' . ($y + $h - 28) .
            ' L ' . ($x + 58) . ' ' . ($y + $h - 28) .
            ' L ' . ($x + 24) . ' ' . ($y + $h - 64);

        $loopEstrecha = fn ($x, $y, $w, $h) => 'M ' . ($x + 18) . ' ' . ($y + $h - 42) .
            ' L ' . ($x + 36) . ' ' . ($y + 27) .
            ' L ' . ($x + $w - 18) . ' ' . ($y + 27) .
            ' L ' . ($x + $w - 6) . ' ' . ($y + 42) .
            ' L ' . ($x + $w - 25) . ' ' . ($y + $h - 60) .
            ' L ' . ($x + $w - 44) . ' ' . ($y + $h - 28) .
            ' L ' . ($x + 30) . ' ' . ($y + $h - 28) .
            ' L ' . ($x + 8) . ' ' . ($y + $h - 64);

        $cadenaPaths = [
            ['d' => 'M 64 328 L 80 66 Q 84 53 100 52 L 192 52 Q 202 54 202 68 L 176 305 Q 172 324 150 331 L 64 328'],
            ['d' => $loopAncha(204, 35, 126, 325)],
            ['d' => $loopEstrecha(330, 35, 76, 325)],
            ['d' => $loopAncha(406, 35, 146, 325)],
            ['d' => $loopEstrecha(552, 35, 76, 325)],
            ['d' => $loopAncha(628, 35, 150, 325)],
            ['d' => $loopEstrecha(778, 35, 77, 325)],
            ['d' => $loopAncha(855, 35, 140, 325)],
            ['d' => $loopEstrecha(995, 35, 72, 325)],
            ['d' => 'M 1086 323 L 1136 58 L 1170 48 Q 1178 52 1178 68 L 1144 195 L 1122 252 L 1150 302 L 1122 324 L 1088 324 L 1068 302'],
            ['d' => 'M 1155 198 L 1451 198 Q 1468 200 1468 218 L 1468 302 Q 1462 317 1445 322 L 1376 324 L 1306 357 L 95 357 L 64 351 L 42 334'],
        ];

        $flechas = [
            ['x1' => 322, 'y1' => 360, 'x2' => 363, 'y2' => 82],
            ['x1' => 905, 'y1' => 356, 'x2' => 805, 'y2' => 356],
            ['x1' => 1298, 'y1' => 64, 'x2' => 1235, 'y2' => 200],
        ];

        $textos = [
            ['class' => 'texto-loca', 'x' => 1161, 'y' => 176, 'text' => 'LOCA'],
            ['class' => 'texto-espreado', 'x' => 1194, 'y' => 64, 'text' => 'Espreador'],
        ];

        $ondas = [
            ['x' => 1430, 'y' => 335, 'w' => 52, 'h' => 24, 'path' => 'M 1437 338 C 1445 358, 1454 358, 1462 338 S 1478 318, 1486 338'],
        ];
    }

    if ($chainMask) {
        $svgWidth = $chainMask['width'];
        $svgHeight = $chainMask['height'];
        $viewBox = '0 0 ' . $svgWidth . ' ' . $svgHeight;
        $shapePath = $chainMask['shape'];
        $centerPaths = $chainMask['center'];
        $chainTransform = $chainMask['transform'] ?? null;

        if ($lineaMaskKey) {
            $exactBaseImage = 'images/Diagramas-Lavadoras/' . $lineaMaskKey . '.png';
            $useExactBaseImage = is_file(public_path($exactBaseImage));
            $chainTransform = $useExactBaseImage ? null : $chainTransform;

            if ($useExactBaseImage && isset($catarinasExactasPorLinea[$lineaMaskKey])) {
                $catarinas = $catarinasExactasPorLinea[$lineaMaskKey];
            }
        }
    }

    $scaleX = $baseWidth > 0 ? $svgWidth / $baseWidth : 1;
    $scaleY = $baseHeight > 0 ? $svgHeight / $baseHeight : 1;
    $contentTransform = (abs($scaleX - 1) > 0.001 || abs($scaleY - 1) > 0.001)
        ? 'scale(' . round($scaleX, 6) . ' ' . round($scaleY, 6) . ')'
        : null;
@endphp

<section class="diagrama-lavadora is-embedded" data-diagrama-lavadora>
    <div class="diagrama-toolbar">
     
   
    </div>

    <div class="diagrama-svg-wrap">
        <svg class="diagrama-lavadora-svg {{ $isWide ? 'is-wide' : '' }}" viewBox="{{ $viewBox }}" role="img" aria-label="Diagrama de transmision de cadena {{ $lineaNombre ?? $tituloGrupo }}">
            <rect class="plano-fondo" x="0" y="0" width="{{ $svgWidth }}" height="{{ $svgHeight }}" />

            @if ($useExactBaseImage)
                <image
                    href="{{ asset($exactBaseImage) }}"
                    x="0"
                    y="0"
                    width="{{ $svgWidth }}"
                    height="{{ $svgHeight }}"
                    preserveAspectRatio="none"
                />
            @else
                <g @if($contentTransform) transform="{{ $contentTransform }}" @endif>
                    <polyline class="plano-borde" points="{{ $planoBorde }}" />

                    @foreach ($paneles as $red)
                        @include('lavadoras.diagramas.partials.red', ['red' => $red])
                    @endforeach

                    @foreach ($ondas as $onda)
                        <rect class="marca-onda" x="{{ $onda['x'] }}" y="{{ $onda['y'] }}" width="{{ $onda['w'] }}" height="{{ $onda['h'] }}" />
                        <path class="marca-onda-linea" d="{{ $onda['path'] }}" />
                    @endforeach

                </g>
            @endif

            @include('lavadoras.diagramas.partials.cadena', [
                'paths' => $cadenaPaths,
                'shapePath' => $shapePath,
                'centerPaths' => $centerPaths,
                'chainTransform' => $chainTransform,
                'patternId' => 'patron-cadena-' . ($lineaMaskKey ?? $grupo),
            ])

            @if ($useExactBaseImage)
                <g class="catarinas-exactas-overlay" @if($contentTransform) transform="{{ $contentTransform }}" @endif>
                    @foreach ($catarinas as $catarina)
                        @php
                            $catarinaAnimada = array_merge($catarina, [
                                'r' => ($catarina['r'] ?? 18) + 5,
                            ]);
                        @endphp
                        @include('lavadoras.diagramas.partials.catarina', ['catarina' => $catarinaAnimada])
                    @endforeach
                </g>
            @else
                <g @if($contentTransform) transform="{{ $contentTransform }}" @endif>
                    @foreach ($catarinas as $catarina)
                        @include('lavadoras.diagramas.partials.catarina', ['catarina' => $catarina])
                    @endforeach
                </g>
            @endif

            @unless ($useExactBaseImage)
                <g class="etiquetas-reductores-overlay etiquetas-reductores-superior" @if($contentTransform) transform="{{ $contentTransform }}" @endif aria-label="Nombres visibles de reductores">
                    @foreach ($paneles as $red)
                        @if ($red['bottomLabel'] ?? null)
                            <text
                                class="red-label red-label-bottom"
                                x="{{ $red['bottomLabelX'] ?? (($red['x'] ?? 0) + (($red['w'] ?? 0) / 2)) }}"
                                y="{{ $red['bottomLabelY'] ?? (($red['y'] ?? 0) + ($red['h'] ?? 0) - 12) }}"
                                text-anchor="middle"
                            >{{ $red['bottomLabel'] }}</text>
                        @endif
                    @endforeach

                    @foreach ($textos as $texto)
                        <text class="{{ $texto['class'] }}" x="{{ $texto['x'] }}" y="{{ $texto['y'] }}">{{ $texto['text'] }}</text>
                    @endforeach
                </g>
            @endunless

            @include('lavadoras.diagramas.partials.monitoreo-fallas', [
                'monitorAlertas' => $monitorAlertas,
                'catarinas' => $catarinas,
                'paneles' => $paneles,
                'baseWidth' => $baseWidth,
                'baseHeight' => $baseHeight,
                'contentTransform' => $contentTransform,
            ])
        </svg>
    </div>
</section>
