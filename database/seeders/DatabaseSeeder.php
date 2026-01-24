<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Linea;
use App\Models\Componente;
use App\Models\Categoria;
use App\Models\NumeroR;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Crear líneas (lavadoras)
        $lineas = [
            ['nombre' => 'L-01', 'descripcion' => 'Línea de envasado 1'],
            ['nombre' => 'L-02', 'descripcion' => 'Línea de envasado 2'],
            ['nombre' => 'L-03', 'descripcion' => 'Línea de envasado 3'],
            ['nombre' => 'L-04', 'descripcion' => 'Línea de envasado 4'],
            ['nombre' => 'L-05', 'descripcion' => 'Línea de envasado 5'],
            ['nombre' => 'L-06', 'descripcion' => 'Línea de envasado 6'],
            ['nombre' => 'L-07', 'descripcion' => 'Línea de envasado 7'],
            ['nombre' => 'L-08', 'descripcion' => 'Línea de envasado 8'],
            ['nombre' => 'L-09', 'descripcion' => 'Línea de envasado 9'],
            ['nombre' => 'L-10', 'descripcion' => 'Línea de envasado 10'],
            ['nombre' => 'L-11', 'descripcion' => 'Línea de envasado 11'],
            ['nombre' => 'L-12', 'descripcion' => 'Línea de envasado 12'],
            ['nombre' => 'L-13', 'descripcion' => 'Línea de envasado 13'],
            ['nombre' => 'L-14', 'descripcion' => 'Línea de envasado 14'],
        ];

        foreach ($lineas as $linea) {
            Linea::create(array_merge($linea, [
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Crear componentes con reductores
        $componentes = [
            [
                'nombre' => 'Reductores Chicos',
                'codigo' => 'RED CHICO',
                'reductor' => 'R1,R2,R3,R4,R5',
                'ubicacion' => 'Eje Principal',
                'cantidad_total' => 21,
                'activo' => true,
            ],
            [
                'nombre' => 'Reductores Grandes',
                'codigo' => 'RED GRANDE',
                'reductor' => 'R6,R7,R8,R9,R10',
                'ubicacion' => 'Base Inferior',
                'cantidad_total' => 16,
                'activo' => true,
            ],
            [
                'nombre' => 'Bujes de Baquelita y Espiga',
                'codigo' => 'BUJES ESPIGA',
                'reductor' => 'R11,R12,R13,R14',
                'ubicacion' => 'Unión Lateral',
                'cantidad_total' => 14,
                'activo' => true,
            ],
            [
                'nombre' => 'Guías Superiores',
                'codigo' => 'GUIAS SUP',
                'reductor' => 'R15,R16,R17',
                'ubicacion' => 'Marco Superior',
                'cantidad_total' => 32,
                'activo' => true,
            ],
            [
                'nombre' => 'Guías Inferiores',
                'codigo' => 'GUIAS INF',
                'reductor' => 'R18,R19,R20',
                'ubicacion' => 'Base de Soporte',
                'cantidad_total' => 36,
                'activo' => true,
            ],
        ];

        foreach ($componentes as $componente) {
            Componente::create(array_merge($componente, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Crear categorías
        $categorias = [
            ['nombre' => 'Mecánico', 'descripcion' => 'Componentes mecánicos'],
            ['nombre' => 'Eléctrico', 'descripcion' => 'Componentes eléctricos'],
            ['nombre' => 'Hidráulico', 'descripcion' => 'Sistemas hidráulicos'],
            ['nombre' => 'Neumático', 'descripcion' => 'Sistemas neumáticos'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create(array_merge($categoria, [
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Crear números R para cada categoría
        $numerosR = [
            // Para categoría Mecánico (id 1)
            ['categoria_id' => 1, 'codigo' => 'R-MEC-01', 'descripcion' => 'Revisión mensual mecánica'],
            ['categoria_id' => 1, 'codigo' => 'R-MEC-02', 'descripcion' => 'Revisión trimestral mecánica'],
            ['categoria_id' => 1, 'codigo' => 'R-MEC-03', 'descripcion' => 'Revisión anual mecánica'],
            
            // Para categoría Eléctrico (id 2)
            ['categoria_id' => 2, 'codigo' => 'R-ELE-01', 'descripcion' => 'Revisión mensual eléctrica'],
            ['categoria_id' => 2, 'codigo' => 'R-ELE-02', 'descripcion' => 'Revisión trimestral eléctrica'],
            
            // Para categoría Hidráulico (id 3)
            ['categoria_id' => 3, 'codigo' => 'R-HID-01', 'descripcion' => 'Revisión mensual hidráulica'],
            ['categoria_id' => 3, 'codigo' => 'R-HID-02', 'descripcion' => 'Revisión trimestral hidráulica'],
            
            // Para categoría Neumático (id 4)
            ['categoria_id' => 4, 'codigo' => 'R-NEU-01', 'descripcion' => 'Revisión mensual neumática'],
        ];

        foreach ($numerosR as $numeroR) {
            NumeroR::create(array_merge($numeroR, [
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Crear usuario de prueba
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@legadoabfenix.com',
            'password' => Hash::make('password'),
            'cedula' => '0000000000',
            'telefono' => '5551234567',
            'puesto' => 'Administrador del Sistema',
            'activo' => true,
        ]);
    }
}