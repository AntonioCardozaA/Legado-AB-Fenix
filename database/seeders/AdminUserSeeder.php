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
                'rol' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'Daniel Castañeda',
                'email' => 'castañeda@legadoabfenix.com',
                'password' => 'password123',
                'cedula' => '12345678',
                'telefono' => '5551234567',
                'puesto' => 'Gerente de Mantenimiento',
                'activo' => true,
                'rol' => User::ROLE_GERENTE_MANTENIMIENTO,
            ],
            [
                'name' => 'Juan Alberto Júarez',
                'email' => 'jajuarez@legadoabfenix.com',
                'password' => 'password123',
                'cedula' => '87654321',
                'telefono' => '5557654321',
                'puesto' => 'Supervisor de Mantenimiento',
                'activo' => true,
                'rol' => User::ROLE_SUPERVISOR,
            ],
            [
                'name' => 'Carlos López',
                'email' => 'clopez@legadoabfenix.com',
                'password' => 'password123',
                'cedula' => '11223344',
                'telefono' => '5559988776',
                'puesto' => 'Técnico de Mantenimiento',
                'activo' => true,
                'rol' => User::ROLE_TECNICO,
            ],
        ];

        foreach ($usuarios as $u) {
            // Crea o actualiza usuario segun cedula o email.
            $user = User::where('cedula', $u['cedula'])
                ->orWhere('email', $u['email'])
                ->first();

            if (!$user) {
                $user = new User();
            }

            $user->fill([
                'name' => $u['name'],
                'email' => $u['email'],
                'password' => Hash::make($u['password']),
                'cedula' => $u['cedula'],
                'telefono' => $u['telefono'],
                'puesto' => $u['puesto'],
                'activo' => $u['activo'],
            ]);

            $user->save();

            // Asignar rol sin duplicar
            $user->syncRoles([$u['rol']]);
        }
    }
}
