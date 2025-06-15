@extends('layouts.app')

@section('title', 'Modifier un utilisateur')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-person-gear me-2"></i>Modifier un utilisateur
        </h1>
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="bi bi-arrow-left fa-sm text-white-50 me-1"></i> Retour à la liste
        </a>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Informations de l'utilisateur</h6>
                    @if($user->employe)
                        <a href="{{ route('employes.show', $user->employe) }}" class="btn btn-sm btn-info">
                            <i class="bi bi-person-badge me-1"></i> Voir la fiche employé
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Adresse email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Nouveau mot de passe (laisser vide pour conserver l'actuel)</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i> 
                                    Si modifié, le mot de passe doit contenir au moins 8 caractères.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirmation du nouveau mot de passe</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="role_id" class="form-label">Rôle <span class="text-danger">*</span></label>
                                <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
                                    <option value="">Sélectionner un rôle</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" 
                                            {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}
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
                                <div id="adminRoleWarning" class="alert alert-warning mt-2 {{ $user->role && $user->role->nom === 'Administrateur' ? '' : 'd-none' }}">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <strong>Attention!</strong> Les utilisateurs avec le rôle Administrateur ont un accès complet à toutes les fonctionnalités du système.
                                </div>
                            </div>
                            <div class="col-md-6">
                                @if($user->employe)
                                    <div class="alert alert-info">
                                        <i class="bi bi-link me-1"></i>
                                        Ce compte est actuellement associé à l'employé <strong>{{ $user->employe->prenom }} {{ $user->employe->nom }}</strong>.
                                        <br>
                                        <small>Pour modifier cette association, veuillez passer par la fiche de l'employé.</small>
                                    </div>
                                @else
                                    <div class="alert alert-secondary">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Ce compte n'est associé à aucun employé.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="border-top pt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Enregistrer les modifications
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x me-1"></i> Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-12">
            <div class="card shadow mb-4 border-left-danger">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">Zone dangereuse</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-danger">Supprimer ce compte utilisateur</h5>
                            <p class="mb-0">
                                Cette action est irréversible et supprimera définitivement ce compte utilisateur.
                                @if($user->employe)
                                <br>
                                <small>L'employé associé ne sera pas supprimé, mais ne pourra plus se connecter au système.</small>
                                @endif
                            </p>
                        </div>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                            <i class="bi bi-trash me-1"></i> Supprimer
                        </button>
                    </div>
                    
                    <!-- Modal de confirmation de suppression -->
                    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteUserModalLabel">Confirmer la suppression</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong>{{ $user->name }}</strong> ?</p>
                                    <p class="text-danger">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Cette action est irréversible !
                                    </p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <form action="{{ route('users.destroy', $user) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
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
});
</script>
@endsection 