<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $usuarios = [
            [
                'name' => 'Administrador',
                'email' => 'admin@legadoabfenix.com',
                'password' => 'admin123',
                'cedula' => '00000000',
                'telefono' => '0000000000',
                'puesto' => 'Administrador del Sistema',
                'activo' => true,
                'rol' => 'admin',
            ],
            [
                'name' => 'Juan Pérez',
                'email' => 'jperez@legadoabfenix.com',
                'password' => 'password123',
                'cedula' => '12345678',
                'telefono' => '5551234567',
                'puesto' => 'Ingeniero en Mantenimiento',
                'activo' => true,
                'rol' => 'ingeniero_mantenimiento',
            ],
            [
                'name' => 'María García',
                'email' => 'mgarcia@legadoabfenix.com',
                'password' => 'password123',
                'cedula' => '87654321',
                'telefono' => '5557654321',
                'puesto' => 'Supervisor de Mantenimiento',
                'activo' => true,
                'rol' => 'supervisor',
            ],
            [
                'name' => 'Carlos López',
                'email' => 'clopez@legadoabfenix.com',
                'password' => 'password123',
                'cedula' => '11223344',
                'telefono' => '5559988776',
                'puesto' => 'Técnico de Mantenimiento',
                'activo' => true,
                'rol' => 'tecnico',
            ],
        ];

        foreach ($usuarios as $u) {
            // Crea o actualiza usuario según email
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make($u['password']),
                    'cedula' => $u['cedula'],
                    'telefono' => $u['telefono'],
                    'puesto' => $u['puesto'],
                    'activo' => $u['activo'],
                ]
            );

            // Asignar rol sin duplicar
            $user->syncRoles([$u['rol']]);
        }
    }
}
