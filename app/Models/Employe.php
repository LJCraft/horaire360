<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Employe extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'nom', 'prenom', 'email', 'telephone', 'date_naissance', 'date_embauche',
        'poste_id', 'statut', 'photo_profil', 'user_id', 'grade', 'departement'
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
        if (!empty($this->photo_profil)) {
            $photoPath = public_path('storage'.DIRECTORY_SEPARATOR.'photos'.DIRECTORY_SEPARATOR.$this->photo_profil);
            if (file_exists($photoPath)) {
                // Retourne toujours un URL avec des slashes pour le navigateur
                return asset('storage/photos/' . $this->photo_profil);
            }
        }
        
        // Retourne une image par défaut basée sur les initiales du prénom et du nom
        $initialPrenom = strtoupper(substr($this->prenom, 0, 1));
        $initialNom = strtoupper(substr($this->nom, 0, 1));
        $initials = $initialPrenom . $initialNom;
        return "https://ui-avatars.com/api/?name={$initials}&background=random&color=fff&size=256";
    }
    
    /**
     * Accesseur pour le service (département)
     * Utilise la colonne departement ou simule un service si la colonne est vide
     */
    public function getServiceAttribute()
    {
        // Si le département est défini, l'utiliser
        if (!empty($this->departement)) {
            return (object)[
                'nom' => $this->departement
            ];
        }
        
        // Sinon, attribuer un service fictif basé sur l'ID du poste
        $services = [
            'Ressources Humaines',
            'Informatique',
            'Finance',
            'Marketing',
            'Opérations'
        ];
        
        $index = ($this->poste_id ?? 1) % count($services);
        
        return (object)[
            'nom' => $services[$index]
        ];
    }
    
    /**
     * Accesseur pour le grade (niveau hiérarchique)
     * Utilise la colonne grade ou simule un grade si la colonne est vide
     */
    public function getGradeAttribute($value)
    {
        // Si le grade est déjà défini dans la base de données, l'utiliser
        if (!empty($value)) {
            return $value;
        }
        
        // Sinon, calculer le grade en fonction de l'ancienneté
        if ($this->date_embauche) {
            $anciennete = $this->date_embauche->diffInYears(now());
            
            if ($anciennete >= 10) {
                return 'Senior';
            } elseif ($anciennete >= 5) {
                return 'Confirmé';
            } elseif ($anciennete >= 2) {
                return 'Intermédiaire';
            } else {
                return 'Junior';
            }
        }
        
        return 'Non défini';
    }
    
    /**
     * Accesseur pour la fonction (poste)
     */
    public function getFonctionAttribute()
    {
        return $this->poste;
    }
}