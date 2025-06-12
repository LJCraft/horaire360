<?php

namespace App\Http\Controllers;

use App\Models\Conge;
use App\Models\Employe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CongeController extends Controller
{
    /**
     * Afficher la liste des congés.
     */
    public function index(Request $request)
    {
        // Paramètres de filtrage
        $employe = $request->query('employe');
        $statut = $request->query('statut');
        $type = $request->query('type');
        
        // Filtrage par employé pour les utilisateurs non admin
        if (!auth()->user()->is_admin && auth()->user()->employe) {
            $employe = auth()->user()->employe->id;
        }
        
        // Construction de la requête
        $congesQuery = Conge::with(['employe', 'traitePar']);
        
        // Filtre par employé
        if ($employe) {
            $congesQuery->where('employe_id', $employe);
        }
        
        // Filtre par statut
        if ($statut) {
            $congesQuery->where('statut', $statut);
        }
        
        // Filtre par type
        if ($type) {
            $congesQuery->where('type', $type);
        }
        
        // Récupération des congés
        $conges = $congesQuery->orderBy('created_at', 'desc')->paginate(15);
        
        // Récupération des employés pour le filtre
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->orderBy('prenom')->get();
        
        return view('conges.index', compact('conges', 'employes', 'employe', 'statut', 'type'));
    }
    
    /**
     * Afficher le formulaire de création d'un congé.
     */
    public function create()
    {
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->orderBy('prenom')->get();
        return view('conges.create', compact('employes'));
    }
    
    /**
     * Stocker un nouveau congé.
     */
    public function store(Request $request)
    {
        // Validation des données
        $validatedData = $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'type' => 'required|in:conge_paye,maladie,sans_solde,autre',
            'motif' => 'nullable|string',
        ]);
        
        try {
            // Création du congé
            Conge::create($validatedData);
            
            return redirect()->route('conges.index')
                ->with('success', 'Demande de congé créée avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la demande de congé : ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la création de la demande de congé.');
        }
    }
    
    /**
     * Afficher les détails d'un congé.
     */
    public function show(Conge $conge)
    {
        return view('conges.show', compact('conge'));
    }
    
    /**
     * Afficher le formulaire d'édition d'un congé.
     */
    public function edit(Conge $conge)
    {
        // Vérifier si l'utilisateur est autorisé à modifier ce congé
        if (!auth()->user()->is_admin && auth()->user()->employe && auth()->user()->employe->id !== $conge->employe_id) {
            return redirect()->route('conges.index')
                ->with('error', 'Vous n\'êtes pas autorisé à modifier cette demande de congé.');
        }
        
        // Vérifier si le congé a déjà été traité
        if ($conge->statut !== 'en_attente') {
            return redirect()->route('conges.show', $conge)
                ->with('error', 'Cette demande de congé a déjà été traitée et ne peut plus être modifiée.');
        }
        
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->orderBy('prenom')->get();
        return view('conges.edit', compact('conge', 'employes'));
    }
    
    /**
     * Mettre à jour un congé.
     */
    public function update(Request $request, Conge $conge)
    {
        // Vérifier si l'utilisateur est autorisé à modifier ce congé
        if (!auth()->user()->is_admin && auth()->user()->employe && auth()->user()->employe->id !== $conge->employe_id) {
            return redirect()->route('conges.index')
                ->with('error', 'Vous n\'êtes pas autorisé à modifier cette demande de congé.');
        }
        
        // Vérifier si le congé a déjà été traité
        if ($conge->statut !== 'en_attente') {
            return redirect()->route('conges.show', $conge)
                ->with('error', 'Cette demande de congé a déjà été traitée et ne peut plus être modifiée.');
        }
        
        // Validation des données
        $validatedData = $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'type' => 'required|in:conge_paye,maladie,sans_solde,autre',
            'motif' => 'nullable|string',
        ]);
        
        try {
            // Mise à jour du congé
            $conge->update($validatedData);
            
            return redirect()->route('conges.index')
                ->with('success', 'Demande de congé mise à jour avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la demande de congé : ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de la demande de congé.');
        }
    }
    
    /**
     * Supprimer un congé.
     */
    public function destroy(Conge $conge)
    {
        // Vérifier si l'utilisateur est autorisé à supprimer ce congé
        if (!auth()->user()->is_admin && auth()->user()->employe && auth()->user()->employe->id !== $conge->employe_id) {
            return redirect()->route('conges.index')
                ->with('error', 'Vous n\'êtes pas autorisé à supprimer cette demande de congé.');
        }
        
        // Vérifier si le congé a déjà été traité
        if ($conge->statut !== 'en_attente') {
            return redirect()->route('conges.show', $conge)
                ->with('error', 'Cette demande de congé a déjà été traitée et ne peut plus être supprimée.');
        }
        
        try {
            // Suppression du congé
            $conge->delete();
            
            return redirect()->route('conges.index')
                ->with('success', 'Demande de congé supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la demande de congé : ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression de la demande de congé.');
        }
    }
    
    /**
     * Approuver une demande de congé.
     */
    public function approuver(Request $request, Conge $conge)
    {
        // Vérifier si l'utilisateur est autorisé à approuver les congés
        if (!auth()->user()->is_admin && !auth()->user()->hasRole('Responsable RH') && !auth()->user()->hasRole('Manager')) {
            return redirect()->route('conges.index')
                ->with('error', 'Vous n\'êtes pas autorisé à approuver des demandes de congé.');
        }
        
        // Validation des données
        $validatedData = $request->validate([
            'commentaire_reponse' => 'nullable|string',
        ]);
        
        try {
            // Mise à jour du congé
            $conge->update([
                'statut' => 'approuve',
                'commentaire_reponse' => $validatedData['commentaire_reponse'],
                'traite_par' => Auth::id(),
            ]);
            
            return redirect()->route('conges.index')
                ->with('success', 'Demande de congé approuvée avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'approbation de la demande de congé : ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'approbation de la demande de congé.');
        }
    }
    
    /**
     * Refuser une demande de congé.
     */
    public function refuser(Request $request, Conge $conge)
    {
        // Vérifier si l'utilisateur est autorisé à refuser les congés
        if (!auth()->user()->is_admin && !auth()->user()->hasRole('Responsable RH') && !auth()->user()->hasRole('Manager')) {
            return redirect()->route('conges.index')
                ->with('error', 'Vous n\'êtes pas autorisé à refuser des demandes de congé.');
        }
        
        // Validation des données
        $validatedData = $request->validate([
            'commentaire_reponse' => 'required|string',
        ]);
        
        try {
            // Mise à jour du congé
            $conge->update([
                'statut' => 'refuse',
                'commentaire_reponse' => $validatedData['commentaire_reponse'],
                'traite_par' => Auth::id(),
            ]);
            
            return redirect()->route('conges.index')
                ->with('success', 'Demande de congé refusée avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors du refus de la demande de congé : ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors du refus de la demande de congé.');
        }
    }
}