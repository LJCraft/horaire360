<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Exécuter les seeders
        $this->call([
            RolesSeeder::class,
            PostesSeeder::class,
        ]);
        
        // Créer un admin par défaut
        User::create([
            'name' => 'Admin',
            'email' => 'admin@horaire360.com',
            'password' => Hash::make('password'),
            'role_id' => 1, // Administrateur
        ]);
    }
}