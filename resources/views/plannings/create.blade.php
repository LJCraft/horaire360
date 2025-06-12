@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Créer un nouveau planning</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('plannings.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(count($employesSansPlannings) > 0)
    <div class="card mb-4">
        <div class="card-header bg-warning-subtle">
            <h5 class="card-title mb-0">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Employés sans planning ({{ count($employesSansPlannings) }})
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($employesSansPlannings->take(5) as $employe)
                <div class="col-md-4 mb-2">
                    <div class="d-flex align-items-center border rounded p-2">
                        <div class="flex-grow-1">
                            <div class="fw-bold">{{ $employe->nom }} {{ $employe->prenom }}</div>
                            <small class="text-muted">{{ $employe->matricule }}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary select-no-planning" data-id="{{ $employe->id }}">
                            Sélectionner
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            
            @if(count($employesSansPlannings) > 5)
            <div class="text-center mt-3">
                <button type="button" class="btn btn-outline-primary btn-sm" id="show-all-no-planning">
                    Voir tous ({{ count($employesSansPlannings) }})
                </button>
            </div>
            
            <div id="all-no-planning" class="mt-3" style="display: none;">
                <div class="row">
                    @foreach($employesSansPlannings->skip(5) as $employe)
                    <div class="col-md-4 mb-2">
                        <div class="d-flex align-items-center border rounded p-2">
                            <div class="flex-grow-1">
                                <div class="fw-bold">{{ $employe->nom }} {{ $employe->prenom }}</div>
                                <small class="text-muted">{{ $employe->matricule }}</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary select-no-planning" data-id="{{ $employe->id }}">
                                Sélectionner
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('plannings.store') }}" method="POST">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employe_id" class="form-label">Employé <span class="text-danger">*</span></label>
                        <select class="form-select @error('employe_id') is-invalid @enderror" id="employe_id" name="employe_id" required>
                            <option value="">Sélectionner un employé</option>
                            @foreach($employes as $employe)
                                <option value="{{ $employe->id }}" {{ old('employe_id', $employe_id) == $employe->id ? 'selected' : '' }}>
                                    {{ $employe->nom }} {{ $employe->prenom }} ({{ $employe->matricule }})
                                </option>
                            @endforeach
                        </select>
                        @error('employe_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="titre" class="form-label">Titre du planning</label>
                        <input type="text" class="form-control @error('titre') is-invalid @enderror" id="titre" name="titre" value="{{ old('titre') }}">
                        @error('titre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="date_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_debut') is-invalid @enderror" id="date_debut" name="date_debut" value="{{ old('date_debut', isset($planning) && $planning->date_debut ? $planning->date_debut->format('Y-m-d') : '') }}" required>
                        @error('date_debut')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="date_fin" class="form-label">Date de fin <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_fin') is-invalid @enderror" id="date_fin" name="date_fin" value="{{ old('date_fin', isset($planning) && $planning->date_fin ? $planning->date_fin->format('Y-m-d') : '') }}" required>
                        @error('date_fin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <hr class="my-4">
                <h4 class="mb-3">Détails du planning</h4>
                
                <!-- Planning hebdomadaire -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Jour</th>
                                <th>Type</th>
                                <th>Heure de début</th>
                                <th>Heure de fin</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                            @endphp
                            
                            @foreach($jours as $index => $jour)
                                @php $jourIndex = $index + 1; @endphp
                                <tr>
                                    <td class="align-middle">{{ $jour }}</td>
                                    <td>
                                        <select class="form-select form-select-sm jour-type" id="jour_type_{{ $jourIndex }}" name="jour_type_{{ $jourIndex }}" data-jour="{{ $jourIndex }}">
                                            <option value="">Non défini</option>
                                            <option value="horaire" {{ old('jour_type_' . $jourIndex) === 'horaire' ? 'selected' : '' }}>Horaire spécifique</option>
                                            <option value="jour_entier" {{ old('jour_type_' . $jourIndex) === 'jour_entier' ? 'selected' : '' }}>Journée complète</option>
                                            <option value="repos" {{ old('jour_type_' . $jourIndex) === 'repos' ? 'selected' : '' }}>Repos</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="time" class="form-control form-control-sm horaire-field horaire-debut-{{ $jourIndex }}" id="heure_debut_{{ $jourIndex }}" name="heure_debut_{{ $jourIndex }}" value="{{ old('heure_debut_' . $jourIndex, '09:00') }}" {{ old('jour_type_' . $jourIndex) === 'horaire' ? '' : 'disabled' }}>
                                    </td>
                                    <td>
                                        <input type="time" class="form-control form-control-sm horaire-field horaire-fin-{{ $jourIndex }}" id="heure_fin_{{ $jourIndex }}" name="heure_fin_{{ $jourIndex }}" value="{{ old('heure_fin_' . $jourIndex, '17:00') }}" {{ old('jour_type_' . $jourIndex) === 'horaire' ? '' : 'disabled' }}>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" id="note_{{ $jourIndex }}" name="note_{{ $jourIndex }}" value="{{ old('note_' . $jourIndex) }}" placeholder="Note optionnelle">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="reset" class="btn btn-outline-secondary me-md-2">Réinitialiser</button>
                    <button type="submit" class="btn btn-primary">Enregistrer le planning</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier que le token CSRF est présent
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const csrfInput = document.querySelector('input[name="_token"]');
        
        // Si le token CSRF manque dans le formulaire, l'ajouter
        if (!csrfInput && csrfToken) {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = csrfToken;
                form.appendChild(input);
            });
        }
        
        const employeSelect = document.getElementById('employe_id');
        
        // Bouton pour afficher tous les employés sans planning
        const showAllNoPlanning = document.getElementById('show-all-no-planning');
        if (showAllNoPlanning) {
            showAllNoPlanning.addEventListener('click', function() {
                const allNoPlanning = document.getElementById('all-no-planning');
                if (allNoPlanning.style.display === 'none') {
                    allNoPlanning.style.display = 'block';
                    showAllNoPlanning.textContent = 'Masquer';
                } else {
                    allNoPlanning.style.display = 'none';
                    showAllNoPlanning.textContent = 'Voir tous ({{ count($employesSansPlannings) }})';
                }
            });
        }
        
        // Gérer les boutons de sélection pour les employés sans planning
        document.querySelectorAll('.select-no-planning').forEach(button => {
            button.addEventListener('click', function() {
                const employeId = this.dataset.id;
                
                // Sélectionner l'option dans le select
                Array.from(employeSelect.options).forEach(option => {
                    if (option.value === employeId) {
                        option.selected = true;
                    }
                });

                // Ajouter une date par défaut si non remplie
                const dateDebut = document.getElementById('date_debut');
                const dateFin = document.getElementById('date_fin');
                
                if (!dateDebut.value) {
                    const today = new Date();
                    const nextMonth = new Date(today);
                    nextMonth.setMonth(today.getMonth() + 1);
                    
                    dateDebut.value = today.toISOString().split('T')[0];
                    dateFin.value = nextMonth.toISOString().split('T')[0];
                }
                
                // Focus sur le champ date_debut pour guider l'utilisateur vers la prochaine étape
                dateDebut.focus();
            });
        });
        
        // S'assurer que le formulaire contient un token CSRF valide avant soumission
        const planningForm = document.querySelector('form[action="{{ route('plannings.store') }}"]');
        if (planningForm) {
            planningForm.addEventListener('submit', function(e) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                let csrfInput = planningForm.querySelector('input[name="_token"]');
                
                if (!csrfInput) {
                    e.preventDefault();
                    
                    // Créer et ajouter un input CSRF
                    csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrfToken;
                    planningForm.appendChild(csrfInput);
                    
                    // Soumettre à nouveau le formulaire
                    setTimeout(() => planningForm.submit(), 100);
                } else {
                    // Mettre à jour la valeur du token
                    csrfInput.value = csrfToken;
                }
            });
        }
        
        // Gestion des changements de type de jour pour activer/désactiver les champs d'horaire
        const jourTypes = document.querySelectorAll('.jour-type');
        
        jourTypes.forEach(function(select) {
            select.addEventListener('change', function() {
                const jour = this.dataset.jour;
                const horaireDebutField = document.querySelector('.horaire-debut-' + jour);
                const horaireFinField = document.querySelector('.horaire-fin-' + jour);
                
                if (this.value === 'horaire') {
                    horaireDebutField.disabled = false;
                    horaireFinField.disabled = false;
                } else {
                    horaireDebutField.disabled = true;
                    horaireFinField.disabled = true;
                }
            });
        });
    });
</script>
@endpush
@endsection