<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TechnicianSeeder extends Seeder
{
    public function run(): void
    {
        $technicians = [
            [
                'name' => 'Jean Dupont',
                'email' => 'jean.dupont@support.com',
                'password' => 'password123',
                'role' => 'technician',
            ],
            [
                'name' => 'Marie Martin',
                'email' => 'marie.martin@support.com',
                'password' => 'password123',
                'role' => 'technician',
            ],
            [
                'name' => 'Pierre Durand',
                'email' => 'pierre.durand@support.com',
                'password' => 'password123',
                'role' => 'technician',
            ],
        ];

        foreach ($technicians as $technician) {
            User::create($technician);
        }
    }
}