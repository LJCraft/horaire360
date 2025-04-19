<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    /**
     * Relation avec le rôle
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    
    /**
     * Relation avec l'employé
     */
    public function employe()
    {
        return $this->hasOne(Employe::class, 'utilisateur_id');
    }
    
    /**
     * Vérifier si l'utilisateur est administrateur
     */
    public function isAdmin()
    {
        return $this->role && $this->role->nom === 'Administrateur';
    }
}