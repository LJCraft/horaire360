<?php

namespace App\Http\Controllers;

use App\Models\Poste;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            'grades' => 'nullable|array',
        ]);
        
        $data = $request->all();
        
        // Convertir les grades sélectionnés en JSON pour le stockage
        if ($request->has('grades')) {
            $data['grades_disponibles'] = json_encode($request->grades);
        }
        
        Poste::create($data);
        
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
            'grades' => 'nullable|array',
        ]);
        
        $data = $request->all();
        
        // Convertir les grades sélectionnés en JSON pour le stockage
        if ($request->has('grades')) {
            $data['grades_disponibles'] = json_encode($request->grades);
        } else {
            $data['grades_disponibles'] = null; // Aucun grade sélectionné
        }
        
        $poste->update($data);
        
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
     * Récupérer les grades disponibles pour un poste (API)
     */
    public function getGradesDisponibles(Poste $poste)
    {
        // Désactiver temporairement le débogueur Laravel pour éviter les réponses HTML en cas d'erreur
        config(['app.debug' => false]);
        
        // Journalisation pour le débogage
        \Illuminate\Support\Facades\Log::info('Récupération des grades pour le poste ID: ' . $poste->id);
        \Illuminate\Support\Facades\Log::info('Valeur brute de grades_disponibles: ' . ($poste->grades_disponibles ?: 'null'));
        
        // Initialiser un tableau vide par défaut
        $grades = [];
        
        try {
            // Vérifier si le champ grades_disponibles existe et n'est pas vide
            if (!empty($poste->grades_disponibles)) {
                // Essayer de décoder le JSON
                $decodedGrades = json_decode($poste->grades_disponibles, true);
                
                // Vérifier s'il y a eu une erreur lors du décodage JSON
                if (json_last_error() !== JSON_ERROR_NONE) {
                    \Illuminate\Support\Facades\Log::error('Erreur de décodage JSON: ' . json_last_error_msg() . ' pour la valeur: ' . $poste->grades_disponibles);
                } else {
                    // Si le décodage a réussi, vérifier si c'est un tableau
                    if (is_array($decodedGrades)) {
                        $grades = $decodedGrades;
                    } elseif ($decodedGrades !== null) {
                        // Si ce n'est pas un tableau mais une valeur valide, la mettre dans un tableau
                        $grades = [$decodedGrades];
                    }
                }
            }
            
            // Filtrer les valeurs null ou vides du tableau
            $grades = array_filter($grades, function($grade) {
                return !empty($grade);
            });
            
            // Convertir en tableau indexé pour JSON
            $grades = array_values($grades);
            
            // Journalisation des grades trouvés
            \Illuminate\Support\Facades\Log::info('Grades trouvés: ' . count($grades) . ' - ' . json_encode($grades));
            
            // Retourner une réponse JSON valide avec les en-têtes appropriés
            return response()->json([
                'grades' => $grades,
                'count' => count($grades),
                'poste_id' => $poste->id,
                'poste_nom' => $poste->nom
            ])->header('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            // Journaliser l'erreur
            \Illuminate\Support\Facades\Log::error('Exception lors de la récupération des grades: ' . $e->getMessage());
            
            // Retourner une réponse d'erreur avec un tableau vide pour les grades
            return response()->json([
                'error' => 'Erreur lors de la récupération des grades: ' . $e->getMessage(),
                'grades' => [],
                'poste_id' => $poste->id
            ], 200)->header('Content-Type', 'application/json'); // Code 200 pour éviter les erreurs côté client
        }
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