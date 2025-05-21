@extends('layouts.app')

@section('title', 'Créer un critère de pointage')

@section('styles')
<style>
    .planning-calendar {
        margin-top: 20px;
    }
    .planning-day {
        margin-bottom: 10px;
        padding: 10px;
        border-radius: 5px;
        background-color: #f8f9fa;
    }
    .planning-header {
        font-weight: bold;
        margin-bottom: 5px;
    }
    .planning-time {
        color: #495057;
    }
    .planning-employee {
        margin-bottom: 15px;
        padding: 10px;
        border-left: 3px solid #0d6efd;
        background-color: #f8f9fa;
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
                        <i class="fas fa-plus-circle me-2"></i> Créer un critère de pointage
                    </h5>
                    <a href="{{ route('criteres-pointage.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form id="critereForm" action="{{ route('criteres-pointage.store') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Niveau de configuration</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Niveau de configuration</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="niveau" id="niveau_individuel" value="individuel" checked>
                                                <label class="form-check-label" for="niveau_individuel">
                                                    Individuel (par employé)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="niveau" id="niveau_departemental" value="departemental">
                                                <label class="form-check-label" for="niveau_departemental">
                                                    Départemental (par service)
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3" id="employe_section">
                                            <label for="employe_id" class="form-label">Employé</label>
                                            <select class="form-select @error('employe_id') is-invalid @enderror" id="employe_id" name="employe_id">
                                                <option value="">Sélectionner un employé</option>
                                                @foreach ($employes as $employe)
                                                    <option value="{{ $employe->id }}" {{ old('employe_id') == $employe->id ? 'selected' : '' }}>
                                                        {{ $employe->nom }} {{ $employe->prenom }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('employe_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="mb-3" id="departement_section" style="display: none;">
                                            <label for="departement_id" class="form-label">Département</label>
                                            <select class="form-select @error('departement_id') is-invalid @enderror" id="departement_id" name="departement_id">
                                                <option value="">Sélectionner un département</option>
                                                @foreach ($departements as $departement)
                                                    <option value="{{ $departement->id }}" {{ old('departement_id') == $departement->id ? 'selected' : '' }}>
                                                        {{ $departement->nom }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('departement_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Période</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Sélection de la période</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="periode" id="periode_jour" value="jour" checked>
                                                <label class="form-check-label" for="periode_jour">
                                                    Jour
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="periode" id="periode_semaine" value="semaine">
                                                <label class="form-check-label" for="periode_semaine">
                                                    Semaine
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="periode" id="periode_mois" value="mois">
                                                <label class="form-check-label" for="periode_mois">
                                                    Mois
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="date_debut" class="form-label">Date de début</label>
                                            <input type="date" class="form-control @error('date_debut') is-invalid @enderror" id="date_debut" name="date_debut" value="{{ old('date_debut', date('Y-m-d')) }}">
                                            @error('date_debut')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="date_fin" class="form-label">Date de fin</label>
                                            <input type="date" class="form-control @error('date_fin') is-invalid @enderror" id="date_fin" name="date_fin" value="{{ old('date_fin', date('Y-m-d')) }}" readonly>
                                            @error('date_fin')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Planning de référence</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Le planning affiché ci-dessous est basé sur les plannings existants pour la période sélectionnée. Ces horaires servent de référence et ne sont pas modifiables.
                                </div>
                                
                                <div id="planning-container" class="planning-calendar">
                                    <div class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Chargement...</span>
                                        </div>
                                        <p class="mt-2">Veuillez sélectionner un employé ou un département et une période pour afficher le planning</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Configuration des critères</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="nombre_pointages" class="form-label">Nombre de pointages requis</label>
                                            <select class="form-select @error('nombre_pointages') is-invalid @enderror" id="nombre_pointages" name="nombre_pointages">
                                                <option value="1" {{ old('nombre_pointages') == 1 ? 'selected' : '' }}>1 pointage (présence uniquement)</option>
                                                <option value="2" {{ old('nombre_pointages', 2) == 2 ? 'selected' : '' }}>2 pointages (arrivée et départ)</option>
                                            </select>
                                            @error('nombre_pointages')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text" id="pointage_info">
                                                Avec 2 pointages, l'employé doit pointer à l'arrivée et au départ dans les plages de tolérance.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="source_pointage" class="form-label">Source de pointage</label>
                                            <select class="form-select @error('source_pointage') is-invalid @enderror" id="source_pointage" name="source_pointage">
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
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="tolerance_avant" class="form-label">Tolérance avant (minutes)</label>
                                            <input type="number" class="form-control @error('tolerance_avant') is-invalid @enderror" id="tolerance_avant" name="tolerance_avant" value="{{ old('tolerance_avant', 10) }}" min="0" max="60">
                                            @error('tolerance_avant')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="tolerance_apres" class="form-label">Tolérance après (minutes)</label>
                                            <input type="number" class="form-control @error('tolerance_apres') is-invalid @enderror" id="tolerance_apres" name="tolerance_apres" value="{{ old('tolerance_apres', 10) }}" min="0" max="60">
                                            @error('tolerance_apres')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="duree_pause" class="form-label">Durée de pause (minutes)</label>
                                            <input type="number" class="form-control @error('duree_pause') is-invalid @enderror" id="duree_pause" name="duree_pause" value="{{ old('duree_pause', 60) }}" min="0" max="240">
                                            @error('duree_pause')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Cette durée sera déduite du temps de travail pour le calcul des heures.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='{{ route('criteres-pointage.index') }}'">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer les critères</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion de l'affichage des sections en fonction du niveau
        const niveauIndividuel = document.getElementById('niveau_individuel');
        const niveauDepartemental = document.getElementById('niveau_departemental');
        const employeSection = document.getElementById('employe_section');
        const departementSection = document.getElementById('departement_section');
        
        function toggleSections() {
            if (niveauIndividuel.checked) {
                employeSection.style.display = 'block';
                departementSection.style.display = 'none';
                document.getElementById('departement_id').value = '';
            } else {
                employeSection.style.display = 'none';
                departementSection.style.display = 'block';
                document.getElementById('employe_id').value = '';
            }
        }
        
        niveauIndividuel.addEventListener('change', toggleSections);
        niveauDepartemental.addEventListener('change', toggleSections);
        
        // Gestion de la période et de la date de fin
        const periodeJour = document.getElementById('periode_jour');
        const periodeSemaine = document.getElementById('periode_semaine');
        const periodeMois = document.getElementById('periode_mois');
        const dateDebut = document.getElementById('date_debut');
        const dateFin = document.getElementById('date_fin');
        
        function updateDateFin() {
            const debut = new Date(dateDebut.value);
            let fin = new Date(debut);
            
            if (periodeJour.checked) {
                // Même jour
            } else if (periodeSemaine.checked) {
                // Ajouter 6 jours (7 jours au total)
                fin.setDate(debut.getDate() + 6);
            } else if (periodeMois.checked) {
                // Aller au dernier jour du mois
                fin = new Date(debut.getFullYear(), debut.getMonth() + 1, 0);
            }
            
            dateFin.value = fin.toISOString().split('T')[0];
        }
        
        periodeJour.addEventListener('change', updateDateFin);
        periodeSemaine.addEventListener('change', updateDateFin);
        periodeMois.addEventListener('change', updateDateFin);
        dateDebut.addEventListener('change', updateDateFin);
        
        // Mise à jour du message d'information sur le pointage
        const nombrePointages = document.getElementById('nombre_pointages');
        const pointageInfo = document.getElementById('pointage_info');
        
        nombrePointages.addEventListener('change', function() {
            if (nombrePointages.value === '1') {
                pointageInfo.innerHTML = 'Avec 1 pointage, l\'employé doit pointer une seule fois dans la journée, dans la plage [heure début - tolérance] → [heure fin + tolérance].';
            } else {
                pointageInfo.innerHTML = 'Avec 2 pointages, l\'employé doit pointer à l\'arrivée et au départ dans les plages de tolérance.';
            }
        });
        
        // Chargement du planning
        function loadPlanning() {
            const niveau = document.querySelector('input[name="niveau"]:checked').value;
            const employe_id = document.getElementById('employe_id').value;
            const departement_id = document.getElementById('departement_id').value;
            const periode = document.querySelector('input[name="periode"]:checked').value;
            const date_debut = document.getElementById('date_debut').value;
            
            // Vérifier que les valeurs nécessaires sont présentes
            if ((niveau === 'individuel' && !employe_id) || (niveau === 'departemental' && !departement_id) || !date_debut) {
                return;
            }
            
            const planningContainer = document.getElementById('planning-container');
            planningContainer.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement du planning...</p>
                </div>
            `;
            
            // Appel AJAX pour récupérer le planning
            fetch('{{ route('criteres-pointage.get-planning') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    niveau: niveau,
                    employe_id: employe_id,
                    departement_id: departement_id,
                    periode: periode,
                    date_debut: date_debut
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayPlanning(data);
                } else {
                    planningContainer.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> Impossible de charger le planning. Veuillez vérifier vos sélections.
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement du planning:', error);
                planningContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> Une erreur est survenue lors du chargement du planning.
                    </div>
                `;
            });
        }
        
        function displayPlanning(data) {
            const planningContainer = document.getElementById('planning-container');
            planningContainer.innerHTML = '';
            
            if (data.niveau === 'individuel') {
                // Affichage pour un employé individuel
                const plannings = data.plannings;
                
                if (Object.keys(plannings).length === 0) {
                    planningContainer.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> Aucun planning trouvé pour cet employé sur la période sélectionnée.
                        </div>
                    `;
                    return;
                }
                
                // Trier les jours par date
                const sortedDays = Object.keys(plannings).sort();
                
                sortedDays.forEach(date => {
                    const day = plannings[date];
                    const dayElement = document.createElement('div');
                    dayElement.className = 'planning-day';
                    dayElement.innerHTML = `
                        <div class="planning-header">
                            ${day.nom_jour} ${new Date(date).toLocaleDateString('fr-FR')}
                        </div>
                        <div class="planning-time">
                            <i class="far fa-clock me-1"></i> ${day.heure_debut} - ${day.heure_fin}
                        </div>
                    `;
                    planningContainer.appendChild(dayElement);
                });
            } else {
                // Affichage pour un département
                const plannings = data.plannings;
                
                if (Object.keys(plannings).length === 0) {
                    planningContainer.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> Aucun planning trouvé pour ce département sur la période sélectionnée.
                        </div>
                    `;
                    return;
                }
                
                // Parcourir les employés
                Object.keys(plannings).forEach(employeId => {
                    const employeData = plannings[employeId];
                    const employeElement = document.createElement('div');
                    employeElement.className = 'planning-employee';
                    employeElement.innerHTML = `
                        <div class="planning-header">
                            <i class="fas fa-user me-1"></i> ${employeData.employe.nom} ${employeData.employe.prenom}
                        </div>
                    `;
                    
                    // Trier les jours par date
                    const sortedDays = Object.keys(employeData.planning).sort();
                    
                    sortedDays.forEach(date => {
                        const day = employeData.planning[date];
                        const dayElement = document.createElement('div');
                        dayElement.className = 'planning-day';
                        dayElement.innerHTML = `
                            <div class="planning-header">
                                ${day.nom_jour} ${new Date(date).toLocaleDateString('fr-FR')}
                            </div>
                            <div class="planning-time">
                                <i class="far fa-clock me-1"></i> ${day.heure_debut} - ${day.heure_fin}
                            </div>
                        `;
                        employeElement.appendChild(dayElement);
                    });
                    
                    planningContainer.appendChild(employeElement);
                });
            }
        }
        
        // Événements pour déclencher le chargement du planning
        document.getElementById('employe_id').addEventListener('change', loadPlanning);
        document.getElementById('departement_id').addEventListener('change', loadPlanning);
        periodeJour.addEventListener('change', loadPlanning);
        periodeSemaine.addEventListener('change', loadPlanning);
        periodeMois.addEventListener('change', loadPlanning);
        dateDebut.addEventListener('change', loadPlanning);
        niveauIndividuel.addEventListener('change', loadPlanning);
        niveauDepartemental.addEventListener('change', loadPlanning);
        
        // Initialisation
        toggleSections();
        updateDateFin();
    });
</script>
@endsection
