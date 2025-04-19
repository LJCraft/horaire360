<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employe;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Constructeur avec middleware d'authentification
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Afficher la liste des utilisateurs
     */
    public function index()
    {
        $users = User::with('role')->get();
        return view('users.index', compact('users'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $roles = Role::all();
        // Récupérer les employés sans compte utilisateur
        $employes = Employe::whereNull('utilisateur_id')->get();
        
        return view('users.create', compact('roles', 'employes'));
    }

    /**
     * Enregistrer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'employe_id' => 'nullable|exists:employes,id',
        ]);
        $password = "password";
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->$password),
            'role_id' => $request->role_id,
        ]);
        
        // Associer à un employé existant si spécifié
        if ($request->employe_id) {
            $employe = Employe::find($request->employe_id);
            $employe->update(['utilisateur_id' => $user->id]);
        }
        
        return redirect()->route('users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Afficher le formulaire de modification
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
        ]);
        
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
        ]);
        
        // Mettre à jour le mot de passe si fourni
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:8|confirmed',
            ]);
            
            $user->update(['password' => Hash::make($request->password)]);
        }
        
        return redirect()->route('users.index')
            ->with('success', 'Utilisateur modifié avec succès.');
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        // Supprimer l'association avec l'employé si elle existe
        if ($user->employe) {
            $user->employe->update(['utilisateur_id' => null]);
        }
        
        $user->delete();
        
        return redirect()->route('users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }
    
    /**
     * Créer un compte utilisateur pour un employé existant
     */
    public function createFromEmployee(Employe $employe)
    {
        // Vérifier si l'employé a déjà un compte
        if ($employe->utilisateur_id) {
            return redirect()->route('employes.show', $employe)
                ->with('error', 'Cet employé a déjà un compte utilisateur.');
        }
        
        // Générer un mot de passe aléatoire
        $password = Str::random(10);
        
        // Créer le compte utilisateur
        $user = User::create([
            'name' => $employe->prenom . ' ' . $employe->nom,
            'email' => $employe->email,
            'password' => Hash::make($password),
            'role_id' => 2, // Rôle Employé par défaut
        ]);
        
        // Associer le compte à l'employé
        $employe->update(['utilisateur_id' => $user->id]);
        
        // Ici, vous pourriez envoyer un email avec les identifiants
        // Mail::to($employe->email)->send(new NouveauCompte($user, $password));
        
        return redirect()->route('employes.show', $employe)
            ->with('success', 'Compte utilisateur créé avec succès. Mot de passe temporaire: ' . $password);
    }
}