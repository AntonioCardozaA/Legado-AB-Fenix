<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TecnicoUserSeeder extends Seeder
{
    public function run(): void
    {
        $tecnicos = [
            [
                'name' => 'Tecnico Prueba',
                'email' => 'tecnico@legadoabfenix.com',
                'password' => 'tecnico123',
                'cedula' => '90000001',
                'telefono' => '5550000001',
                'puesto' => 'Tecnico de Mantenimiento',
            ],
            [
                'name' => 'Tecnico Lavadora',
                'email' => 'tecnico.lavadora@legadoabfenix.com',
                'password' => 'tecnico123',
                'cedula' => '90000002',
                'telefono' => '5550000002',
                'puesto' => 'Tecnico Operativo Lavadora',
            ],
            [
                'name' => 'Tecnico Pasteurizadora',
                'email' => 'tecnico.pasteurizadora@legadoabfenix.com',
                'password' => 'tecnico123',
                'cedula' => '90000003',
                'telefono' => '5550000003',
                'puesto' => 'Tecnico Operativo Pasteurizadora',
            ],
            [
                'name' => 'Jorge Trejo Ramirez',
                'email' => 'jorgetr@legadoabfenix.com',
                'password' => 'jtr2026',
                'cedula' => '90000003',
                'telefono' => '5550000003',
                'puesto' => 'Tecnico PCM',
            ],
        ];

        foreach ($tecnicos as $tecnico) {
            $user = User::updateOrCreate(
                ['email' => $tecnico['email']],
                [
                    'name' => $tecnico['name'],
                    'password' => Hash::make($tecnico['password']),
                    'cedula' => $tecnico['cedula'],
                    'telefono' => $tecnico['telefono'],
                    'puesto' => $tecnico['puesto'],
                    'activo' => true,
                ]
            );

            $user->syncRoles([User::ROLE_TECNICO]);
        }
    }
}
