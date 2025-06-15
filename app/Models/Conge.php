<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conge extends Model
{
    use HasFactory;
    
    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employe_id',
        'date_debut',
        'date_fin',
        'type',
        'motif',
        'statut',
        'commentaire_reponse',
        'traite_par',
    ];
    
    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];
    
    /**
     * Obtenir l'employé associé à ce congé.
     */
    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
    
    /**
     * Obtenir l'utilisateur qui a traité la demande.
     */
    public function traitePar()
    {
        return $this->belongsTo(Utilisateur::class, 'traite_par');
    }
    
    /**
     * Obtenir le nombre de jours de congé.
     */
    public function getNombreJoursAttribute()
    {
        return $this->date_debut->diffInDays($this->date_fin) + 1;
    }
    
    /**
     * Obtenir le type de congé en format lisible.
     */
    public function getTypeLibelleAttribute()
    {
        switch ($this->type) {
            case 'conge_paye':
                return 'Congé payé';
            case 'maladie':
                return 'Maladie';
            case 'sans_solde':
                return 'Sans solde';
            case 'autre':
                return 'Autre';
            default:
                return $this->type;
        }
    }
    
    /**
     * Obtenir le statut en format lisible.
     */
    public function getStatutLibelleAttribute()
    {
        switch ($this->statut) {
            case 'en_attente':
                return 'En attente';
            case 'approuve':
                return 'Approuvé';
            case 'refuse':
                return 'Refusé';
            default:
                return $this->statut;
        }
    }
}