<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Employe extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'matricule', 'nom', 'prenom', 'email', 'telephone',
        'date_naissance', 'date_embauche', 'poste_id',
        'utilisateur_id', 'statut'
    ];

    // protected $casts = [
    //     'date_embauche' => 'datetime',
    // ];
    
    protected $dates = ['date_naissance', 'date_embauche'];
    
    /**
     * Relation avec le poste
     */
    public function poste()
    {
        return $this->belongsTo(Poste::class);
    }
    
    /**
     * Relation avec l'utilisateur
     */
    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
    
    /**
     * Relation avec les plannings
     */
    public function plannings()
    {
        return $this->hasMany(Planning::class);
    }
    
    /**
     * Relation avec les présences
     */
    public function presences()
    {
        return $this->hasMany(Presence::class);
    }
    
    /**
     * Nom complet de l'employé
     */
    public function getNomCompletAttribute()
    {
        return $this->prenom . ' ' . $this->nom;
    }

}