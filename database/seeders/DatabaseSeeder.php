<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Linea;
use App\Models\Componente;
use Spatie\Permission\Models\Role;


// database/seeders/DatabaseSeeder.php
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Crear 14 líneas
        $lineas = [];
        for ($i = 1; $i <= 14; $i++) {
            $lineas[] = Linea::create([
                'nombre' => 'L-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'descripcion' => 'Línea de envasado ' . $i,
                'activa' => true
            ]);
        }
        
        // Componentes iniciales
        $componentes = [
            ['nombre' => 'Reductores Chicos', 'codigo' => 'RED_CHICO', 'cantidad_total' => 21],
            ['nombre' => 'Reductores Grandes', 'codigo' => 'RED_GRANDE', 'cantidad_total' => 16],
            ['nombre' => 'Bujes de Baquelita y Espiga', 'codigo' => 'BUJES_ESPIGA', 'cantidad_total' => 14],
            ['nombre' => 'Guías Superiores', 'codigo' => 'GUIAS_SUP', 'cantidad_total' => 32],
            ['nombre' => 'Guías Inferiores', 'codigo' => 'GUIAS_INF', 'cantidad_total' => 36],
            ['nombre' => 'Guías de Retorno', 'codigo' => 'GUIAS_RET', 'cantidad_total' => 2],
            ['nombre' => 'Catarinas', 'codigo' => 'CATARINAS', 'cantidad_total' => 44],
        ];
        
        foreach ($componentes as $componente) {
            Componente::create($componente);
        }
        
        // Crear roles de usuario
        $roles = ['admin', 'ingeniero_mantenimiento', 'supervisor', 'tecnico'];
        foreach ($roles as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
