<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        
        $debut = \Carbon\Carbon::parse($this->heure_arrivee);
        $fin = \Carbon\Carbon::parse($this->heure_depart);
        
        // Si l'heure de fin est inférieure à l'heure de début, on ajoute 24h (pour les présences de nuit)
        if ($fin < $debut) {
            $fin->addDay();
        }
        
        return $debut->diffInHours($fin) + ($debut->diffInMinutes($fin) % 60) / 60;
    }
    
    /**
     * Vérifier si l'employé est en retard par rapport à son planning.
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
        
        $jourSemaine = \Carbon\Carbon::parse($this->date)->dayOfWeekIso;
        
        // Récupérer le détail du planning pour ce jour de la semaine
        $planningDetail = $planning->details()
            ->where('jour', $jourSemaine)
            ->first();
            
        if (!$planningDetail || $planningDetail->jour_repos) {
            return false;
        }
        
        $heureArrivee = \Carbon\Carbon::parse($this->heure_arrivee);
        $heureDebutPlanning = \Carbon\Carbon::parse($planningDetail->heure_debut);
        
        // Tolérance de 10 minutes
        $heureDebutAvecTolerance = (clone $heureDebutPlanning)->addMinutes(10);
        
        return $heureArrivee->gt($heureDebutAvecTolerance);
    }
    
    /**
     * Vérifier si l'employé est parti avant l'heure prévue.
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
        
        $jourSemaine = \Carbon\Carbon::parse($this->date)->dayOfWeekIso;
        
        // Récupérer le détail du planning pour ce jour de la semaine
        $planningDetail = $planning->details()
            ->where('jour', $jourSemaine)
            ->first();
            
        if (!$planningDetail || $planningDetail->jour_repos) {
            return false;
        }
        
        $heureDepart = \Carbon\Carbon::parse($this->heure_depart);
        $heureFinPlanning = \Carbon\Carbon::parse($planningDetail->heure_fin);
        
        // Tolérance de 10 minutes
        $heureFinAvecTolerance = (clone $heureFinPlanning)->subMinutes(10);
        
        return $heureDepart->lt($heureFinAvecTolerance);
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
}