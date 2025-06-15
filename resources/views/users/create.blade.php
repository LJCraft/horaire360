@extends('layouts.app')

@section('title', 'Ajouter un utilisateur')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-person-plus me-2"></i>Ajouter un utilisateur
        </h1>
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="bi bi-arrow-left fa-sm text-white-50 me-1"></i> Retour à la liste
        </a>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informations de l'utilisateur</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Adresse email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i> 
                                    Le mot de passe doit contenir au moins 8 caractères.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirmation du mot de passe <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="role_id" class="form-label">Rôle <span class="text-danger">*</span></label>
                                <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
                                    <option value="">Sélectionner un rôle</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" 
                                            {{ old('role_id') == $role->id ? 'selected' : '' }}
                                            {{ $role->nom === 'Administrateur' ? 'data-badge=admin' : '' }}>
                                            {{ $role->nom }}
                                            @if($role->nom === 'Administrateur')
                                                (Accès complet au système)
                                            @elseif($role->nom === 'Employé')
                                                (Accès limité au système)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="adminRoleWarning" class="alert alert-warning mt-2 d-none">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <strong>Attention!</strong> Les utilisateurs avec le rôle Administrateur ont un accès complet à toutes les fonctionnalités du système.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="employe_id" class="form-label">Associer à un employé (optionnel)</label>
                                <select class="form-select @error('employe_id') is-invalid @enderror" id="employe_id" name="employe_id">
                                    <option value="">Aucun employé associé</option>
                                    @foreach($employes as $employe)
                                        <option value="{{ $employe->id }}" 
                                            {{ old('employe_id') == $employe->id ? 'selected' : '' }}
                                            data-email="{{ $employe->email }}">
                                            {{ $employe->prenom }} {{ $employe->nom }} ({{ $employe->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('employe_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i> 
                                    Si vous associez un employé, il pourra se connecter au système.
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Enregistrer
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x me-1"></i> Annuler
                            </a>
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
    // Afficher un avertissement lors de la sélection du rôle Administrateur
    const roleSelect = document.getElementById('role_id');
    const adminWarning = document.getElementById('adminRoleWarning');
    
    roleSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.getAttribute('data-badge') === 'admin') {
            adminWarning.classList.remove('d-none');
        } else {
            adminWarning.classList.add('d-none');
        }
    });
    
    // Auto-remplir l'email lors de la sélection d'un employé
    const employeSelect = document.getElementById('employe_id');
    const emailInput = document.getElementById('email');
    const nameInput = document.getElementById('name');
    
    employeSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            const employeEmail = selectedOption.getAttribute('data-email');
            const employeName = selectedOption.text.split(' (')[0];
            
            // Proposer de remplir l'email automatiquement
            if (employeEmail && (!emailInput.value || confirm('Voulez-vous utiliser l\'email de l\'employé sélectionné?'))) {
                emailInput.value = employeEmail;
            }
            
            // Proposer de remplir le nom automatiquement
            if (employeName && (!nameInput.value || confirm('Voulez-vous utiliser le nom de l\'employé sélectionné?'))) {
                nameInput.value = employeName;
            }
        }
    });
});
</script>
@endsection 