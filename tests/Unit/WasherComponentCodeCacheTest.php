<?php

namespace Tests\Unit;

use App\Models\AnalisisLavadora;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class WasherComponentCodeCacheTest extends TestCase
{
    public function test_bounded_cache_preserves_the_original_mapping_including_eviction_and_numeric_keys(): void
    {
        $original = new ReflectionMethod(AnalisisLavadora::class, 'calcularCodigoBaseComponente');
        $codes = [null, '', '0', '123', ' GUIA_INFERIOR ', 'l04-guia-inferior', 'GUI_INT_TAQNQUE', 'RV200_SIN_FIN', 'SIN_FIN_CORONA_RV200', 'L05_SERVO_CHICO', 'BUE_BAQ', 'OTRO codigo'];
        for ($i = 0; $i < 2100; $i++) $codes[] = 'CACHE_FIXTURE_' . $i;
        $expected = $actual = [];
        foreach (array_merge($codes, array_reverse($codes)) as $code) {
            $expected[] = $original->invoke(null, $code);
            $actual[] = AnalisisLavadora::codigoBaseComponente($code);
        }
        $this->assertSame($expected, $actual);
        $cache = (new ReflectionMethod(AnalisisLavadora::class, 'codigoBaseComponente'))->getStaticVariables()['cache'];
        $this->assertLessThanOrEqual(2048, count($cache));
        $this->assertSame(AnalisisLavadora::codigoBaseComponente(' GUIA_INFERIOR '), AnalisisLavadora::codigoBaseComponente('guia_inferior'));
    }
}
