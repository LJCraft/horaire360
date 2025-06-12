<?php

namespace Database\Seeders;

use App\Models\Poste;
use Illuminate\Database\Seeder;

class PostesSeeder extends Seeder
{
    public function run()
    {
        $postes = [
            ['nom' => 'Développeur', 'description' => 'Développement logiciel', 'departement' => 'IT'],
            ['nom' => 'Designer', 'description' => 'Conception graphique', 'departement' => 'Marketing'],
            ['nom' => 'Comptable', 'description' => 'Gestion financière', 'departement' => 'Finance'],
            ['nom' => 'Responsable RH', 'description' => 'Gestion des ressources humaines', 'departement' => 'RH'],
            ['nom' => 'Chef de projet', 'description' => 'Gestion de projets', 'departement' => 'Production'],
        ];
        
        foreach ($postes as $poste) {
            Poste::create($poste);
        }
    }
}