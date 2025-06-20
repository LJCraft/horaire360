<?php

namespace App\Services\CriteriaEngine\Validators;

use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Services\CriteriaEngine\ValidationResult;
use Carbon\Carbon;

/**
 * Validateur de ponctualité
 * Calcule les retards et départs anticipés selon le planning et les tolérances
 */
class PunctualityValidator extends BaseValidator
{
    public function getName(): string
    {
        return 'Ponctualité';
    }

    public function getDescription(): string
    {
        return 'Calcule les retards et départs anticipés selon le planning et les tolérances définies';
    }

    public function getPriority(): int
    {
        return 6; // Priorité moyenne - nécessite planning
    }

    public function canApplyWithoutPlanning(): bool
    {
        return false; // Nécessite absolument un planning
    }

    public function appliesTo(CriterePointage $criteria): bool
    {
        // S'applique si des tolérances sont définies
        return !is_null($criteria->tolerance_avant) || !is_null($criteria->tolerance_apres);
    }

    public function getRequiredCriteriaFields(): array
    {
        return ['tolerance_avant', 'tolerance_apres'];
    }

    public function getCalculatedFields(): array
    {
        return ['retard', 'depart_anticipe'];
    }

    public function validate(Presence $pointage, CriterePointage $criteria, ?Planning $planning = null): ValidationResult
    {
        // Vérifier les données de base
        $baseValidation = $this->validatePointageData($pointage);
        if (!$baseValidation->success) {
            return $baseValidation;
        }

        // Vérifier la présence du planning
        if (!$planning) {
            return ValidationResult::pendingPlanning('Planning requis pour calculer la ponctualité');
        }

        // Vérifier si c'est un jour de repos
        if ($this->isJourRepos($planning, $pointage)) {
            return ValidationResult::success('Jour de repos - ponctualité non applicable', [
                'jour_repos' => true,
                'retard' => false,
                'depart_anticipe' => false
            ]);
        }

        // Obtenir le détail du planning
        $planningDetail = $this->getPlanningDetail($planning, $pointage);
        if (!$planningDetail) {
            return ValidationResult::failure('Détail de planning non trouvé pour ce jour');
        }

        $data = [];
        $warnings = [];

        // Calculer le retard si heure d'arrivée présente
        if ($pointage->heure_arrivee) {
            $retardResult = $this->calculateRetard($pointage, $planningDetail, $criteria);
            $data = array_merge($data, $retardResult['data']);
            $warnings = array_merge($warnings, $retardResult['warnings']);
        }

        // Calculer le départ anticipé si heure de départ présente
        if ($pointage->heure_depart && $criteria->nombre_pointages == 2) {
            $departResult = $this->calculateDepartAnticipe($pointage, $planningDetail, $criteria);
            $data = array_merge($data, $departResult['data']);
            $warnings = array_merge($warnings, $departResult['warnings']);
        }

        $result = ValidationResult::success('Ponctualité calculée', $data);
        foreach ($warnings as $warning) {
            $result->addWarning($warning);
        }

        return $result;
    }

    /**
     * Calculer le retard
     */
    private function calculateRetard(Presence $pointage, $planningDetail, CriterePointage $criteria): array
    {
        $heureArrivee = Carbon::parse($pointage->heure_arrivee);
        $heureDebutPlanning = Carbon::parse($planningDetail->heure_debut);
        $toleranceAvant = $criteria->tolerance_avant ?? 0;
        $toleranceApres = $criteria->tolerance_apres ?? 0;

        $data = [];
        $warnings = [];

        // Calculer la différence en minutes
        $differenceMinutes = $heureDebutPlanning->diffInMinutes($heureArrivee, false);

        if ($criteria->nombre_pointages == 1) {
            // Pour un seul pointage, vérifier la plage complète
            $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);
            $debutPlage = (clone $heureDebutPlanning)->subMinutes($toleranceAvant);
            $finPlage = (clone $heureFinPlanning)->addMinutes($toleranceApres);

            $retard = !($heureArrivee->gte($debutPlage) && $heureArrivee->lte($finPlage));
            
            $data['retard'] = $retard;
            $data['retard_minutes'] = $retard ? max(0, $differenceMinutes) : 0;
            $data['plage_autorisee'] = "{$debutPlage->format('H:i')} - {$finPlage->format('H:i')}";
            
        } else {
            // Pour deux pointages, vérifier uniquement l'arrivée
            $debutPlage = (clone $heureDebutPlanning)->subMinutes($toleranceAvant);
            $finPlage = (clone $heureDebutPlanning)->addMinutes($toleranceApres);

            $retard = !($heureArrivee->gte($debutPlage) && $heureArrivee->lte($finPlage));
            
            $data['retard'] = $retard;
            $data['retard_minutes'] = $retard ? max(0, $differenceMinutes) : 0;
            $data['plage_arrivee_autorisee'] = "{$debutPlage->format('H:i')} - {$finPlage->format('H:i')}";
        }

        // Ajouter des avertissements pour les cas limites
        if ($data['retard'] && $data['retard_minutes'] > 60) {
            $warnings[] = "Retard important détecté: {$data['retard_minutes']} minutes";
        }

        if (!$data['retard'] && $differenceMinutes < -$toleranceAvant) {
            $warnings[] = "Arrivée très en avance: " . abs($differenceMinutes) . " minutes";
        }

        return ['data' => $data, 'warnings' => $warnings];
    }

    /**
     * Calculer le départ anticipé
     */
    private function calculateDepartAnticipe(Presence $pointage, $planningDetail, CriterePointage $criteria): array
    {
        $heureDepart = Carbon::parse($pointage->heure_depart);
        $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);
        $toleranceAvant = $criteria->tolerance_avant ?? 0;
        $toleranceApres = $criteria->tolerance_apres ?? 0;

        $data = [];
        $warnings = [];

        // Calculer la différence en minutes (négatif si départ anticipé)
        $differenceMinutes = $heureFinPlanning->diffInMinutes($heureDepart, false);

        $debutPlage = (clone $heureFinPlanning)->subMinutes($toleranceAvant);
        $finPlage = (clone $heureFinPlanning)->addMinutes($toleranceApres);

        $departAnticipe = !($heureDepart->gte($debutPlage) && $heureDepart->lte($finPlage));

        $data['depart_anticipe'] = $departAnticipe;
        $data['depart_anticipe_minutes'] = $departAnticipe && $differenceMinutes > 0 ? $differenceMinutes : 0;
        $data['plage_depart_autorisee'] = "{$debutPlage->format('H:i')} - {$finPlage->format('H:i')}";

        // Avertissements
        if ($data['depart_anticipe'] && $data['depart_anticipe_minutes'] > 60) {
            $warnings[] = "Départ très anticipé détecté: {$data['depart_anticipe_minutes']} minutes";
        }

        if (!$data['depart_anticipe'] && $differenceMinutes < -$toleranceApres) {
            $warnings[] = "Départ très tardif: " . abs($differenceMinutes) . " minutes";
        }

        return ['data' => $data, 'warnings' => $warnings];
    }
} 