<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poste extends Model
{
    use HasFactory;
    
    protected $fillable = ['nom', 'description', 'departement', 'grades_disponibles'];
    
    /**
     * Relation avec les employés
     */
    public function employes()
    {
        return $this->hasMany(Employe::class);
    }
}