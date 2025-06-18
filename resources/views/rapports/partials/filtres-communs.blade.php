{{-- 
    Composant de filtres communs pour tous les rapports
    Variables attendues :
    - $employes : Collection des employés
    - $departements : Collection des départements 
    - $postes : Collection des postes
    - $grades : Collection des grades (optionnel)
    - $employeId : ID de l'employé sélectionné (optionnel)
    - $departementId : ID du département sélectionné (optionnel)
    - $posteId : ID du poste sélectionné (optionnel)
    - $gradeId : ID du grade sélectionné (optionnel)
    - $showGrades : Afficher le filtre par grade (optionnel, défaut: true)
    - $showServices : Afficher le filtre par service (optionnel, défaut: false)
--}}

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0 text-primary">
            <i class="bi bi-funnel me-2"></i>Critères de filtrage
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <!-- Filtre par employé individuel -->
            <div class="col-md-6 col-lg-3">
                <label for="employe_id" class="form-label">
                    <i class="bi bi-person me-1"></i>Employé
                </label>
                <select class="form-select" id="employe_id" name="employe_id">
                    <option value="">Tous les employés</option>
                    @if(isset($employes))
                        @foreach($employes as $employe)
                            <option value="{{ $employe->id }}" 
                                    {{ (isset($employeId) && $employeId == $employe->id) ? 'selected' : '' }}
                                    data-departement="{{ $employe->poste ? $employe->poste->departement : '' }}"
                                    data-poste="{{ $employe->poste_id ?? '' }}"
                                    data-grade="{{ $employe->grade_id ?? '' }}">
                                {{ $employe->prenom }} {{ $employe->nom }}
                                @if($employe->poste)
                                    - {{ $employe->poste->nom }}
                                @endif
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Filtre par département -->
            <div class="col-md-6 col-lg-3">
                <label for="departement_id" class="form-label">
                    <i class="bi bi-building me-1"></i>Département
                </label>
                <select class="form-select" id="departement_id" name="departement_id">
                    <option value="">Tous les départements</option>
                    @if(isset($departements))
                        @foreach($departements as $departement)
                            <option value="{{ is_object($departement) ? $departement->departement : $departement['departement'] }}" 
                                    {{ (isset($departementId) && $departementId == (is_object($departement) ? $departement->departement : $departement['departement'])) ? 'selected' : '' }}>
                                {{ is_object($departement) ? $departement->departement : $departement['departement'] }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Filtre par poste -->
            <div class="col-md-6 col-lg-3">
                <label for="poste_id" class="form-label">
                    <i class="bi bi-briefcase me-1"></i>Poste
                </label>
                <select class="form-select" id="poste_id" name="poste_id">
                    <option value="">Tous les postes</option>
                    @if(isset($postes))
                        @foreach($postes as $poste)
                            <option value="{{ $poste->id }}" 
                                    {{ (isset($posteId) && $posteId == $poste->id) ? 'selected' : '' }}
                                    data-departement="{{ $poste->departement }}">
                                {{ $poste->nom }}
                                @if($poste->departement)
                                    <small class="text-muted">({{ $poste->departement }})</small>
                                @endif
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Filtre par grade (optionnel) -->
            @if(!isset($showGrades) || $showGrades)
                <div class="col-md-6 col-lg-3">
                    <label for="grade_id" class="form-label">
                        <i class="bi bi-award me-1"></i>Grade
                    </label>
                    <select class="form-select" id="grade_id" name="grade_id">
                        <option value="">Tous les grades</option>
                        @if(isset($grades))
                            @foreach($grades as $grade)
                                <option value="{{ $grade->id }}" 
                                        {{ (isset($gradeId) && $gradeId == $grade->id) ? 'selected' : '' }}>
                                    {{ $grade->nom }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            @endif

            <!-- Filtre par service (optionnel) -->
            @if(isset($showServices) && $showServices && isset($services))
                <div class="col-md-6 col-lg-3">
                    <label for="service_id" class="form-label">
                        <i class="bi bi-people me-1"></i>Service
                    </label>
                    <select class="form-select" id="service_id" name="service_id">
                        <option value="">Tous les services</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" 
                                    {{ (isset($serviceId) && $serviceId == $service->id) ? 'selected' : '' }}>
                                {{ $service->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <!-- Boutons d'action -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-secondary" id="btn-reset-filtres">
                        <i class="bi bi-arrow-clockwise me-1"></i>Réinitialiser
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel-fill me-1"></i>Appliquer les filtres
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript pour la gestion des filtres --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeSelect = document.getElementById('employe_id');
    const departementSelect = document.getElementById('departement_id');
    const posteSelect = document.getElementById('poste_id');
    const gradeSelect = document.getElementById('grade_id');
    const serviceSelect = document.getElementById('service_id');
    const resetButton = document.getElementById('btn-reset-filtres');

    // Fonction pour filtrer les postes par département
    function filtrerPostesParDepartement() {
        if (!departementSelect || !posteSelect) return;
        
        const departementSelectionne = departementSelect.value;
        const options = posteSelect.querySelectorAll('option');
        
        options.forEach(option => {
            if (option.value === '') {
                option.style.display = 'block';
                return;
            }
            
            const departementPoste = option.getAttribute('data-departement');
            if (!departementSelectionne || departementPoste === departementSelectionne) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });
        
        // Réinitialiser la sélection de poste si elle n'est plus valide
        const posteActuel = posteSelect.value;
        if (posteActuel) {
            const optionActuelle = posteSelect.querySelector(`option[value="${posteActuel}"]`);
            if (optionActuelle && optionActuelle.style.display === 'none') {
                posteSelect.value = '';
            }
        }
    }

    // Fonction pour filtrer les employés selon les critères sélectionnés
    function filtrerEmployes() {
        if (!employeSelect) return;
        
        const departementSelectionne = departementSelect?.value;
        const posteSelectionne = posteSelect?.value;
        const gradeSelectionne = gradeSelect?.value;
        const options = employeSelect.querySelectorAll('option');
        
        options.forEach(option => {
            if (option.value === '') {
                option.style.display = 'block';
                return;
            }
            
            let afficher = true;
            
            // Filtrer par département
            if (departementSelectionne) {
                const departementEmploye = option.getAttribute('data-departement');
                if (departementEmploye !== departementSelectionne) {
                    afficher = false;
                }
            }
            
            // Filtrer par poste
            if (posteSelectionne && afficher) {
                const posteEmploye = option.getAttribute('data-poste');
                if (posteEmploye !== posteSelectionne) {
                    afficher = false;
                }
            }
            
            // Filtrer par grade
            if (gradeSelectionne && afficher) {
                const gradeEmploye = option.getAttribute('data-grade');
                if (gradeEmploye !== gradeSelectionne) {
                    afficher = false;
                }
            }
            
            option.style.display = afficher ? 'block' : 'none';
        });
    }

    // Événements pour le filtrage en cascade
    if (departementSelect) {
        departementSelect.addEventListener('change', function() {
            filtrerPostesParDepartement();
            filtrerEmployes();
        });
    }

    if (posteSelect) {
        posteSelect.addEventListener('change', function() {
            filtrerEmployes();
        });
    }

    if (gradeSelect) {
        gradeSelect.addEventListener('change', function() {
            filtrerEmployes();
        });
    }

    // Bouton de réinitialisation
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            // Réinitialiser tous les sélecteurs
            if (employeSelect) employeSelect.value = '';
            if (departementSelect) departementSelect.value = '';
            if (posteSelect) posteSelect.value = '';
            if (gradeSelect) gradeSelect.value = '';
            if (serviceSelect) serviceSelect.value = '';
            
            // Réafficher toutes les options
            document.querySelectorAll('select option').forEach(option => {
                option.style.display = 'block';
            });
        });
    }

    // Initialiser les filtres au chargement
    filtrerPostesParDepartement();
    filtrerEmployes();
});
</script>

<style>
.card {
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.form-select {
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.btn {
    transition: all 0.15s ease-in-out;
}

.form-label {
    font-weight: 500;
    color: #495057;
}

.text-muted {
    font-size: 0.875em;
}
</style> 