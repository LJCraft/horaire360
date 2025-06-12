@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-building me-2"></i>Créer un planning pour le département : {{ $departement }}
                    </h5>
                    <a href="{{ route('plannings.departement.index', ['departement' => $departement]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Retour
                    </a>
                </div>

                <div class="card-body">
                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <form action="{{ route('plannings.departement.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="departement" value="{{ $departement }}">

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_debut" class="form-label">Date de début</label>
                                    <input type="date" class="form-control @error('date_debut') is-invalid @enderror" 
                                           id="date_debut" name="date_debut" value="{{ old('date_debut', date('Y-m-d')) }}" required>
                                    @error('date_debut')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_fin" class="form-label">Date de fin</label>
                                    <input type="date" class="form-control @error('date_fin') is-invalid @enderror" 
                                           id="date_fin" name="date_fin" value="{{ old('date_fin', date('Y-m-d', strtotime('+30 days'))) }}" required>
                                    @error('date_fin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                     id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="card border-primary mb-4">
                            <div class="card-header bg-primary text-white">
                                Détails du planning (horaires types pour la semaine)
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Jour</th>
                                                <th>Type</th>
                                                <th>Horaires</th>
                                                <th>Note</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $joursLabel = [
                                                    1 => 'Lundi',
                                                    2 => 'Mardi',
                                                    3 => 'Mercredi',
                                                    4 => 'Jeudi',
                                                    5 => 'Vendredi',
                                                    6 => 'Samedi',
                                                    7 => 'Dimanche'
                                                ];
                                            @endphp

                                            @for($jour = 1; $jour <= 7; $jour++)
                                            <tr>
                                                <td>{{ $joursLabel[$jour] }}</td>
                                                <td>
                                                    <select name="jour_type_{{ $jour }}" id="jour_type_{{ $jour }}" 
                                                            class="form-select form-select-sm jour-type" data-jour="{{ $jour }}">
                                                        <option value="">-- Sélectionner --</option>
                                                        <option value="jour_entier">Journée entière</option>
                                                        <option value="horaire">Horaire spécifique</option>
                                                        <option value="repos">Repos</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="row g-2 horaires-container" id="horaires_{{ $jour }}" style="display: none;">
                                                        <div class="col">
                                                            <input type="time" class="form-control form-control-sm" 
                                                                  name="heure_debut_{{ $jour }}" placeholder="Début">
                                                        </div>
                                                        <div class="col">
                                                            <input type="time" class="form-control form-control-sm" 
                                                                  name="heure_fin_{{ $jour }}" placeholder="Fin">
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="note_{{ $jour }}" placeholder="Note optionnelle">
                                                </td>
                                            </tr>
                                            @endfor
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card border-success mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-people me-2"></i>Sélectionner les employés
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Note :</strong> Si un employé a déjà un planning individuel pour cette période, il ne sera pas affecté par ce planning départemental.
                                </div>

                                <div class="row mb-3">
                                    <div class="col">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="select-all" value="1">
                                            <label class="form-check-label" for="select-all">
                                                <strong>Sélectionner tous les employés</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    @forelse($employes as $employe)
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input employe-check" type="checkbox" 
                                                   name="employe_ids[]" value="{{ $employe->id }}" 
                                                   id="employe_{{ $employe->id }}">
                                            <label class="form-check-label" for="employe_{{ $employe->id }}">
                                                {{ $employe->nom_complet }} - {{ $employe->poste->nom }}
                                            </label>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="col-12">
                                        <div class="alert alert-warning">
                                            Aucun employé trouvé pour ce département.
                                        </div>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('plannings.departement.index', ['departement' => $departement]) }}" class="btn btn-outline-secondary me-md-2">
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Enregistrer le planning départemental
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage des horaires
    const jourTypeSelects = document.querySelectorAll('.jour-type');
    jourTypeSelects.forEach(select => {
        select.addEventListener('change', function() {
            const jour = this.getAttribute('data-jour');
            const horairesContainer = document.getElementById('horaires_' + jour);
            
            if (this.value === 'horaire') {
                horairesContainer.style.display = 'flex';
            } else {
                horairesContainer.style.display = 'none';
            }
        });
    });
    
    // Gestion de la sélection de tous les employés
    const selectAllCheckbox = document.getElementById('select-all');
    const employeCheckboxes = document.querySelectorAll('.employe-check');
    
    selectAllCheckbox.addEventListener('change', function() {
        employeCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
    
    // Si on décoche un employé, décocher "select all"
    employeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                // Vérifier si tous les employés sont cochés
                const allChecked = Array.from(employeCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }
        });
    });
});
</script>
@endpush
@endsection 