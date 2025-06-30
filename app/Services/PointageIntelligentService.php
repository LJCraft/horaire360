<?php

namespace App\Services;

use App\Models\Presence;
use App\Models\Employe;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service de traitement intelligent des pointages
 * 
 * Centralise la logique métier pour traiter correctement les heures de pointage
 * multiples et désordonnées pour un même employé sur une journée.
 */
class PointageIntelligentService
{
    /**
     * Traiter une liste de pointages bruts avec regroupement intelligent
     * 
     * @param array $pointagesBruts Liste des pointages bruts
     * @param string $source Source des données (api_mobile, import_dat, etc.)
     * @param string $sessionId ID de session pour le logging
     * @return array Résultat du traitement
     */
    public function traiterPointagesBruts(array $pointagesBruts, string $source = 'unknown', string $sessionId = null): array
    {
        $sessionId = $sessionId ?? uniqid('pointage_');
        
        Log::info("🚀 Début traitement pointages intelligents", [
            'nombre_pointages' => count($pointagesBruts),
            'source' => $source,
            'session_id' => $sessionId
        ]);

        $stats = [
            'total_recu' => count($pointagesBruts),
            'groupes_crees' => 0,
            'presences_creees' => 0,
            'presences_mises_a_jour' => 0,
            'erreurs' => 0,
            'ignores' => 0
        ];

        try {
            // 1. Regrouper par employé et date
            $groupes = $this->regrouperParEmployeEtDate($pointagesBruts, $sessionId);
            $stats['groupes_crees'] = count($groupes);

            // 2. Traiter chaque groupe avec la logique métier
            foreach ($groupes as $groupe) {
                $resultat = $this->traiterGroupeEmployeJour($groupe, $source, $sessionId);
                $this->mettreAJourStats($stats, $resultat);
            }

            Log::info("✅ Traitement pointages terminé avec succès", [
                'stats' => $stats,
                'session_id' => $sessionId
            ]);

            return [
                'success' => true,
                'stats' => $stats,
                'message' => $this->genererMessageResultat($stats)
            ];

        } catch (\Exception $e) {
            Log::error("❌ Erreur traitement pointages intelligents", [
                'erreur' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_id' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $stats
            ];
        }
    }

    /**
     * Regrouper les pointages par employé et date
     */
    private function regrouperParEmployeEtDate(array $pointagesBruts, string $sessionId): array
    {
        $groupes = [];

        foreach ($pointagesBruts as $index => $pointage) {
            // Validation des données essentielles
            if (!$this->validerPointageBrut($pointage, $index, $sessionId)) {
                continue;
            }

            $employeId = $pointage['employe_id'];
            $date = $pointage['date'];
            $cle = "{$employeId}_{$date}";

            if (!isset($groupes[$cle])) {
                $groupes[$cle] = [
                    'employe_id' => $employeId,
                    'date' => $date,
                    'pointages' => [],
                    'meta' => [
                        'source' => $pointage['source'] ?? 'unknown',
                        'premier_recu' => now()
                    ]
                ];
            }

            // Ajouter le pointage au groupe
            $groupes[$cle]['pointages'][] = [
                'heure' => $pointage['heure'],
                'terminal_id' => $pointage['terminal_id'] ?? null,
                'meta_data' => $pointage['meta_data'] ?? [],
                'donnee_brute' => $pointage['donnee_brute'] ?? $pointage,
                'index_original' => $index
            ];
        }

        Log::info("📊 Regroupement terminé", [
            'nombre_groupes' => count($groupes),
            'session_id' => $sessionId
        ]);

        return array_values($groupes);
    }

    /**
     * Traiter un groupe employé/jour avec la logique métier intelligente
     */
    private function traiterGroupeEmployeJour(array $groupe, string $source, string $sessionId): array
    {
        $employeId = $groupe['employe_id'];
        $date = $groupe['date'];
        $pointages = $groupe['pointages'];

        Log::info("🔍 Traitement groupe employé/jour", [
            'employe_id' => $employeId,
            'date' => $date,
            'nombre_pointages' => count($pointages),
            'session_id' => $sessionId
        ]);

        // 1. Vérifier que l'employé existe
        $employe = Employe::find($employeId);
        if (!$employe) {
            Log::warning("❌ Employé introuvable", [
                'employe_id' => $employeId,
                'session_id' => $sessionId
            ]);
            return ['status' => 'error', 'message' => "Employé {$employeId} introuvable"];
        }

        // 2. Extraire et trier les heures
        $heures = array_column($pointages, 'heure');
        sort($heures); // Tri chronologique

        Log::info("⏰ Heures triées pour analyse", [
            'employe_id' => $employeId,
            'date' => $date,
            'heures_brutes' => array_column($pointages, 'heure'),
            'heures_triees' => $heures,
            'session_id' => $sessionId
        ]);

        // 3. Appliquer la logique métier intelligente
        $resultatAnalyse = $this->analyserHeuresPointage($heures);

        // 4. Créer ou mettre à jour la présence
        return $this->creerOuMettreAJourPresence(
            $employeId,
            $date,
            $resultatAnalyse,
            $pointages,
            $source,
            $sessionId
        );
    }

    /**
     * Analyser les heures de pointage selon la logique métier
     */
    private function analyserHeuresPointage(array $heures): array
    {
        $nombreHeures = count($heures);

        if ($nombreHeures === 0) {
            return [
                'type' => 'aucune_heure',
                'heure_arrivee' => null,
                'heure_depart' => null,
                'statut' => 'ignore'
            ];
        }

        if ($nombreHeures === 1) {
            return [
                'type' => 'heure_unique',
                'heure_arrivee' => $heures[0],
                'heure_depart' => null,
                'statut' => 'arrivee_seulement'
            ];
        }

        // Plusieurs heures : première = arrivée, dernière = départ
        return [
            'type' => 'heures_multiples',
            'heure_arrivee' => $heures[0],
            'heure_depart' => $heures[count($heures) - 1],
            'statut' => 'journee_complete',
            'heures_intermediaires' => array_slice($heures, 1, -1)
        ];
    }

    /**
     * Créer ou mettre à jour une présence selon l'analyse
     */
    private function creerOuMettreAJourPresence(
        int $employeId, 
        string $date, 
        array $analyse, 
        array $pointages, 
        string $source, 
        string $sessionId
    ): array {
        try {
            // Vérifier s'il existe déjà une présence pour ce jour
            $presenceExistante = Presence::where('employe_id', $employeId)
                                        ->where('date', $date)
                                        ->first();

            $metaData = [
                'source_traitement' => 'pointage_intelligent_service',
                'source_donnees' => $source,
                'session_id' => $sessionId,
                'analyse' => $analyse,
                'pointages_originaux' => $pointages,
                'timestamp_traitement' => now()->toISOString()
            ];

            if ($analyse['statut'] === 'ignore') {
                Log::warning("⚠️ Aucune heure valide, présence ignorée", [
                    'employe_id' => $employeId,
                    'date' => $date,
                    'session_id' => $sessionId
                ]);
                return ['status' => 'ignored', 'message' => 'Aucune heure valide trouvée'];
            }

            $donneesPresence = [
                'employe_id' => $employeId,
                'date' => $date,
                'heure_arrivee' => $analyse['heure_arrivee'],
                'heure_depart' => $analyse['heure_depart'],
                'source_pointage' => $source,
                'meta_data' => $metaData
            ];

            // Ajouter le statut seulement si la colonne existe
            if (Schema::hasColumn('presences', 'statut')) {
                $donneesPresence['statut'] = $analyse['statut'];
            }

            if ($presenceExistante) {
                // Mise à jour intelligente
                $changements = $this->detecterChangements($presenceExistante, $donneesPresence);
                
                if (!empty($changements)) {
                    $presenceExistante->update($donneesPresence);
                    
                    Log::info("✏️ Présence mise à jour", [
                        'employe_id' => $employeId,
                        'date' => $date,
                        'changements' => $changements,
                        'nouvelle_arrivee' => $analyse['heure_arrivee'],
                        'nouveau_depart' => $analyse['heure_depart'],
                        'session_id' => $sessionId
                    ]);

                    return ['status' => 'updated', 'message' => 'Présence mise à jour avec succès'];
                } else {
                    Log::debug("🔄 Aucun changement détecté", [
                        'employe_id' => $employeId,
                        'date' => $date,
                        'heure_arrivee_actuelle' => $presenceExistante->heure_arrivee,
                        'heure_depart_actuelle' => $presenceExistante->heure_depart,
                        'session_id' => $sessionId
                    ]);

                    return ['status' => 'unchanged', 'message' => 'Aucun changement détecté'];
                }
            } else {
                // Création nouvelle présence
                $presence = Presence::create($donneesPresence);
                
                Log::info("✨ Nouvelle présence créée", [
                    'employe_id' => $employeId,
                    'date' => $date,
                    'heure_arrivee' => $analyse['heure_arrivee'],
                    'heure_depart' => $analyse['heure_depart'],
                    'statut' => $analyse['statut'],
                    'session_id' => $sessionId
                ]);

                return ['status' => 'created', 'message' => 'Nouvelle présence créée avec succès'];
            }

        } catch (\Exception $e) {
            Log::error("❌ Erreur création/mise à jour présence", [
                'employe_id' => $employeId,
                'date' => $date,
                'erreur' => $e->getMessage(),
                'session_id' => $sessionId
            ]);

            return ['status' => 'error', 'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()];
        }
    }

    /**
     * Valider un pointage brut avant traitement
     */
    private function validerPointageBrut(array $pointage, int $index, string $sessionId): bool
    {
        // Vérification employé ID
        if (!isset($pointage['employe_id']) || !is_numeric($pointage['employe_id'])) {
            Log::warning("⚠️ Pointage ignoré - employé_id manquant ou invalide", [
                'index' => $index,
                'pointage' => $pointage,
                'session_id' => $sessionId
            ]);
            return false;
        }

        // Vérification date
        if (!isset($pointage['date']) || !$this->validerFormatDate($pointage['date'])) {
            Log::warning("⚠️ Pointage ignoré - date manquante ou invalide", [
                'index' => $index,
                'employe_id' => $pointage['employe_id'] ?? 'N/A',
                'date' => $pointage['date'] ?? 'N/A',
                'session_id' => $sessionId
            ]);
            return false;
        }

        // Vérification heure
        if (!isset($pointage['heure']) || !$this->validerFormatHeure($pointage['heure'])) {
            Log::warning("⚠️ Pointage ignoré - heure manquante ou invalide", [
                'index' => $index,
                'employe_id' => $pointage['employe_id'],
                'date' => $pointage['date'],
                'heure' => $pointage['heure'] ?? 'N/A',
                'session_id' => $sessionId
            ]);
            return false;
        }

        return true;
    }

    /**
     * Valider le format de date (Y-m-d)
     */
    private function validerFormatDate(string $date): bool
    {
        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date);
            return $parsed->format('Y-m-d') === $date;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Valider le format d'heure (H:i:s ou H:i)
     */
    private function validerFormatHeure(string $heure): bool
    {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $heure);
    }

    /**
     * Détecter les changements entre l'ancienne et la nouvelle présence
     */
    private function detecterChangements(Presence $ancienne, array $nouvelles): array
    {
        $changements = [];

        // Normaliser les heures pour la comparaison (assurer le format HH:MM:SS)
        $ancienneArrivee = $this->normaliserHeure($ancienne->heure_arrivee);
        $ancienneDepart = $this->normaliserHeure($ancienne->heure_depart);
        $nouvelleArrivee = $this->normaliserHeure($nouvelles['heure_arrivee']);
        $nouveauDepart = $this->normaliserHeure($nouvelles['heure_depart']);

        Log::debug("🔍 Comparaison heures pour détection changements", [
            'ancienne_arrivee_brute' => $ancienne->heure_arrivee,
            'ancienne_arrivee_normalisee' => $ancienneArrivee,
            'nouvelle_arrivee_brute' => $nouvelles['heure_arrivee'],
            'nouvelle_arrivee_normalisee' => $nouvelleArrivee,
            'ancienne_depart_brute' => $ancienne->heure_depart,
            'ancienne_depart_normalisee' => $ancienneDepart,
            'nouvelle_depart_brute' => $nouvelles['heure_depart'],
            'nouvelle_depart_normalisee' => $nouveauDepart
        ]);

        if ($ancienneArrivee !== $nouvelleArrivee) {
            $changements['heure_arrivee'] = [
                'avant' => $ancienneArrivee,
                'apres' => $nouvelleArrivee
            ];
        }

        if ($ancienneDepart !== $nouveauDepart) {
            $changements['heure_depart'] = [
                'avant' => $ancienneDepart,
                'apres' => $nouveauDepart
            ];
        }

        if ($ancienne->source_pointage !== $nouvelles['source_pointage']) {
            $changements['source_pointage'] = [
                'avant' => $ancienne->source_pointage,
                'apres' => $nouvelles['source_pointage']
            ];
        }

        return $changements;
    }

    /**
     * Normaliser l'heure au format HH:MM:SS pour les comparaisons
     */
    private function normaliserHeure($heure): ?string
    {
        if (is_null($heure)) {
            return null;
        }

        // Si c'est déjà au format HH:MM:SS, on retourne tel quel
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $heure)) {
            return $heure;
        }

        // Si c'est au format HH:MM, on ajoute :00
        if (preg_match('/^\d{2}:\d{2}$/', $heure)) {
            return $heure . ':00';
        }

        // Essayer de parser avec Carbon pour normaliser
        try {
            $carbon = Carbon::parse($heure);
            return $carbon->format('H:i:s');
        } catch (\Exception $e) {
            return (string) $heure;
        }
    }

    /**
     * Mettre à jour les statistiques
     */
    private function mettreAJourStats(array &$stats, array $resultat): void
    {
        switch ($resultat['status']) {
            case 'created':
                $stats['presences_creees']++;
                break;
            case 'updated':
                $stats['presences_mises_a_jour']++;
                break;
            case 'unchanged':
                $stats['ignores']++;
                break;
            case 'ignored':
                $stats['ignores']++;
                break;
            case 'error':
                $stats['erreurs']++;
                break;
        }
    }

    /**
     * Générer un message de résultat lisible
     */
    private function genererMessageResultat(array $stats): string
    {
        $messages = [];
        
        if ($stats['presences_creees'] > 0) {
            $messages[] = "{$stats['presences_creees']} nouvelle(s) présence(s) créée(s)";
        }
        
        if ($stats['presences_mises_a_jour'] > 0) {
            $messages[] = "{$stats['presences_mises_a_jour']} présence(s) mise(s) à jour";
        }
        
        if ($stats['ignores'] > 0) {
            $messages[] = "{$stats['ignores']} pointage(s) ignoré(s)";
        }
        
        if ($stats['erreurs'] > 0) {
            $messages[] = "{$stats['erreurs']} erreur(s)";
        }

        return empty($messages) ? 
            "Aucune modification apportée" : 
            implode(', ', $messages);
    }
} 