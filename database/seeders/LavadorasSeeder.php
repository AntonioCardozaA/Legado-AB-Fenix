<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Componente;
use App\Models\Linea;
use App\Support\LavadoraCatalog;
use Illuminate\Support\Str;

class LavadorasSeeder extends Seeder
{
    public function run(): void
    {
        /* 
        |--------------------------------------------------------------------------
        | COMPONENTES DE LAVADORAS - POR LÍNEA ESPECÍFICA
        |--------------------------------------------------------------------------
        */

        // Línea 4 - Lavadoras
        $linea4 = Linea::where('nombre', 'L-04')->first();
        if ($linea4) {
            $this->seedLinea4($linea4);
        }

        // Línea 5 - Lavadoras
        $linea5 = Linea::where('nombre', 'L-05')->first();
        if ($linea5) {
            $this->seedLinea5($linea5);
        }

        // Línea 6 - Lavadoras
        $linea6 = Linea::where('nombre', 'L-06')->first();
        if ($linea6) {
            $this->seedLinea6($linea6);
        }

        // Línea 7 - Lavadoras
        $linea7 = Linea::where('nombre', 'L-07')->first();
        if ($linea7) {
            $this->seedLinea7($linea7);
        }

        // Línea 8 - Lavadoras
        $linea8 = Linea::where('nombre', 'L-08')->first();
        if ($linea8) {
            $this->seedLinea8($linea8);
        }

        // Línea 9 - Lavadoras
        $linea9 = Linea::where('nombre', 'L-09')->first();
        if ($linea9) {
            $this->seedLinea9($linea9);
        }

        // Línea 12 - Lavadoras
        $linea12 = Linea::where('nombre', 'L-12')->first();
        if ($linea12) {
            $this->seedLinea12($linea12);
        }

        // Línea 13 - Lavadoras
        $linea13 = Linea::where('nombre', 'L-13')->first();
        if ($linea13) {
            $this->seedLinea13($linea13);
        }
    }

    /**
     * Crear componente con validación
     */
    private function createComponente(array $params): void
    {
        $codigo = $params['codigo'];
        
        // Limitar longitud del código si es necesario
        if (strlen($codigo) > 100) {
            $codigo = substr($codigo, 0, 97) . '...';
        }

        Componente::firstOrCreate(
            ['codigo' => $codigo],
            [
                'nombre' => $params['nombre'],
                'reductor' => $params['reductor'],
                'ubicacion' => $params['ubicacion'],
                'linea' => $params['linea'],
                'cantidad_total' => $params['cantidad_total'],
                'activo' => $params['activo'],
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Generar código para componente
     */
    private function generateCodigo(string $prefijo, string $reductor, string $sufijo): string
    {
        return $prefijo . '_' . Str::slug($reductor, '_') . '_' . $sufijo;
    }

    /**
     * Determinar el tipo de componente basado en el sufijo del código
     */
    private function getNombreComponente(string $sufijo): string
    {
        return LavadoraCatalog::nombreComponente($sufijo);
    }

    /**
     * Seed para Línea 4
     */
    private function seedLinea4(Linea $linea): void
    {
        $reductoresLinea4 = LavadoraCatalog::reductoresPorLinea('L-04');

        foreach ($reductoresLinea4 as $reductor) {
            // Servo chico
            $this->createComponente([
                'codigo' => $this->generateCodigo('L04', $reductor, 'SERVO_CHICO'),
                'nombre' => 'Servo Chico', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Servo grande
            $this->createComponente([
                'codigo' => $this->generateCodigo('L04', $reductor, 'SERVO_GRANDE'),
                'nombre' => 'Servo Grande', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Buje de baquelita-espiga de flecha
            $this->createComponente([
                'codigo' => $this->generateCodigo('L04', $reductor, 'BUJE_ESPIGA'),
                'nombre' => 'Buje Baquelita-Espiga', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (tanques inferior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L04', $reductor, 'GUI_INF_TANQUE'),
                'nombre' => 'Guía Inf Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (lado vapor, lado pasillo)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L04', $reductor, 'GUI_INT_TANQUE'),
                'nombre' => 'Guía Intermedia', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 2,
                'activo' => true,
            ]);

            // Guía superior (tanque superior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L04', $reductor, 'GUI_SUP_TANQUE'),
                'nombre' => 'Guía Sup Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Catarinas
            $this->createComponente([
                'codigo' => $this->generateCodigo('L04', $reductor, 'CATARINAS'),
                'nombre' => 'Catarinas', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);
        }
    }

    /**
     * Seed para Línea 5
     */
    private function seedLinea5(Linea $linea): void
    {
        $reductoresLinea5 = LavadoraCatalog::reductoresPorLinea('L-05');

        foreach ($reductoresLinea5 as $reductor) {
            // RV250 Sin Fin Corona
            $this->createComponente([
                'codigo' => $this->generateCodigo('L05', $reductor, 'RV200'),
                'nombre' => 'RV250 Sin Fin Corona',
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Buje de baquelita-espiga de flecha
            $this->createComponente([
                'codigo' => $this->generateCodigo('L05', $reductor, 'BUJE_ESPIGA'),
                'nombre' => 'Buje Baquelita-Espiga', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (tanques inferior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L05', $reductor, 'GUI_INF_TANQUE'),
                'nombre' => 'Guía Inf Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (lado vapor, lado pasillo)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L05', $reductor, 'GUI_INT_TANQUE'),
                'nombre' => 'Guía Intermedia', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 2,
                'activo' => true,
            ]);

            // Guía superior (tanque superior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L05', $reductor, 'GUI_SUP_TANQUE'),
                'nombre' => 'Guía Sup Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Catarinas
            $this->createComponente([
                'codigo' => $this->generateCodigo('L05', $reductor, 'CATARINAS'),
                'nombre' => 'Catarinas', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);
        }
    }

    /**
     * Seed para Línea 6
     */
    private function seedLinea6(Linea $linea): void
    {
        $reductoresLinea6 = LavadoraCatalog::reductoresPorLinea('L-06');

        foreach ($reductoresLinea6 as $reductor) {
            // Servo chico
            $this->createComponente([
                'codigo' => $this->generateCodigo('L06', $reductor, 'SERVO_CHICO'),
                'nombre' => 'Servo Chico', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Servo grande
            $this->createComponente([
                'codigo' => $this->generateCodigo('L06', $reductor, 'SERVO_GRANDE'),
                'nombre' => 'Servo Grande', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Buje de baquelita-espiga de flecha
            $this->createComponente([
                'codigo' => $this->generateCodigo('L06', $reductor, 'BUJE_ESPIGA'),
                'nombre' => 'Buje Baquelita-Espiga', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (tanques inferior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L06', $reductor, 'GUI_INF_TANQUE'),
                'nombre' => 'Guía Inf Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (lado vapor, lado pasillo)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L06', $reductor, 'GUI_INT_TANQUE'),
                'nombre' => 'Guía Intermedia', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 2,
                'activo' => true,
            ]);

            // Guía superior (tanque superior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L06', $reductor, 'GUI_SUP_TANQUE'),
                'nombre' => 'Guía Sup Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Catarinas
            $this->createComponente([
                'codigo' => $this->generateCodigo('L06', $reductor, 'CATARINAS'),
                'nombre' => 'Catarinas', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);
        }
    }

    /**
     * Seed para Línea 7
     */
    private function seedLinea7(Linea $linea): void
    {
        $reductoresLinea7 = LavadoraCatalog::reductoresPorLinea('L-07');

        foreach ($reductoresLinea7 as $reductor) {
            // Servo chico
            $this->createComponente([
                'codigo' => $this->generateCodigo('L07', $reductor, 'SERVO_CHICO'),
                'nombre' => 'Servo Chico', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Servo grande
            $this->createComponente([
                'codigo' => $this->generateCodigo('L07', $reductor, 'SERVO_GRANDE'),
                'nombre' => 'Servo Grande', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Buje de baquelita-espiga de flecha
            $this->createComponente([
                'codigo' => $this->generateCodigo('L07', $reductor, 'BUJE_ESPIGA'),
                'nombre' => 'Buje Baquelita-Espiga', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (tanques inferior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L07', $reductor, 'GUI_INF_TANQUE'),
                'nombre' => 'Guía Inf Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (lado vapor, lado pasillo)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L07', $reductor, 'GUI_INT_TANQUE'),
                'nombre' => 'Guía Intermedia', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 2,
                'activo' => true,
            ]);

            // Guía superior (tanque superior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L07', $reductor, 'GUI_SUP_TANQUE'),
                'nombre' => 'Guía Sup Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Catarinas
            $this->createComponente([
                'codigo' => $this->generateCodigo('L07', $reductor, 'CATARINAS'),
                'nombre' => 'Catarinas', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);
        }
    }

    /**
     * Seed para Línea 8
     */
    private function seedLinea8(Linea $linea): void
    {
        $reductoresLinea8 = LavadoraCatalog::reductoresPorLinea('L-08');

        foreach ($reductoresLinea8 as $reductor) {
            // Servo chico
            $this->createComponente([
                'codigo' => $this->generateCodigo('L08', $reductor, 'SERVO_CHICO'),
                'nombre' => 'Servo Chico', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Servo grande
            $this->createComponente([
                'codigo' => $this->generateCodigo('L08', $reductor, 'SERVO_GRANDE'),
                'nombre' => 'Servo Grande', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Buje de baquelita-espiga de flecha
            $this->createComponente([
                'codigo' => $this->generateCodigo('L08', $reductor, 'BUJE_ESPIGA'),
                'nombre' => 'Buje Baquelita-Espiga', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (tanques inferior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L08', $reductor, 'GUI_INF_TANQUE'),
                'nombre' => 'Guía Inf Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (lado vapor, lado pasillo)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L08', $reductor, 'GUI_INT_TANQUE'),
                'nombre' => 'Guía Intermedia', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 2,
                'activo' => true,
            ]);

            // Guía superior (tanque superior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L08', $reductor, 'GUI_SUP_TANQUE'),
                'nombre' => 'Guía Sup Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Catarinas
            $this->createComponente([
                'codigo' => $this->generateCodigo('L08', $reductor, 'CATARINAS'),
                'nombre' => 'Catarinas', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);
        }
    }

    /**
     * Seed para Línea 9
     */
    private function seedLinea9(Linea $linea): void
    {
        $reductoresLinea9 = LavadoraCatalog::reductoresPorLinea('L-09');

        foreach ($reductoresLinea9 as $reductor) {
            // Servo chico
            $this->createComponente([
                'codigo' => $this->generateCodigo('L09', $reductor, 'SERVO_CHICO'),
                'nombre' => 'Servo Chico', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Servo grande
            $this->createComponente([
                'codigo' => $this->generateCodigo('L09', $reductor, 'SERVO_GRANDE'),
                'nombre' => 'Servo Grande', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Buje de baquelita-espiga de flecha
            $this->createComponente([
                'codigo' => $this->generateCodigo('L09', $reductor, 'BUJE_ESPIGA'),
                'nombre' => 'Buje Baquelita-Espiga', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (tanques inferior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L09', $reductor, 'GUI_INF_TANQUE'),
                'nombre' => 'Guía Inf Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (lado vapor, lado pasillo)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L09', $reductor, 'GUI_INT_TANQUE'),
                'nombre' => 'Guía Intermedia', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 2,
                'activo' => true,
            ]);

            // Guía superior (tanque superior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L09', $reductor, 'GUI_SUP_TANQUE'),
                'nombre' => 'Guía Sup Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Catarinas
            $this->createComponente([
                'codigo' => $this->generateCodigo('L09', $reductor, 'CATARINAS'),
                'nombre' => 'Catarinas', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);
        }
    }

    /**
     * Seed para Línea 12
     */
    private function seedLinea12(Linea $linea): void
    {
        $reductoresLinea12 = LavadoraCatalog::reductoresPorLinea('L-12');

        foreach ($reductoresLinea12 as $reductor) {
            // RV250 Sin Fin Corona
            $this->createComponente([
                'codigo' => $this->generateCodigo('L12', $reductor, 'RV200_SIN_FIN'),
                'nombre' => 'RV250 Sin Fin Corona',
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Buje de baquelita-espiga de flecha
            $this->createComponente([
                'codigo' => $this->generateCodigo('L12', $reductor, 'BUJE_ESPIGA'),
                'nombre' => 'Buje Baquelita-Espiga', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (tanques inferior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L12', $reductor, 'GUI_INF_TANQUE'),
                'nombre' => 'Guía Inf Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (lado vapor, lado pasillo)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L12', $reductor, 'GUI_INT_TANQUE'),
                'nombre' => 'Guía Intermedia', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 2,
                'activo' => true,
            ]);

            // Guía superior (tanque superior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L12', $reductor, 'GUI_SUP_TANQUE'),
                'nombre' => 'Guía Sup Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Catarinas
            $this->createComponente([
                'codigo' => $this->generateCodigo('L12', $reductor, 'CATARINAS'),
                'nombre' => 'Catarinas', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);
        }
    }

    /**
     * Seed para Línea 13
     */
    private function seedLinea13(Linea $linea): void
    {
        $reductoresLinea13 = LavadoraCatalog::reductoresPorLinea('L-13');

        foreach ($reductoresLinea13 as $reductor) {
            // RV250 Sin Fin Corona
            $this->createComponente([
                'codigo' => $this->generateCodigo('L13', $reductor, 'RV200'),
                'nombre' => 'RV250 Sin Fin Corona',
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Buje de baquelita-espiga de flecha
            $this->createComponente([
                'codigo' => $this->generateCodigo('L13', $reductor, 'BUJE_ESPIGA'),
                'nombre' => 'Buje Baquelita-Espiga', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (tanques inferior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L13', $reductor, 'GUI_INF_TANQUE'),
                'nombre' => 'Guía Inf Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Guía inferior (lado vapor, lado pasillo)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L13', $reductor, 'GUI_INT_TANQUE'),
                'nombre' => 'Guía Intermedia', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 2,
                'activo' => true,
            ]);

            // Guía superior (tanque superior)
            $this->createComponente([
                'codigo' => $this->generateCodigo('L13', $reductor, 'GUI_SUP_TANQUE'),
                'nombre' => 'Guía Sup Tanque', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);

            // Catarinas
            $this->createComponente([
                'codigo' => $this->generateCodigo('L13', $reductor, 'CATARINAS'),
                'nombre' => 'Catarinas', // SIN reductor
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'activo' => true,
            ]);
        }
    }
}
