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
                                <img id="preview_image" src="https://ui-avatars.com/api/?name=?&background=random&color=fff&size=256" 
                                    alt="Preview" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                            </div>
                            <div class="input-group">
                                <input type="file" class="form-control @error('photo_profil') is-invalid @enderror" 
                                    id="photo_profil" name="photo_profil" accept="image/*">
                                <button type="button" class="btn btn-outline-secondary" 
                                    onclick="document.getElementById('photo_profil').value='';document.getElementById('preview_image').src='https://ui-avatars.com/api/?name=?&background=random&color=fff&size=256';">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                            @error('photo_profil')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Formats acceptés: JPG, PNG. Max: 2 Mo</div>
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
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
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
</script>
@endpush
@endsection