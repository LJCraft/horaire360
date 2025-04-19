<?php

namespace App\Http\Controllers;

use App\Models\Employe;
use App\Models\Poste;
use App\Models\User;
use App\Imports\EmployesImport;
use App\Exports\EmployesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class EmployeController extends Controller
{
    /**
     * Constructeur avec middleware d'authentification
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Afficher la liste des employés
     */
    public function index(Request $request)
    {
        $query = Employe::with('poste');
        
        // Filtres
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('prenom', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('matricule', 'like', "%$search%");
            });
        }
        
        if ($request->has('poste_id') && $request->poste_id) {
            $query->where('poste_id', $request->poste_id);
        }
        
        if ($request->has('statut') && $request->statut) {
            $query->where('statut', $request->statut);
        }
        
        // Tri
        $sortField = $request->input('sort', 'nom');
        $sortDirection = $request->input('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $employes = $query->paginate(10);
        $postes = Poste::all();
        
        return view('employes.index', compact('employes', 'postes'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $postes = Poste::all();
        return view('employes.create', compact('postes'));
    }

    /**
     * Enregistrer un nouvel employé
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:employes,email',
            'telephone' => 'nullable|string|max:20',
            'date_naissance' => 'nullable|date',
            'date_embauche' => 'required|date',
            'poste_id' => 'required|exists:postes,id',
            'create_user' => 'boolean',
        ]);
        
        // Génération du matricule (préfixe EMP + 5 chiffres)
        $lastEmploye = Employe::latest()->first();
        $lastId = $lastEmploye ? $lastEmploye->id + 1 : 1;
        $matricule = 'EMP' . str_pad($lastId, 5, '0', STR_PAD_LEFT);
        
        $employe = new Employe([
            'matricule' => $matricule,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'date_naissance' => $request->date_naissance,
            'date_embauche' => $request->date_embauche,
            'poste_id' => $request->poste_id,
            'statut' => 'actif',
        ]);
        
        // Création d'un compte utilisateur si demandé
        if ($request->create_user) {
            $password = Str::random(10);
            $user = User::create([
                'name' => $request->prenom . ' ' . $request->nom,
                'email' => $request->email,
                'password' => Hash::make($password),
                'role_id' => 2, // Rôle Employé
            ]);
            
            $employe->utilisateur_id = $user->id;
            
            // Ici, vous pourriez envoyer un email avec les identifiants
            // Mail::to($request->email)->send(new NouveauCompte($user, $password));
        }
        
        $employe->save();
        
        return redirect()->route('employes.index')
            ->with('success', 'Employé créé avec succès.');
    }

    /**
     * Afficher les détails d'un employé
     */
    public function show(Employe $employe)
    {
        return view('employes.show', compact('employe'));
    }

    /**
     * Afficher le formulaire de modification
     */
    public function edit(Employe $employe)
    {
        $postes = Poste::all();
        return view('employes.edit', compact('employe', 'postes'));
    }

    /**
     * Mettre à jour un employé
     */
    public function update(Request $request, Employe $employe)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:employes,email,' . $employe->id,
            'telephone' => 'nullable|string|max:20',
            'date_naissance' => 'nullable|date',
            'date_embauche' => 'required|date',
            'poste_id' => 'required|exists:postes,id',
            'statut' => 'required|in:actif,inactif',
        ]);
        
        $employe->update($request->all());
        
        // Si l'employé a un compte utilisateur, mettre à jour son nom et email
        if ($employe->utilisateur) {
            $employe->utilisateur->update([
                'name' => $request->prenom . ' ' . $request->nom,
                'email' => $request->email,
            ]);
        }
        
        return redirect()->route('employes.index')
            ->with('success', 'Employé modifié avec succès.');
    }

    /**
     * Supprimer un employé
     */
    public function destroy(Employe $employe)
    {
        $employe->delete();
        
        return redirect()->route('employes.index')
            ->with('success', 'Employé supprimé avec succès.');
    }

    /**
     * Importer des employés depuis un fichier Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);
        
        Excel::import(new EmployesImport, $request->file('file'));
        
        return redirect()->route('employes.index')
            ->with('success', 'Employés importés avec succès.');
    }

    /**
     * Exporter le modèle d'importation
     */
    public function exportTemplate()
    {
        return Excel::download(new EmployesExport(true), 'modele_employes.xlsx');
    }

    /**
     * Exporter la liste des employés
     */
    public function export()
    {
        return Excel::download(new EmployesExport, 'employes.xlsx');
    }
}