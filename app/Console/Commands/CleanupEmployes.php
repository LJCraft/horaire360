<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Employe;

class CleanupEmployes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-employes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoyer les employés qui doivent être supprimés définitivement';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Début du nettoyage des employés...');
        
        try {
            // Nettoyer les employes 
            $count = $this->cleanupEmployes();
            
            $this->info("Nettoyage terminé avec succès ! $count employés ont été traités.");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Une erreur est survenue : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Nettoie les employés qui doivent être supprimés définitivement
     */
    private function cleanupEmployes()
    {
        $employes = Employe::all();
        $count = 0;
        
        foreach ($employes as $employe) {
            // Supprimer toutes les présences liées
            DB::statement('DELETE FROM presences WHERE employe_id = ?', [$employe->id]);
            
            // Supprimer tous les plannings liés
            DB::statement('DELETE FROM plannings WHERE employe_id = ?', [$employe->id]);
            
            // Supprimer l'utilisateur associé si existant
            if ($employe->utilisateur_id) {
                DB::statement('DELETE FROM users WHERE id = ?', [$employe->utilisateur_id]);
            }
            
            // Supprimer l'employé lui-même
            DB::statement('DELETE FROM employes WHERE id = ?', [$employe->id]);
            
            $count++;
            
            $this->info("Employé #$employe->id ($employe->prenom $employe->nom) supprimé avec succès");
        }
        
        return $count;
    }
}
