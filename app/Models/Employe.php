<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Employe extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'matricule', 'nom', 'prenom', 'email', 'telephone',
        'date_naissance', 'date_embauche', 'poste_id',
        'utilisateur_id', 'statut', 'photo_profil'
    ];

    protected $casts = [
        'date_embauche' => 'datetime',
        'date_naissance' => 'datetime',
    ];
    
    protected $dates = ['date_naissance', 'date_embauche', 'created_at', 'updated_at'];
    
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

    /**
     * Récupère l'URL de la photo de profil ou une image par défaut
     */
    public function getPhotoProfilUrlAttribute()
    {
        if (!empty($this->photo_profil) && file_exists(public_path('storage/photos/' . $this->photo_profil))) {
            return asset('storage/photos/' . $this->photo_profil);
        }
        
        // Retourne une image par défaut basée sur les initiales du prénom et du nom
        $initialPrenom = strtoupper(substr($this->prenom, 0, 1));
        $initialNom = strtoupper(substr($this->nom, 0, 1));
        $initials = $initialPrenom . $initialNom;
        return "https://ui-avatars.com/api/?name={$initials}&background=random&color=fff&size=256";
    }
}