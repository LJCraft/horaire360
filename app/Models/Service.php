<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Get the department that owns the service.
     */
    public function departement()
    {
        return $this->belongsTo(Departement::class);
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
