<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Poste;

class Service extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'services';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nom',
        'description',
        'code',
        'departement_id',
        'responsable_id',
        'statut'
    ];

    /**
     * Get the department name for this service.
     * 
     * @return string|null
     */
    public function getDepartementAttribute()
    {
        // Récupérer le nom du département à partir de la colonne departement de la table postes
        $poste = Poste::where('departement', $this->departement_id)->first();
        return $poste ? $poste->departement : null;
    }

    /**
     * Get the employees that belong to the service.
     */
    public function employes()
    {
        return $this->hasMany(Employe::class);
    }

    /**
     * Get the service manager.
     */
    public function responsable()
    {
        return $this->belongsTo(Employe::class, 'responsable_id');
    }
}
