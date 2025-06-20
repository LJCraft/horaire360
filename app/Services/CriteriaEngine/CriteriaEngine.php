<?php

namespace App\Services\CriteriaEngine;

use App\Models\Presence;
use App\Models\Employe;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Enums\ProcessingStatus;
use App\Services\CriteriaEngine\Contracts\CriteriaValidator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Moteur principal d'exécution des critères de pointage
 */
class CriteriaEngine
{
    protected array $validators = [];

    public function __construct()
    {
        $this->loadValidators();
    }

    /**
     * Charger automatiquement tous les validateurs
     */
    protected function loadValidators(): void
    {
        $this->validators = [
            app(Validators\SourceValidator::class),
            app(Validators\FormatValidator::class),
            app(Validators\DuplicateValidator::class),
            app(Validators\PointageCountValidator::class),
            app(Validators\TimeIntervalValidator::class),
            app(Validators\ToleranceValidator::class),
            app(Validators\PauseValidator::class),
            app(Validators\PunctualityValidator::class),
            app(Validators\OvertimeValidator::class),
            app(Validators\WorkTimeValidator::class),
        ];

        // Trier par priorité
        usort($this->validators, fn($a, $b) => $a->getPriority() <=> $b->getPriority());
    }

    /**
     * Appliquer les critères à un pointage
     */
    public function applyCriteriaToPointage(Presence $pointage): ProcessingResult
    {
        $result = new ProcessingResult(ProcessingStatus::NOT_PROCESSED);
        
        try {
            // Récupérer l'employé et la date
            $employe = $pointage->employe;
            if (!$employe) {
                return new ProcessingResult(
                    ProcessingStatus::CRITERIA_ERROR,
                    errors: [['message' => 'Employé non trouvé pour le pointage', 'context' => ['pointage_id' => $pointage->id]]]
                );
            }

            // Obtenir les critères applicables
            $criteria = $this->getApplicableCriteria($employe, $pointage->date);
            
            if ($criteria->isEmpty()) {
                return new ProcessingResult(
                    ProcessingStatus::NO_CRITERIA,
                    metadata: ['message' => 'Aucun critère applicable trouvé']
                );
            }

            // Obtenir le planning si disponible
            $planning = $this->getApplicablePlanning($employe, $pointage->date);

            // Appliquer chaque critère
            foreach ($criteria as $critere) {
                $criteriaResult = $this->applySingleCriteria($pointage, $critere, $planning);
                $this->mergeCriteriaResult($result, $criteriaResult);
            }

            // Déterminer le statut final
            $this->finalizeProcessingStatus($result);

            // Sauvegarder le résultat dans le pointage
            $this->saveProcessingResult($pointage, $result);

            Log::info('Critères appliqués avec succès', [
                'pointage_id' => $pointage->id,
                'employe_id' => $employe->id,
                'status' => $result->status->value,
                'applied_validators' => count($result->appliedValidators)
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'application des critères', [
                'pointage_id' => $pointage->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $result = new ProcessingResult(
                ProcessingStatus::CRITERIA_ERROR,
                errors: [['message' => $e->getMessage(), 'context' => ['exception' => get_class($e)]]]
            );
        }

        return $result;
    }

    /**
     * Appliquer les critères à un lot de pointages
     */
    public function applyCriteriaToBatch(Collection $pointages): BatchResult
    {
        $batchResult = new BatchResult();
        
        foreach ($pointages as $pointage) {
            $result = $this->applyCriteriaToPointage($pointage);
            $batchResult->addResult($pointage->id, $result);
        }

        return $batchResult;
    }

    /**
     * Retraiter les critères pour un employé sur une période
     */
    public function reprocessEmployeeCriteria(Employe $employe, Carbon $from, Carbon $to): BatchResult
    {
        $pointages = Presence::where('employe_id', $employe->id)
            ->whereBetween('date', [$from, $to])
            ->get();

        Log::info('Retraitement des critères démarré', [
            'employe_id' => $employe->id,
            'period' => "{$from->toDateString()} - {$to->toDateString()}",
            'pointages_count' => $pointages->count()
        ]);

        return $this->applyCriteriaToBatch($pointages);
    }

    /**
     * Obtenir les critères applicables pour un employé à une date donnée
     */
    public function getApplicableCriteria(Employe $employe, Carbon $date): Collection
    {
        // Récupérer les critères individuels actifs
        $criteresIndividuels = CriterePointage::where('niveau', 'individuel')
            ->where('employe_id', $employe->id)
            ->where('actif', true)
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->orderBy('priorite')
            ->get();

        // Si des critères individuels existent, les utiliser en priorité
        if ($criteresIndividuels->isNotEmpty()) {
            return $criteresIndividuels;
        }

        // Sinon, chercher les critères départementaux
        if ($employe->departement_id) {
            $criteresDepartementaux = CriterePointage::where('niveau', 'departemental')
                ->where('departement_id', $employe->departement_id)
                ->where('actif', true)
                ->where('date_debut', '<=', $date)
                ->where('date_fin', '>=', $date)
                ->orderBy('priorite')
                ->get();

            return $criteresDepartementaux;
        }

        return collect();
    }

    /**
     * Obtenir le planning applicable pour un employé à une date donnée
     */
    protected function getApplicablePlanning(Employe $employe, Carbon $date): ?Planning
    {
        return Planning::where('employe_id', $employe->id)
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->where('actif', true)
            ->first();
    }

    /**
     * Appliquer un seul critère
     */
    protected function applySingleCriteria(Presence $pointage, CriterePointage $critere, ?Planning $planning): ProcessingResult
    {
        $result = new ProcessingResult(ProcessingStatus::NOT_PROCESSED);

        foreach ($this->validators as $validator) {
            if (!$validator->appliesTo($critere)) {
                continue;
            }

            // Vérifier si le validateur nécessite un planning
            if (!$validator->canApplyWithoutPlanning() && !$planning) {
                $result->addPendingValidator(
                    get_class($validator),
                    'Planning requis pour ce validateur'
                );
                continue;
            }

            try {
                $validationResult = $validator->validate($pointage, $critere, $planning);
                
                if ($validationResult->success) {
                    $result->addAppliedValidator(get_class($validator), $validationResult->data);
                    
                    // Copier les valeurs calculées
                    foreach ($validationResult->data as $key => $value) {
                        $result->addCalculatedValue($key, $value, get_class($validator));
                    }
                } else {
                    if ($validationResult->requiresPlanning) {
                        $result->addPendingValidator(get_class($validator), $validationResult->message);
                    } else {
                        $result->addError(get_class($validator), $validationResult->message, $validationResult->errors);
                    }
                }

                // Ajouter les avertissements
                foreach ($validationResult->warnings as $warning) {
                    $result->addWarning(get_class($validator), $warning);
                }

            } catch (\Exception $e) {
                $result->addError(
                    get_class($validator),
                    'Erreur lors de la validation: ' . $e->getMessage(),
                    ['exception' => get_class($e)]
                );
            }
        }

        return $result;
    }

    /**
     * Fusionner les résultats de critères
     */
    protected function mergeCriteriaResult(ProcessingResult $main, ProcessingResult $additional): void
    {
        $main->appliedValidators = array_merge($main->appliedValidators, $additional->appliedValidators);
        $main->pendingValidators = array_merge($main->pendingValidators, $additional->pendingValidators);
        $main->errors = array_merge($main->errors, $additional->errors);
        $main->warnings = array_merge($main->warnings, $additional->warnings);
        $main->calculatedValues = array_merge($main->calculatedValues, $additional->calculatedValues);
    }

    /**
     * Finaliser le statut de traitement
     */
    protected function finalizeProcessingStatus(ProcessingResult $result): void
    {
        if ($result->hasErrors()) {
            $result->status = ProcessingStatus::CRITERIA_ERROR;
        } elseif (!empty($result->pendingValidators)) {
            $result->status = ProcessingStatus::PENDING_PLANNING;
        } elseif (!empty($result->appliedValidators)) {
            $result->status = empty($result->pendingValidators) 
                ? ProcessingStatus::FULLY_PROCESSED 
                : ProcessingStatus::PARTIALLY_PROCESSED;
        } else {
            $result->status = ProcessingStatus::NO_CRITERIA;
        }
    }

    /**
     * Sauvegarder le résultat du traitement dans le pointage
     */
    protected function saveProcessingResult(Presence $pointage, ProcessingResult $result): void
    {
        // Mettre à jour les champs calculés dans le pointage
        $calculatedFields = [];
        
        foreach ($result->calculatedValues as $key => $data) {
            if (in_array($key, ['retard', 'depart_anticipe', 'heures_faites', 'heures_supplementaires'])) {
                $calculatedFields[$key] = $data['value'];
            }
        }

        if (!empty($calculatedFields)) {
            $pointage->update($calculatedFields);
        }

        // Sauvegarder les métadonnées de traitement
        $metaData = $pointage->meta_data ?? [];
        $metaData['criteria_processing'] = $result->toArray();
        $pointage->update(['meta_data' => $metaData]);
    }
} 