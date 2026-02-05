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
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | LÍNEAS
        |--------------------------------------------------------------------------
        */
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
            Linea::firstOrCreate(
                ['nombre' => $linea['nombre']],
                [
                    'descripcion' => $linea['descripcion'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | COMPONENTES DE LAVADORAS
        |--------------------------------------------------------------------------
        */
        $this->call(LavadorasSeeder::class);
        /*
        |--------------------------------------------------------------------------
        | USUARIO ADMIN
        |--------------------------------------------------------------------------
        */
        User::firstOrCreate(
            ['email' => 'admin@legadoabfenix.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'cedula' => '0000000000',
                'telefono' => '5551234567',
                'puesto' => 'Administrador del Sistema',
                'activo' => true,
            ]
        );
        
        // --- Seeder de Lavadoras ---
        $this->call(LavadorasSeeder::class);
    }
}