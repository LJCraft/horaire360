<?php

namespace App\Services\CriteriaEngine\Validators;

use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Services\CriteriaEngine\ValidationResult;

/**
 * Validateur de nombre de pointages
 * Vérifie que le nombre de pointages correspond aux critères (1 ou 2 pointages)
 */
class PointageCountValidator extends BaseValidator
{
    public function getName(): string
    {
        return 'Nombre de pointages';
    }

    public function getDescription(): string
    {
        return 'Vérifie que le nombre de pointages (arrivée/départ) correspond aux critères définis';
    }

    public function getPriority(): int
    {
        return 4; // Priorité élevée
    }

    public function canApplyWithoutPlanning(): bool
    {
        return true; // Peut fonctionner sans planning
    }

    public function appliesTo(CriterePointage $criteria): bool
    {
        // S'applique si le nombre de pointages est défini
        return !is_null($criteria->nombre_pointages);
    }

    public function getRequiredCriteriaFields(): array
    {
        return ['nombre_pointages'];
    }

    public function validate(Presence $pointage, CriterePointage $criteria, ?Planning $planning = null): ValidationResult
    {
        // Vérifier les données de base
        $baseValidation = $this->validatePointageData($pointage);
        if (!$baseValidation->success) {
            return $baseValidation;
        }

        // Vérifier les champs requis
        $fieldsValidation = $this->validateRequiredFields($criteria);
        if (!$fieldsValidation->success) {
            return $fieldsValidation;
        }

        $nombreAttendu = $criteria->nombre_pointages;
        $hasArrivee = !is_null($pointage->heure_arrivee);
        $hasDepart = !is_null($pointage->heure_depart);
        
        $nombreActuel = 0;
        if ($hasArrivee) $nombreActuel++;
        if ($hasDepart) $nombreActuel++;

        $data = [
            'nombre_attendu' => $nombreAttendu,
            'nombre_actuel' => $nombreActuel,
            'has_arrivee' => $hasArrivee,
            'has_depart' => $hasDepart
        ];

        $warnings = [];

        // Validation selon le nombre attendu
        if ($nombreAttendu == 1) {
            // Un seul pointage attendu
            if ($nombreActuel == 0) {
                return ValidationResult::failure(
                    'Aucun pointage trouvé (1 attendu)',
                    ['expected' => 1, 'actual' => 0]
                );
            }
            
            if ($nombreActuel == 1) {
                $data['validation_passed'] = true;
                if ($hasArrivee && !$hasDepart) {
                    $data['type_pointage'] = 'arrivee_seule';
                } elseif (!$hasArrivee && $hasDepart) {
                    $data['type_pointage'] = 'depart_seul';
                    $warnings[] = 'Départ sans arrivée - cas inhabituel';
                }
            } else {
                // 2 pointages alors qu'un seul est attendu
                $warnings[] = 'Deux pointages détectés alors qu\'un seul est attendu';
                $data['validation_passed'] = true; // On accepte mais on avertit
                $data['type_pointage'] = 'arrivee_et_depart';
            }
            
        } elseif ($nombreAttendu == 2) {
            // Deux pointages attendus
            if ($nombreActuel == 0) {
                return ValidationResult::failure(
                    'Aucun pointage trouvé (2 attendus)',
                    ['expected' => 2, 'actual' => 0]
                );
            }
            
            if ($nombreActuel == 1) {
                $data['validation_passed'] = false;
                $data['pointage_incomplet'] = true;
                
                if ($hasArrivee && !$hasDepart) {
                    $warnings[] = 'Arrivée enregistrée mais départ manquant';
                    $data['type_pointage'] = 'arrivee_seule';
                } else {
                    $warnings[] = 'Départ enregistré mais arrivée manquante';
                    $data['type_pointage'] = 'depart_seul';
                }
                
                // Ce n'est pas une erreur bloquante, juste un avertissement
                $result = ValidationResult::success('Pointage incomplet détecté', $data);
                foreach ($warnings as $warning) {
                    $result->addWarning($warning);
                }
                return $result;
                
            } else {
                // 2 pointages comme attendu
                $data['validation_passed'] = true;
                $data['type_pointage'] = 'arrivee_et_depart';
            }
        }

        // Vérifications supplémentaires
        if ($hasArrivee && $hasDepart) {
            // Vérifier l'ordre logique des heures
            try {
                $arrivee = \Carbon\Carbon::parse($pointage->heure_arrivee);
                $depart = \Carbon\Carbon::parse($pointage->heure_depart);
                
                if ($depart->lt($arrivee)) {
                    $warnings[] = 'Heure de départ antérieure à l\'arrivée (travail de nuit possible)';
                    $data['ordre_inverse'] = true;
                }
            } catch (\Exception $e) {
                $warnings[] = 'Erreur lors de la vérification des heures';
            }
        }

        $result = ValidationResult::success('Validation du nombre de pointages réussie', $data);
        foreach ($warnings as $warning) {
            $result->addWarning($warning);
        }

        return $result;
    }
} 