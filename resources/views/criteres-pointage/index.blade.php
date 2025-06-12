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
                                                        {{ $critere->employe->nom }} {{ $critere->employe->prenom }}
                                                    @else
                                                        {{ $critere->departement->nom }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($critere->periode === 'jour')
                                                        <span class="badge bg-secondary">Jour</span>
                                                    @elseif ($critere->periode === 'semaine')
                                                        <span class="badge bg-secondary">Semaine</span>
                                                    @else
                                                        <span class="badge bg-secondary">Mois</span>
                                                    @endif
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
                                                        <a href="#" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $critere->id }}" title="Supprimer le critère">
                                                            <i class="bi bi-trash-fill fs-5 text-danger"></i>
                                                        </a>
                                                    </div>
                                                    
                                                    <!-- Modal de suppression -->
                                                    <div class="modal fade" id="deleteModal{{ $critere->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $critere->id }}" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-danger text-white">
                                                                    <h5 class="modal-title" id="deleteModalLabel{{ $critere->id }}">Confirmation de suppression</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    Êtes-vous sûr de vouloir supprimer ce critère de pointage ?
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                    <form action="{{ route('criteres-pointage.destroy', $critere) }}" method="POST">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-danger">Supprimer</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center">Aucun critère de pointage configuré</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-flex justify-content-center mt-3">
                                {{ $criteres->links() }}
                            </div>
                        </div>
                        
                        <!-- Onglet Configuration par département -->
                        <div class="tab-pane fade" id="departements" role="tabpanel" aria-labelledby="departements-tab">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Filtrer par département</h6>
                                        </div>
                                        <div class="card-body">
                                            <form id="departementFilterForm" action="javascript:void(0);">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label for="departement_filter" class="form-label">Département</label>
                                                        <select class="form-select" id="departement_filter" name="departement_id">
                                                            <option value="">Sélectionner un département</option>
                                                            @foreach ($departements as $departement)
                                                                <option value="{{ $departement->departement }}" {{ $departementId == $departement->departement ? 'selected' : '' }}>
                                                                    {{ $departement->nom }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label for="poste_filter" class="form-label">Poste</label>
                                                        <select class="form-select" id="poste_filter" name="poste_id" disabled required>
                                                            <option value="">Tous les postes</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label for="grade_filter" class="form-label">Grade</label>
                                                        <select class="form-select" id="grade_filter" name="grade_id" disabled required>
                                                            <option value="">Tous les grades</option>
                                                        </select>
                                                        <div class="form-text text-muted small"><i class="bi bi-info-circle"></i> Sélectionnez d'abord un poste</div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label for="periode_filter" class="form-label">Période</label>
                                                        <select class="form-select" id="periode_filter" name="periode" required>
                                                            <option value="jour" {{ $periode == 'jour' ? 'selected' : '' }}>Jour</option>
                                                            <option value="semaine" {{ $periode == 'semaine' ? 'selected' : '' }}>Semaine</option>
                                                            <option value="mois" {{ $periode == 'mois' ? 'selected' : '' }}>Mois</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12">
                                                        <button type="button" id="filterButton" class="btn btn-primary">
                                                            <i class="fas fa-filter me-1"></i> Filtrer
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Informations</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i> Sélectionnez un département pour afficher les employés et configurer les critères de pointage.
                                            </div>
                                            <div id="departement-stats" class="d-none">
                                                <h6 class="fw-bold">Département: <span id="departement-nom"></span></h6>
                                                <div class="row mt-3">
                                                    <div class="col-md-6">
                                                        <div class="card bg-light">
                                                            <div class="card-body py-2 px-3">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <span>Employés:</span>
                                                                    <span class="badge bg-primary" id="employes-count">0</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card bg-light">
                                                            <div class="card-body py-2 px-3">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <span>Configurés:</span>
                                                                    <span class="badge bg-success" id="employes-configured-count">0</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-success btn-sm" id="create-departement-critere">
                                                        <i class="fas fa-plus-circle me-1"></i> Créer un critère départemental
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Liste des employés du département -->
                            <div id="employes-container" class="d-none">
                                <div class="card">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Employés du département</h6>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="show-only-unconfigured" required>
                                            <label class="form-check-label" for="show-only-unconfigured">Afficher uniquement les employés non configurés</label>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="employes-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Employé</th>
                                                        <th>Poste</th>
                                                        <th>Grade</th>
                                                        <th>Statut</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Les employés seront chargés dynamiquement ici -->
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
                            <label for="periode" class="form-label">Période <span class="text-danger">*</span></label>
                            <select class="form-select @error('periode') is-invalid @enderror" id="periode" name="periode" required>
                                <option value="jour" {{ old('periode') == 'jour' ? 'selected' : '' }}>Jour</option>
                                <option value="semaine" {{ old('periode') == 'semaine' ? 'selected' : '' }}>Semaine</option>
                                <option value="mois" {{ old('periode', 'mois') == 'mois' ? 'selected' : '' }}>Mois</option>
                            </select>
                            @error('periode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                        {{ $departement->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="periode" class="form-label">Période</label>
                            <select class="form-select" id="periode" name="periode" required>
                                <option value="jour">Jour</option>
                                <option value="semaine">Semaine</option>
                                <option value="mois" selected>Mois</option>
                            </select>
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

@endsection

@section('scripts')
<!-- Script personnalisé pour la gestion des critères de pointage -->
<script src="{{ asset('js/criteres-pointage.js') }}"></script>
<script>
    // Initialiser les tooltips pour les icônes d'action
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                placement: 'top',
                trigger: 'hover'
            });
        });
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        // Filtrage par département
        const filterButton = document.getElementById('filterButton');
        const departementFilter = document.getElementById('departement_filter');
        const periodeFilter = document.getElementById('periode_filter');
        const posteFilter = document.getElementById('poste_filter');
        const employesContainer = document.getElementById('employes-container');
        const departementStats = document.getElementById('departement-stats');
        const departementNom = document.getElementById('departement-nom');
        const employesCount = document.getElementById('employes-count');
        const employesConfiguredCount = document.getElementById('employes-configured-count');
        const employesTable = document.getElementById('employes-table')?.querySelector('tbody');
        const showOnlyUnconfigured = document.getElementById('show-only-unconfigured');
        const createDepartementCritere = document.getElementById('create-departement-critere');
        
        // Éléments pour le modal de critère départemental
        const departementModal = document.getElementById('departementModal');
        const departementSelect = document.getElementById('departement_id');
        const periodeSelect = document.getElementById('periode');
        const employesList = document.getElementById('employes-list');
        
        // Code pour le filtrage des employés dans la liste principale
        
        // Chargement des employés lors du changement de département dans le modal
        if (departementSelect) {
            departementSelect.addEventListener('change', function() {
                const departementId = this.value;
                if (departementId) {
                    loadEmployesForSelection(departementId);
                } else {
                    employesList.innerHTML = '<div class="text-center">Veuillez sélectionner un département</div>';
                }
            });
        }
        
        // Mise à jour des postes lors du changement de département
        if (departementFilter) {
            departementFilter.addEventListener('change', function() {
                const departementId = this.value;
                if (departementId) {
                    updatePostesByDepartement(departementId);
                } else {
                    // Réinitialiser le sélecteur de postes
                    if (posteFilter) {
                        posteFilter.innerHTML = '<option value="">Tous les postes</option>';
                        posteFilter.disabled = true;
                    }
                    
                    // Réinitialiser le sélecteur de grades
                    const gradeFilter = document.getElementById('grade_filter');
                    if (gradeFilter) {
                        gradeFilter.innerHTML = '<option value="">Tous les grades</option>';
                        gradeFilter.disabled = true;
                    }
                }
            });
        }
        
        // Mise à jour des grades lors du changement de poste
        const posteFilter = document.getElementById('poste_filter');
        const gradeFilter = document.getElementById('grade_filter');
        
        if (posteFilter) {
            posteFilter.addEventListener('change', function() {
                const posteId = this.value;
                if (posteId) {
                    updateGradesByPoste(posteId);
                } else {
                    // Réinitialiser le sélecteur de grades
                    if (gradeFilter) {
                        gradeFilter.innerHTML = '<option value="">Tous les grades</option>';
                        gradeFilter.disabled = true;
                    }
                }
            });
        }
        
        // Fonction pour mettre à jour les postes en fonction du département
        function updatePostesByDepartement(departementId) {
            if (!posteFilter) return;
            
            fetch('{{ route("criteres-pointage.get-postes-departement") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    departement_id: departementId
                })
            })
            .then(response => response.json())
            .then(data => {
                // Réinitialiser le sélecteur de postes
                posteFilter.innerHTML = '<option value="">Tous les postes</option>';
                
                // Ajouter les postes du département
                data.postes.forEach(poste => {
                    const option = document.createElement('option');
                    option.value = poste.id;
                    option.textContent = poste.nom;
                    posteFilter.appendChild(option);
                });
                
                // Activer le sélecteur de postes
                posteFilter.disabled = false;
                
                // Réinitialiser le sélecteur de grades
                if (gradeFilter) {
                    gradeFilter.innerHTML = '<option value="">Tous les grades</option>';
                    gradeFilter.disabled = true;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                posteFilter.innerHTML = '<option value="">Erreur de chargement</option>';
            });
        }
        
        // Fonction pour mettre à jour les grades en fonction du poste
        function updateGradesByPoste(posteId) {
            if (!gradeFilter) return;
            
            fetch('{{ route("criteres-pointage.get-grades-poste") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    poste_id: posteId
                })
            })
            .then(response => response.json())
            .then(data => {
                // Réinitialiser le sélecteur de grades
                gradeFilter.innerHTML = '<option value="">Tous les grades</option>';
                
                // Ajouter les grades du poste
                if (data.grades && data.grades.length > 0) {
                    data.grades.forEach(grade => {
                        const option = document.createElement('option');
                        option.value = grade.id;
                        option.textContent = grade.nom;
                        gradeFilter.appendChild(option);
                    });
                    
                    // Activer le sélecteur de grades
                    gradeFilter.disabled = false;
                    
                    // Mettre à jour le message d'information
                    const gradeInfoDiv = gradeFilter.nextElementSibling;
                    if (gradeInfoDiv && gradeInfoDiv.classList.contains('form-text')) {
                        gradeInfoDiv.innerHTML = `<i class="bi bi-info-circle"></i> ${data.grades.length} grade(s) disponible(s) pour ce poste`;
                        gradeInfoDiv.classList.remove('text-muted');
                        gradeInfoDiv.classList.add('text-primary');
                    }
                } else {
                    // Aucun grade disponible pour ce poste
                    gradeFilter.disabled = true;
                    
                    // Mettre à jour le message d'information
                    const gradeInfoDiv = gradeFilter.nextElementSibling;
                    if (gradeInfoDiv && gradeInfoDiv.classList.contains('form-text')) {
                        gradeInfoDiv.innerHTML = `<i class="bi bi-exclamation-circle"></i> Aucun grade disponible pour ce poste`;
                        gradeInfoDiv.classList.remove('text-muted');
                        gradeInfoDiv.classList.add('text-warning');
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                gradeFilter.innerHTML = '<option value="">Erreur de chargement</option>';
                gradeFilter.disabled = true;
            });
        }
        
        // Filtrer les employés par département
        if (filterButton) {
            filterButton.addEventListener('click', function() {
                const departementId = departementFilter.value;
                const periode = periodeFilter.value;
                const posteId = posteFilter ? posteFilter.value : null;
                const gradeId = gradeFilter ? gradeFilter.value : null;
                
                if (!departementId) {
                    alert('Veuillez sélectionner un département');
                    return;
                }
                
                // Charger les employés du département
                fetch('{{ route("criteres-pointage.get-employes-departement") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        departement_id: departementId,
                        periode: periode,
                        poste_id: posteId,
                        grade_id: gradeId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Afficher les statistiques du département
                    departementStats.classList.remove('d-none');
                    departementNom.textContent = data.departement;
                    
                    // Compter les employés configurés
                    const employesConfigured = data.employes.filter(employe => employe.a_critere).length;
                    employesCount.textContent = data.employes.length;
                    employesConfiguredCount.textContent = employesConfigured;
                    
                    // Afficher la liste des employés
                    employesContainer.classList.remove('d-none');
                    renderEmployes(data.employes);
                    
                    // Mettre à jour le bouton de création de critère départemental
                    if (createDepartementCritere) {
                        createDepartementCritere.dataset.departementId = departementId;
                        createDepartementCritere.dataset.periode = periode;
                    }
                    
                    // Mettre à jour les postes dans le sélecteur
                    if (data.postes && posteFilter) {
                        posteFilter.innerHTML = '<option value="">Tous les postes</option>';
                        data.postes.forEach(poste => {
                            const option = document.createElement('option');
                            option.value = poste.id;
                            option.textContent = poste.nom;
                            posteFilter.appendChild(option);
                        });
                        posteFilter.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors du chargement des employés');
                });
            });
        }
        
        // Filtrer les employés affichés (configurés/non configurés)
        if (showOnlyUnconfigured) {
            showOnlyUnconfigured.addEventListener('change', function() {
                const rows = employesTable.querySelectorAll('tr');
                rows.forEach(row => {
                    if (this.checked && row.dataset.hasCritere === 'true') {
                        row.classList.add('d-none');
                    } else {
                        row.classList.remove('d-none');
                    }
                });
            });
        }
        
        // Ouvrir le modal de création de critère départemental
        if (createDepartementCritere) {
            createDepartementCritere.addEventListener('click', function() {
                const departementId = this.dataset.departementId;
                const periode = this.dataset.periode;
                
                // Pré-remplir le formulaire
                departementSelect.value = departementId;
                document.getElementById('periode').value = periode;
                
                // Charger les employés pour la sélection
                loadEmployesForSelection(departementId);
                
                // Ouvrir le modal
                const modal = new bootstrap.Modal(departementModal);
                modal.show();
            });
        }
        
        // Fonction pour charger les employés pour la sélection dans le modal départemental
        function loadEmployesForSelection(departementId) {
            if (!employesList) return;
            
            // Afficher un indicateur de chargement
            employesList.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div><p class="mt-2">Chargement des employés...</p></div>';
            
            fetch('{{ route("criteres-pointage.get-employes-departement") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    departement_id: departementId,
                    periode: periodeSelect ? periodeSelect.value : 'mois'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.employes && data.employes.length > 0) {
                    employesList.innerHTML = '';
                    
                    // Créer une liste de cases à cocher pour les employés
                    data.employes.forEach(employe => {
                        const employeItem = document.createElement('div');
                        employeItem.className = 'form-check mb-2';
                        employeItem.innerHTML = `
                            <input class="form-check-input" type="checkbox" name="employes[]" value="${employe.id}" id="employe-${employe.id}">
                            <label class="form-check-label d-flex align-items-center" for="employe-${employe.id}">
                                <img src="${employe.photo || '/images/default-avatar.png'}" alt="${employe.nom}" class="me-2" style="width: 30px; height: 30px; object-fit: cover; border-radius: 50%;">
                                <div>
                                    <span class="fw-bold">${employe.nom} ${employe.prenom}</span>
                                    <small class="d-block text-muted">${employe.poste || 'Non assigné'} ${employe.grade ? '- ' + employe.grade : ''}</small>
                                </div>
                            </label>
                        `;
                        employesList.appendChild(employeItem);
                    });
                    
                    // Ajouter des boutons pour sélectionner/désélectionner tous
                    const buttonsDiv = document.createElement('div');
                    buttonsDiv.className = 'mt-3 d-flex gap-2';
                    buttonsDiv.innerHTML = `
                        <button type="button" class="btn btn-sm btn-outline-primary" id="select-all-employes">Sélectionner tous</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselect-all-employes">Désélectionner tous</button>
                    `;
                    employesList.appendChild(buttonsDiv);
                    
                    // Ajouter les événements pour les boutons
                    document.getElementById('select-all-employes').addEventListener('click', function() {
                        const checkboxes = employesList.querySelectorAll('input[type="checkbox"]');
                        checkboxes.forEach(checkbox => checkbox.checked = true);
                    });
                    
                    document.getElementById('deselect-all-employes').addEventListener('click', function() {
                        const checkboxes = employesList.querySelectorAll('input[type="checkbox"]');
                        checkboxes.forEach(checkbox => checkbox.checked = false);
                    });
                } else {
                    employesList.innerHTML = '<div class="alert alert-warning">Aucun employé trouvé dans ce département</div>';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                employesList.innerHTML = '<div class="alert alert-danger">Une erreur est survenue lors du chargement des employés</div>';
            });
        }
        
        // Fonction pour afficher les employés dans le tableau
        function renderEmployes(employes) {
            employesTable.innerHTML = '';
            
            if (employes.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = `<td colspan="5" class="text-center">Aucun employé trouvé dans ce département</td>`;
                employesTable.appendChild(row);
                return;
            }
            
            employes.forEach(employe => {
                const row = document.createElement('tr');
                row.dataset.hasCritere = employe.a_critere;
                
                if (showOnlyUnconfigured && showOnlyUnconfigured.checked && employe.a_critere) {
                    row.classList.add('d-none');
                }
                
                row.innerHTML = `
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${employe.photo}" alt="${employe.nom}" class="employe-avatar me-2">
                            <div>
                                <div class="fw-bold">${employe.nom} ${employe.prenom}</div>
                            </div>
                        </div>
                    </td>
                    <td>${employe.poste}</td>
                    <td>${employe.grade}</td>
                    <td>
                        ${employe.a_critere ? 
                            '<span class="badge bg-success">Configuré</span>' : 
                            '<span class="badge bg-warning text-dark">Non configuré</span>'}
                    </td>
                    <td>
                        <a href="{{ route('criteres-pointage.create') }}?employe_id=${employe.id}" class="btn btn-sm btn-primary">
                            <i class="fas fa-cog"></i> Configurer
                        </a>
                    </td>
                `;
                
                employesTable.appendChild(row);
            });
        }
        
        // Le code pour le chargement des employés lors du changement de département
        // a été déplacé dans le fichier criteres-pointage.js
        // Initialiser la page si un département est déjà sélectionné
        if (departementFilter && departementFilter.value) {
            // Mettre à jour les postes d'abord
            updatePostesByDepartement(departementFilter.value);
            // Puis cliquer sur le bouton de filtrage
            filterButton.click();
        }

        // Gérer la soumission AJAX du formulaire de critère départemental
        const departementalForm = document.getElementById('critere-departemental-form');
        const departementalMessageContainer = document.getElementById('departemental-message-container');
        const departementModalElement = document.getElementById('departementModal');

        if (departementalForm) {
            departementalForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Empêche la soumission normale du formulaire

                // Réinitialiser les messages et les erreurs
                departementalMessageContainer.innerHTML = '';
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(({ status, body }) => {
                    if (status === 200 || status === 201) {
                        // Succès
                        departementalMessageContainer.innerHTML = `
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                ${body.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        // Fermer le modal après un court délai
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(departementModalElement);
                            if (modal) {
                                modal.hide();
                            }
                            // Recharger la page pour rafraîchir la liste des critères
                            location.reload(); 
                        }, 1500); 
                    } else if (status === 422) {
                        // Erreurs de validation
                        let errorMessage = '<div class="alert alert-danger"><ul>';
                        for (const key in body.errors) {
                            if (body.errors.hasOwnProperty(key)) {
                                const inputElement = departementalForm.querySelector(`[name="${key}"]`);
                                if (inputElement) {
                                    inputElement.classList.add('is-invalid');
                                    const feedbackDiv = document.createElement('div');
                                    feedbackDiv.classList.add('invalid-feedback');
                                    feedbackDiv.textContent = body.errors[key][0];
                                    inputElement.parentNode.appendChild(feedbackDiv);
                                }
                                errorMessage += `<li>${body.errors[key][0]}</li>`;
                            }
                        }
                        errorMessage += '</ul></div>';
                        departementalMessageContainer.innerHTML = errorMessage;
                    } else {
                        // Autres erreurs serveur
                        departementalMessageContainer.innerHTML = `
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                Une erreur est survenue lors de l'enregistrement.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erreur AJAX:', error);
                    departementalMessageContainer.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Erreur de connexion au serveur.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                });
            });
        }
    });
</script>
@endsection
