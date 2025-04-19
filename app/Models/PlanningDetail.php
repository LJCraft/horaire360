<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PlanningDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'planning_id',
        'jour',
        'heure_debut',
        'heure_fin',
        'jour_entier',
        'jour_repos',
        'note',
    ];

    protected $casts = [
        'jour_entier' => 'boolean',
        'jour_repos' => 'boolean',
    ];

    /**
     * Relation avec le planning
     */
    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }

    /**
     * Obtenir le nom du jour
     */
    public function getNomJourAttribute()
    {
        $jours = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        return $jours[$this->jour] ?? 'Inconnu';
    }

    /**
     * Calculer la durée de travail
     */
    public function getDureeAttribute()
    {
        if ($this->jour_repos) {
            return 'Repos';
        }

        if ($this->jour_entier) {
            return '8h';
        }

        if ($this->heure_debut && $this->heure_fin) {
            $debut = Carbon::parse($this->heure_debut);
            $fin = Carbon::parse($this->heure_fin);
            
            // Si l'heure de fin est avant l'heure de début, on ajoute 24h (pour les horaires de nuit)
            if ($fin->lt($debut)) {
                $fin->addDay();
            }
            
            $minutes = $debut->diffInMinutes($fin);
            $heures = floor($minutes / 60);
            $minutesRestantes = $minutes % 60;
            
            return $heures . 'h' . ($minutesRestantes > 0 ? $minutesRestantes : '');
        }

        return '-';
    }
}