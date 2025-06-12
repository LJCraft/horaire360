<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['nom' => 'Administrateur', 'description' => 'Accès complet au système'],
            ['nom' => 'Employé', 'description' => 'Accès limité au dashboard employé'],
            ['nom' => 'Manager', 'description' => 'Gestion des plannings et validation des présences'],
        ];
        
        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}