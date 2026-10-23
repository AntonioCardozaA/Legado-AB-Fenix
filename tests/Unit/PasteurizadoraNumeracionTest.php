<?php

namespace Tests\Unit;

use App\Support\PasteurizadoraNumeracion;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PasteurizadoraNumeracionTest extends TestCase
{
    public static function equipos(): iterable
    {
        foreach (['P-03', 'P-05', 'P-08', 'P-09', 'P-10', 'P-12', 'P-13', 'P-14'] as $linea) {
            yield $linea => [$linea, 9, ['ANILLAS' => 3, 'EXCENTRICOS' => 2, 'PISTAS' => 2, 'PLACAS_PERNO' => 3]];
        }
        yield 'P-04' => ['P-04', 12, ['ANILLAS' => 3, 'EXCENTRICOS' => 2, 'PISTAS' => 2, 'PLACAS_PERNO' => 3]];
        foreach (['P-06', 'P-07', 'P-11'] as $linea) {
            yield $linea => [$linea, 16, ['ANILLAS' => 5, 'EXCENTRICOS' => 2, 'PISTAS' => 4, 'PLACAS_PERNO' => 5, 'RODAJAS' => 2]];
        }
    }

    #[DataProvider('equipos')]
    public function test_secuencias_independientes_sin_huecos_ni_duplicados(string $linea, int $modulos, array $cantidades): void
    {
        foreach ($cantidades as $componente => $cantidad) {
            $secuencia = [];
            for ($modulo = 1; $modulo <= $modulos; $modulo++) {
                $numeros = PasteurizadoraNumeracion::numeros($linea, $componente, $modulo);
                $this->assertCount($cantidad, $numeros);
                $this->assertSame(($modulo - 1) * $cantidad + 1, $numeros[0]);
                $this->assertSame($modulo * $cantidad, $numeros[$cantidad - 1]);
                $secuencia = array_merge($secuencia, $numeros);
            }
            $this->assertSame(range(1, $modulos * $cantidad), $secuencia);
            $this->assertSame($secuencia, array_values(array_unique($secuencia)));
        }
    }

    public function test_no_hay_componentes_para_equipos_tipos_o_modulos_invalidos(): void
    {
        $this->assertSame([], PasteurizadoraNumeracion::numeros('P-99', 'ANILLAS', 1));
        $this->assertSame([], PasteurizadoraNumeracion::numeros('P-03', 'RODAJAS', 1));
        $this->assertSame([], PasteurizadoraNumeracion::numeros('P-03', 'ANILLAS', 0));
        $this->assertSame([], PasteurizadoraNumeracion::numeros('P-03', 'ANILLAS', 10));
    }

    public function test_rechaza_una_seleccion_mixta_sin_aceptarla_parcialmente(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PasteurizadoraNumeracion::validarSeleccion([6, 1], 'P-06', 'ANILLAS', 2);
    }
}
