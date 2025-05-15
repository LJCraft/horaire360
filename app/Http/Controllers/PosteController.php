<?php

namespace App\Http\Controllers;

use App\Models\Poste;
use Illuminate\Http\Request;

class PosteController extends Controller
{
    /**
     * Constructeur avec middleware d'authentification
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Afficher la liste des postes
     */
    public function index()
    {
        $postes = Poste::withCount('employes')->get();
        return view('postes.index', compact('postes'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        return view('postes.create');
    }

    /**
     * Enregistrer un nouveau poste
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255|unique:postes',
            'description' => 'nullable|string',
            'departement' => 'required|string|max:255',
        ]);
        
        Poste::create($request->all());
        
        return redirect()->route('postes.index')
            ->with('success', 'Poste créé avec succès.');
    }

    /**
     * Afficher le formulaire de modification
     */
    public function edit(Poste $poste)
    {
        return view('postes.edit', compact('poste'));
    }

    /**
     * Mettre à jour un poste
     */
    public function update(Request $request, Poste $poste)
    {
        $request->validate([
            'nom' => 'required|string|max:255|unique:postes,nom,' . $poste->id,
            'description' => 'nullable|string',
            'departement' => 'required|string|max:255',
        ]);
        
        $poste->update($request->all());
        
        return redirect()->route('postes.index')
            ->with('success', 'Poste modifié avec succès.');
    }

    /**
     * Supprimer un poste
     */
    public function destroy(Poste $poste)
    {
        // Vérifier si des employés sont associés à ce poste
        if ($poste->employes()->count() > 0) {
            return redirect()->route('postes.index')
                ->with('error', 'Impossible de supprimer ce poste car il est associé à des employés.');
        }
        
        $poste->delete();
        
        return redirect()->route('postes.index')
            ->with('success', 'Poste supprimé avec succès.');
    }
    
    /**
     * Récupérer les postes par département (API)
     */
    public function getPostesByDepartement(Request $request)
    {
        $departement = $request->input('departement');
        
        if (!$departement) {
            return response()->json([]);
        }
        
        $postes = Poste::where('departement', $departement)
            ->orderBy('nom')
            ->get(['id', 'nom']);
            
        return response()->json($postes);
    }
}