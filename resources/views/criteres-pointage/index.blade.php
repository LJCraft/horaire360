@extends('layouts.app')

@section('title', 'Configuration des critères de pointage')

@section('styles')
<style>
    .nav-tabs .nav-link.active {
        font-weight: bold;
        border-bottom: 3px solid #0d6efd;
    }
    .employe-card {
        border-left: 3px solid #0d6efd;
        transition: all 0.2s;
    }
    .employe-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .employe-card.has-critere {
        border-left: 3px solid #198754;
    }
    .employe-avatar {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 50%;
    }
    .status-badge {
        position: absolute;
        top: 5px;
        right: 5px;
    }
    .critere-card {
        border-left: 3px solid #0d6efd;
        transition: all 0.2s;
    }
    .critere-card.individuel {
        border-left: 3px solid #198754;
    }
    .critere-card.departemental {
        border-left: 3px solid #dc3545;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* SOLUTION RADICALE - FORCER LE DÉVERROUILLAGE DES DROPDOWNS */
    #poste_filter,
    #grade_filter {
        background-color: #ffffff !important;
        opacity: 1 !important;
        cursor: pointer !important;
        pointer-events: auto !important;
    }

    #poste_filter:disabled,
    #grade_filter:disabled {
        background-color: #ffffff !important;
        opacity: 1 !important;
        cursor: pointer !important;
        pointer-events: auto !important;
        color: #212529 !important;
    }

    #poste_filter[disabled],
    #grade_filter[disabled] {
        background-color: #ffffff !important;
        opacity: 1 !important;
        cursor: pointer !important;
        pointer-events: auto !important;
        color: #212529 !important;
        border-color: #ced4da !important;
    }

    #poste_filter:focus,
    #grade_filter:focus {
        border-color: #86b7fe !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
    }

    /* Styles professionnels pour la configuration par département */
    .alerte-flottante {
        transform: translateX(100%);
        transition: all 0.3s ease-in-out;
    }

    .alerte-flottante.show {
        transform: translateX(0);
    }

    .ligne-employe {
        transition: all 0.3s ease;
    }

    .ligne-employe:hover {
        background-color: #f8f9fa;
    }

    .employe-avec-critere {
        background-color: #fdf7e3;
    }

    .employe-sans-critere:hover {
        background-color: #e3f2fd;
    }

    .card {
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }

    .stat-card .card-body {
        padding: 1.5rem;
    }

    .stat-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .btn-group .btn {
        border-radius: 0.375rem !important;
        margin-right: 0.25rem;
    }

    .btn-group .btn:last-child {
        margin-right: 0;
    }

    .alert-floating {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1055;
        min-width: 300px;
        animation: slideInFromRight 0.5s ease-out;
    }

    @keyframes slideInFromRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(0,0,0,.3);
        border-radius: 50%;
        border-top-color: #007bff;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .table th {
        background-color: #f8f9fa;
        border-top: none;
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.375rem 0.75rem;
    }

    .section-header {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border-radius: 0.75rem;
        color: white;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .section-header h4 {
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .section-header p {
        margin-bottom: 0;
        opacity: 0.9;
    }

    @media (max-width: 768px) {
        .btn-group {
            flex-direction: column;
        }
        
        .btn-group .btn {
            margin-right: 0;
            margin-bottom: 0.25rem;
        }
        
        .alert-floating {
            position: fixed;
            top: 10px;
            left: 10px;
            right: 10px;
            min-width: auto;
        }
    }

    /* Style pour les initiales des employés */
    .employe-initiales {
        width: 30px !important;
        height: 30px !important;
        min-width: 30px;
        min-height: 30px;
        background-color: #f8f9fa !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 0.75rem !important;
        font-weight: bold !important;
        color: #0d6efd !important;
        margin-right: 0.5rem !important;
        flex-shrink: 0 !important;
        text-transform: uppercase !important;
        line-height: 1 !important;
        letter-spacing: 0.5px !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        text-align: center !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
    }

    .employe-photo {
        width: 30px !important;
        height: 30px !important;
        min-width: 30px;
        min-height: 30px;
        border-radius: 50% !important;
        object-fit: cover !important;
        margin-right: 0.5rem !important;
        flex-shrink: 0 !important;
        border: 1px solid #dee2e6 !important;
        box-sizing: border-box !important;
    }

    /* Assurer que les conteneurs ne perturbent pas l'affichage */
    .position-relative {
        position: relative !important;
        display: inline-block !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i> Configuration des critères de pointage
                    </h5>
                    <div>
                        <a href="{{ route('criteres-pointage.departementaux') }}" class="btn btn-outline-light btn-sm me-2">
                            <i class="bi bi-list-check"></i> Liste départementaux
                        </a>
                        <button type="button" class="btn btn-light btn-sm me-2" data-bs-toggle="modal" data-bs-target="#individuellModal">
                            <i class="fas fa-user"></i> Critère individuel
                        </button>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#departementModal">
                            <i class="fas fa-users"></i> Critère départemental
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Onglets de navigation -->
                    <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="criteres-tab" data-bs-toggle="tab" data-bs-target="#criteres" type="button" role="tab" aria-controls="criteres" aria-selected="true">
                                <i class="fas fa-list me-1"></i> Liste des critères
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="departements-tab" data-bs-toggle="tab" data-bs-target="#departements" type="button" role="tab" aria-controls="departements" aria-selected="false">
                                <i class="fas fa-building me-1"></i> Configuration par département
                            </button>
                        </li>
                    </ul>

                    <!-- Contenu des onglets -->
                    <div class="tab-content" id="configTabsContent">
                        <!-- Onglet Liste des critères -->
                        <div class="tab-pane fade show active" id="criteres" role="tabpanel" aria-labelledby="criteres-tab">
                            
                            <!-- Filtres pour la liste des critères -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-filter me-2"></i>Filtres
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="GET" action="{{ route('criteres-pointage.index') }}" id="filtres-criteres-form">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label for="departement_filter_criteres" class="form-label">Département</label>
                                                <select class="form-select" id="departement_filter_criteres" name="departement_id">
                                                    <option value="">Tous les départements</option>
                                                    @foreach ($departements as $dept)
                                                        <option value="{{ $dept->departement }}" {{ $departementId == $dept->departement ? 'selected' : '' }}>
                                                            {{ $dept->departement }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="niveau_filter" class="form-label">Niveau</label>
                                                <select class="form-select" id="niveau_filter" name="niveau">
                                                    <option value="">Tous les niveaux</option>
                                                    <option value="individuel" {{ request('niveau') == 'individuel' ? 'selected' : '' }}>Individuel</option>
                                                    <option value="departemental" {{ request('niveau') == 'departemental' ? 'selected' : '' }}>Départemental</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="statut_filter" class="form-label">Statut</label>
                                                <select class="form-select" id="statut_filter" name="statut">
                                                    <option value="">Tous les statuts</option>
                                                    <option value="actif" {{ request('statut') == 'actif' ? 'selected' : '' }}>Actif</option>
                                                    <option value="inactif" {{ request('statut') == 'inactif' ? 'selected' : '' }}>Inactif</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary me-2">
                                                    <i class="fas fa-search me-1"></i>Filtrer
                                                </button>
                                                <a href="{{ route('criteres-pointage.index') }}" class="btn btn-outline-secondary">
                                                    <i class="fas fa-undo me-1"></i>Reset
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Niveau</th>
                                            <th>Cible</th>
                                            <th>Période</th>
                                            <th>Dates</th>
                                            <th>Pointages</th>
                                            <th>Tolérance</th>
                                            <th>Source</th>
                                            <th>Heures Sup.</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($criteres as $critere)
                                            <tr>
                                                <td>
                                                    @if ($critere->niveau === 'individuel')
                                                        <span class="badge bg-success">Individuel</span>
                                                    @else
                                                        <span class="badge bg-danger">Départemental</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($critere->niveau === 'individuel')
                                                        {{ $critere->employe->nom ?? 'N/A' }} {{ $critere->employe->prenom ?? '' }}
                                                    @else
                                                        {{ $critere->departement->nom ?? $critere->departement_id }}
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">{{ $critere->periode_calculee }}</span>
                                                </td>
                                                <td>
                                                    {{ $critere->date_debut->format('d/m/Y') }} - {{ $critere->date_fin->format('d/m/Y') }}
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary">{{ $critere->nombre_pointages }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        Avant: {{ $critere->tolerance_avant }} min
                                                    </span>
                                                    <span class="badge bg-light text-dark">
                                                        Après: {{ $critere->tolerance_apres }} min
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($critere->source_pointage === 'biometrique')
                                                        <span class="badge bg-info">Biométrique</span>
                                                    @elseif ($critere->source_pointage === 'manuel')
                                                        <span class="badge bg-warning text-dark">Manuel</span>
                                                    @else
                                                        <span class="badge bg-secondary">Tous</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($critere->calcul_heures_sup)
                                                        <span class="badge bg-success">Activé ({{ $critere->seuil_heures_sup }} min)</span>
                                                    @else
                                                        <span class="badge bg-secondary">Désactivé</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($critere->actif)
                                                        <span class="badge bg-success">Actif</span>
                                                    @else
                                                        <span class="badge bg-danger">Inactif</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <a href="/criteres-pointage/{{ $critere->id }}" data-bs-toggle="tooltip" title="Voir les détails">
                                                            <i class="bi bi-eye-fill fs-5 text-info"></i>
                                                        </a>
                                                        <a href="/criteres-pointage/edit/{{ $critere->id }}" data-bs-toggle="tooltip" title="Modifier le critère">
                                                            <i class="bi bi-pencil-square fs-5 text-warning"></i>
                                                        </a>
                                                        <a href="#" onclick="supprimerCritere({{ $critere->id }})" title="Supprimer le critère">
                                                            <i class="bi bi-trash-fill fs-5 text-danger"></i>
                                                        </a>
                                                        
                                                        <!-- Formulaire de suppression caché -->
                                                        <form id="delete-form-{{ $critere->id }}" action="{{ route('criteres-pointage.destroy', $critere) }}" method="POST" style="display: none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    </div>

                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center">
                                                    @if(request()->hasAny(['departement_id', 'niveau', 'statut']))
                                                        Aucun critère ne correspond aux filtres sélectionnés.
                                                        <a href="{{ route('criteres-pointage.index') }}" class="btn btn-link">Voir tous les critères</a>
                                                    @else
                                                        Aucun critère de pointage configuré
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            
                            @if($criteres->hasPages())
                            <div class="d-flex justify-content-center mt-3">
                                {{ $criteres->links() }}
                            </div>
                            @endif
                        </div>
                        
                        <!-- Onglet Configuration par département -->
                        <div class="tab-pane fade" id="departements" role="tabpanel" aria-labelledby="departements-tab">
                            
                            <!-- Section de sélection du département -->
                            <div class="card mb-4">
                                        <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-filter me-2"></i>Sélection du département
                                    </h6>
                                        </div>
                                        <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-8">
                                            <label for="departement_selector" class="form-label">Département <span class="text-danger">*</span></label>
                                            <select class="form-select" id="departement_selector" name="departement_id" required>
                                                            <option value="">Sélectionner un département</option>
                                                            @foreach ($departements as $departement)
                                                    <option value="{{ $departement->departement }}">
                                                                    {{ $departement->departement }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                        <div class="col-md-4">
                                            <button type="button" id="charger_departement_btn" class="btn btn-primary w-100">
                                                <i class="fas fa-search me-1"></i>Filtrer
                                            </button>
                                                    </div>
                                                    </div>
                                                    </div>
                                                    </div>

                            <!-- Message d'instruction initial -->
                            <div id="message_instruction" class="card border-0 shadow-sm">
                                <div class="card-body text-center py-5">
                                    <div class="mb-4">
                                        <i class="bi bi-building fa-4x text-primary mb-3"></i>
                                        <h4 class="text-primary mb-3">Configuration par département</h4>
                                        <p class="text-muted mb-4 fs-6">
                                            Sélectionnez un département ci-dessus pour visualiser la liste des employés et configurer leurs critères de pointage.<br>
                                            Vous pourrez appliquer des critères individuels ou créer un critère départemental global.
                                        </p>
                                                </div>
                                    <div class="alert alert-info border-0 bg-light">
                                        <i class="bi bi-info-circle me-2 text-info"></i>
                                        <strong>Important :</strong> Les employés ayant déjà un critère individuel ne seront pas affectés par les critères départementaux.
                                        </div>
                                    </div>
                                </div>

                            <!-- Section des résultats départementaux -->
                            <div id="resultats_departement" class="d-none">
                                
                                <!-- En-tête avec informations du département -->
                                <div class="card mb-4 border-0 shadow-sm">
                                    <div class="card-header bg-primary text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-0">
                                                    <i class="bi bi-building me-2"></i>Département : <span id="nom_departement_actuel">-</span>
                                                </h5>
                                                <small class="opacity-75">Gestion des critères de pointage</small>
                                            </div>
                                            <div>
                                                <button type="button" id="creer_critere_departemental_btn" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalCritereDepartemental">
                                                    <i class="fas fa-plus-circle me-1"></i>Créer critère départemental
                                                </button>
                                            </div>
                                        </div>
                                        </div>
                                        <div class="card-body">
                                        <!-- Alertes critère départemental existant -->
                                        <div id="alerte_critere_existant" class="d-none">
                                            <div class="alert alert-info border-start border-4 border-info bg-light">
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0">
                                                        <i class="fas fa-info-circle fa-lg text-info"></i>
                                            </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h6 class="alert-heading mb-2 text-info">Critère départemental existant</h6>
                                                        <div id="details_critere_existant" class="small"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                        
                                        <!-- Statistiques du département -->
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="card h-100 border-0 shadow-sm">
                                                    <div class="card-body text-center bg-light">
                                                        <div class="d-flex align-items-center justify-content-center mb-2">
                                                            <i class="fas fa-users fa-2x text-info me-3"></i>
                                                            <div>
                                                                <h3 class="mb-0 text-info" id="stat_total_employes">0</h3>
                                                                <small class="text-muted">Total employés</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <div class="col-md-3">
                                                <div class="card h-100 border-0 shadow-sm">
                                                    <div class="card-body text-center bg-light">
                                                        <div class="d-flex align-items-center justify-content-center mb-2">
                                                            <i class="fas fa-user-check fa-2x text-success me-3"></i>
                                                            <div>
                                                                <h3 class="mb-0 text-success" id="stat_avec_critere_individuel">0</h3>
                                                                <small class="text-muted">Critère individuel</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                            <div class="col-md-3">
                                                <div class="card h-100 border-0 shadow-sm">
                                                    <div class="card-body text-center bg-light">
                                                        <div class="d-flex align-items-center justify-content-center mb-2">
                                                            <i class="fas fa-user-times fa-2x text-warning me-3"></i>
                                                            <div>
                                                                <h3 class="mb-0 text-warning" id="stat_sans_critere">0</h3>
                                                                <small class="text-muted">Sans critère</small>
                            </div>
                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card h-100 border-0 shadow-sm">
                                                    <div class="card-body text-center bg-light">
                                                        <div class="d-flex align-items-center justify-content-center mb-2">
                                                            <i class="fas fa-building fa-2x text-primary me-3"></i>
                                                            <div>
                                                                <h3 class="mb-0 text-primary" id="stat_eligible_departemental">0</h3>
                                                                <small class="text-muted">Éligibles départemental</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section des postes du département -->
                                <div class="card mb-4 border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">
                                            <i class="fas fa-briefcase me-2"></i>Postes du département
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="conteneur_postes" class="row g-3">
                                            <!-- Les postes seront affichés ici dynamiquement -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Tableau des employés du département -->
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="fas fa-users me-2"></i>Employés du département
                                            </h6>
                                            <div class="d-flex gap-3 align-items-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="filtre_sans_critere_individuel">
                                                    <label class="form-check-label small text-muted" for="filtre_sans_critere_individuel">
                                                        Afficher uniquement les employés sans critère individuel
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0" id="tableau_employes">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="5%" class="text-center">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="selectionner_tous_employes">
                                                            </div>
                                                        </th>
                                                        <th width="25%">Employé</th>
                                                        <th width="15%">Poste</th>
                                                        <th width="15%">Grade</th>
                                                        <th width="15%">État</th>
                                                        <th width="15%" class="text-center">Pointages</th>
                                                        <th width="10%" class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody_employes">
                                                    <tr>
                                                        <td colspan="7" class="text-center py-5 text-muted">
                                                            <i class="fas fa-search fa-3x mb-3 d-block text-muted opacity-50"></i>
                                                            <p class="mb-0">Sélectionnez un département pour afficher les employés</p>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour la création d'un critère individuel -->
<div class="modal fade" id="individuellModal" tabindex="-1" aria-labelledby="individuellModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="individuellModalLabel">Créer un critère individuel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="critere-individuel-form" action="{{ route('criteres-pointage.store') }}" method="POST" onsubmit="document.getElementById('btn-submit-individuel').disabled = true; document.getElementById('btn-submit-individuel').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="niveau" value="individuel">
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="employe_id" class="form-label">Employé <span class="text-danger">*</span></label>
                            <select class="form-select @error('employe_id') is-invalid @enderror" id="employe_id" name="employe_id" required>
                                <option value="">Sélectionner un employé</option>
                                @foreach($employes as $employe)
                                    <option value="{{ $employe->id }}" {{ old('employe_id') == $employe->id ? 'selected' : '' }}>
                                        {{ $employe->nom }} {{ $employe->prenom }} - {{ $employe->poste->nom ?? 'Sans poste' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employe_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date_debut') is-invalid @enderror" id="date_debut" name="date_debut" value="{{ old('date_debut', date('Y-m-d')) }}" required>
                            @error('date_debut')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="date_fin" class="form-label">Date de fin <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date_fin') is-invalid @enderror" id="date_fin" name="date_fin" value="{{ old('date_fin', date('Y-m-d', strtotime('+1 year'))) }}" required>
                            @error('date_fin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="periode_preview" class="form-label">Période calculée</label>
                            <input type="text" class="form-control" id="periode_preview" readonly placeholder="Sélectionnez les dates pour voir la période">
                            <div class="form-text text-info">
                                <i class="fas fa-info-circle me-1"></i>La période est calculée automatiquement à partir des dates sélectionnées
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="nombre_pointages" class="form-label">Nombre de pointages <span class="text-danger">*</span></label>
                            <select class="form-select @error('nombre_pointages') is-invalid @enderror" id="nombre_pointages" name="nombre_pointages" required>
                                <option value="1" {{ old('nombre_pointages') == '1' ? 'selected' : '' }}>1 (Présence uniquement)</option>
                                <option value="2" {{ old('nombre_pointages', '2') == '2' ? 'selected' : '' }}>2 (Arrivée et départ)</option>
                            </select>
                            @error('nombre_pointages')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tolerance_avant" class="form-label">Tolérance avant (minutes)</label>
                            <input type="number" class="form-control @error('tolerance_avant') is-invalid @enderror" id="tolerance_avant" name="tolerance_avant" value="{{ old('tolerance_avant', 10) }}" min="0" max="60" required>
                            @error('tolerance_avant')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Minutes de tolérance avant l'heure prévue (0 = pas de tolérance).
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="tolerance_apres" class="form-label">Tolérance après (minutes)</label>
                            <input type="number" class="form-control @error('tolerance_apres') is-invalid @enderror" id="tolerance_apres" name="tolerance_apres" value="{{ old('tolerance_apres', 10) }}" min="0" max="60" required>
                            @error('tolerance_apres')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Minutes de tolérance après l'heure prévue (0 = pas de tolérance).
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="duree_pause" class="form-label">Durée de pause (minutes)</label>
                            <input type="number" class="form-control @error('duree_pause') is-invalid @enderror" id="duree_pause" name="duree_pause" value="{{ old('duree_pause', 0) }}" min="0" max="240" required>
                            @error('duree_pause')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Durée de pause non décomptée du temps de travail (0 = pas de pause).
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="source_pointage" class="form-label">Source de pointage</label>
                            <select class="form-select @error('source_pointage') is-invalid @enderror" id="source_pointage" name="source_pointage" required>
                                <option value="tous" {{ old('source_pointage', 'tous') == 'tous' ? 'selected' : '' }}>Tous types de pointage</option>
                                <option value="biometrique" {{ old('source_pointage') == 'biometrique' ? 'selected' : '' }}>Biométrique uniquement</option>
                                <option value="manuel" {{ old('source_pointage') == 'manuel' ? 'selected' : '' }}>Manuel uniquement</option>
                            </select>
                            @error('source_pointage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Définit le type de pointage à prendre en compte pour les critères.
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="calcul_heures_sup_individuel" name="calcul_heures_sup" value="1" {{ old('calcul_heures_sup') ? 'checked' : '' }}>
                                <label class="form-check-label" for="calcul_heures_sup_individuel">Activer le calcul des heures supplémentaires</label>
                            </div>
                            <div class="form-text">
                                Permet de comptabiliser automatiquement les heures supplémentaires.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="seuil_heures_sup" class="form-label">Seuil des heures supplémentaires (minutes)</label>
                            <input type="number" class="form-control @error('seuil_heures_sup') is-invalid @enderror" id="seuil_heures_sup" name="seuil_heures_sup" value="{{ old('seuil_heures_sup', 15) }}" min="0" max="240" required>
                            @error('seuil_heures_sup')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Durée minimale au-delà de l'heure de fin prévue pour comptabiliser des heures supplémentaires.
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="priorite" class="form-label">Priorité du critère</label>
                            <select class="form-select @error('priorite') is-invalid @enderror" id="priorite" name="priorite" required>
                                <option value="1" {{ old('priorite', 1) == 1 ? 'selected' : '' }}>Haute (1)</option>
                                <option value="2" {{ old('priorite') == 2 ? 'selected' : '' }}>Normale (2)</option>
                                <option value="3" {{ old('priorite') == 3 ? 'selected' : '' }}>Basse (3)</option>
                            </select>
                            @error('priorite')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                En cas de chevauchement de critères, celui avec la priorité la plus haute sera appliqué.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1" {{ old('actif', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="actif">Critère actif</label>
                            </div>
                            <div class="form-text">
                                Décochez cette case pour désactiver ce critère sans le supprimer.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-individuel">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour la création d'un critère départemental -->
<div class="modal fade" id="departementModal" tabindex="-1" aria-labelledby="departementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="departementModalLabel">Créer un critère départemental</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="departemental-message-container" class="px-4 pt-3">
                <!-- Les messages de succès/erreur AJAX seront affichés ici -->
            </div>
            <form id="critere-departemental-form" action="{{ route('criteres-pointage.store') }}" method="POST">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="niveau" value="departemental">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="departement_id" class="form-label">Département</label>
                            <select class="form-select" id="departement_id" name="departement_id" required>
                                <option value="">Sélectionner un département</option>
                                @foreach ($departements as $departement)
                                    <option value="{{ $departement->departement }}">
                                        {{ $departement->departement }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="periode_preview_dept" class="form-label">Période calculée</label>
                            <input type="text" class="form-control" id="periode_preview_dept" readonly placeholder="Sélectionnez les dates pour voir la période">
                            <div class="form-text text-info">
                                <i class="fas fa-info-circle me-1"></i>La période est calculée automatiquement à partir des dates sélectionnées
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ date('Y-m-d', strtotime('+1 month')) }}" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nombre_pointages" class="form-label">Nombre de pointages</label>
                            <select class="form-select" id="nombre_pointages" name="nombre_pointages" required>
                                <option value="1">1 pointage (présence uniquement)</option>
                                <option value="2" selected>2 pointages (arrivée et départ)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="source_pointage" class="form-label">Source de pointage</label>
                            <select class="form-select" id="source_pointage" name="source_pointage" required>
                                <option value="biometrique">Biométrique</option>
                                <option value="manuel">Manuel</option>
                                <option value="tous" selected>Tous</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="tolerance_avant" class="form-label">Tolérance avant (minutes)</label>
                            <input type="number" class="form-control" id="tolerance_avant" name="tolerance_avant" value="5" min="0" max="60" required>
                        </div>
                        <div class="col-md-4">
                            <label for="tolerance_apres" class="form-label">Tolérance après (minutes)</label>
                            <input type="number" class="form-control" id="tolerance_apres" name="tolerance_apres" value="5" min="0" max="60" required>
                        </div>
                        <div class="col-md-4">
                            <label for="duree_pause" class="form-label">Durée de pause (minutes)</label>
                            <input type="number" class="form-control" id="duree_pause" name="duree_pause" value="60" min="0" max="120" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="calcul_heures_sup" name="calcul_heures_sup" value="1">
                                <label class="form-check-label" for="calcul_heures_sup">Activer le calcul des heures supplémentaires</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="seuil_heures_sup" class="form-label">Seuil des heures supplémentaires (minutes)</label>
                            <input type="number" class="form-control" id="seuil_heures_sup" name="seuil_heures_sup" value="30" min="0" max="240">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Appliquer à:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="appliquer_a" id="appliquer_tous" value="tous" checked>
                            <label class="form-check-label" for="appliquer_tous">
                                Tous les employés sans critère individuel
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="appliquer_a" id="appliquer_selection" value="selection">
                            <label class="form-check-label" for="appliquer_selection">
                                Sélection d'employés spécifiques
                            </label>
                        </div>
                    </div>
                    
                    <div id="employes-selection" class="d-none">
                        <label class="form-label">Sélectionner les employés:</label>
                        <div class="employes-list border p-3 rounded" style="max-height: 200px; overflow-y: auto;">
                            <!-- Les employés seront chargés dynamiquement ici -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour créer un critère départemental -->
<div class="modal fade" id="modalCritereDepartemental" tabindex="-1" aria-labelledby="modalCritereDepartementalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalCritereDepartementalLabel">
                    <i class="fas fa-building me-2"></i>Créer un critère départemental
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="form_critere_departemental" action="{{ route('criteres-pointage.store') }}" method="POST">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="niveau" value="departemental">
                    <input type="hidden" name="departement_id" id="hidden_departement_id">
                    
                    <!-- Affichage des employés sélectionnés -->
                    <div id="section_employes_selectionnes" class="mb-4">
                        <h6 class="text-primary">
                            <i class="fas fa-users me-2"></i>Employés qui recevront ce critère
                        </h6>
                        <div id="liste_employes_selectionnes" class="alert alert-light border">
                            <!-- Liste dynamique des employés sélectionnés -->
                        </div>
                    </div>
                    
                    <!-- Configuration du critère -->
                    <div class="row">
                        <div class="col-md-6">
                            <label for="modal_date_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_date_debut" name="date_debut" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_date_fin" class="form-label">Date de fin <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_date_fin" name="date_fin" value="{{ date('Y-m-d', strtotime('+1 year')) }}" required>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="modal_periode" class="form-label">Période <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_periode" name="periode" required>
                                <option value="jour">Jour</option>
                                <option value="semaine">Semaine</option>
                                <option value="mois" selected>Mois</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_nombre_pointages" class="form-label">Nombre de pointages <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_nombre_pointages" name="nombre_pointages" required>
                                <option value="1">1 (Présence uniquement)</option>
                                <option value="2" selected>2 (Arrivée et départ)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="modal_tolerance_avant" class="form-label">Tolérance avant (min)</label>
                            <input type="number" class="form-control" id="modal_tolerance_avant" name="tolerance_avant" value="10" min="0" max="60">
                            <div class="form-text">Minutes de tolérance avant l'heure prévue</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_tolerance_apres" class="form-label">Tolérance après (min)</label>
                            <input type="number" class="form-control" id="modal_tolerance_apres" name="tolerance_apres" value="10" min="0" max="60">
                            <div class="form-text">Minutes de tolérance après l'heure prévue</div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="modal_duree_pause" class="form-label">Durée de pause (min)</label>
                            <input type="number" class="form-control" id="modal_duree_pause" name="duree_pause" value="0" min="0" max="240">
                            <div class="form-text">Durée de pause non décomptée</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_source_pointage" class="form-label">Source de pointage</label>
                            <select class="form-select" id="modal_source_pointage" name="source_pointage">
                                <option value="tous" selected>Tous types de pointage</option>
                                <option value="mobile">Mobile uniquement</option>
                                <option value="web">Web uniquement</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Zone de message -->
                    <div id="zone_message_modal" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" id="btn_valider_critere" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Créer le critère départemental
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Variables globales pour la configuration départementale
let employesDepartement = [];
let postesDepartement = [];
let departementActuel = '';
let criteresDepartementauxExistants = [];

console.log('Configuration départementale - Script chargé');

// Initialisation des fonctionnalités
document.addEventListener('DOMContentLoaded', function() {
    initConfigurationDepartement();
});

// Fonction d'initialisation principale
function initConfigurationDepartement() {
    console.log('Initialisation configuration départementale');
    
    const boutonCharger = document.getElementById('charger_departement_btn');
    const selectDepartement = document.getElementById('departement_selector');
    const formCritere = document.getElementById('form_critere_departemental');
    
    // Événement pour charger un département
    if (boutonCharger) {
        boutonCharger.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Bouton filtrer cliqué');
            chargerDepartement();
        });
    }
    
    // Événement de changement de département
    if (selectDepartement) {
        selectDepartement.addEventListener('change', function() {
            if (!this.value) {
                masquerResultats();
                }
            });
        }
        
    // Gestion du formulaire de création de critère départemental
    if (formCritere) {
        formCritere.addEventListener('submit', function(e) {
            e.preventDefault();
            creerCritereDepartemental();
        });
    }
    
    // Gestion de la sélection multiple des employés
    document.addEventListener('change', function(e) {
        if (e.target.id === 'selectionner_tous_employes') {
            toggleSelectionTousEmployes(e.target.checked);
        } else if (e.target.classList.contains('checkbox-employe')) {
            mettreAJourSelectionTous();
        } else if (e.target.id === 'filtre_sans_critere_individuel') {
            filtrerEmployesSansCritere(e.target.checked);
                }
            });
        }
        
// Fonction pour charger les données d'un département
async function chargerDepartement() {
    const selectDepartement = document.getElementById('departement_selector');
    const departementId = selectDepartement.value;
    
    console.log('Chargement département:', departementId);
    
    if (!departementId) {
        afficherMessage('Veuillez sélectionner un département', 'warning');
        return;
    }
    
    // Affichage du loading
    afficherLoading('Chargement des données du département...');
    
    try {
        const response = await fetch('/criteres-pointage/get-employes-departement', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    departement_id: departementId
                })
        });
        
        console.log('Réponse reçue:', response.status);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Données reçues:', data);
        
        if (data.success) {
            departementActuel = departementId;
            employesDepartement = data.employes || [];
            postesDepartement = data.postes || [];
            criteresDepartementauxExistants = data.criteres_departementaux || [];
            
            // Affichage des résultats
            afficherResultatsDepartement(data);
            masquerLoading();
            
        } else {
            throw new Error(data.message || 'Erreur lors du chargement du département');
        }
        
    } catch (error) {
                console.error('Erreur:', error);
        afficherMessage(`Erreur: ${error.message}`, 'danger');
        masquerLoading();
    }
}

// Fonction pour afficher les résultats du département
function afficherResultatsDepartement(data) {
    console.log('Affichage des résultats pour:', data.departement);
    
    // Masquer le message d'instruction et afficher les résultats
    document.getElementById('message_instruction').classList.add('d-none');
    document.getElementById('resultats_departement').classList.remove('d-none');
    
    // Mise à jour du nom du département
    document.getElementById('nom_departement_actuel').textContent = departementActuel;
    
    // Mise à jour des statistiques
    mettreAJourStatistiques(data.statistiques);
    
    // Affichage des postes
    afficherPostesDepartement(data.postes || []);
    
    // Affichage des employés
    afficherEmployesDepartement(data.employes || []);
    
    // Gestion des critères départementaux existants
    gererCriteresDepartementauxExistants(data.criteres_departementaux || []);
    
    // Scroll vers les résultats
    document.getElementById('resultats_departement').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
}

// Fonction pour mettre à jour les statistiques
function mettreAJourStatistiques(stats) {
    if (!stats) return;
    
    document.getElementById('stat_total_employes').textContent = stats.total_employes || 0;
    document.getElementById('stat_avec_critere_individuel').textContent = stats.avec_critere_individuel || 0;
    document.getElementById('stat_sans_critere').textContent = stats.sans_critere || 0;
    document.getElementById('stat_eligible_departemental').textContent = stats.eligible_departemental || 0;
}

// Fonction pour afficher les postes du département
function afficherPostesDepartement(postes) {
    const conteneur = document.getElementById('conteneur_postes');
    
    if (!postes || postes.length === 0) {
        conteneur.innerHTML = `
            <div class="col-12">
                <div class="alert alert-light border-0 text-center py-4">
                    <i class="bi bi-briefcase fa-3x text-muted mb-3 d-block"></i>
                    <h6 class="text-muted mb-2">Aucun poste défini</h6>
                    <p class="text-muted small mb-0">Ce département ne contient aucun poste configuré.</p>
                </div>
            </div>
        `;
        return;
    }
    
    conteneur.innerHTML = postes.map(poste => `
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center bg-light">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="bi bi-briefcase fa-2x text-primary me-3"></i>
                        <div class="text-start">
                            <h6 class="mb-1 text-primary">${poste.nom || poste.poste}</h6>
                            <small class="text-muted">
                                <i class="bi bi-people me-1"></i>${poste.nombre_employes || 0} employé(s)
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Fonction pour afficher les employés du département
function afficherEmployesDepartement(employes) {
    const tbody = document.getElementById('tbody_employes');
    
    if (!employes || employes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center justify-content-center text-muted">
                        <i class="bi bi-people-fill fa-4x mb-3 opacity-50"></i>
                        <h6 class="text-muted mb-2">Aucun employé trouvé</h6>
                        <p class="small mb-0">Ce département ne contient aucun employé actif.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = employes.map(employe => {
        // Le backend envoie le nom complet dans le champ 'nom'
        const nomComplet = employe.nom || 'Nom non défini';
        
        // Extraire les initiales du nom complet (format: "Prénom Nom")
        let initiales = '';
        const parties = nomComplet.trim().split(' ');
        if (parties.length >= 2) {
            // Prendre la première lettre du premier mot (prénom) et du dernier mot (nom)
            initiales = parties[0].substring(0, 1).toUpperCase() + parties[parties.length - 1].substring(0, 1).toUpperCase();
        } else if (parties.length === 1 && parties[0].length > 0) {
            // Si un seul mot, prendre les deux premières lettres
            initiales = parties[0].substring(0, 2).toUpperCase();
        } else {
            initiales = '??';
        }
        
        // Méthode simple et élégante comme dans gestion des employés
        const avatarHtml = employe.photo && employe.photo !== '' && employe.photo !== 'null' && employe.photo !== null && employe.photo !== '/images/default-avatar.png' ?
            `<img src="${employe.photo}" alt="Photo de ${nomComplet}" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">` :
            `<div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                ${initiales}
             </div>`;
        
        return `
        <tr data-employe-id="${employe.user_id}" class="ligne-employe ${employe.a_critere_individuel ? 'employe-avec-critere' : 'employe-sans-critere'}">
            <td class="text-center">
                ${employe.a_critere_individuel ? 
                    '<i class="bi bi-lock-fill text-muted" title="Critère individuel existant" data-bs-toggle="tooltip"></i>' : 
                    `<div class="form-check">
                        <input class="form-check-input checkbox-employe" type="checkbox" 
                               value="${employe.user_id}" id="employe_${employe.user_id}">
                    </div>`
                }
            </td>
            <td>
                <div class="d-flex align-items-center">
                    ${avatarHtml}
                    <div>
                        <div class="fw-medium text-dark">${nomComplet}</div>
                        <small class="text-muted">${employe.email || 'Email non défini'}</small>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-light text-dark border">
                    <i class="bi bi-briefcase me-1"></i>${employe.poste || 'N/A'}
                </span>
            </td>
            <td>
                <span class="badge bg-light text-dark border">
                    <i class="bi bi-award me-1"></i>${employe.grade || 'N/A'}
                </span>
            </td>
            <td>${genererBadgeEtat(employe)}</td>
            <td class="text-center">
                <span class="badge ${employe.nombre_pointages ? 'bg-primary' : 'bg-light text-dark border'}">
                    ${employe.nombre_pointages || 0} pointage(s)
                </span>
            </td>
            <td class="text-center">
                ${genererBoutonsActions(employe)}
            </td>
        </tr>
        `;
    }).join('');
    
    // Initialiser les tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
}

// Fonction pour générer le badge d'état
function genererBadgeEtat(employe) {
    if (employe.a_critere_individuel) {
        return '<span class="badge bg-success"><i class="bi bi-person-check me-1"></i>Critère individuel</span>';
    } else if (employe.a_critere_departemental) {
        return '<span class="badge bg-primary"><i class="bi bi-building me-1"></i>Critère départemental</span>';
    } else {
        return '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Sans critère</span>';
    }
}

// Fonction pour générer les boutons d'actions
function genererBoutonsActions(employe) {
    if (employe.a_critere_individuel) {
        return `
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-info" 
                        onclick="voirCritereIndividuel(${employe.user_id})" 
                        title="Voir le critère individuel"
                        data-bs-toggle="tooltip">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        `;
    } else {
        return `
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-success" 
                        onclick="creerCritereIndividuel(${employe.user_id})" 
                        title="Créer un critère individuel"
                        data-bs-toggle="tooltip">
                    <i class="bi bi-plus-circle"></i>
                </button>
            </div>
        `;
    }
}

// Fonction pour gérer les critères départementaux existants
function gererCriteresDepartementauxExistants(criteres) {
    const alerte = document.getElementById('alerte_critere_existant');
    const details = document.getElementById('details_critere_existant');
    
    if (criteres && criteres.length > 0) {
        const critere = criteres[0];
        details.innerHTML = `
            <div class="row g-2">
                <div class="col-md-6">
                    <strong class="text-info">Période :</strong> ${critere.periode} | 
                    <strong class="text-info">Pointages :</strong> ${critere.nombre_pointages}
                </div>
                <div class="col-md-6">
                    <strong class="text-info">Du :</strong> ${critere.date_debut} <strong class="text-info">au :</strong> ${critere.date_fin}
                </div>
                <div class="col-12">
                    <strong class="text-info">Tolérances :</strong> ${critere.tolerance_avant}min avant, ${critere.tolerance_apres}min après
                    ${critere.duree_pause ? ` | <strong class="text-info">Pause :</strong> ${critere.duree_pause}min` : ''}
                </div>
            </div>
        `;
        alerte.classList.remove('d-none');
                    } else {
        alerte.classList.add('d-none');
    }
}

// Fonctions utilitaires
function toggleSelectionTousEmployes(selectionne) {
    const checkboxes = document.querySelectorAll('.checkbox-employe');
    checkboxes.forEach(checkbox => {
        if (!checkbox.disabled) {
            checkbox.checked = selectionne;
        }
    });
}

function mettreAJourSelectionTous() {
    const checkboxTous = document.getElementById('selectionner_tous_employes');
    const checkboxes = document.querySelectorAll('.checkbox-employe');
    const checkboxesActives = Array.from(checkboxes).filter(cb => !cb.disabled);
    const checkboxesCochees = Array.from(checkboxesActives).filter(cb => cb.checked);
    
    if (checkboxesCochees.length === 0) {
        checkboxTous.indeterminate = false;
        checkboxTous.checked = false;
    } else if (checkboxesCochees.length === checkboxesActives.length) {
        checkboxTous.indeterminate = false;
        checkboxTous.checked = true;
    } else {
        checkboxTous.indeterminate = true;
    }
}

function filtrerEmployesSansCritere(afficherUniquement) {
    const lignes = document.querySelectorAll('.ligne-employe');
    
    lignes.forEach(ligne => {
        if (afficherUniquement) {
            if (ligne.classList.contains('employe-avec-critere')) {
                ligne.style.display = 'none';
            } else {
                ligne.style.display = '';
            }
        } else {
            ligne.style.display = '';
        }
    });
}

function masquerResultats() {
    document.getElementById('message_instruction').classList.remove('d-none');
    document.getElementById('resultats_departement').classList.add('d-none');
    
    employesDepartement = [];
    postesDepartement = [];
    departementActuel = '';
}

function afficherMessage(message, type = 'info') {
    // Supprimer les anciennes alertes flottantes
    const anciennesAlertes = document.querySelectorAll('.alerte-flottante');
    anciennesAlertes.forEach(alerte => alerte.remove());
    
    // Icône selon le type de message
    const icones = {
        'success': 'bi bi-check-circle',
        'danger': 'bi bi-exclamation-triangle',
        'warning': 'bi bi-exclamation-circle',
        'info': 'bi bi-info-circle'
    };
    
    const alerte = document.createElement('div');
    alerte.className = `alert alert-${type} alert-dismissible fade show alerte-flottante border-0 shadow`;
    alerte.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 350px; max-width: 500px;';
    alerte.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="${icones[type] || icones.info} me-3 fa-lg"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>
                                </div>
    `;
    
    document.body.appendChild(alerte);
    
    // Animation d'entrée
    requestAnimationFrame(() => {
        alerte.style.transform = 'translateX(0)';
    });
    
    // Auto-suppression après 5 secondes
    setTimeout(() => {
        if (alerte.parentNode) {
            alerte.classList.remove('show');
            setTimeout(() => alerte.remove(), 150);
        }
    }, 5000);
}

function afficherLoading(message = 'Chargement en cours...') {
    // Supprimer l'ancien loading s'il existe
    masquerLoading();
    
    const loading = document.createElement('div');
    loading.id = 'loading-overlay';
    loading.className = 'position-fixed w-100 h-100 d-flex align-items-center justify-content-center';
    loading.style.cssText = 'top: 0; left: 0; background: rgba(255,255,255,0.9); backdrop-filter: blur(2px); z-index: 9999;';
    loading.innerHTML = `
        <div class="card border-0 shadow-lg">
            <div class="card-body text-center py-5 px-5">
                <div class="mb-4">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Chargement...</span>
                            </div>
                        </div>
                <h6 class="text-primary mb-2">${message}</h6>
                <p class="text-muted small mb-0">Veuillez patienter...</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(loading);
    
    // Animation d'entrée
    requestAnimationFrame(() => {
        loading.style.opacity = '1';
    });
}

function masquerLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.style.opacity = '0';
        setTimeout(() => loading.remove(), 200);
    }
}

// Fonctions pour les actions individuelles
function creerCritereIndividuel(userId) {
    // Ouvrir le modal de critère individuel
    const modalIndividuel = document.getElementById('individuellModal');
    if (modalIndividuel) {
        const modal = new bootstrap.Modal(modalIndividuel);
        modal.show();
        
        // Pré-sélectionner l'employé dans le modal s'il existe un select
        setTimeout(() => {
            const selectEmploye = modalIndividuel.querySelector('select[name="employe_id"]');
            if (selectEmploye) {
                selectEmploye.value = userId;
                selectEmploye.dispatchEvent(new Event('change'));
            }
        }, 300);
    } else {
        // Si le modal n'existe pas, passer à l'onglet liste des critères
        const ongletListe = document.querySelector('#criteres-tab');
        if (ongletListe) {
            ongletListe.click();
            
            setTimeout(() => {
                // Essayer d'ouvrir le modal depuis l'autre onglet
                const btnCritereIndividuel = document.querySelector('[data-bs-target="#individuellModal"]');
                if (btnCritereIndividuel) {
                    btnCritereIndividuel.click();
                    
                        setTimeout(() => {
                        const selectUser = document.querySelector('select[name="employe_id"]');
                        if (selectUser) {
                            selectUser.value = userId;
                            selectUser.dispatchEvent(new Event('change'));
                        }
                    }, 500);
                }
            }, 200);
            
            afficherMessage('Création de critère individuel pour l\'employé sélectionné', 'info');
        }
    }
}

function voirCritereIndividuel(userId) {
    // Passer à l'onglet liste des critères pour voir le critère existant
    const ongletListe = document.querySelector('#criteres-tab');
    if (ongletListe) {
        ongletListe.click();
        afficherMessage('Consultez la liste des critères pour voir le critère individuel de cet employé', 'info');
    }
}

// Fonction pour créer un critère départemental (simplifié pour l'instant)
async function creerCritereDepartemental() {
    const checkboxes = document.querySelectorAll('.checkbox-employe:checked');
    const employesSelectionnes = Array.from(checkboxes).map(cb => cb.value);
    
    if (employesSelectionnes.length === 0) {
        afficherMessage('Veuillez sélectionner au moins un employé', 'warning');
        return;
    }
    
    afficherMessage(`Critère départemental pour ${employesSelectionnes.length} employé(s) - Fonctionnalité en cours de développement`, 'info');
}

// Fonction pour calculer automatiquement la période à partir des dates
function calculerPeriode(dateDebut, dateFin) {
    if (!dateDebut || !dateFin) {
        return 'Sélectionnez les dates';
    }
    
    const debut = new Date(dateDebut);
    const fin = new Date(dateFin);
    
    // Même jour
    if (debut.toDateString() === fin.toDateString()) {
        return debut.toLocaleDateString('fr-FR');
    }
    
    // Même semaine (approximatif)
    const diffTime = Math.abs(fin - debut);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays <= 7) {
        return `Semaine du ${debut.toLocaleDateString('fr-FR')}`;
    }
    
    // Même mois
    if (debut.getMonth() === fin.getMonth() && debut.getFullYear() === fin.getFullYear()) {
        const premierJour = new Date(debut.getFullYear(), debut.getMonth(), 1);
        const dernierJour = new Date(debut.getFullYear(), debut.getMonth() + 1, 0);
        
        if (debut.getDate() === 1 && fin.getDate() === dernierJour.getDate()) {
            // Mois complet
            return debut.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
        } else {
            // Partie du mois
            return `Du ${debut.getDate()} au ${fin.getDate()} ${debut.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })}`;
        }
    }
    
    // Même année
    if (debut.getFullYear() === fin.getFullYear()) {
        return `Du ${debut.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })} au ${fin.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}`;
    }
    
    // Années différentes
    return `Du ${debut.toLocaleDateString('fr-FR')} au ${fin.toLocaleDateString('fr-FR')}`;
}

// Event listeners pour la mise à jour automatique de la période
document.addEventListener('DOMContentLoaded', function() {
    // Pour le formulaire individuel
    const dateDebutIndividuel = document.getElementById('date_debut');
    const dateFinIndividuel = document.getElementById('date_fin');
    const periodePreviewIndividuel = document.getElementById('periode_preview');
    
    if (dateDebutIndividuel && dateFinIndividuel && periodePreviewIndividuel) {
        function mettreAJourPeriodeIndividuelle() {
            const periode = calculerPeriode(dateDebutIndividuel.value, dateFinIndividuel.value);
            periodePreviewIndividuel.value = periode;
        }
        
        dateDebutIndividuel.addEventListener('change', mettreAJourPeriodeIndividuelle);
        dateFinIndividuel.addEventListener('change', mettreAJourPeriodeIndividuelle);
        
        // Calcul initial si les dates sont déjà remplies
        mettreAJourPeriodeIndividuelle();
    }
    
    // Pour le formulaire départemental
    const dateDebutDept = document.querySelector('#departementModal [name="date_debut"]');
    const dateFinDept = document.querySelector('#departementModal [name="date_fin"]');
    const periodePreviewDept = document.getElementById('periode_preview_dept');
    
    if (dateDebutDept && dateFinDept && periodePreviewDept) {
        function mettreAJourPeriodeDepartementale() {
            const periode = calculerPeriode(dateDebutDept.value, dateFinDept.value);
            periodePreviewDept.value = periode;
        }
        
        dateDebutDept.addEventListener('change', mettreAJourPeriodeDepartementale);
        dateFinDept.addEventListener('change', mettreAJourPeriodeDepartementale);
        
        // Calcul initial si les dates sont déjà remplies
        mettreAJourPeriodeDepartementale();
    }
});

// Fonction pour supprimer un critère
function supprimerCritere(critereId) {
    if (confirm('Êtes-vous vraiment sûr de vouloir supprimer ce critère de pointage ?')) {
        // Soumettre le formulaire de suppression
        const form = document.getElementById('delete-form-' + critereId);
        if (form) {
            form.submit();
        } else {
            console.error('Formulaire de suppression non trouvé pour le critère ID:', critereId);
            alert('Erreur: Impossible de supprimer le critère. Veuillez recharger la page.');
        }
    }
}
</script>
@endpush

