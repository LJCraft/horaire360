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
        'matricule','nom', 'prenom', 'email', 'telephone', 'date_naissance', 'date_embauche',
        'poste_id', 'statut', 'photo_profil', 'utilisateur_id', 'grade_id', 'departement'
    ];

    protected $with = ['grade', 'poste'];

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
     * Accesseur pour le département
     */
    public function getDepartementAttribute()
    {
        return $this->poste ? $this->poste->departement : null;
    }
    
    /**
     * Relation avec le département
     * Cette relation utilise le modèle Departement qui pointe vers la table postes
     */
    public function departement()
    {
        // Utiliser le modèle Departement qui pointe vers la table postes
        return $this->belongsTo(Departement::class, 'departement', 'departement');
    }
    
    /**
     * Relation avec les présences
     */
    public function presences()
    {
        return $this->hasMany(Presence::class);
    }

    /**
     * Relation avec le grade
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
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
     * Relation avec le service (pour compatibilité)
     * Cette relation est ajoutée pour éviter l'erreur "Call to undefined relationship [service]"
     * Elle utilise le département du poste au lieu de faire référence à une table services
     */
    public function service()
    {
        // Retourner une relation vide qui ne fait pas référence à une table services
        return $this->belongsTo(Poste::class, 'poste_id')->select(['id', 'departement as nom']);
    }
    
    /**
     * Accesseur pour le grade calculé (niveau hiérarchique)
     * Utilise la relation grade ou simule un grade si la relation est vide
     */
    public function getGradeCalculeAttribute()
    {
        // Si la relation grade existe, l'utiliser
        if ($this->relationLoaded('grade') && $this->grade) {
            return $this->grade;
        }
        
        // Sinon, calculer le grade en fonction de l'ancienneté
        if ($this->date_embauche) {
            $anciennete = $this->date_embauche->diffInYears(now());
            
            if ($anciennete >= 10) {
                return (object)['nom' => 'Senior'];
            } elseif ($anciennete >= 5) {
                return (object)['nom' => 'Confirmé'];
            } elseif ($anciennete >= 2) {
                return (object)['nom' => 'Intermédiaire'];
            } else {
                return (object)['nom' => 'Junior'];
            }
        }
        
        return (object)['nom' => 'Non défini'];
    }
    
    /**
     * Accesseur pour la fonction (poste)
     */
    public function getFonctionAttribute()
    {
        return $this->poste;
    }
}