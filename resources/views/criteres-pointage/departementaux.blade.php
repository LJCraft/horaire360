@extends('layouts.app')

@section('title', 'Critères Départementaux')

@section('content')
<div class="container-fluid">
    <!-- En-tête avec statistiques -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold text-primary">
                <i class="bi bi-building-gear me-2"></i>Critères Départementaux
            </h1>
            <p class="text-muted">Gestion et visualisation des critères de pointage par département</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('criteres-pointage.index') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-arrow-left me-1"></i>Retour aux critères
            </a>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createCritereModal">
                <i class="bi bi-plus-lg me-1"></i>Nouveau critère départemental
            </button>
        </div>
    </div>

    <!-- Messages Flash -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Erreurs de validation :</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Statistiques générales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="bi bi-list-ul" style="font-size: 2rem;"></i>
                    </div>
                    <h3 class="mb-1">{{ $totalCriteres }}</h3>
                    <p class="text-muted mb-0">Total critères</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                    </div>
                    <h3 class="mb-1">{{ $criteresActifs }}</h3>
                    <p class="text-muted mb-0">Critères actifs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="bi bi-pause-circle" style="font-size: 2rem;"></i>
                    </div>
                    <h3 class="mb-1">{{ $criteresInactifs }}</h3>
                    <p class="text-muted mb-0">Critères inactifs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-info mb-2">
                        <i class="bi bi-building" style="font-size: 2rem;"></i>
                    </div>
                    <h3 class="mb-1">{{ $departementsAvecCriteres }}</h3>
                    <p class="text-muted mb-0">Départements couverts</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0 text-primary">
                <i class="bi bi-funnel me-2"></i>Filtres
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('criteres-pointage.departementaux') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="departement_id" class="form-label">Département</label>
                        <select class="form-select" id="departement_id" name="departement_id">
                            <option value="">Tous les départements</option>
                            @foreach($departements as $dept)
                                <option value="{{ $dept->departement }}" {{ $departementId == $dept->departement ? 'selected' : '' }}>
                                    {{ $dept->departement }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="statut" class="form-label">Statut</label>
                        <select class="form-select" id="statut" name="statut">
                            <option value="tous" {{ $statut == 'tous' ? 'selected' : '' }}>Tous</option>
                            <option value="actif" {{ $statut == 'actif' ? 'selected' : '' }}>Actifs</option>
                            <option value="inactif" {{ $statut == 'inactif' ? 'selected' : '' }}>Inactifs</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="periode" class="form-label">Période</label>
                        <select class="form-select" id="periode" name="periode">
                            <option value="">Toutes les périodes</option>
                            <option value="jour" {{ $periode == 'jour' ? 'selected' : '' }}>Jour</option>
                            <option value="semaine" {{ $periode == 'semaine' ? 'selected' : '' }}>Semaine</option>
                            <option value="mois" {{ $periode == 'mois' ? 'selected' : '' }}>Mois</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-1"></i>Filtrer
                        </button>
                        <a href="{{ route('criteres-pointage.departementaux') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des critères -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary">
                <i class="bi bi-list-check me-2"></i>Liste des critères départementaux
            </h5>
            <span class="badge bg-primary rounded-pill">{{ $criteres->total() }} critères</span>
        </div>
        <div class="card-body p-0">
            @if($criteres->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Département</th>
                                <th>Période</th>
                                <th>Dates d'application</th>
                                <th>Configuration</th>
                                <th>Statut</th>
                                <th>Créé par</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($criteres as $critere)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 rounded p-2 me-2">
                                            <i class="bi bi-building text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium">{{ $critere->departement_id }}</div>
                                            <small class="text-muted">Département</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info rounded-pill">
                                        {{ ucfirst($critere->periode) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <div><strong>Du:</strong> {{ $critere->date_debut->format('d/m/Y') }}</div>
                                        <div><strong>Au:</strong> {{ $critere->date_fin->format('d/m/Y') }}</div>
                                        @if($critere->date_fin < now())
                                            <small class="text-danger">Expiré</small>
                                        @elseif($critere->date_debut > now())
                                            <small class="text-warning">À venir</small>
                                        @else
                                            <small class="text-success">En cours</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div><strong>Pointages:</strong> {{ $critere->nombre_pointages }}</div>
                                        <div><strong>Tolérance:</strong> -{{ $critere->tolerance_avant }}min / +{{ $critere->tolerance_apres }}min</div>
                                        <div><strong>Pause:</strong> {{ $critere->duree_pause }}min</div>
                                        <div><strong>Source:</strong> {{ ucfirst($critere->source_pointage) }}</div>
                                    </div>
                                </td>
                                <td>
                                    @if($critere->actif)
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </td>
                                <td>
                                    @if($critere->createur)
                                        <div class="small">
                                            <div class="fw-medium">{{ $critere->createur->name }}</div>
                                            <small class="text-muted">{{ $critere->createur->email }}</small>
                                        </div>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">
                                        <div>{{ $critere->created_at->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $critere->created_at->format('H:i') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('criteres-pointage.show', $critere->id) }}" 
                                           class="btn btn-outline-primary" 
                                           title="Voir les détails">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('criteres-pointage.edit', $critere->id) }}" 
                                           class="btn btn-outline-warning" 
                                           title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                title="Supprimer"
                                                onclick="confirmerSuppression({{ $critere->id }}, '{{ $critere->departement_id }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                @if($criteres->hasPages())
                <div class="card-footer bg-white">
                    {{ $criteres->appends(request()->query())->links() }}
                </div>
                @endif
            @else
                <div class="text-center py-5">
                    <div class="text-muted mb-3">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="text-muted">Aucun critère départemental trouvé</h5>
                    <p class="text-muted">Aucun critère ne correspond aux filtres sélectionnés.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCritereModal">
                        <i class="bi bi-plus-lg me-1"></i>Créer le premier critère
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal de création de critère départemental -->
<div class="modal fade" id="createCritereModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>Nouveau critère départemental
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('criteres-pointage.store') }}" method="POST" id="createCritereForm">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="niveau" value="departemental">
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="departement_id_create" class="form-label">Département <span class="text-danger">*</span></label>
                            <select class="form-select @error('departement_id') is-invalid @enderror" id="departement_id_create" name="departement_id" required>
                                <option value="">Sélectionner un département</option>
                                @foreach($departements as $dept)
                                    <option value="{{ $dept->departement }}" {{ old('departement_id') == $dept->departement ? 'selected' : '' }}>
                                        {{ $dept->departement }}
                                    </option>
                                @endforeach
                            </select>
                            @error('departement_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date_debut_create" class="form-label">Date de début <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date_debut') is-invalid @enderror" id="date_debut_create" name="date_debut" value="{{ old('date_debut', date('Y-m-d')) }}" required>
                            @error('date_debut')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="date_fin_create" class="form-label">Date de fin <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date_fin') is-invalid @enderror" id="date_fin_create" name="date_fin" value="{{ old('date_fin', date('Y-m-d', strtotime('+1 year'))) }}" required>
                            @error('date_fin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="periode_create" class="form-label">Période <span class="text-danger">*</span></label>
                            <select class="form-select @error('periode') is-invalid @enderror" id="periode_create" name="periode" required>
                                <option value="jour" {{ old('periode') == 'jour' ? 'selected' : '' }}>Jour</option>
                                <option value="semaine" {{ old('periode') == 'semaine' ? 'selected' : '' }}>Semaine</option>
                                <option value="mois" {{ old('periode', 'mois') == 'mois' ? 'selected' : '' }}>Mois</option>
                            </select>
                            @error('periode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="nombre_pointages_create" class="form-label">Nombre de pointages <span class="text-danger">*</span></label>
                            <select class="form-select @error('nombre_pointages') is-invalid @enderror" id="nombre_pointages_create" name="nombre_pointages" required>
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
                            <label for="tolerance_avant_create" class="form-label">Tolérance avant (minutes)</label>
                            <input type="number" class="form-control @error('tolerance_avant') is-invalid @enderror" id="tolerance_avant_create" name="tolerance_avant" value="{{ old('tolerance_avant', 10) }}" min="0" max="60" required>
                            @error('tolerance_avant')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="tolerance_apres_create" class="form-label">Tolérance après (minutes)</label>
                            <input type="number" class="form-control @error('tolerance_apres') is-invalid @enderror" id="tolerance_apres_create" name="tolerance_apres" value="{{ old('tolerance_apres', 10) }}" min="0" max="60" required>
                            @error('tolerance_apres')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="duree_pause_create" class="form-label">Durée de pause (minutes)</label>
                            <input type="number" class="form-control @error('duree_pause') is-invalid @enderror" id="duree_pause_create" name="duree_pause" value="{{ old('duree_pause', 60) }}" min="0" max="240" required>
                            @error('duree_pause')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="source_pointage_create" class="form-label">Source de pointage</label>
                            <select class="form-select @error('source_pointage') is-invalid @enderror" id="source_pointage_create" name="source_pointage" required>
                                <option value="tous" {{ old('source_pointage', 'tous') == 'tous' ? 'selected' : '' }}>Tous</option>
                                <option value="biometrique" {{ old('source_pointage') == 'biometrique' ? 'selected' : '' }}>Biométrique uniquement</option>
                                <option value="manuel" {{ old('source_pointage') == 'manuel' ? 'selected' : '' }}>Manuel uniquement</option>
                            </select>
                            @error('source_pointage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="calcul_heures_sup_create" name="calcul_heures_sup" {{ old('calcul_heures_sup') ? 'checked' : '' }}>
                        <label class="form-check-label" for="calcul_heures_sup_create">
                            Activer le calcul des heures supplémentaires
                        </label>
                    </div>

                    <div class="row mb-3" id="seuil_heures_sup_container_create" style="display: {{ old('calcul_heures_sup') ? 'block' : 'none' }};">
                        <div class="col-md-6">
                            <label for="seuil_heures_sup_create" class="form-label">Seuil heures supplémentaires (minutes)</label>
                            <input type="number" class="form-control @error('seuil_heures_sup') is-invalid @enderror" id="seuil_heures_sup_create" name="seuil_heures_sup" value="{{ old('seuil_heures_sup', 480) }}" min="0" max="1440">
                            @error('seuil_heures_sup')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="priorite_create" class="form-label">Priorité</label>
                            <select class="form-select" id="priorite_create" name="priorite">
                                <option value="1" {{ old('priorite') == '1' ? 'selected' : '' }}>Haute (1)</option>
                                <option value="2" {{ old('priorite', '2') == '2' ? 'selected' : '' }}>Normale (2)</option>
                                <option value="3" {{ old('priorite') == '3' ? 'selected' : '' }}>Basse (3)</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="actif_create" name="actif" checked>
                                <label class="form-check-label" for="actif_create">
                                    Critère actif
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success" id="btn-submit-create">
                        <i class="bi bi-check-lg me-1"></i>Créer le critère
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le critère départemental pour <strong id="departementNom"></strong> ?</p>
                <p class="text-danger">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmerSuppression(critereId, departementNom) {
    document.getElementById('departementNom').textContent = departementNom;
    document.getElementById('deleteForm').action = `/criteres-pointage/${critereId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Gérer l'affichage du seuil des heures supplémentaires dans le modal de création
    const calcHeuresSup = document.getElementById('calcul_heures_sup_create');
    const seuilContainer = document.getElementById('seuil_heures_sup_container_create');
    
    if (calcHeuresSup) {
        calcHeuresSup.addEventListener('change', function() {
            seuilContainer.style.display = this.checked ? 'block' : 'none';
        });
    }

    // Gérer la soumission du formulaire de création
    const createForm = document.getElementById('createCritereForm');
    if (createForm) {
        createForm.addEventListener('submit', function() {
            const submitBtn = document.getElementById('btn-submit-create');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Création...';
        });
    }

    // Si il y a des erreurs de validation, rouvrir le modal
    @if($errors->any())
        const createModal = new bootstrap.Modal(document.getElementById('createCritereModal'));
        createModal.show();
    @endif
    
    // Animation pour les cartes statistiques
    const statCards = document.querySelectorAll('.card h3');
    statCards.forEach(card => {
        const targetNumber = parseInt(card.textContent);
        if (targetNumber > 0) {
            let currentNumber = 0;
            const increment = Math.ceil(targetNumber / 20);
            
            const timer = setInterval(() => {
                currentNumber += increment;
                if (currentNumber >= targetNumber) {
                    currentNumber = targetNumber;
                    clearInterval(timer);
                }
                card.textContent = currentNumber;
            }, 50);
        }
    });

    // Filtrage en temps réel (optionnel)
    const departementFilter = document.getElementById('departement_id');
    const statutFilter = document.getElementById('statut');
    const periodeFilter = document.getElementById('periode');
    
    if (departementFilter || statutFilter || periodeFilter) {
        [departementFilter, statutFilter, periodeFilter].forEach(filter => {
            if (filter) {
                filter.addEventListener('change', function() {
                    // Auto-submit du formulaire de filtrage
                    this.closest('form').submit();
                });
            }
        });
    }
});
</script>
@endpush

@push('styles')
<style>
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

.badge {
    font-size: 0.75em;
}

.bg-opacity-10 {
    background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
}
</style>
@endpush 