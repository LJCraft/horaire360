<?php

namespace App\Services\CriteriaEngine\Validators;

use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Services\CriteriaEngine\ValidationResult;
use Carbon\Carbon;

/**
 * Validateur de format et cohérence des données
 * Vérifie la validité des formats de date/heure et la cohérence des données
 */
class FormatValidator extends BaseValidator
{
    public function getName(): string
    {
        return 'Format et cohérence';
    }

    public function getDescription(): string
    {
        return 'Valide le format des données et leur cohérence (dates, heures, etc.)';
    }

    public function getPriority(): int
    {
        return 2; // Haute priorité - validation de base
    }

    public function canApplyWithoutPlanning(): bool
    {
        return true; // Peut fonctionner sans planning
    }

    public function appliesTo(CriterePointage $criteria): bool
    {
        // S'applique toujours pour vérifier la cohérence des données
        return true;
    }

    public function validate(Presence $pointage, CriterePointage $criteria, ?Planning $planning = null): ValidationResult
    {
        $errors = [];
        $warnings = [];
        $data = [];

        // Vérifier les données de base
        $baseValidation = $this->validatePointageData($pointage);
        if (!$baseValidation->success) {
            return $baseValidation;
        }

        // Vérifier le format de la date
        try {
            $date = Carbon::parse($pointage->date);
            $data['date_validee'] = $date->toDateString();
        } catch (\Exception $e) {
            $errors[] = 'Format de date invalide';
        }

        // Vérifier l'heure d'arrivée si présente
        if ($pointage->heure_arrivee) {
            try {
                $heureArrivee = Carbon::parse($pointage->heure_arrivee);
                $data['heure_arrivee_validee'] = $heureArrivee->format('H:i:s');
            } catch (\Exception $e) {
                $errors[] = 'Format d\'heure d\'arrivée invalide';
            }
        }

        // Vérifier l'heure de départ si présente
        if ($pointage->heure_depart) {
            try {
                $heureDepart = Carbon::parse($pointage->heure_depart);
                $data['heure_depart_validee'] = $heureDepart->format('H:i:s');

                // Vérifier la cohérence arrivée/départ
                if ($pointage->heure_arrivee && isset($heureArrivee)) {
                    if ($heureDepart->lt($heureArrivee)) {
                        // Cas possible : travail de nuit
                        $warnings[] = 'Heure de départ antérieure à l\'arrivée (travail de nuit possible)';
                        $data['travail_nuit_detecte'] = true;
                    }

                    // Calculer la durée
                    $duree = $heureArrivee->diffInMinutes($heureDepart);
                    if ($heureDepart->lt($heureArrivee)) {
                        // Ajouter 24h pour le travail de nuit
                        $duree = $heureArrivee->diffInMinutes($heureDepart->addDay());
                    }

                    $data['duree_calculee_minutes'] = $duree;
                    $data['duree_calculee_heures'] = round($duree / 60, 2);

                    // Vérifier les durées aberrantes
                    if ($duree > 16 * 60) { // Plus de 16h
                        $warnings[] = 'Durée de travail exceptionnellement longue (' . round($duree / 60, 1) . 'h)';
                    }
                    if ($duree < 30) { // Moins de 30 minutes
                        $warnings[] = 'Durée de travail très courte (' . $duree . ' minutes)';
                    }
                }
            } catch (\Exception $e) {
                $errors[] = 'Format d\'heure de départ invalide';
            }
        }

        // Vérifier les métadonnées si présentes
        if ($pointage->meta_data) {
            if (!is_array($pointage->meta_data)) {
                $warnings[] = 'Métadonnées dans un format inattendu';
            } else {
                $data['meta_data_validees'] = true;
            }
        }

        // Vérifier la source de pointage
        $sourcesValides = ['biometrique', 'manuel', 'import', 'synchronisation'];
        $source = $pointage->source_pointage ?? 'manuel';
        if (!in_array($source, $sourcesValides)) {
            $warnings[] = "Source de pointage inconnue: {$source}";
        } else {
            $data['source_pointage_validee'] = $source;
        }

        // Résultat final
        if (!empty($errors)) {
            return ValidationResult::failure(
                'Erreurs de format détectées: ' . implode(', ', $errors),
                $errors
            );
        }

        $result = ValidationResult::success('Validation de format réussie', $data);
        foreach ($warnings as $warning) {
            $result->addWarning($warning);
        }

        return $result;
    }
} 