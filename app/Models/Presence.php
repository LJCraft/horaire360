<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Presence extends Model
{
    use HasFactory;
    
    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employe_id',
        'date',
        'heure_arrivee',
        'heure_depart',
        'retard',
        'depart_anticipe',
        'commentaire',
        'meta_data',
        'source_pointage', // 'biometrique' ou 'manuel'
        'heures_prevues',
        'heures_faites',
        'heures_supplementaires',
    ];
    
    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'heure_arrivee' => 'datetime:H:i',
        'heure_depart' => 'datetime:H:i',
        'retard' => 'boolean',
        'depart_anticipe' => 'boolean',
        'meta_data' => 'json',
        'heures_prevues' => 'float',
        'heures_faites' => 'float',
        'heures_supplementaires' => 'integer',
    ];
    
    /**
     * Obtenir l'employé associé à cette présence.
     */
    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
    
    /**
     * Calculer la durée de la présence en heures.
     */
    public function getDureeAttribute()
    {
        if (!$this->heure_depart) {
            return null;
        }
        
        $debut = Carbon::parse($this->heure_arrivee);
        $fin = Carbon::parse($this->heure_depart);
        
        // Si l'heure de fin est inférieure à l'heure de début, on ajoute 24h (pour les présences de nuit)
        if ($fin < $debut) {
            $fin->addDay();
        }
        
        return $debut->diffInHours($fin) + ($debut->diffInMinutes($fin) % 60) / 60;
    }
    
    /**
     * Vérifier si l'employé est en retard par rapport à son planning en utilisant les critères configurés.
     *
     * @return bool
     */
    public function determinerRetard()
    {
        // Rechercher le planning pour cette date et cet employé
        $planning = Planning::where('employe_id', $this->employe_id)
            ->where('date_debut', '<=', $this->date)
            ->where('date_fin', '>=', $this->date)
            ->where('statut', 'actif')
            ->first();
        
        if (!$planning) {
            return false;
        }
        
        $jourSemaine = Carbon::parse($this->date)->dayOfWeekIso;
        
        // Récupérer le détail du planning pour ce jour de la semaine
        $planningDetail = $planning->details()
            ->where('jour', $jourSemaine)
            ->first();
            
        if (!$planningDetail || $planningDetail->jour_repos) {
            return false;
        }
        
        // Récupérer les critères de pointage applicables
        $employe = Employe::find($this->employe_id);
        $critere = CriterePointage::getCritereApplicable($employe, $this->date);
        
        // Utiliser les critères configurés ou les valeurs par défaut
        $toleranceAvant = $critere ? $critere->tolerance_avant : 10;
        $toleranceApres = $critere ? $critere->tolerance_apres : 10;
        $nombrePointages = $critere ? $critere->nombre_pointages : 2;
        
        $heureArrivee = Carbon::parse($this->heure_arrivee);
        $heureDebutPlanning = Carbon::parse($planningDetail->heure_debut);
        
        if ($nombrePointages == 1) {
            // Si un seul pointage est requis, on vérifie que le pointage est dans la plage
            // [heure début - tolérance] -> [heure fin + tolérance]
            $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);
            $debutPlage = (clone $heureDebutPlanning)->subMinutes($toleranceAvant);
            $finPlage = (clone $heureFinPlanning)->addMinutes($toleranceApres);
            
            return !($heureArrivee->gte($debutPlage) && $heureArrivee->lte($finPlage));
        } else {
            // Si deux pointages sont requis, on vérifie que l'arrivée est dans la plage
            // [heure début - tolérance] -> [heure début + tolérance]
            $debutPlage = (clone $heureDebutPlanning)->subMinutes($toleranceAvant);
            $finPlage = (clone $heureDebutPlanning)->addMinutes($toleranceApres);
            
            return !($heureArrivee->gte($debutPlage) && $heureArrivee->lte($finPlage));
        }
    }
    
    /**
     * Vérifier si l'employé est parti avant l'heure prévue en utilisant les critères configurés.
     *
     * @return bool
     */
    public function determinerDepartAnticipe()
    {
        if (!$this->heure_depart) {
            return false;
        }
        
        // Rechercher le planning pour cette date et cet employé
        $planning = Planning::where('employe_id', $this->employe_id)
            ->where('date_debut', '<=', $this->date)
            ->where('date_fin', '>=', $this->date)
            ->where('statut', 'actif')
            ->first();
        
        if (!$planning) {
            return false;
        }
        
        $jourSemaine = Carbon::parse($this->date)->dayOfWeekIso;
        
        // Récupérer le détail du planning pour ce jour de la semaine
        $planningDetail = $planning->details()
            ->where('jour', $jourSemaine)
            ->first();
            
        if (!$planningDetail || $planningDetail->jour_repos) {
            return false;
        }
        
        // Récupérer les critères de pointage applicables
        $employe = Employe::find($this->employe_id);
        $critere = CriterePointage::getCritereApplicable($employe, $this->date);
        
        // Utiliser les critères configurés ou les valeurs par défaut
        $toleranceAvant = $critere ? $critere->tolerance_avant : 10;
        $toleranceApres = $critere ? $critere->tolerance_apres : 10;
        $nombrePointages = $critere ? $critere->nombre_pointages : 2;
        
        // Si un seul pointage est requis, pas de départ anticipé
        if ($nombrePointages == 1) {
            return false;
        }
        
        $heureDepart = Carbon::parse($this->heure_depart);
        $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);
        
        // Pour deux pointages, on vérifie que le départ est dans la plage
        // [heure fin - tolérance] -> [heure fin + tolérance]
        $debutPlage = (clone $heureFinPlanning)->subMinutes($toleranceApres);
        $finPlage = (clone $heureFinPlanning)->addMinutes($toleranceApres);
        
        return !($heureDepart->gte($debutPlage) && $heureDepart->lte($finPlage));
    }
    
    /**
     * Calculer les heures prévues et les heures faites selon les critères configurés
     */
    public function calculerHeures()
    {
        // Rechercher le planning pour cette date et cet employé
        $planning = Planning::where('employe_id', $this->employe_id)
            ->where('date_debut', '<=', $this->date)
            ->where('date_fin', '>=', $this->date)
            ->where('statut', 'actif')
            ->first();
        
        if (!$planning) {
            return [
                'heures_prevues' => 0,
                'heures_faites' => 0
            ];
        }
        
        $jourSemaine = Carbon::parse($this->date)->dayOfWeekIso;
        
        // Récupérer le détail du planning pour ce jour de la semaine
        $planningDetail = $planning->details()
            ->where('jour', $jourSemaine)
            ->first();
            
        if (!$planningDetail || $planningDetail->jour_repos) {
            return [
                'heures_prevues' => 0,
                'heures_faites' => 0
            ];
        }
        
        // Récupérer les critères de pointage applicables
        $employe = Employe::find($this->employe_id);
        $critere = CriterePointage::getCritereApplicable($employe, $this->date);
        
        // Utiliser les critères configurés ou les valeurs par défaut
        $duree_pause = $critere ? $critere->duree_pause : 0;
        $nombrePointages = $critere ? $critere->nombre_pointages : 2;
        
        $heureDebutPlanning = Carbon::parse($planningDetail->heure_debut);
        $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);
        
        // Calculer la durée prévue (en heures)
        $dureeMinutes = $heureDebutPlanning->diffInMinutes($heureFinPlanning);
        if ($heureFinPlanning->lt($heureDebutPlanning)) {
            $dureeMinutes = 1440 - $dureeMinutes; // 24h = 1440 minutes
        }
        $dureeMinutes -= $duree_pause;
        $heuresPrevues = $dureeMinutes / 60;
        
        // Calculer les heures faites
        $heuresFaites = 0;
        
        if ($nombrePointages == 1) {
            // Si un seul pointage, les heures faites = heures prévues si présent
            $heuresFaites = $this->retard || $this->depart_anticipe ? 0 : $heuresPrevues;
        } else {
            // Si deux pointages, calculer la durée réelle
            if ($this->heure_arrivee && $this->heure_depart && !$this->retard && !$this->depart_anticipe) {
                $debut = Carbon::parse($this->heure_arrivee);
                $fin = Carbon::parse($this->heure_depart);
                
                if ($fin->lt($debut)) {
                    $fin->addDay();
                }
                
                $dureeReelleMinutes = $debut->diffInMinutes($fin) - $duree_pause;
                $heuresFaites = $dureeReelleMinutes / 60;
            }
        }
        
        return [
            'heures_prevues' => round($heuresPrevues, 2),
            'heures_faites' => round($heuresFaites, 2)
        ];
    }
    
    /**
     * Déterminer si la présence est valide selon les critères configurés
     */
    public function estValide()
    {
        // Récupérer les critères de pointage applicables
        $employe = Employe::find($this->employe_id);
        $critere = CriterePointage::getCritereApplicable($employe, $this->date);
        
        // Utiliser les critères configurés ou les valeurs par défaut
        $nombrePointages = $critere ? $critere->nombre_pointages : 2;
        
        if ($nombrePointages == 1) {
            // Pour un seul pointage, la présence est valide si l'employé n'est pas en retard
            return !$this->retard;
        } else {
            // Pour deux pointages, la présence est valide si l'employé n'est ni en retard ni parti en avance
            return !$this->retard && !$this->depart_anticipe && $this->heure_depart;
        }
    }
    
    /**
     * Récupérer les informations de localisation d'arrivée
     */
    public function getLocationArriveeAttribute()
    {
        $metaData = json_decode($this->meta_data, true);
        return $metaData['location'] ?? null;
    }
    
    /**
     * Récupérer les informations de localisation de départ
     */
    public function getLocationDepartAttribute()
    {
        $metaData = json_decode($this->meta_data, true);
        return $metaData['checkout']['location'] ?? null;
    }
    
    /**
     * Récupérer le score de confiance biométrique d'arrivée
     */
    public function getScoreBiometriqueArriveeAttribute()
    {
        $metaData = json_decode($this->meta_data, true);
        return $metaData['biometric_verification']['confidence_score'] ?? null;
    }
    
    /**
     * Vérifier si le pointage est biométrique
     */
    public function estBiometrique()
    {
        return $this->source_pointage === 'biometrique';
    }
    
    /**
     * Récupérer les critères de pointage appliqués
     */
    public function getCriteresAppliques()
    {
        $employe = Employe::find($this->employe_id);
        $sourcePointage = $this->source_pointage ?? 'manuel';
        
        // Rechercher d'abord un critère spécifique à la source de pointage
        $critereSpecifique = CriterePointage::where(function($query) use ($employe) {
            $query->where(function($q) use ($employe) {
                $q->where('niveau', 'individuel')
                  ->where('employe_id', $employe->id);
            })->orWhere(function($q) use ($employe) {
                $q->where('niveau', 'departemental')
                  ->where('departement_id', $employe->departement_id);
            });
        })
        ->where('date_debut', '<=', $this->date)
        ->where('date_fin', '>=', $this->date)
        ->where('actif', true)
        ->where(function($query) use ($sourcePointage) {
            $query->where('source_pointage', $sourcePointage)
                  ->orWhere('source_pointage', 'tous');
        })
        ->orderBy('source_pointage', 'asc') // Priorité aux critères spécifiques à la source
        ->orderBy('niveau', 'asc') // Priorité au niveau individuel
        ->orderBy('created_at', 'desc')
        ->first();
        
        return $critereSpecifique ?: CriterePointage::getCritereApplicable($employe, $this->date);
    }
}