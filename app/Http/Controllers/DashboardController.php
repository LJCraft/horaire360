<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employe;
use App\Models\Poste;
use App\Models\Presence;
use App\Models\Planning;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Constructeur avec middleware d'authentification
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Afficher le tableau de bord approprié selon le rôle de l'utilisateur
     */
    public function index(Request $request)
    {
        if (auth()->user()->isAdmin()) {
            return $this->adminDashboard();
        }
        
        return $this->employeDashboard();
    }
    
    /**
     * Tableau de bord administrateur
     */
    private function adminDashboard()
    {
        // Statistiques générales
        $stats = [
            'employes' => Employe::count(),
            'employes_actifs' => Employe::where('statut', 'actif')->count(),
            'postes' => Poste::count(),
            'nouveaux' => Employe::where('date_embauche', '>=', Carbon::now()->subDays(30))->count(),
        ];
        
        // Répartition des employés par poste
        $postes = Poste::withCount('employes')->get();
        
        // Employés récemment ajoutés
        $recent_employes = Employe::with('poste')
                             ->orderBy('created_at', 'desc')
                             ->limit(5)
                             ->get();
        
        return view('dashboard.admin', compact('stats', 'postes', 'recent_employes'));
    }
    
    /**
     * Tableau de bord employé
     */
    private function employeDashboard()
    {
        // Récupérer l'employé associé à l'utilisateur connecté
        $employe = auth()->user()->employe;
        
        if (!$employe) {
            return view('dashboard.employe', ['employe' => null]);
        }
        
        // Récupérer le planning courant de l'employé
        $planning = Planning::where('employe_id', $employe->id)
                          ->where('date_debut', '<=', Carbon::now())
                          ->where('date_fin', '>=', Carbon::now())
                          ->first();
        
        // Récupérer les présences récentes
        $presences = Presence::where('employe_id', $employe->id)
                           ->orderBy('date', 'desc')
                           ->limit(5)
                           ->get();
        
        return view('dashboard.employe', compact('employe', 'planning', 'presences'));
    }
}