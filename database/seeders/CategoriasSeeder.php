<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriasSeeder extends Seeder
{
    public function run()
    {
        $categorias = [
            ['nombre' => 'Revisión General', 'descripcion' => 'Revisión general del componente'],
            ['nombre' => 'Cambio/Reemplazo', 'descripcion' => 'Cambio o reemplazo de componente'],
            ['nombre' => 'Mantenimiento Preventivo', 'descripcion' => 'Mantenimiento preventivo programado'],
            ['nombre' => 'Mantenimiento Correctivo', 'descripcion' => 'Mantenimiento correctivo por falla'],
            ['nombre' => 'Lubricación', 'descripcion' => 'Lubricación de componentes'],
            ['nombre' => 'Cambio de Aceite', 'descripcion' => 'Cambio de aceite de servos o reductores'],
            ['nombre' => 'Ajuste de Holguras', 'descripcion' => 'Ajuste de holguras y tolerancias'],
            ['nombre' => 'Verificación de Desgaste', 'descripcion' => 'Verificación de desgaste de componentes'],
            ['nombre' => 'Inspección Visual', 'descripcion' => 'Inspección visual de estado'],
            ['nombre' => 'Medición y Control', 'descripcion' => 'Medición y control de parámetros'],
            ['nombre' => 'Limpieza', 'descripcion' => 'Limpieza de componentes'],
            ['nombre' => 'Calibración', 'descripcion' => 'Calibración de componentes'],
        ];

        foreach ($categorias as $categoria) {
            DB::table('categorias')->insert([
                'nombre' => $categoria['nombre'],
                'descripcion' => $categoria['descripcion'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}