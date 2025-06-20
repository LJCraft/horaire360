<?php

namespace App\Observers;

use App\Models\Presence;
use App\Services\CriteriaEngine\CriteriaEngine;
use App\Enums\ProcessingStatus;
use Illuminate\Support\Facades\Log;

class PresenceObserver
{
    protected CriteriaEngine $criteriaEngine;

    public function __construct(CriteriaEngine $criteriaEngine)
    {
        $this->criteriaEngine = $criteriaEngine;
    }

    /**
     * Handle the Presence "created" event.
     */
    public function created(Presence $presence): void
    {
        $this->applyCriteria($presence, 'created');
    }

    /**
     * Handle the Presence "updated" event.
     */
    public function updated(Presence $presence): void
    {
        // Vérifier si des champs critiques ont été modifiés
        $criticalFields = ['heure_arrivee', 'heure_depart', 'date', 'employe_id'];
        
        if ($presence->wasChanged($criticalFields)) {
            $this->applyCriteria($presence, 'updated');
        }
    }

    /**
     * Appliquer les critères à un pointage
     */
    protected function applyCriteria(Presence $presence, string $event): void
    {
        try {
            // Éviter les boucles infinies en vérifiant si on est déjà en train de traiter
            if ($presence->isDirty(['criteria_processing_status', 'criteria_processed_at'])) {
                return;
            }

            Log::info("Application des critères déclenchée", [
                'pointage_id' => $presence->id,
                'employe_id' => $presence->employe_id,
                'event' => $event
            ]);

            // Appliquer les critères
            $result = $this->criteriaEngine->applyCriteriaToPointage($presence);

            // Mettre à jour le statut de traitement (sans déclencher l'observateur)
            $presence->updateQuietly([
                'criteria_processing_status' => $result->status->value,
                'criteria_processed_at' => now(),
                'criteria_version' => $this->getCriteriaVersion($presence)
            ]);

            Log::info("Critères appliqués avec succès", [
                'pointage_id' => $presence->id,
                'status' => $result->status->value,
                'applied_validators' => count($result->appliedValidators),
                'pending_validators' => count($result->pendingValidators),
                'errors' => count($result->errors)
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'application automatique des critères", [
                'pointage_id' => $presence->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Marquer comme erreur de critère
            $presence->updateQuietly([
                'criteria_processing_status' => ProcessingStatus::CRITERIA_ERROR->value,
                'criteria_processed_at' => now()
            ]);
        }
    }

    /**
     * Obtenir la version des critères pour le retraitement
     */
    protected function getCriteriaVersion(Presence $presence): string
    {
        // Créer une version basée sur les critères applicables
        $employe = $presence->employe;
        if (!$employe) {
            return 'no-employee';
        }

        $criteria = $this->criteriaEngine->getApplicableCriteria($employe, $presence->date);
        
        if ($criteria->isEmpty()) {
            return 'no-criteria';
        }

        // Créer un hash basé sur les IDs et dates de modification des critères
        $criteriaData = $criteria->map(function ($critere) {
            return $critere->id . ':' . $critere->updated_at->timestamp;
        })->join('|');

        return substr(md5($criteriaData), 0, 10);
    }

    /**
     * Handle the Presence "deleted" event.
     */
    public function deleted(Presence $presence): void
    {
        Log::info("Pointage supprimé", [
            'pointage_id' => $presence->id,
            'employe_id' => $presence->employe_id
        ]);
    }

    /**
     * Handle the Presence "restored" event.
     */
    public function restored(Presence $presence): void
    {
        $this->applyCriteria($presence, 'restored');
    }

    /**
     * Handle the Presence "force deleted" event.
     */
    public function forceDeleted(Presence $presence): void
    {
        //
    }
}
