<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class StoreLogo extends Controller
{
    /**
     * Enregistre le logo SVG dans le dossier public
     */
    public static function store()
    {
        // Contenu du fichier SVG
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 150">
  <!-- Background circle -->
  <circle cx="75" cy="75" r="60" fill="#4361ee" />
  
  <!-- Clock hands -->
  <line x1="75" y1="75" x2="75" y2="35" stroke="#ffffff" stroke-width="4" stroke-linecap="round" />
  <line x1="75" y1="75" x2="100" y2="90" stroke="#ffffff" stroke-width="4" stroke-linecap="round" />
  
  <!-- Central dot -->
  <circle cx="75" cy="75" r="5" fill="#ffffff" />
  
  <!-- Text -->
  <text x="150" y="85" font-family="Arial, sans-serif" font-size="48" font-weight="bold" fill="#333333">Horaire<tspan fill="#4361ee">360</tspan></text>
  
  <!-- Tagline -->
  <text x="150" y="110" font-family="Arial, sans-serif" font-size="16" fill="#666666">Gestion intelligente des présences</text>
</svg>
SVG;

        // Chemin de destination
        $path = public_path('logo.svg');
        
        // Créer le répertoire si nécessaire
        $directory = dirname($path);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        // Enregistrer le fichier
        File::put($path, $svg);
    }
}