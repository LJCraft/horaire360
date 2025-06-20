<?php

namespace App\Services\CriteriaEngine\Validators;

use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Services\CriteriaEngine\ValidationResult;
use Carbon\Carbon;

/**
 * Validateur de temps de travail effectif
 * Calcule le temps de travail effectif et les heures faites
 */
class WorkTimeValidator extends BaseValidator
{
    public function getName(): string
    {
        return 'Temps de travail effectif';
    }

    public function getDescription(): string
    {
        return 'Calcule le temps de travail effectif et les heures faites';
    }

    public function getPriority(): int
    {
        return 10; // Priorité basse - calcul final
    }

    public function canApplyWithoutPlanning(): bool
    {
        return false; // Nécessite un planning pour calculer les heures prévues
    }

    public function appliesTo(CriterePointage $criteria): bool
    {
        // S'applique toujours quand il y a un planning
        return true;
    }

    public function getCalculatedFields(): array
    {
        return ['heures_faites', 'heures_prevues'];
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
            return ValidationResult::pendingPlanning('Planning requis pour calculer le temps de travail effectif');
        }

        // Vérifier si c'est un jour de repos
        if ($this->isJourRepos($planning, $pointage)) {
            $heuresFaites = $this->calculateHeuresFaites($pointage);
            return ValidationResult::success('Jour de repos', [
                'jour_repos' => true,
                'heures_faites' => $heuresFaites,
                'heures_prevues' => 0
            ]);
        }

        // Obtenir le détail du planning
        $planningDetail = $this->getPlanningDetail($planning, $pointage);
        if (!$planningDetail) {
            return ValidationResult::failure('Détail de planning non trouvé pour ce jour');
        }

        return $this->calculateWorkTime($pointage, $planningDetail, $criteria);
    }

    /**
     * Calculer les heures faites
     */
    private function calculateHeuresFaites(Presence $pointage): float
    {
        if (!$pointage->heure_arrivee || !$pointage->heure_depart) {
            return 0;
        }

        try {
            $arrivee = Carbon::parse($pointage->heure_arrivee);
            $depart = Carbon::parse($pointage->heure_depart);

            // Gérer le travail de nuit
            if ($depart->lt($arrivee)) {
                $depart->addDay();
            }

            return round($arrivee->diffInMinutes($depart) / 60, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculer le temps de travail effectif
     */
    private function calculateWorkTime(Presence $pointage, $planningDetail, CriterePointage $criteria): ValidationResult
    {
        $data = [];
        $warnings = [];

        // Calculer les heures faites
        $heuresFaites = $this->calculateHeuresFaites($pointage);

        // Calculer les heures prévues selon le planning
        try {
            $heureDebut = Carbon::parse($planningDetail->heure_debut);
            $heureFin = Carbon::parse($planningDetail->heure_fin);
            
            // Gérer le travail de nuit dans le planning
            if ($heureFin->lt($heureDebut)) {
                $heureFin->addDay();
            }
            
            $heuresPrevues = round($heureDebut->diffInMinutes($heureFin) / 60, 2);
            
        } catch (\Exception $e) {
            return ValidationResult::failure('Erreur lors du calcul des heures prévues: ' . $e->getMessage());
        }

        $data['heures_faites'] = $heuresFaites;
        $data['heures_prevues'] = $heuresPrevues;

        // Calculer les écarts
        $ecartHeures = $heuresFaites - $heuresPrevues;
        $data['ecart_heures'] = round($ecartHeures, 2);

        // Calculer le pourcentage de présence
        if ($heuresPrevues > 0) {
            $data['pourcentage_presence'] = round(($heuresFaites / $heuresPrevues) * 100, 1);
        } else {
            $data['pourcentage_presence'] = 0;
        }

        // Analyser les écarts
        if (abs($ecartHeures) > 0.5) { // Plus de 30 minutes d'écart
            if ($ecartHeures > 0) {
                $warnings[] = "Heures supplémentaires détectées: +" . round($ecartHeures, 1) . "h";
                $data['heures_supplementaires_detectees'] = true;
            } else {
                $warnings[] = "Heures manquantes détectées: " . round($ecartHeures, 1) . "h";
                $data['heures_manquantes_detectees'] = true;
            }
        }

        // Détecter les absences partielles
        if ($heuresFaites == 0) {
            $data['absence_totale'] = true;
            $warnings[] = "Absence totale détectée";
        } elseif ($heuresFaites < $heuresPrevues * 0.5) {
            $data['absence_partielle'] = true;
            $warnings[] = "Absence partielle importante détectée";
        }

        // Calculer les pauses si configurées
        if ($criteria->duree_pause && $criteria->duree_pause > 0) {
            $pauseMinutes = $criteria->duree_pause;
            $data['pause_prevue_minutes'] = $pauseMinutes;
            $data['pause_prevue_heures'] = round($pauseMinutes / 60, 2);
            
            // Ajuster le temps de travail effectif
            $tempsEffectifSansPause = max(0, $heuresFaites - ($pauseMinutes / 60));
            $data['temps_effectif_sans_pause'] = round($tempsEffectifSansPause, 2);
        }

        // Analyser la régularité
        if ($pointage->heure_arrivee && $pointage->heure_depart) {
            $this->analyzeRegularity($pointage, $planningDetail, $data, $warnings);
        }

        $result = ValidationResult::success('Temps de travail calculé', $data);
        foreach ($warnings as $warning) {
            $result->addWarning($warning);
        }

        return $result;
    }

    /**
     * Analyser la régularité par rapport au planning
     */
    private function analyzeRegularity(Presence $pointage, $planningDetail, array &$data, array &$warnings): void
    {
        try {
            $arriveeReelle = Carbon::parse($pointage->heure_arrivee);
            $departReel = Carbon::parse($pointage->heure_depart);
            $arriveePrevu = Carbon::parse($planningDetail->heure_debut);
            $departPrevu = Carbon::parse($planningDetail->heure_fin);

            // Calculer les écarts en minutes
            $ecartArrivee = $arriveePrevu->diffInMinutes($arriveeReelle, false);
            $ecartDepart = $departPrevu->diffInMinutes($departReel, false);

            $data['ecart_arrivee_minutes'] = $ecartArrivee;
            $data['ecart_depart_minutes'] = $ecartDepart;

            // Analyser la ponctualité
            if (abs($ecartArrivee) <= 5) {
                $data['arrivee_ponctuelle'] = true;
            } elseif ($ecartArrivee > 0) {
                $data['arrivee_en_retard'] = true;
                if ($ecartArrivee > 30) {
                    $warnings[] = "Retard important à l'arrivée: " . $ecartArrivee . " minutes";
                }
            } else {
                $data['arrivee_en_avance'] = true;
                if (abs($ecartArrivee) > 30) {
                    $warnings[] = "Arrivée très en avance: " . abs($ecartArrivee) . " minutes";
                }
            }

            // Analyser le départ
            if (abs($ecartDepart) <= 5) {
                $data['depart_ponctuel'] = true;
            } elseif ($ecartDepart < 0) {
                $data['depart_anticipe'] = true;
                if (abs($ecartDepart) > 30) {
                    $warnings[] = "Départ très anticipé: " . abs($ecartDepart) . " minutes";
                }
            } else {
                $data['depart_tardif'] = true;
                if ($ecartDepart > 30) {
                    $warnings[] = "Départ tardif: " . $ecartDepart . " minutes";
                }
            }

        } catch (\Exception $e) {
            $warnings[] = "Erreur lors de l'analyse de régularité: " . $e->getMessage();
        }
    }
} 