<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'departements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nom',
        'description',
        'code',
        'responsable_id',
        'statut'
    ];

    /**
     * Get the employees that belong to the department.
     */
    public function employes()
    {
        return $this->hasMany(Employe::class);
    }

    /**
     * Get the services that belong to the department.
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get the department manager.
     */
    public function responsable()
    {
        return $this->belongsTo(Employe::class, 'responsable_id');
    }
}
