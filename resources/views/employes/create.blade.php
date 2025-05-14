@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Ajouter un employé</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('employes.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('employes.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="photo_profil" class="form-label">Photo de profil</label>
                        <div class="card p-3 text-center">
                            <div class="mb-3">
                                <div class="photo-container position-relative" style="width: 150px; height: 150px; margin: 0 auto;">
                                    <img id="preview_image" src="https://ui-avatars.com/api/?name=?&background=random&color=fff&size=256" 
                                        alt="Preview" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
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
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" 
                                onclick="resetImage()">
                                <i class="bi bi-x-circle"></i> Annuler
                            </button>
                            @error('photo_profil')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text small mt-2">Formats acceptés: JPG, PNG. Max: 2 Mo</div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nom') is-invalid @enderror" id="nom" name="nom" value="{{ old('nom') }}" required>
                                @error('nom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('prenom') is-invalid @enderror" id="prenom" name="prenom" value="{{ old('prenom') }}" required>
                                @error('prenom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                    <button class="btn btn-outline-secondary" type="button" id="check-email-btn">
                                        <i class="bi bi-check-circle"></i> Vérifier
                                    </button>
                                </div>
                                <div id="email-feedback" class="mt-2" style="display: none;"></div>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="text" class="form-control @error('telephone') is-invalid @enderror" id="telephone" name="telephone" value="{{ old('telephone') }}">
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
                        <input type="date" class="form-control @error('date_naissance') is-invalid @enderror" id="date_naissance" name="date_naissance" value="{{ old('date_naissance') }}">
                        @error('date_naissance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="date_embauche" class="form-label">Date d'embauche <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_embauche') is-invalid @enderror" id="date_embauche" name="date_embauche" value="{{ old('date_embauche', date('Y-m-d')) }}" required>
                        @error('date_embauche')
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
                                <option value="{{ $poste->id }}" {{ old('poste_id') == $poste->id ? 'selected' : '' }}>
                                    {{ $poste->nom }} ({{ $poste->departement }})
                                </option>
                            @endforeach
                        </select>
                        @error('poste_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label d-block">Créer un compte utilisateur</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="create_user" name="create_user" value="1" {{ old('create_user') ? 'checked' : '' }}>
                            <label class="form-check-label" for="create_user">
                                Activer le compte avec le rôle "Employé"
                            </label>
                        </div>
                        <small class="text-muted">Un email avec les identifiants sera envoyé à l'employé</small>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-outline-secondary me-md-2">Réinitialiser</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
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

    function resetImage() {
        document.getElementById('photo_profil').value = '';
        document.getElementById('preview_image').src = 'https://ui-avatars.com/api/?name=?&background=random&color=fff&size=256';
    }
    
    // Vérification d'email
    document.addEventListener('DOMContentLoaded', function() {
        const checkEmailBtn = document.getElementById('check-email-btn');
        const emailInput = document.getElementById('email');
        const prenomInput = document.getElementById('prenom');
        const nomInput = document.getElementById('nom');
        const emailFeedback = document.getElementById('email-feedback');
        
        checkEmailBtn.addEventListener('click', function() {
            checkEmail();
        });
        
        // Vérifier l'email quand l'utilisateur quitte le champ
        emailInput.addEventListener('blur', function() {
            if (emailInput.value.trim() !== '') {
                checkEmail();
            }
        });
        
        function checkEmail() {
            const email = emailInput.value.trim();
            const prenom = prenomInput.value.trim();
            const nom = nomInput.value.trim();
            
            if (!email || !prenom || !nom) {
                emailFeedback.innerHTML = '<div class="alert alert-warning">Veuillez remplir les champs prénom, nom et email.</div>';
                emailFeedback.style.display = 'block';
                return;
            }
            
            // Afficher un indicateur de chargement
            emailFeedback.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Vérification en cours...</div>';
            emailFeedback.style.display = 'block';
            
            // Créer l'URL pour la requête AJAX
            const url = "{{ route('employes.check-email') }}";
            const token = "{{ csrf_token() }}";
            
            // Créer un objet XMLHttpRequest
            const xhr = new XMLHttpRequest();
            xhr.open("POST", url);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.setRequestHeader("X-CSRF-TOKEN", token);
            
            xhr.onreadystatechange = function() {
                if (this.readyState === 4) {
                    if (this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        
                        if (response.exists) {
                            // Email existe déjà, afficher suggestion
                            emailFeedback.innerHTML = `
                                <div class="alert alert-warning">
                                    <div class="mb-2">${response.message}</div>
                                    <div class="d-flex align-items-center">
                                        <strong class="me-2">${response.alternativeEmail}</strong>
                                        <button type="button" class="btn btn-sm btn-success me-2" id="accept-email-btn">
                                            <i class="bi bi-check-lg"></i> Utiliser
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="cancel-email-btn">
                                            <i class="bi bi-x-lg"></i> Non
                                        </button>
                                    </div>
                                </div>
                            `;
                            
                            document.getElementById('accept-email-btn').addEventListener('click', function() {
                                emailInput.value = response.alternativeEmail;
                                emailFeedback.style.display = 'none';
                            });
                            
                            document.getElementById('cancel-email-btn').addEventListener('click', function() {
                                emailFeedback.style.display = 'none';
                            });
                            
                        } else {
                            // Email disponible
                            emailFeedback.innerHTML = `<div class="alert alert-success">${response.message}</div>`;
                            setTimeout(() => {
                                emailFeedback.style.display = 'none';
                            }, 3000);
                        }
                    } else {
                        // Erreur serveur
                        emailFeedback.innerHTML = '<div class="alert alert-danger">Erreur de communication avec le serveur.</div>';
                    }
                }
            };
            
            // Préparer les données à envoyer
            const data = JSON.stringify({
                email: email,
                prenom: prenom,
                nom: nom
            });
            
            // Envoyer la requête
            xhr.send(data);
        }
    });
</script>
@endpush
@endsection