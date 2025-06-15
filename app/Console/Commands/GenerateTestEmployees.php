<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employe;
use App\Models\Poste;
use Illuminate\Support\Facades\DB;

class GenerateTestEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-test-employees {count=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère un nombre spécifié d\'employés de test pour les tests de performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = $this->argument('count');
        $this->info("Génération de {$count} employés de test...");
        
        // Récupérer les postes existants ou en créer si nécessaire
        $postes = Poste::all();
        if ($postes->isEmpty()) {
            $this->info("Création des postes de test...");
            $departements = [
                'Ressources Humaines', 'Informatique', 'Commercial', 
                'Comptabilité', 'Production', 'Direction', 'Marketing',
                'Logistique', 'Juridique', 'Recherche et Développement'
            ];
            
            foreach ($departements as $departement) {
                Poste::create([
                    'nom' => "Responsable {$departement}",
                    'departement' => $departement
                ]);
                
                Poste::create([
                    'nom' => "Assistant {$departement}",
                    'departement' => $departement
                ]);
            }
            
            $postes = Poste::all();
        }
        
        // Noms et prénoms réalistes
        $noms = ['Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau', 
                'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent', 'Fournier',
                'Morel', 'Girard', 'Andre', 'Lefevre', 'Mercier', 'Bonnet', 'Francois', 'Martinez', 'Legrand', 'Garnier',
                'Faure', 'Rousseau', 'Blanc', 'Guerin', 'Muller', 'Henry', 'Roussel', 'Nicolas', 'Perrin', 'Morin'];
        
        $prenoms = ['Jean', 'Pierre', 'Michel', 'André', 'Philippe', 'René', 'Louis', 'Alain', 'Jacques', 'Bernard',
                   'Marie', 'Isabelle', 'Françoise', 'Catherine', 'Anne', 'Monique', 'Sylvie', 'Nathalie', 'Nicole', 'Sophie',
                   'Daniel', 'Paul', 'Robert', 'Richard', 'Henri', 'Christian', 'Joseph', 'Thomas', 'Antoine', 'Georges',
                   'Jeanne', 'Marguerite', 'Hélène', 'Suzanne', 'Madeleine', 'Louise', 'Julie', 'Émilie', 'Claire', 'Alice'];
        
        // Utiliser des transactions pour améliorer les performances
        DB::beginTransaction();
        
        try {
            $bar = $this->output->createProgressBar($count);
            $bar->start();
            
            $batchSize = 100; // Traiter par lots de 100 pour économiser la mémoire
            
            for ($i = 0; $i < $count; $i++) {
                $nom = $noms[array_rand($noms)];
                $prenom = $prenoms[array_rand($prenoms)];
                $poste = $postes->random();
                
                Employe::create([
                    'matricule' => 'EMP' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => strtolower($prenom . '.' . $nom . ($i + 1) . '@entreprise.com'),
                    'telephone' => '06' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                    'date_naissance' => now()->subYears(rand(25, 60))->subDays(rand(0, 365)),
                    'date_embauche' => now()->subYears(rand(0, 15))->subMonths(rand(0, 11)),
                    'poste_id' => $poste->id,
                    'statut' => rand(1, 10) == 1 ? 'inactif' : 'actif', // 10% inactifs
                ]);
                
                $bar->advance();
                
                // Commit par lots pour éviter de surcharger la mémoire
                if (($i + 1) % $batchSize === 0) {
                    DB::commit();
                    DB::beginTransaction();
                }
            }
            
            DB::commit();
            $bar->finish();
            
            $this->newLine();
            $this->info("{$count} employés de test ont été générés avec succès!");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Erreur lors de la génération des employés: " . $e->getMessage());
        }
    }
} 