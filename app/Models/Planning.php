<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Planning extends Model
{
    use HasFactory;

    protected $fillable = [
        'employe_id',
        'date_debut',
        'date_fin',
        'titre',
        'description',
        'actif',
    ];

    protected $dates = [
        'date_debut',
        'date_fin',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    /**
     * Relation avec l'employé
     */
    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    /**
     * Relation avec les détails du planning
     */
    public function details()
    {
        return $this->hasMany(PlanningDetail::class);
    }

    /**
     * Vérifier si un planning est en cours
     */
    public function estEnCours()
    {
        $today = Carbon::today();
        return $this->date_debut->lte($today) && $this->date_fin->gte($today);
    }

    /**
     * Vérifier si un planning est à venir
     */
    public function estAVenir()
    {
        return $this->date_debut->gt(Carbon::today());
    }

    /**
     * Vérifier si un planning est terminé
     */
    public function estTermine()
    {
        return $this->date_fin->lt(Carbon::today());
    }

    /**
     * Obtenir le statut du planning
     */
    public function getStatutAttribute()
    {
        if ($this->estEnCours()) {
            return 'en_cours';
        } elseif ($this->estAVenir()) {
            return 'a_venir';
        } else {
            return 'termine';
        }
    }

    /**
     * Calculer le nombre total d'heures de travail pour ce planning
     */
    public function calculerHeuresTotales()
    {
        $totalMinutes = 0;

        foreach ($this->details as $detail) {
            if (!$detail->jour_repos && !$detail->jour_entier && $detail->heure_debut && $detail->heure_fin) {
                $debut = Carbon::parse($detail->heure_debut);
                $fin = Carbon::parse($detail->heure_fin);
                
                // Si l'heure de fin est avant l'heure de début, on ajoute 24h (pour les horaires de nuit)
                if ($fin->lt($debut)) {
                    $fin->addDay();
                }
                
                $totalMinutes += $debut->diffInMinutes($fin);
            } elseif ($detail->jour_entier) {
                // Jour entier standard (8h)
                $totalMinutes += 480;
            }
        }

        $heures = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return $heures . 'h' . ($minutes > 0 ? $minutes : '');
    }
}