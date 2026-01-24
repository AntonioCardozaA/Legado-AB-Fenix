<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComponentesSeeder extends Seeder
{
    public function run()
    {
        $componentes = [
            // Reductores (RED 9 a RED 20)
            ['nombre' => 'Reductor RED-1', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-1', 'descripcion' => 'Reductor número 1 - LAV L-06'],
            ['nombre' => 'Reductor RED-9', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-9', 'descripcion' => 'Reductor número 9 - LAV L-06'],
            ['nombre' => 'Reductor RED-10', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-10', 'descripcion' => 'Reductor número 10 - LAV L-06'],
            ['nombre' => 'Reductor RED-11', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-11', 'descripcion' => 'Reductor número 11 - LAV L-06'],
            ['nombre' => 'Reductor RED-12', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-12', 'descripcion' => 'Reductor número 12 - LAV L-06'],
            ['nombre' => 'Reductor RED-13', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-13', 'descripcion' => 'Reductor número 13 - LAV L-06'],
            ['nombre' => 'Reductor RED-14', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-14', 'descripcion' => 'Reductor número 14 - LAV L-06'],
            ['nombre' => 'Reductor RED-15', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-15', 'descripcion' => 'Reductor número 15 - LAV L-06'],
            ['nombre' => 'Reductor RED-16', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-16', 'descripcion' => 'Reductor número 16 - LAV L-06'],
            ['nombre' => 'Reductor RED-17', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-17', 'descripcion' => 'Reductor número 17 - LAV L-06'],
            ['nombre' => 'Reductor RED-18', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-18', 'descripcion' => 'Reductor número 18 - LAV L-06'],
            ['nombre' => 'Reductor RED-19', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-19', 'descripcion' => 'Reductor número 19 - LAV L-06'],
            ['nombre' => 'Reductor RED-20', 'tipo' => 'REDUCTOR', 'codigo' => 'RED-20', 'descripcion' => 'Reductor número 20 - LAV L-06'],

            // Servos
            ['nombre' => 'Servo Chico', 'tipo' => 'SERVO', 'codigo' => 'SERVO-CHICO', 'descripcion' => 'Servo chico para mantenimiento'],
            ['nombre' => 'Servo Grande', 'tipo' => 'SERVO', 'codigo' => 'SERVO-GRANDE', 'descripcion' => 'Servo grande para cambio de aceite'],

            // Bue de Baquelita
            ['nombre' => 'Bue de Baquelita', 'tipo' => 'BUE_BAQUELITA', 'codigo' => 'BUE-BAQ', 'descripcion' => 'Bue de baquelita y espiga de flecha'],

            // Espiga de Flecha
            ['nombre' => 'Espiga de Flecha', 'tipo' => 'ESPIGA_FLECHA', 'codigo' => 'ESPIGA-FLECHA', 'descripcion' => 'Espiga de flecha para revisión'],

            // Guías
            ['nombre' => 'Guía Tanque Superior', 'tipo' => 'GUIA', 'codigo' => 'GUIA-SUP', 'descripcion' => 'Guía de tanque superior'],
            ['nombre' => 'Guía Tanque Inferior', 'tipo' => 'GUIA', 'codigo' => 'GUIA-INF', 'descripcion' => 'Guía de tanque inferior'],

            // Catarinas
            ['nombre' => 'Catarina', 'tipo' => 'CATARINA', 'codigo' => 'CATARINA', 'descripcion' => 'Catarina del sistema de transmisión'],

            // Cadenas
            ['nombre' => 'Cadena 173mm', 'tipo' => 'CADENA', 'codigo' => 'CADENA-173', 'descripcion' => 'Cadena paso 173mm, revisión de holgura'],
            ['nombre' => 'Cadena (otro paso)', 'tipo' => 'CADENA', 'codigo' => 'CADENA-OTRO', 'descripcion' => 'Cadena de otro paso diferente'],

            // Rodajas
            ['nombre' => 'Rodaja', 'tipo' => 'RODAJA', 'codigo' => 'RODAJA', 'descripcion' => 'Rodaja del sistema, revisión de juego'],

            // Tanques
            ['nombre' => 'Tanque Superior', 'tipo' => 'TANQUE', 'codigo' => 'TANQUE-SUP', 'descripcion' => 'Tanque superior de la lavadora'],
            ['nombre' => 'Tanque Inferior', 'tipo' => 'TANQUE', 'codigo' => 'TANQUE-INF', 'descripcion' => 'Tanque inferior de la lavadora'],

            // Componentes generales de mantenimiento
            ['nombre' => 'Sistema de Transmisión', 'tipo' => 'SISTEMA', 'codigo' => 'SIS-TRANS', 'descripcion' => 'Sistema completo de transmisión'],
            ['nombre' => 'Sistema Hidráulico', 'tipo' => 'SISTEMA', 'codigo' => 'SIS-HIDRA', 'descripcion' => 'Sistema hidráulico completo'],
            ['nombre' => 'Sistema Eléctrico', 'tipo' => 'SISTEMA', 'codigo' => 'SIS-ELEC', 'descripcion' => 'Sistema eléctrico de control'],
            ['nombre' => 'Estructura Soporte', 'tipo' => 'ESTRUCTURA', 'codigo' => 'EST-SOPORTE', 'descripcion' => 'Estructura de soporte y fijación'],
        ];

        foreach ($componentes as $componente) {
            DB::table('componentes')->insert([
                'nombre' => $componente['nombre'],
                'tipo' => $componente['tipo'],
                'codigo' => $componente['codigo'],
                'descripcion' => $componente['descripcion'],
                'lavadora' => 'L-06', // Especificar que son para LAV L-06
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}