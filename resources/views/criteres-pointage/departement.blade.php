@extends('layouts.app')

@section('title', 'Critères par département')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold text-primary">
                <i class="bi bi-building-gear me-2"></i>Configuration par département
            </h1>
            <p class="text-muted">Gérez les critères de pointage par département</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('criteres-pointage.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i>Retour aux critères
            </a>
        </div>
    </div>

    <!-- Filtre département -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0">
                <i class="fas fa-filter me-2"></i>Sélectionner un département
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('criteres-pointage.departement') }}" class="row g-3">
                <div class="col-md-6">
                    <label for="departement_id" class="form-label">Département</label>
                    <select name="departement_id" id="departement_id" class="form-select" required>
                        <option value="">-- Choisir un département --</option>
                        @foreach($departements as $d)
                            <option value="{{ $d->id }}" {{ ($departementId==$d->id)?'selected':'' }}>{{ $d->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Afficher
                    </button>
                    @if($departementId)
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createDeptCritereModal">
                            <i class="fas fa-plus me-1"></i>Nouveau critère départemental
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($departementId)
    <div class="row">
        <div class="col-md-4">
            <h6>Postes</h6>
            <ul class="list-group">
                @foreach($postes as $p)
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        {{ $p->nom }}
                        <span class="badge bg-primary rounded-pill">{{ $employesParPoste[$p->id]->count() ?? 0 }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="col-md-8">
            <table class="table table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Employé</th>
                        <th>Poste</th>
                        <th>Critère</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employesParPoste->flatten() as $emp)
                        @php $c=$critereParEmploye[$emp->id]??null; @endphp
                        <tr>
                            <td>{{ $emp->nom }} {{ $emp->prenom }}</td>
                            <td>{{ $emp->poste?->nom }}</td>
                            <td>
                                @if($c)
                                    <span class="badge bg-{{ $c->niveau=='departemental'?'danger':'success' }}">{{ ucfirst($c->niveau) }}</span>
                                @else
                                    <span class="text-muted">Aucun</span>
                                @endif
                            </td>
                            <td>
                                @if($c)
                                    <a href="{{ route('criteres-pointage.show-custom',$c->id) }}" class="btn btn-sm btn-outline-info">Voir</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal pour créer un critère départemental -->
    <div class="modal fade" id="createDeptCritereModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>Nouveau critère départemental
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('criteres-pointage.store') }}" method="POST" id="deptCritereForm">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" name="niveau" value="departemental">
                        <input type="hidden" name="departement_id" value="{{ $departementId }}">
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Ce critère s'appliquera à tout le département <strong>{{ $departementId }}</strong>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="date_debut_dept" class="form-label">Date de début <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date_debut_dept" name="date_debut" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="date_fin_dept" class="form-label">Date de fin <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date_fin_dept" name="date_fin" value="{{ date('Y-m-d', strtotime('+1 year')) }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="periode_dept" class="form-label">Période <span class="text-danger">*</span></label>
                                <select class="form-select" id="periode_dept" name="periode" required>
                                    <option value="jour">Jour</option>
                                    <option value="semaine">Semaine</option>
                                    <option value="mois" selected>Mois</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="nombre_pointages_dept" class="form-label">Nombre de pointages <span class="text-danger">*</span></label>
                                <select class="form-select" id="nombre_pointages_dept" name="nombre_pointages" required>
                                    <option value="1">1 (Présence uniquement)</option>
                                    <option value="2" selected>2 (Arrivée et départ)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tolerance_avant_dept" class="form-label">Tolérance avant (minutes)</label>
                                <input type="number" class="form-control" id="tolerance_avant_dept" name="tolerance_avant" value="10" min="0" max="60" required>
                            </div>
                            <div class="col-md-6">
                                <label for="tolerance_apres_dept" class="form-label">Tolérance après (minutes)</label>
                                <input type="number" class="form-control" id="tolerance_apres_dept" name="tolerance_apres" value="10" min="0" max="60" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="duree_pause_dept" class="form-label">Durée de pause (minutes)</label>
                                <input type="number" class="form-control" id="duree_pause_dept" name="duree_pause" value="60" min="0" max="240" required>
                            </div>
                            <div class="col-md-6">
                                <label for="source_pointage_dept" class="form-label">Source de pointage</label>
                                <select class="form-select" id="source_pointage_dept" name="source_pointage" required>
                                    <option value="tous" selected>Tous</option>
                                    <option value="biometrique">Biométrique uniquement</option>
                                    <option value="manuel">Manuel uniquement</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="calcul_heures_sup_dept" name="calcul_heures_sup">
                            <label class="form-check-label" for="calcul_heures_sup_dept">
                                Activer le calcul des heures supplémentaires
                            </label>
                        </div>

                        <div class="row mb-3" id="seuil_heures_sup_container_dept" style="display: none;">
                            <div class="col-md-6">
                                <label for="seuil_heures_sup_dept" class="form-label">Seuil heures supplémentaires (minutes)</label>
                                <input type="number" class="form-control" id="seuil_heures_sup_dept" name="seuil_heures_sup" value="480" min="0" max="1440">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success" id="btn-submit-dept">
                            <i class="bi bi-check-lg me-1"></i>Créer le critère
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gérer l'affichage du seuil des heures supplémentaires
    const calcHeuresSup = document.getElementById('calcul_heures_sup_dept');
    const seuilContainer = document.getElementById('seuil_heures_sup_container_dept');
    
    if (calcHeuresSup) {
        calcHeuresSup.addEventListener('change', function() {
            seuilContainer.style.display = this.checked ? 'block' : 'none';
        });
    }

    // Gérer la soumission du formulaire
    const deptForm = document.getElementById('deptCritereForm');
    if (deptForm) {
        deptForm.addEventListener('submit', function() {
            const submitBtn = document.getElementById('btn-submit-dept');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Création...';
        });
    }
});
</script>
@endpush
