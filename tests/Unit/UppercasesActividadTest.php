<?php

namespace Tests\Unit;

use App\Models\Analisis;
use App\Models\AnalisisEtiquetadora;
use App\Models\AnalisisLavadora;
use App\Models\AnalisisPasteurizadora;
use App\Models\PlanAccion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UppercasesActividadTest extends TestCase
{
    #[DataProvider('actividadModels')]
    public function test_actividad_is_normalized_to_uppercase_on_assignment(string $modelClass): void
    {
        $model = new $modelClass();

        $model->actividad = 'reparar guía numero ñ1';

        $this->assertSame('REPARAR GUÍA NUMERO Ñ1', $model->getAttributes()['actividad']);
    }

    public static function actividadModels(): array
    {
        return [
            'analisis' => [Analisis::class],
            'analisis etiquetadora' => [AnalisisEtiquetadora::class],
            'analisis lavadora' => [AnalisisLavadora::class],
            'analisis pasteurizadora' => [AnalisisPasteurizadora::class],
            'plan accion' => [PlanAccion::class],
        ];
    }
}
