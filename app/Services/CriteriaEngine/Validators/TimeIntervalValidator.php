<?php

namespace App\Services\CriteriaEngine\Validators;

use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Services\CriteriaEngine\ValidationResult;
use Carbon\Carbon;

/**
 * Validateur d'intervalle de temps
 * Vérifie l'intervalle minimum entre les pointages et détecte les anomalies temporelles
 */
class TimeIntervalValidator extends BaseValidator
{
    public function getName(): string
    {
        return 'Intervalle de temps';
    }

    public function getDescription(): string
    {
        return 'Vérifie les intervalles de temps entre pointages et détecte les anomalies';
    }

    public function getPriority(): int
    {
        return 5; // Priorité moyenne
    }

    public function canApplyWithoutPlanning(): bool
    {
        return true; // Peut fonctionner sans planning
    }

    public function appliesTo(CriterePointage $criteria): bool
    {
        // S'applique toujours pour vérifier les intervalles
        return true;
    }

    public function validate(Presence $pointage, CriterePointage $criteria, ?Planning $planning = null): ValidationResult
    {
        // Vérifier les données de base
        $baseValidation = $this->validatePointageData($pointage);
        if (!$baseValidation->success) {
            return $baseValidation;
        }

        $data = [];
        $warnings = [];

        // Vérifier l'intervalle minimum entre arrivée et départ
        if ($pointage->heure_arrivee && $pointage->heure_depart) {
            $intervalResult = $this->validateArriveeDepartInterval($pointage);
            $data = array_merge($data, $intervalResult['data']);
            $warnings = array_merge($warnings, $intervalResult['warnings']);
        }

        // Vérifier les intervalles avec les pointages précédents/suivants
        $intervalHistoryResult = $this->validateHistoricalIntervals($pointage);
        $data = array_merge($data, $intervalHistoryResult['data']);
        $warnings = array_merge($warnings, $intervalHistoryResult['warnings']);

        // Vérifier les plages horaires autorisées si définies
        if ($planning) {
            $plageResult = $this->validatePlageHoraire($pointage, $planning);
            $data = array_merge($data, $plageResult['data']);
            $warnings = array_merge($warnings, $plageResult['warnings']);
        }

        $result = ValidationResult::success('Validation des intervalles terminée', $data);
        foreach ($warnings as $warning) {
            $result->addWarning($warning);
        }

        return $result;
    }

    /**
     * Valider l'intervalle entre arrivée et départ
     */
    private function validateArriveeDepartInterval(Presence $pointage): array
    {
        $data = [];
        $warnings = [];

        try {
            $arrivee = Carbon::parse($pointage->heure_arrivee);
            $depart = Carbon::parse($pointage->heure_depart);

            // Calculer l'intervalle
            $intervalMinutes = $arrivee->diffInMinutes($depart);
            
            // Gérer le cas du travail de nuit
            if ($depart->lt($arrivee)) {
                $intervalMinutes = $arrivee->diffInMinutes($depart->addDay());
                $data['travail_nuit'] = true;
            }

            $data['intervalle_arrivee_depart_minutes'] = $intervalMinutes;
            $data['intervalle_arrivee_depart_heures'] = round($intervalMinutes / 60, 2);

            // Vérifications des intervalles anormaux
            if ($intervalMinutes < 15) {
                $warnings[] = "Intervalle très court entre arrivée et départ: {$intervalMinutes} minutes";
                $data['intervalle_suspect'] = true;
            } elseif ($intervalMinutes < 60) {
                $warnings[] = "Intervalle court entre arrivée et départ: {$intervalMinutes} minutes";
            }

            if ($intervalMinutes > 12 * 60) { // Plus de 12h
                $warnings[] = "Intervalle très long: " . round($intervalMinutes / 60, 1) . " heures";
                $data['intervalle_long'] = true;
            }

            // Détecter les pointages trop rapprochés (possible erreur)
            if ($intervalMinutes < 5) {
                $warnings[] = "Pointages très rapprochés - possible erreur de saisie";
                $data['possible_erreur'] = true;
            }

        } catch (\Exception $e) {
            $warnings[] = "Erreur lors du calcul de l'intervalle: " . $e->getMessage();
        }

        return ['data' => $data, 'warnings' => $warnings];
    }

    /**
     * Valider les intervalles avec l'historique des pointages
     */
    private function validateHistoricalIntervals(Presence $pointage): array
    {
        $data = [];
        $warnings = [];

        // Rechercher les pointages récents du même employé
        $pointagesRecents = Presence::where('employe_id', $pointage->employe_id)
            ->where('id', '!=', $pointage->id)
            ->whereBetween('date', [
                Carbon::parse($pointage->date)->subDays(1),
                Carbon::parse($pointage->date)->addDays(1)
            ])
            ->orderBy('date')
            ->orderBy('heure_arrivee')
            ->get();

        if ($pointagesRecents->isEmpty()) {
            $data['pointages_recents_found'] = false;
            return ['data' => $data, 'warnings' => $warnings];
        }

        $data['pointages_recents_found'] = true;
        $data['pointages_recents_count'] = $pointagesRecents->count();

        // Analyser les intervalles avec les pointages adjacents
        foreach ($pointagesRecents as $pointageRecent) {
            $this->analyzeIntervalWithPointage($pointage, $pointageRecent, $data, $warnings);
        }

        // Détecter les pointages multiples le même jour
        $pointagesMemeJour = $pointagesRecents->where('date', $pointage->date);
        if ($pointagesMemeJour->count() > 0) {
            $warnings[] = "Pointages multiples détectés le même jour (" . ($pointagesMemeJour->count() + 1) . " total)";
            $data['pointages_multiples_jour'] = true;
        }

        return ['data' => $data, 'warnings' => $warnings];
    }

    /**
     * Analyser l'intervalle avec un pointage spécifique
     */
    private function analyzeIntervalWithPointage(Presence $pointage, Presence $pointageRecent, array &$data, array &$warnings): void
    {
        try {
            // Comparer avec l'arrivée si disponible
            if ($pointage->heure_arrivee && $pointageRecent->heure_depart) {
                $arriveeActuelle = Carbon::parse($pointage->date . ' ' . $pointage->heure_arrivee);
                $departPrecedent = Carbon::parse($pointageRecent->date . ' ' . $pointageRecent->heure_depart);

                $intervalMinutes = $departPrecedent->diffInMinutes($arriveeActuelle);

                // Intervalle trop court entre départ précédent et arrivée actuelle
                if ($intervalMinutes < 480) { // Moins de 8h de repos
                    $warnings[] = "Temps de repos court: " . round($intervalMinutes / 60, 1) . "h entre départ précédent et arrivée";
                    $data['repos_insuffisant'] = true;
                }
            }

            // Détecter les chevauchements
            if ($pointage->heure_arrivee && $pointage->heure_depart && 
                $pointageRecent->heure_arrivee && $pointageRecent->heure_depart &&
                $pointage->date == $pointageRecent->date) {
                
                $this->detectOverlap($pointage, $pointageRecent, $data, $warnings);
            }

        } catch (\Exception $e) {
            $warnings[] = "Erreur lors de l'analyse des intervalles: " . $e->getMessage();
        }
    }

    /**
     * Détecter les chevauchements entre pointages
     */
    private function detectOverlap(Presence $pointage1, Presence $pointage2, array &$data, array &$warnings): void
    {
        $debut1 = Carbon::parse($pointage1->heure_arrivee);
        $fin1 = Carbon::parse($pointage1->heure_depart);
        $debut2 = Carbon::parse($pointage2->heure_arrivee);
        $fin2 = Carbon::parse($pointage2->heure_depart);

        // Vérifier le chevauchement
        if ($debut1->lt($fin2) && $debut2->lt($fin1)) {
            $warnings[] = "Chevauchement détecté avec un autre pointage du même jour";
            $data['chevauchement_detecte'] = true;
        }
    }

    /**
     * Valider la plage horaire selon le planning
     */
    private function validatePlageHoraire(Presence $pointage, Planning $planning): array
    {
        $data = [];
        $warnings = [];

        $planningDetail = $this->getPlanningDetail($planning, $pointage);
        if (!$planningDetail || $planningDetail->jour_repos) {
            return ['data' => $data, 'warnings' => $warnings];
        }

        try {
            $heureDebutPlanning = Carbon::parse($planningDetail->heure_debut);
            $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);

            // Vérifier l'arrivée dans la plage étendue
            if ($pointage->heure_arrivee) {
                $heureArrivee = Carbon::parse($pointage->heure_arrivee);
                $this->validateHeureInPlage($heureArrivee, $heureDebutPlanning, $heureFinPlanning, 'arrivée', $data, $warnings);
            }

            // Vérifier le départ dans la plage étendue
            if ($pointage->heure_depart) {
                $heureDepart = Carbon::parse($pointage->heure_depart);
                $this->validateHeureInPlage($heureDepart, $heureDebutPlanning, $heureFinPlanning, 'départ', $data, $warnings);
            }

        } catch (\Exception $e) {
            $warnings[] = "Erreur lors de la validation de la plage horaire: " . $e->getMessage();
        }

        return ['data' => $data, 'warnings' => $warnings];
    }

    /**
     * Valider qu'une heure est dans une plage acceptable
     */
    private function validateHeureInPlage(Carbon $heure, Carbon $debut, Carbon $fin, string $type, array &$data, array &$warnings): void
    {
        // Plage étendue : 2h avant le début à 4h après la fin
        $plageDebut = (clone $debut)->subHours(2);
        $plageFin = (clone $fin)->addHours(4);

        if (!($heure->gte($plageDebut) && $heure->lte($plageFin))) {
            $warnings[] = "Heure de {$type} hors de la plage habituelle ({$plageDebut->format('H:i')} - {$plageFin->format('H:i')})";
            $data["heure_{$type}_hors_plage"] = true;
        }
    }
} 