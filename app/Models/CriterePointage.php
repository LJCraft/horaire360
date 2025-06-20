<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CriterePointage extends Model
{
    use HasFactory;

    protected $table = 'criteres_pointage';

    protected $fillable = [
        'niveau', // 'individuel' ou 'departemental'
        'employe_id', // si niveau = 'individuel'
        'departement_id', // si niveau = 'departemental'
        'date_debut',
        'date_fin',
        'nombre_pointages', // 1 ou 2
        'tolerance_avant', // en minutes
        'tolerance_apres', // en minutes
        'duree_pause', // en minutes
        'source_pointage', // 'biometrique', 'manuel', 'tous'
        'calcul_heures_sup', // boolean
        'seuil_heures_sup', // en minutes
        'actif',
        'created_by',
        'priorite', // 1 (haute) à 3 (basse)
    ];

    protected $appends = ['periode_calculee'];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'nombre_pointages' => 'integer',
        'tolerance_avant' => 'integer',
        'tolerance_apres' => 'integer',
        'duree_pause' => 'integer',
        'calcul_heures_sup' => 'boolean',
        'seuil_heures_sup' => 'integer',
        'actif' => 'boolean',
        'priorite' => 'integer',
    ];

    /**
     * Relation avec l'employé
     */
    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    /**
     * Relation avec le département
     */
    public function departement()
    {
        return $this->belongsTo(Departement::class, 'departement_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé le critère
     */
    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Génère automatiquement la période à partir des dates
     */
    public function getPeriodeCalculeeAttribute()
    {
        if (!$this->date_debut || !$this->date_fin) {
            return 'Période non définie';
        }

        $debut = Carbon::parse($this->date_debut);
        $fin = Carbon::parse($this->date_fin);

        // Même jour
        if ($debut->isSameDay($fin)) {
            return $debut->format('d/m/Y');
        }

        // Même semaine
        if ($debut->weekOfYear === $fin->weekOfYear && $debut->year === $fin->year) {
            return 'Semaine du ' . $debut->format('d/m/Y');
        }

        // Même mois
        if ($debut->month === $fin->month && $debut->year === $fin->year) {
            if ($debut->day === 1 && $fin->day === $debut->daysInMonth) {
                // Mois complet
                return $debut->locale('fr')->format('F Y');
            } else {
                // Partie du mois
                return 'Du ' . $debut->format('d') . ' au ' . $fin->format('d') . ' ' . $debut->locale('fr')->format('F Y');
            }
        }

        // Même année
        if ($debut->year === $fin->year) {
            return 'Du ' . $debut->locale('fr')->format('d M') . ' au ' . $fin->locale('fr')->format('d M Y');
        }

        // Années différentes
        return 'Du ' . $debut->locale('fr')->format('d M Y') . ' au ' . $fin->locale('fr')->format('d M Y');
    }

    /**
     * Alias pour la compatibilité
     */
    public function getPeriodeAttribute()
    {
        return $this->periode_calculee;
    }

    /**
     * Vérifier si le critère est applicable à un employé spécifique
     */
    public function estApplicableA(Employe $employe)
    {
        if ($this->niveau === 'individuel') {
            return $this->employe_id === $employe->id;
        } elseif ($this->niveau === 'departemental') {
            return $employe->departement_id === $this->departement_id;
        }
        
        return false;
    }

    /**
     * Vérifier si le critère est applicable à une date spécifique
     */
    public function estApplicableDate($date)
    {
        $date = Carbon::parse($date);
        return $date->between($this->date_debut, $this->date_fin);
    }

    /**
     * Obtenir le critère applicable pour un employé et une date spécifiques
     * 
     * @param Employe $employe L'employé pour lequel rechercher un critère
     * @param string|Carbon $date La date pour laquelle rechercher un critère
     * @param string $sourcePointage Source du pointage ('biometrique', 'manuel', 'tous')
     * @return CriterePointage|null
     */
    public static function getCritereApplicable(Employe $employe, $date, $sourcePointage = 'tous')
    {
        $date = Carbon::parse($date);
        
        // Chercher d'abord un critère individuel spécifique à la source de pointage
        $critereIndividuel = self::where('niveau', 'individuel')
            ->where('employe_id', $employe->id)
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->where('actif', true)
            ->where(function($query) use ($sourcePointage) {
                $query->where('source_pointage', $sourcePointage)
                      ->orWhere('source_pointage', 'tous');
            })
            ->orderBy('source_pointage', 'asc') // Priorité aux critères spécifiques à la source
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($critereIndividuel) {
            return $critereIndividuel;
        }
        
        // Sinon, chercher un critère départemental
        if ($employe->departement_id) {
            $critereDepartemental = self::where('niveau', 'departemental')
                ->where('departement_id', $employe->departement_id)
                ->where('date_debut', '<=', $date)
                ->where('date_fin', '>=', $date)
                ->where('actif', true)
                ->where(function($query) use ($sourcePointage) {
                    $query->where('source_pointage', $sourcePointage)
                          ->orWhere('source_pointage', 'tous');
                })
                ->orderBy('source_pointage', 'asc') // Priorité aux critères spécifiques à la source
                ->orderBy('created_at', 'desc')
                ->first();
                
            if ($critereDepartemental) {
                return $critereDepartemental;
            }
        }
        
        // Si aucun critère spécifique n'est trouvé, chercher un critère 'tous'
        if ($sourcePointage !== 'tous') {
            return self::getCritereApplicable($employe, $date, 'tous');
        }
        
        return null;
    }
}
