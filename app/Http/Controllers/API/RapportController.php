<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employe;
use App\Models\Presence;
use App\Models\Poste;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class RapportController extends Controller
{
    /**
     * Récupère les données du rapport de ponctualité et d'assiduité via AJAX
     * pour mettre à jour les graphiques sans recharger toute la page
     */
    public function getRapportData(Request $request)
    {
        // Récupérer les paramètres de la requête
        $periode = $request->input('periode', 'semaine');
        $dateDebut = $request->input('date_debut', Carbon::now()->format('Y-m-d'));
        $departementId = $request->input('departement_id');
        $posteId = $request->input('poste_id');
        
        // Convertir la date de début en objet Carbon
        $dateDebutCarbon = Carbon::parse($dateDebut);
        
        // Déterminer la date de fin en fonction de la période
        $dateFin = $this->getDateFin($dateDebutCarbon, $periode);
        
        // Construire le libellé de la période
        $periodeLabel = $this->getPeriodeLabel($dateDebutCarbon, $dateFin, $periode);
        
        // Récupérer les employés concernés
        $employesQuery = Employe::query()
            ->with(['poste', 'presences' => function($query) use ($dateDebutCarbon, $dateFin) {
                $query->whereBetween('date', [$dateDebutCarbon->format('Y-m-d'), $dateFin->format('Y-m-d')]);
            }])
            ->where('statut', 'actif');
        
        // Filtrer par département si spécifié
        if ($departementId) {
            $employesQuery->whereHas('poste', function($query) use ($departementId) {
                $query->where('departement', $departementId);
            });
        }
        
        // Filtrer par poste si spécifié
        if ($posteId) {
            $employesQuery->where('poste_id', $posteId);
        }
        
        $employes = $employesQuery->get();
        
        // Préparer les données pour les graphiques
        $employesNoms = [];
        $tauxPonctualiteData = [];
        $tauxAssiduiteData = [];
        $tableData = [];
        
        foreach ($employes as $employe) {
            $employesNoms[] = $employe->nom . ' ' . $employe->prenom;
            
            // Calculer le taux de ponctualité
            $frequencePrevue = 0;
            $frequenceRealisee = 0;
            
            // Calculer le taux d'assiduité
            $heuresPrevues = 0;
            $heuresFaites = 0;
            
            foreach ($employe->presences as $presence) {
                // Pour chaque présence, incrémenter les compteurs
                $frequencePrevue++;
                if (!$presence->retard) {
                    $frequenceRealisee++;
                }
                
                // Calculer les heures prévues et faites
                $heureDebutPrevue = Carbon::parse($presence->heure_debut_prevue);
                $heureFinPrevue = Carbon::parse($presence->heure_fin_prevue);
                $heuresPrevuesJour = $heureDebutPrevue->diffInHours($heureFinPrevue);
                $heuresPrevues += $heuresPrevuesJour;
                
                if ($presence->heure_debut_reelle && $presence->heure_fin_reelle) {
                    $heureDebutReelle = Carbon::parse($presence->heure_debut_reelle);
                    $heureFinReelle = Carbon::parse($presence->heure_fin_reelle);
                    $heuresFaitesJour = $heureDebutReelle->diffInHours($heureFinReelle);
                    $heuresFaites += $heuresFaitesJour;
                }
            }
            
            // Calculer les taux en pourcentage
            $tauxPonctualite = $frequencePrevue > 0 ? round(($frequenceRealisee / $frequencePrevue) * 100) : 0;
            $tauxAssiduite = $heuresPrevues > 0 ? round(($heuresFaites / $heuresPrevues) * 100) : 0;
            
            $tauxPonctualiteData[] = $tauxPonctualite;
            $tauxAssiduiteData[] = $tauxAssiduite;
            
            // Ajouter les données au tableau
            $tableData[] = [
                'employe' => $employe->nom . ' ' . $employe->prenom,
                'poste' => $employe->poste ? $employe->poste->nom : 'Non assigné',
                'frequence_prevue' => $frequencePrevue,
                'frequence_realisee' => $frequenceRealisee,
                'taux_ponctualite' => $tauxPonctualite . '%',
                'heures_prevues' => $heuresPrevues,
                'heures_faites' => $heuresFaites,
                'taux_assiduite' => $tauxAssiduite . '%'
            ];
        }
        
        // Retourner les données au format JSON
        return response()->json([
            'employes' => $employesNoms,
            'tauxPonctualite' => $tauxPonctualiteData,
            'tauxAssiduite' => $tauxAssiduiteData,
            'periodeLabel' => $periodeLabel,
            'tableData' => $tableData
        ]);
    }
    
    /**
     * Détermine la date de fin en fonction de la période
     */
    private function getDateFin(Carbon $dateDebut, $periode)
    {
        switch ($periode) {
            case 'jour':
                return $dateDebut->copy()->endOfDay();
            case 'semaine':
                return $dateDebut->copy()->addDays(6)->endOfDay();
            case 'mois':
                return $dateDebut->copy()->endOfMonth();
            case 'annee':
                return $dateDebut->copy()->endOfYear();
            default:
                return $dateDebut->copy()->addDays(6)->endOfDay();
        }
    }
    
    /**
     * Construit le libellé de la période
     */
    private function getPeriodeLabel(Carbon $dateDebut, Carbon $dateFin, $periode)
    {
        switch ($periode) {
            case 'jour':
                return $dateDebut->format('d/m/Y');
            case 'semaine':
                return 'Du ' . $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y');
            case 'mois':
                return $dateDebut->format('F Y');
            case 'annee':
                return $dateDebut->format('Y');
            default:
                return 'Du ' . $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y');
        }
    }
}
