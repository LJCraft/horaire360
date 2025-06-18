<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Employe;

class CleanupOrphanUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:cleanup-orphans {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphan users and resolve email conflicts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('🔍 Recherche des utilisateurs orphelins...');
        
        // Trouver les utilisateurs qui n'ont pas d'employé associé
        $orphanUsers = User::doesntHave('employe')->get();
        
        if ($orphanUsers->isEmpty()) {
            $this->info('✅ Aucun utilisateur orphelin trouvé.');
            return 0;
        }
        
        $this->info("📊 {$orphanUsers->count()} utilisateur(s) orphelin(s) trouvé(s):");
        
        foreach ($orphanUsers as $user) {
            $this->line("   - {$user->name} ({$user->email})");
            
            // Chercher un employé avec le même email
            $employe = Employe::where('email', $user->email)->whereNull('utilisateur_id')->first();
            
            if ($employe) {
                $this->info("   → Employé correspondant trouvé: {$employe->prenom} {$employe->nom}");
                
                if (!$isDryRun) {
                    $employe->update(['utilisateur_id' => $user->id]);
                    $this->info("   ✅ Association créée!");
                } else {
                    $this->info("   🔄 Serait associé (mode dry-run)");
                }
            } else {
                $this->warn("   ⚠️  Aucun employé correspondant trouvé");
                
                if ($this->confirm("Voulez-vous supprimer cet utilisateur orphelin?")) {
                    if (!$isDryRun) {
                        $user->delete();
                        $this->info("   🗑️  Utilisateur supprimé");
                    } else {
                        $this->info("   🗑️  Serait supprimé (mode dry-run)");
                    }
                }
            }
        }
        
        $this->info('🎉 Nettoyage terminé!');
        
        if ($isDryRun) {
            $this->warn('Mode dry-run activé - aucune modification effectuée.');
            $this->info('Exécutez sans --dry-run pour appliquer les changements.');
        }
        
        return 0;
    }
} 