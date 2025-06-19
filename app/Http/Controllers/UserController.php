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
        $users = User::with(['role', 'employe'])->get();
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
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
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
        
        // Vérifier si un utilisateur avec cet email existe déjà
        $existingUser = User::where('email', $employe->email)->first();
        if ($existingUser) {
            // Vérifier si cet utilisateur n'est pas déjà associé à un autre employé
            if ($existingUser->employe) {
                return redirect()->route('employes.show', $employe)
                    ->with('error', 'Un utilisateur avec cet email existe déjà et est associé à un autre employé.');
            } else {
                // Si l'utilisateur existe mais n'est pas associé à un employé, l'associer à cet employé
                $employe->update(['utilisateur_id' => $existingUser->id]);
                
            return redirect()->route('employes.show', $employe)
                    ->with('success', 'L\'employé a été associé au compte utilisateur existant.');
            }
        }
        
        try {
        // Utiliser le mot de passe par défaut
        $password = 'password';
        
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
        
        $message = 'Compte utilisateur créé avec succès. Mot de passe par défaut: ' . $password;
        
        return redirect()->route('employes.show', $employe)
            ->with('success', $message);
                
        } catch (\Illuminate\Database\QueryException $e) {
            // Si on a encore une erreur de duplicata, cela signifie qu'il y a une condition de concurrence
            if ($e->errorInfo[1] == 1062) { // Code d'erreur MySQL pour duplicata
                $existingUser = User::where('email', $employe->email)->first();
                if ($existingUser && !$existingUser->employe) {
                    // Associer l'employé à l'utilisateur existant
                    $employe->update(['utilisateur_id' => $existingUser->id]);
                    
                    return redirect()->route('employes.show', $employe)
                        ->with('success', 'L\'employé a été associé au compte utilisateur existant.');
                }
            }
            
            return redirect()->route('employes.show', $employe)
                ->with('error', 'Erreur lors de la création du compte : ' . $e->getMessage());
        }
    }

    /**
     * Rediriger vers la création d'un compte utilisateur (méthode GET vers POST)
     */
    public function redirectToCreateFromEmployee(Employe $employe)
    {
        // Vérifier si l'employé a déjà un compte
        if ($employe->utilisateur_id) {
            return redirect()->route('employes.show', $employe)
                ->with('error', 'Cet employé a déjà un compte utilisateur.');
        }
        
        // Afficher une vue qui va automatiquement soumettre un formulaire POST
        return view('users.redirect-create', compact('employe'));
    }
}