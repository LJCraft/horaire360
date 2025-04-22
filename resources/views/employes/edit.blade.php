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
            <form action="{{ route('employes.update', $employe) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-3">
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
                    <div class="col-md-5">
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
                                <option value="{{ $poste->id }}" {{ old('poste_id', $employe->poste_id) == $poste->id ? 'selected' : '' }}>
                                    {{ $poste->nom }} ({{ $poste->departement }})
                                </option>
                            @endforeach
                        </select>
                        @error('poste_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
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
@endsection