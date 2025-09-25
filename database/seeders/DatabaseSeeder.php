<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Admin Sistema
        User::create([
            'name' => 'Admin Sistema',
            'email' => 'admin@bsdrive.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin_sistema',
        ]);

        // Admin Empresa
        User::create([
            'name' => 'Admin Empresa',
            'email' => 'empresa@bsdrive.com',
            'password' => Hash::make('empresa123'),
            'role' => 'admin_empresa',
        ]);

        // Usuário Comum
        User::create([
            'name' => 'Usuário Comum',
            'email' => 'usuario@bsdrive.com',
            'password' => Hash::make('usuario123'),
            'role' => 'usuario',
        ]);
    }
}
