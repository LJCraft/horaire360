@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Ajouter un poste</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('postes.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('postes.store') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom du poste <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nom') is-invalid @enderror" id="nom" name="nom" value="{{ old('nom') }}" required>
                    @error('nom')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="departement" class="form-label">Département <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('departement') is-invalid @enderror" id="departement" name="departement" value="{{ old('departement') }}" required>
                    @error('departement')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="grades_disponibles" class="form-label">Grades disponibles pour ce poste</label>
                    <div class="card">
                        <div class="card-header bg-light">
                            <ul class="nav nav-tabs card-header-tabs" id="gradesTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="standards-tab" data-bs-toggle="tab" data-bs-target="#standards" type="button" role="tab" aria-controls="standards" aria-selected="true">Grades standards</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="personnalises-tab" data-bs-toggle="tab" data-bs-target="#personnalises" type="button" role="tab" aria-controls="personnalises" aria-selected="false">Grades personnalisés</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="supprimes-tab" data-bs-toggle="tab" data-bs-target="#supprimes" type="button" role="tab" aria-controls="supprimes" aria-selected="false">Grades supprimés</button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="gradesTabContent">
                                <!-- Onglet Grades Standards -->
                                <div class="tab-pane fade show active" id="standards" role="tabpanel" aria-labelledby="standards-tab">
                                    <div class="mb-2" id="grades_standards">
                                        <div class="row">
                                            <!-- Colonne 1 : Niveau d'expérience -->
                                            <div class="col-md-4 mb-3">
                                                <h6 class="border-bottom pb-2">Niveau d'expérience</h6>
                                                <div class="d-flex flex-column">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_stagiaire" name="grades[]" value="Stagiaire" {{ in_array('Stagiaire', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_stagiaire">Stagiaire</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Stagiaire">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_junior" name="grades[]" value="Junior" {{ in_array('Junior', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_junior">Junior</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Junior">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_intermediaire" name="grades[]" value="Intermédiaire" {{ in_array('Intermédiaire', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_intermediaire">Intermédiaire</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Intermédiaire">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_senior" name="grades[]" value="Senior" {{ in_array('Senior', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_senior">Senior</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Senior">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_expert" name="grades[]" value="Expert" {{ in_array('Expert', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_expert">Expert</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Expert">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Colonne 2 : Niveau hiérarchique -->
                                            <div class="col-md-4 mb-3">
                                                <h6 class="border-bottom pb-2">Niveau hiérarchique</h6>
                                                <div class="d-flex flex-column">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_employe" name="grades[]" value="Employé" {{ in_array('Employé', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_employe">Employé</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Employé">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_chef_equipe" name="grades[]" value="Chef d'équipe" {{ in_array('Chef d\'équipe', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_chef_equipe">Chef d'équipe</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Chef d'équipe">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_superviseur" name="grades[]" value="Superviseur" {{ in_array('Superviseur', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_superviseur">Superviseur</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Superviseur">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_manager" name="grades[]" value="Manager" {{ in_array('Manager', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_manager">Manager</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Manager">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_directeur" name="grades[]" value="Directeur" {{ in_array('Directeur', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_directeur">Directeur</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Directeur">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Colonne 3 : Spécialisation -->
                                            <div class="col-md-4 mb-3">
                                                <h6 class="border-bottom pb-2">Spécialisation</h6>
                                                <div class="d-flex flex-column">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_specialiste" name="grades[]" value="Spécialiste" {{ in_array('Spécialiste', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_specialiste">Spécialiste</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Spécialiste">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_consultant" name="grades[]" value="Consultant" {{ in_array('Consultant', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_consultant">Consultant</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Consultant">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_analyste" name="grades[]" value="Analyste" {{ in_array('Analyste', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_analyste">Analyste</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Analyste">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_architecte" name="grades[]" value="Architecte" {{ in_array('Architecte', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_architecte">Architecte</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Architecte">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="grade_chef_projet" name="grades[]" value="Chef de projet" {{ in_array('Chef de projet', old('grades', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="grade_chef_projet">Chef de projet</label>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm ms-2 supprimer-grade-standard" data-grade="Chef de projet">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="grades_standards_restauration"></div>
                                    </div>
                                </div>
                                
                                <!-- Onglet Grades Personnalisés -->
                                <div class="tab-pane fade" id="personnalises" role="tabpanel" aria-labelledby="personnalises-tab">
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="nouveau_grade" placeholder="Ajouter un nouveau grade...">
                                        <button class="btn btn-outline-primary" type="button" id="ajouter_grade">Ajouter</button>
                                    </div>
                                    <div id="grades_personnalises" class="row">
                                        <!-- Les grades personnalisés seront ajoutés ici par JavaScript -->
                                    </div>
                                </div>
                                
                                <!-- Onglet Grades Supprimés -->
                                <div class="tab-pane fade" id="supprimes" role="tabpanel" aria-labelledby="supprimes-tab">
                                    <div id="grades_supprimes" class="row">
                                        <p class="text-muted" id="aucun_grade_supprime">Aucun grade supprimé.</p>
                                        <!-- Les grades supprimés seront affichés ici par JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @error('grades')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-outline-secondary me-md-2">Réinitialiser</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ajouterGradeBtn = document.getElementById('ajouter_grade');
        const nouveauGradeInput = document.getElementById('nouveau_grade');
        const gradesPersonnalises = document.getElementById('grades_personnalises');
        const gradesStandards = document.getElementById('grades_standards');
        const gradesSupprimesList = document.getElementById('grades_supprimes');
        const aucunGradeSupprime = document.getElementById('aucun_grade_supprime');
        let gradeCounter = 0;
        let gradesSupprimes = [];
        
        // Fonction pour ajouter un nouveau grade personnalisé
        ajouterGradeBtn.addEventListener('click', function() {
            const nouveauGrade = nouveauGradeInput.value.trim();
            
            if (nouveauGrade) {
                // Vérifier si ce grade a été supprimé précédemment
                const indexSupprime = gradesSupprimes.indexOf(nouveauGrade);
                if (indexSupprime !== -1) {
                    // Retirer de la liste des grades supprimés
                    gradesSupprimes.splice(indexSupprime, 1);
                    mettreAJourListeGradesSupprimes();
                }
                
                // Créer un élément de grade personnalisé
                const gradeCol = document.createElement('div');
                gradeCol.className = 'col-md-4 mb-2';
                gradeCol.id = `grade_personnalise_${gradeCounter}`;
                
                const gradeHtml = `
                    <div class="card">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <input class="form-check-input" type="checkbox" id="grade_custom_${gradeCounter}" name="grades[]" value="${nouveauGrade}" checked>
                                    <label class="form-check-label ms-2" for="grade_custom_${gradeCounter}">${nouveauGrade}</label>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger supprimer-grade" data-grade-id="${gradeCounter}">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                gradeCol.innerHTML = gradeHtml;
                gradesPersonnalises.appendChild(gradeCol);
                
                // Ajouter l'écouteur d'événement pour le bouton de suppression
                const supprimerBtn = gradeCol.querySelector('.supprimer-grade');
                supprimerBtn.addEventListener('click', function() {
                    const gradeId = this.getAttribute('data-grade-id');
                    const gradeElement = document.getElementById(`grade_personnalise_${gradeId}`);
                    if (gradeElement) {
                        gradeElement.remove();
                    }
                });
                
                // Réinitialiser l'input et incrémenter le compteur
                nouveauGradeInput.value = '';
                gradeCounter++;
                
                // Activer l'onglet des grades personnalisés
                document.getElementById('personnalises-tab').click();
            }
        });
        
        // Gestion de la suppression des grades standards
        document.querySelectorAll('.supprimer-grade-standard').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const grade = this.getAttribute('data-grade');
                const parentElement = this.closest('.form-check');
                
                if (parentElement) {
                    // Ajouter à la liste des grades supprimés
                    if (!gradesSupprimes.includes(grade)) {
                        gradesSupprimes.push(grade);
                    }
                    
                    // Cacher l'élément plutôt que de le supprimer complètement
                    parentElement.style.display = 'none';
                    
                    // Décocher la case
                    const checkbox = parentElement.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        checkbox.checked = false;
                    }
                    
                    // Ajouter un bouton pour restaurer le grade
                    const restaurerBtn = document.createElement('button');
                    restaurerBtn.className = 'btn btn-sm btn-outline-secondary mt-1';
                    restaurerBtn.innerHTML = `<i class="bi bi-arrow-counterclockwise"></i> Restaurer "${grade}"`;
                    restaurerBtn.setAttribute('data-grade', grade);
                    restaurerBtn.addEventListener('click', function() {
                        const gradeARestaurer = this.getAttribute('data-grade');
                        
                        // Trouver l'élément caché correspondant
                        const elements = gradesStandards.querySelectorAll('.form-check-inline');
                        for (let i = 0; i < elements.length; i++) {
                            const checkbox = elements[i].querySelector('input[type="checkbox"]');
                            if (checkbox && checkbox.value === gradeARestaurer) {
                                // Afficher l'élément
                                elements[i].style.display = '';
                                
                                // Retirer de la liste des grades supprimés
                                const index = gradesSupprimes.indexOf(gradeARestaurer);
                                if (index !== -1) {
                                    gradesSupprimes.splice(index, 1);
                                }
                                
                                // Supprimer le bouton de restauration
                                this.remove();
                                break;
                            }
                        }
                    });
                    
                    // Ajouter le bouton après la liste des grades standards
                    gradesStandards.appendChild(restaurerBtn);
                }
            });
        });
        
        // Permettre l'ajout de grade en appuyant sur Entrée
        nouveauGradeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                ajouterGradeBtn.click();
            }
        });
    });
</script>
@endpush