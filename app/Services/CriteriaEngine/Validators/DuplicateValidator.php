<?php

namespace App\Services\CriteriaEngine\Validators;

use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Services\CriteriaEngine\ValidationResult;
use Carbon\Carbon;

/**
 * Validateur de détection de doublons
 * Détecte les pointages en double pour le même employé et la même date
 */
class DuplicateValidator extends BaseValidator
{
    public function getName(): string
    {
        return 'Détection de doublons';
    }

    public function getDescription(): string
    {
        return 'Détecte les pointages en double pour éviter les incohérences';
    }

    public function getPriority(): int
    {
        return 3; // Haute priorité - validation de base
    }

    public function canApplyWithoutPlanning(): bool
    {
        return true; // Peut fonctionner sans planning
    }

    public function appliesTo(CriterePointage $criteria): bool
    {
        // S'applique toujours pour détecter les doublons
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

        // Rechercher d'autres pointages pour le même employé et la même date
        $duplicates = Presence::where('employe_id', $pointage->employe_id)
            ->where('date', $pointage->date)
            ->where('id', '!=', $pointage->id)
            ->get();

        if ($duplicates->isEmpty()) {
            return ValidationResult::success('Aucun doublon détecté', [
                'duplicates_found' => false,
                'duplicates_count' => 0
            ]);
        }

        $data['duplicates_found'] = true;
        $data['duplicates_count'] = $duplicates->count();
        $data['duplicate_ids'] = $duplicates->pluck('id')->toArray();

        // Analyser les types de doublons
        $exactDuplicates = [];
        $similarDuplicates = [];
        
        foreach ($duplicates as $duplicate) {
            $duplicateInfo = [
                'id' => $duplicate->id,
                'heure_arrivee' => $duplicate->heure_arrivee,
                'heure_depart' => $duplicate->heure_depart,
                'source_pointage' => $duplicate->source_pointage
            ];

            // Vérifier si c'est un doublon exact (mêmes heures)
            if ($this->isExactDuplicate($pointage, $duplicate)) {
                $exactDuplicates[] = $duplicateInfo;
            } else {
                $similarDuplicates[] = $duplicateInfo;
            }
        }

        $data['exact_duplicates'] = $exactDuplicates;
        $data['similar_duplicates'] = $similarDuplicates;

        // Générer des avertissements appropriés
        if (!empty($exactDuplicates)) {
            $warnings[] = "Doublons exacts détectés (" . count($exactDuplicates) . ") - vérification requise";
        }

        if (!empty($similarDuplicates)) {
            $warnings[] = "Pointages similaires détectés (" . count($similarDuplicates) . ") - possibles corrections";
        }

        // Analyser les sources de pointage
        $sources = $duplicates->pluck('source_pointage')->unique()->toArray();
        if (count($sources) > 1) {
            $warnings[] = "Pointages de sources différentes: " . implode(', ', $sources);
            $data['mixed_sources'] = true;
        }

        // Recommandations
        $recommendations = $this->generateRecommendations($pointage, $duplicates);
        $data['recommendations'] = $recommendations;

        $result = ValidationResult::success('Doublons analysés', $data);
        foreach ($warnings as $warning) {
            $result->addWarning($warning);
        }

        return $result;
    }

    /**
     * Vérifier si deux pointages sont des doublons exacts
     */
    private function isExactDuplicate(Presence $pointage1, Presence $pointage2): bool
    {
        return $pointage1->heure_arrivee == $pointage2->heure_arrivee 
            && $pointage1->heure_depart == $pointage2->heure_depart;
    }

    /**
     * Générer des recommandations pour gérer les doublons
     */
    private function generateRecommendations(Presence $pointage, $duplicates): array
    {
        $recommendations = [];

        // Recommandation basée sur la source
        $biometricCount = $duplicates->where('source_pointage', 'biometrique')->count();
        $manualCount = $duplicates->where('source_pointage', 'manuel')->count();

        if ($biometricCount > 0 && $manualCount > 0) {
            $recommendations[] = "Privilégier les pointages biométriques sur les pointages manuels";
        }

        // Recommandation basée sur l'heure de création
        $newest = $duplicates->sortByDesc('created_at')->first();
        if ($newest) {
            $recommendations[] = "Le pointage le plus récent (ID: {$newest->id}) pourrait être le plus fiable";
        }

        // Recommandation basée sur la complétude des données
        $complete = $duplicates->filter(function($duplicate) {
            return $duplicate->heure_arrivee && $duplicate->heure_depart;
        });

        if ($complete->isNotEmpty()) {
            $recommendations[] = "Privilégier les pointages avec arrivée ET départ renseignés";
        }

        return $recommendations;
    }
} 