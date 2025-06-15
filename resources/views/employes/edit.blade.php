@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Modifier l'employé</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('employes.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('employes.update', $employe) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="photo_profil" class="form-label">Photo de profil</label>
                        <div class="card p-3 text-center">
                            <div class="mb-3">
                                <div class="photo-container position-relative" style="width: 150px; height: 150px; margin: 0 auto;">
                                    <img id="preview_image" src="{{ $employe->photo_profil ? asset('storage/photos/' . $employe->photo_profil) : 'https://ui-avatars.com/api/?name=' . substr($employe->prenom, 0, 1) . substr($employe->nom, 0, 1) . '&background=random&color=fff&size=256' }}" 
                                        alt="Photo de {{ $employe->prenom }}" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                                    <label for="photo_profil" class="change-photo-btn">
                                        <div class="photo-overlay rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-camera-fill text-white"></i>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="input-group" style="display:none;">
                                <input type="file" class="form-control @error('photo_profil') is-invalid @enderror" 
                                    id="photo_profil" name="photo_profil" accept="image/*">
                            </div>
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" 
                                    onclick="resetImage()">
                                    <i class="bi bi-x-circle"></i> Annuler
                                </button>
                                @if($employe->photo_profil)
                                <div class="form-check form-switch d-flex align-items-center mt-2 ms-2">
                                    <input class="form-check-input" type="checkbox" name="supprimer_photo" id="supprimer_photo">
                                    <label class="form-check-label ms-2" for="supprimer_photo">
                                        Supprimer la photo
                                    </label>
                                </div>
                                @endif
                            </div>
                            @error('photo_profil')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text small mt-2">Formats acceptés: JPG, PNG. Max: 2 Mo</div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="matricule" class="form-label">Matricule</label>
                                <input type="text" class="form-control" id="matricule" value="{{ $employe->matricule }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nom') is-invalid @enderror" id="nom" name="nom" value="{{ old('nom', $employe->nom) }}" required>
                                @error('nom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('prenom') is-invalid @enderror" id="prenom" name="prenom" value="{{ old('prenom', $employe->prenom) }}" required>
                                @error('prenom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $employe->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="text" class="form-control @error('telephone') is-invalid @enderror" id="telephone" name="telephone" value="{{ old('telephone', $employe->telephone) }}">
                                @error('telephone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="date_naissance" class="form-label">Date de naissance</label>
                        <input type="date" class="form-control @error('date_naissance') is-invalid @enderror" id="date_naissance" name="date_naissance" value="{{ old('date_naissance', optional(\Carbon\Carbon::parse($employe->date_naissance))->format('Y-m-d')) }}">                        @error('date_naissance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="date_embauche" class="form-label">Date d'embauche <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_embauche') is-invalid @enderror" id="date_embauche" name="date_embauche" value="{{ old('date_embauche', optional(\Carbon\Carbon::parse($employe->date_embauche))->format('Y-m-d')) }}" required>                        @error('date_embauche')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="poste_id" class="form-label">Poste <span class="text-danger">*</span></label>
                        <select class="form-select @error('poste_id') is-invalid @enderror" id="poste_id" name="poste_id" required>
                            <option value="">Sélectionner un poste</option>
                            @foreach($postes as $poste)
                                <option value="{{ $poste->id }}" 
                                    data-departement="{{ $poste->departement }}" 
                                    data-grades="{{ $poste->grades_disponibles }}" 
                                    {{ old('poste_id', $employe->poste_id) == $poste->id ? 'selected' : '' }}>
                                    {{ $poste->nom }} ({{ $poste->departement }})
                                </option>
                            @endforeach
                        </select>
                        @error('poste_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="grade" class="form-label">Grade</label>
                        <select class="form-select @error('grade') is-invalid @enderror" id="grade" name="grade">
                            <option value="">Sélectionner un grade</option>
                            <!-- Les options de grade seront chargées dynamiquement en fonction du poste sélectionné -->
                        </select>
                        @error('grade')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <!-- Champ caché pour le département, sera rempli automatiquement en fonction du poste -->
                        <input type="hidden" id="departement" name="departement" value="{{ old('departement', $employe->departement) }}">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                        <select class="form-select @error('statut') is-invalid @enderror" id="statut" name="statut" required>
                            <option value="actif" {{ old('statut', $employe->statut) === 'actif' ? 'selected' : '' }}>Actif</option>
                            <option value="inactif" {{ old('statut', $employe->statut) === 'inactif' ? 'selected' : '' }}>Inactif</option>
                        </select>
                        @error('statut')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-outline-secondary me-md-2">Réinitialiser</button>
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>

    @if($employe->utilisateur)
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Informations du compte utilisateur</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nom d'utilisateur :</strong> {{ $employe->utilisateur->name }}</p>
                    <p><strong>Email :</strong> {{ $employe->utilisateur->email }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Rôle :</strong> {{ $employe->utilisateur->role->nom }}</p>
                    <p><strong>Créé le :</strong> {{ $employe->utilisateur->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Pour modifier le mot de passe de l'utilisateur, veuillez accéder à la section Gestion des utilisateurs.
            </div>
        </div>
    </div>
    @else
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="card-title mb-0">Compte utilisateur</h5>
        </div>
        <div class="card-body">
            <p class="mb-0">Cet employé n'a pas de compte utilisateur associé.</p>
            <form action="{{ route('users.create-from-employee', $employe->id) }}" method="POST" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-person-plus"></i> Créer un compte utilisateur
                </button>
            </form>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    // Fonction pour réinitialiser l'image
    function resetImage() {
        document.getElementById('photo_profil').value = '';
        document.getElementById('preview_image').src = "{{ $employe->photo_profil ? asset('storage/' . $employe->photo_profil) : 'https://ui-avatars.com/api/?name=' . urlencode($employe->prenom . ' ' . $employe->nom) . '&background=random&color=fff&size=256' }}";
    }
    
    document.getElementById('photo_profil').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview_image').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Gestion du département et des grades en fonction du poste sélectionné
    document.addEventListener('DOMContentLoaded', function() {
        const posteSelect = document.getElementById('poste_id');
        const departementInput = document.getElementById('departement');
        const gradeSelect = document.getElementById('grade');
        const currentGrade = "{{ old('grade', $employe->grade) }}"; // Sauvegarder le grade actuel
        
        // Mettre à jour le département et les grades disponibles lorsque le poste est sélectionné
        posteSelect.addEventListener('change', function() {
            const selectedOption = posteSelect.options[posteSelect.selectedIndex];
            if (selectedOption.value) {
                const departement = selectedOption.getAttribute('data-departement');
                departementInput.value = departement;
                
                // Récupérer les grades spécifiques au poste sélectionné
                console.log(`Chargement des grades pour le poste ID: ${selectedOption.value}, grade actuel: ${currentGrade}`);
                
                // Vider le select de grade
                gradeSelect.innerHTML = '<option value="">Sélectionner un grade</option>';
                
                // Récupérer les grades disponibles depuis l'attribut data-grades
                let gradesDisponibles = [];
                try {
                    const gradesJson = selectedOption.getAttribute('data-grades');
                    console.log('Grades JSON:', gradesJson);
                    
                    if (gradesJson && gradesJson !== 'null' && gradesJson !== '') {
                        gradesDisponibles = JSON.parse(gradesJson);
                        console.log('Grades disponibles pour ce poste:', gradesDisponibles);
                    } else {
                        console.log('Aucun grade configuré pour ce poste');
                    }
                } catch (error) {
                    console.error('Erreur lors du parsing des grades:', error);
                }
                
                if (Array.isArray(gradesDisponibles) && gradesDisponibles.length > 0) {
                    let gradeActuelTrouve = false;
                    
                    // Ajouter les options de grade disponibles
                    gradesDisponibles.forEach(grade => {
                        if (grade) { // Vérifier que le grade n'est pas null ou undefined
                            const option = document.createElement('option');
                            option.value = grade;
                            option.textContent = grade;
                            if (grade === currentGrade) {
                                option.selected = true;
                                gradeActuelTrouve = true;
                                console.log(`Grade actuel '${currentGrade}' trouvé dans les grades disponibles`);
                            }
                            gradeSelect.appendChild(option);
                        }
                    });
                    
                    console.log(`${gradesDisponibles.length} grades chargés avec succès`);
                    
                    // Si le grade actuel n'est pas dans la liste mais existe
                    if (!gradeActuelTrouve && currentGrade) {
                        console.log(`Grade actuel '${currentGrade}' non trouvé dans les grades disponibles, l'ajout comme option`);
                        const option = document.createElement('option');
                        option.value = currentGrade;
                        option.textContent = currentGrade + ' (grade actuel)';
                        option.selected = true;
                        option.style.fontStyle = 'italic';
                        gradeSelect.appendChild(option);
                    }
                } else {
                    // Si aucun grade n'est défini pour ce poste, afficher un message
                    console.log('Aucun grade disponible pour ce poste');
                    gradeSelect.innerHTML = '<option value="">Aucun grade disponible pour ce poste</option>';
                    
                    // Si l'employé a déjà un grade, l'ajouter comme option
                    if (currentGrade) {
                        console.log(`Ajout du grade actuel '${currentGrade}' comme seule option`);
                        const option = document.createElement('option');
                        option.value = currentGrade;
                        option.textContent = currentGrade + ' (grade actuel)';
                        option.selected = true;
                        gradeSelect.appendChild(option);
                    }
                }
            } else {
                departementInput.value = '';
                // Réinitialiser le select de grade
                gradeSelect.innerHTML = '<option value="">Veuillez d\'abord sélectionner un poste</option>';
                
                // Si l'employé a déjà un grade, l'ajouter comme option
                if (currentGrade) {
                    const option = document.createElement('option');
                    option.value = currentGrade;
                    option.textContent = currentGrade + ' (grade actuel)';
                    option.selected = true;
                    gradeSelect.appendChild(option);
                }
            }
        });
        
        // Initialiser le département et les grades si un poste est déjà sélectionné
        if (posteSelect.value) {
            const selectedOption = posteSelect.options[posteSelect.selectedIndex];
            const departement = selectedOption.getAttribute('data-departement');
            departementInput.value = departement;
            
            // Déclencher l'événement change pour charger les grades
            posteSelect.dispatchEvent(new Event('change'));
        }
    });
    
    // Si la case "supprimer la photo" est cochée, désactiver l'input de fichier
    document.addEventListener('DOMContentLoaded', function() {
        const supprimerPhotoCheckbox = document.getElementById('supprimer_photo');
        if (supprimerPhotoCheckbox) {
            supprimerPhotoCheckbox.addEventListener('change', function() {
                const photoInput = document.getElementById('photo_profil');
                photoInput.disabled = this.checked;
            });
        }
    });
</script>
@endpush
@endsection