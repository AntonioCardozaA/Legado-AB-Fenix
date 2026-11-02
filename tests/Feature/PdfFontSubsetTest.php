<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class PdfFontSubsetTest extends TestCase
{
    public function test_subset_fonts_preserve_unicode_text_page_geometry_and_drawing_commands(): void
    {
        $this->assertTrue(config('dompdf.options.enable_font_subsetting'));
        $root = sys_get_temp_dir() . '/legado-pdf-font-test-' . bin2hex(random_bytes(5));
        File::ensureDirectoryExists($root);
        config(['dompdf.options.font_dir' => $root, 'dompdf.options.font_cache' => $root, 'dompdf.options.temp_dir' => $root, 'dompdf.options.log_output_file' => null]);
        $html = '<html><head><meta charset="UTF-8"><style>body{font-family:"DejaVu Sans";font-size:12px}h1{font-weight:bold}</style></head><body><h1>Legado AB Fénix</h1><p>Revisión: ÁÉÍÓÚ Ü Ñ ñ</p><p>Refacción Ø 25 mm ± 0,5 €</p><div style="page-break-before:always">Segunda página: línea L-04</div></body></html>';
        $results = [];
        foreach ([false, true] as $subset) {
            config(['dompdf.options.enable_font_subsetting' => $subset]);
            $wrapper = app('dompdf.wrapper');
            $this->assertSame($subset, $wrapper->getDomPDF()->getOptions()->getIsFontSubsettingEnabled());
            $pdf = $wrapper->loadHTML($html)->setPaper('a4')->output();
            $text = (new Parser)->parseContent($pdf)->getText();
            $this->assertStringContainsString('Revisión: ÁÉÍÓÚ Ü Ñ ñ', $text);
            $this->assertStringContainsString('Refacción Ø 25 mm ± 0,5 €', $text);
            $this->assertStringContainsString('Segunda página: línea L-04', $text);
            $canvas = $wrapper->getDomPDF()->getCanvas();
            $this->assertSame(2, $canvas->get_page_count());
            $objects = (new \ReflectionProperty($canvas->get_cpdf(), 'objects'))->getValue($canvas->get_cpdf());
            $commands = [];
            foreach ($objects as $object) {
                if (($object['t'] ?? null) !== 'page') continue;
                foreach ($object['info']['contents'] ?? [] as $id) $commands[] = $objects[$id]['c'] ?? '';
            }
            $this->assertNotEmpty($commands);
            $results[] = ['text' => $text, 'size' => strlen($pdf), 'width' => $canvas->get_width(), 'height' => $canvas->get_height(), 'commands' => $commands];
        }
        $this->assertSame($results[0]['text'], $results[1]['text']);
        $this->assertSame($results[0]['commands'], $results[1]['commands']);
        $this->assertSame([$results[0]['width'], $results[0]['height']], [$results[1]['width'], $results[1]['height']]);
        $this->assertLessThan($results[0]['size'], $results[1]['size']);
    }
}
