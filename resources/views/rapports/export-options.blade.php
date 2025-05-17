@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Options d'exportation du rapport</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('rapports.export-pdf') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        
                        <div class="mb-4">
                            <h5>Type de rapport : <span class="text-primary">{{ $typeLabel ?? ucfirst(str_replace('-', ' ', $type)) }}</span></h5>
                        </div>
                        
                        @if(!isset($typeLabel))
                        <div class="form-group mb-3">
                            <label for="type" class="form-label">Sélectionner le type de rapport</label>
                            <select name="type" id="type" class="form-select">
                                <option value="presences" {{ $type == 'presences' ? 'selected' : '' }}>Rapport des présences</option>
                                <option value="absences" {{ $type == 'absences' ? 'selected' : '' }}>Rapport des absences</option>
                                <option value="retards" {{ $type == 'retards' ? 'selected' : '' }}>Rapport des retards</option>
                                <option value="biometrique" {{ $type == 'biometrique' ? 'selected' : '' }}>Rapport des pointages biométriques</option>
                                <option value="global-multi-periode" {{ $type == 'global-multi-periode' ? 'selected' : '' }}>Rapport Global de Présence</option>
                                <option value="ponctualite-assiduite" {{ $type == 'ponctualite-assiduite' ? 'selected' : '' }}>Rapport Ponctualité & Assiduité</option>
                            </select>
                        </div>
                        @endif

                        <div class="form-group mb-3">
                            <label for="periode" class="form-label">Période</label>
                            <select name="periode" id="periode" class="form-select">
                                <option value="jour" {{ $periode == 'jour' ? 'selected' : '' }}>Jour</option>
                                <option value="semaine" {{ $periode == 'semaine' ? 'selected' : '' }}>Semaine</option>
                                <option value="mois" {{ $periode == 'mois' ? 'selected' : '' }}>Mois</option>
                                @if($type != 'global-multi-periode' && $type != 'ponctualite-assiduite')
                                <option value="annee" {{ $periode == 'annee' ? 'selected' : '' }}>Année</option>
                                @endif
                            </select>
                        </div>

                        <div class="form-group mb-3" id="date-selector">
                            <label for="date" class="form-label">Date de référence</label>
                            <input type="date" name="date" id="date" class="form-control" value="{{ $date ?? now()->format('Y-m-d') }}">
                            <small class="form-text text-muted">La période sera calculée à partir de cette date selon l'option sélectionnée.</small>
                        </div>
                        
                        <div class="form-group mb-3" id="date-range-selector" style="display: none;">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $dateDebut ?? now()->format('Y-m-d') }}">
                            
                            <label for="date_fin" class="form-label mt-2">Date de fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ $dateFin ?? now()->format('Y-m-d') }}">
                        </div>

                        @if(isset($employes) && count($employes) > 0)
                        <div class="form-group mb-3">
                            <label for="employe_id" class="form-label">Employé (optionnel)</label>
                            <select name="employe_id" id="employe_id" class="form-select">
                                <option value="">Tous les employés</option>
                                @foreach($employes as $employe)
                                    <option value="{{ $employe->id }}" {{ $employeId == $employe->id ? 'selected' : '' }}>
                                        {{ $employe->prenom }} {{ $employe->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if(isset($departements) && count($departements) > 0)
                        <div class="form-group mb-3">
                            <label for="departement_id" class="form-label">Département (optionnel)</label>
                            <select name="departement_id" id="departement_id" class="form-select">
                                <option value="">Tous les départements</option>
                                @foreach($departements as $departement)
                                    <option value="{{ $departement->departement }}" {{ $departementId == $departement->departement ? 'selected' : '' }}>
                                        {{ $departement->departement }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if(isset($postes) && count($postes) > 0)
                        <div class="form-group mb-3">
                            <label for="poste_id" class="form-label">Poste (optionnel)</label>
                            <select name="poste_id" id="poste_id" class="form-select">
                                <option value="">Tous les postes</option>
                                @foreach($postes as $poste)
                                    <option value="{{ $poste->id }}" {{ $posteId == $poste->id ? 'selected' : '' }} data-departement="{{ $poste->departement }}">
                                        {{ $poste->nom }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted" id="poste-info">Sélectionnez d'abord un département pour filtrer les postes disponibles.</small>
                        </div>
                        @endif
                        
                        @if(isset($grades) && count($grades) > 0)
                        <div class="form-group mb-3">
                            <label for="grade_id" class="form-label">Grade (optionnel)</label>
                            <select name="grade_id" id="grade_id" class="form-select">
                                <option value="">Tous les grades</option>
                                @foreach($grades as $grade)
                                    <option value="{{ $grade->id }}" {{ $gradeId == $grade->id ? 'selected' : '' }}>
                                        Grade {{ $grade->id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="form-group mb-3">
                            <label for="format" class="form-label">Format</label>
                            <select name="format" id="format" class="form-select">
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-export me-1"></i> Exporter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const periodeSelect = document.getElementById('periode');
    const dateSelector = document.getElementById('date-selector');
    const dateRangeSelector = document.getElementById('date-range-selector');
    const dateInput = document.getElementById('date');
    const formatSelect = document.getElementById('format');
    const departementSelect = document.getElementById('departement_id');
    const posteSelect = document.getElementById('poste_id');
    const posteInfo = document.getElementById('poste-info');
    
    // Données des postes par département
    const postesByDepartement = @json($postesByDepartementJson);
    
    // Fonction pour mettre à jour l'affichage des sélecteurs de date
    function updateDateSelectors() {
        const isMultiPeriode = typeSelect && (typeSelect.value === 'global-multi-periode' || typeSelect.value === 'ponctualite-assiduite');
        
        if (isMultiPeriode || (periodeSelect.value === 'jour' || periodeSelect.value === 'semaine' || periodeSelect.value === 'mois')) {
            dateSelector.style.display = 'block';
            dateRangeSelector.style.display = 'none';
        } else {
            dateSelector.style.display = 'none';
            dateRangeSelector.style.display = 'block';
        }
    }
    
    // Mettre à jour les options de période en fonction du type de rapport
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            // Limiter les périodes pour les rapports spécifiques
            const isMultiPeriode = this.value === 'global-multi-periode' || this.value === 'ponctualite-assiduite';
            
            // Masquer l'option année pour les rapports multi-période
            const anneeOption = periodeSelect.querySelector('option[value="annee"]');
            if (anneeOption) {
                anneeOption.style.display = isMultiPeriode ? 'none' : 'block';
                
                // Si l'option année est sélectionnée mais doit être masquée, sélectionner mois par défaut
                if (isMultiPeriode && periodeSelect.value === 'annee') {
                    periodeSelect.value = 'mois';
                }
            }
            
            updateDateSelectors();
        });
    }
    
    // Mettre à jour l'affichage en fonction de la période sélectionnée
    periodeSelect.addEventListener('change', function() {
        updateDateSelectors();
        
        // Mettre à jour la date de référence
        const today = new Date();
        let referenceDate = new Date();
        
        switch(this.value) {
            case 'jour':
                // Aujourd'hui
                break;
            case 'semaine':
                // Début de la semaine
                referenceDate.setDate(today.getDate() - today.getDay() + 1); // Lundi de la semaine courante
                break;
            case 'mois':
                // Début du mois
                referenceDate.setDate(1);
                break;
        }
        
        dateInput.value = referenceDate.toISOString().split('T')[0];
    });
    
    // Mettre à jour la date au format YYYY-MM-DD
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // Fonction pour filtrer les postes en fonction du département sélectionné
    function filterPostesByDepartement() {
        const selectedDepartement = departementSelect.value;
        
        // Cacher toutes les options de postes sauf "Tous les postes"
        Array.from(posteSelect.options).forEach((option, index) => {
            if (index === 0) return; // Garder l'option "Tous les postes"
            
            const departement = option.getAttribute('data-departement');
            
            if (!selectedDepartement || selectedDepartement === departement) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
                // Si l'option cachée était sélectionnée, sélectionner "Tous les postes"
                if (option.selected) {
                    posteSelect.selectedIndex = 0;
                }
            }
        });
        
        // Mettre à jour le message d'information
        if (selectedDepartement) {
            posteInfo.textContent = `Affichage des postes du département: ${selectedDepartement}`;
        } else {
            posteInfo.textContent = "Sélectionnez d'abord un département pour filtrer les postes disponibles.";
        }
    }
    
    // Ajouter l'écouteur d'événement pour le changement de département
    if (departementSelect) {
        departementSelect.addEventListener('change', filterPostesByDepartement);
        
        // Filtrer les postes au chargement de la page
        filterPostesByDepartement();
    }
    
    // Initialiser l'affichage
    updateDateSelectors();
});
</script>
@endsection
