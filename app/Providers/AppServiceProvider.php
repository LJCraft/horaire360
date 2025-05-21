<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use App\Models\Poste;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         Schema::defaultStringLength(191);
        // Ajouter un validateur personnalisé pour vérifier l'existence d'un département
        Validator::extend('departement_exists', function ($attribute, $value, $parameters, $validator) {
            // Vérifier si le département existe dans la table postes
            return Poste::where('departement', $value)->exists();
        }, 'Le département sélectionné n\'existe pas.');
        
        // Intercepter les requêtes de validation qui vérifient l'existence d'un ID dans la table departements
        Validator::extendImplicit('exists_departement', function ($attribute, $value, $parameters, $validator) {
            // Vérifier si le département existe dans la table postes
            return Poste::where('departement', $value)->exists();
        }, 'Le département sélectionné n\'existe pas.');
        
        // Remplacer la règle exists:departements,id par notre validateur personnalisé
        Validator::replacer('exists', function ($message, $attribute, $rule, $parameters) {
            if (isset($parameters[0]) && $parameters[0] === 'departements') {
                return str_replace(':attribute', $attribute, 'Le département sélectionné n\'existe pas.');
            }
            
            return $message;
        });
    }
}
