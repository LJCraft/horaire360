<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Poste;
use Illuminate\Database\Eloquent\Builder;

class Departement extends Model
{
    use HasFactory;

    /**
     * Indique si le modèle doit être horodaté.
     *
     * @var bool
     */
    public $timestamps = false;
    
    /**
     * Indique le nom de la table associée au modèle.
     * Nous utilisons la table postes au lieu d'une table departements.
     *
     * @var string
     */
    protected $table = 'postes';
    
    /**
     * Indique la clé primaire de la table.
     *
     * @var string
     */
    protected $primaryKey = 'departement';
    
    /**
     * Indique si la clé primaire est auto-incrémentée.
     *
     * @var bool
     */
    public $incrementing = false;
    
    /**
     * Le type de la clé primaire.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Initialiser le modèle avec les événements de boot
     */
    protected static function boot()
    {
        parent::boot();
        
        // Ajouter un scope global pour ne sélectionner que les départements uniques
        static::addGlobalScope('uniqueDepartements', function (Builder $builder) {
            $builder->select('departement as id', 'departement as nom')
                   ->whereNotNull('departement')
                   ->where('departement', '!=', '')
                   ->distinct();
        });
    }
    
    /**
     * Méthode statique pour trouver un département par son ID
     * Cette méthode utilise la table postes pour récupérer les départements
     *
     * @param mixed $id
     * @return Departement|null
     */
    public static function find($id)
    {
        // Récupérer le département à partir de la table postes
        return static::withoutGlobalScope('uniqueDepartements')
            ->select('departement as id', 'departement as nom')
            ->where('departement', $id)
            ->first();
    }
    
    /**
     * Méthode statique pour récupérer tous les départements
     * Cette méthode utilise la table postes pour récupérer les départements
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function all($columns = ['*'])
    {
        // Le scope global 'uniqueDepartements' s'applique automatiquement
        return static::get();
    }
    
    /**
     * Méthode pour vérifier si un département existe
     * Cette méthode utilise la table postes pour vérifier l'existence d'un département
     *
     * @param mixed $id
     * @return bool
     */
    public static function exists($id)
    {
        return static::withoutGlobalScope('uniqueDepartements')
            ->where('departement', $id)
            ->exists();
    }
    
    /**
     * Méthode pour compter le nombre d'enregistrements
     * Cette méthode est nécessaire pour les requêtes de type whereHas
     *
     * @param mixed $id
     * @return int
     */
    public static function count()
    {
        return static::get()->count();
    }

    /**
     * Get the employees that belong to the department.
     */
    public function employes()
    {
        return $this->hasMany(Employe::class, 'departement', 'departement');
    }

    /**
     * Get the services that belong to the department.
     */
    public function services()
    {
        return $this->hasMany(Service::class, 'departement_id', 'departement');
    }

    /**
     * Get the department manager.
     */
    public function responsable()
    {
        return $this->belongsTo(Employe::class, 'responsable_id', 'id');
    }
    
    /**
     * Get the postes that belong to this department.
     */
    public function postes()
    {
        return $this->hasMany(Poste::class, 'departement', 'departement');
    }
}
