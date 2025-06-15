@extends('layouts.app')

@section('title', 'Gestion des utilisateurs')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-people-gear me-2"></i>Gestion des utilisateurs
        </h1>
        <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary shadow-sm">
            <i class="bi bi-plus-circle fa-sm text-white-50 me-1"></i> Ajouter un utilisateur
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Liste des utilisateurs</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <li><a class="dropdown-item" href="#" id="exportUsersPDF">
                        <i class="bi bi-file-pdf fa-sm fa-fw me-2 text-gray-400"></i>Exporter en PDF
                    </a></li>
                    <li><a class="dropdown-item" href="#" id="exportUsersExcel">
                        <i class="bi bi-file-excel fa-sm fa-fw me-2 text-gray-400"></i>Exporter en Excel
                    </a></li>
                    <li><div class="dropdown-divider"></div></li>
                    <li><a class="dropdown-item" href="#" id="refreshUsersList">
                        <i class="bi bi-arrow-clockwise fa-sm fa-fw me-2 text-gray-400"></i>Actualiser
                    </a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Employé associé</th>
                            <th>Date de création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge bg-{{ $user->role && $user->role->nom === 'Administrateur' ? 'danger' : 'primary' }}">
                                        {{ $user->role ? $user->role->nom : 'Non défini' }}
                                    </span>
                                </td>
                                <td>
                                    @if($user->employe)
                                        <a href="{{ route('employes.show', $user->employe) }}">
                                            {{ $user->employe->prenom }} {{ $user->employe->nom }}
                                        </a>
                                    @else
                                        <span class="text-muted">Aucun</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                            data-bs-toggle="modal" data-bs-target="#deleteModal{{ $user->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>

                                    <!-- Modal de confirmation de suppression -->
                                    <div class="modal fade" id="deleteModal{{ $user->id }}" tabindex="-1" 
                                        aria-labelledby="deleteModalLabel{{ $user->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel{{ $user->id }}">
                                                        Confirmer la suppression
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong>{{ $user->name }}</strong> ?</p>
                                                    <p class="text-danger">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                                        Cette action est irréversible !
                                                    </p>
                                                    @if($user->employe)
                                                        <p class="text-warning">
                                                            <i class="bi bi-info-circle me-1"></i>
                                                            L'employé associé ne pourra plus se connecter au système.
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Supprimer</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">
                                    <p class="text-muted my-5">
                                        <i class="bi bi-people-slash fa-3x mb-3 d-block"></i>
                                        Aucun utilisateur trouvé
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // DataTable initialization
    if (document.getElementById('usersTable')) {
        $('#usersTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json'
            },
            "order": [[ 0, "desc" ]],
            "pageLength": 25
        });
    }
    
    // Handle refresh button
    document.getElementById('refreshUsersList').addEventListener('click', function(e) {
        e.preventDefault();
        window.location.reload();
    });
    
    // Handle export buttons (to be implemented)
    document.getElementById('exportUsersPDF').addEventListener('click', function(e) {
        e.preventDefault();
        alert('Fonctionnalité en cours de développement');
    });
    
    document.getElementById('exportUsersExcel').addEventListener('click', function(e) {
        e.preventDefault();
        alert('Fonctionnalité en cours de développement');
    });
});
</script>
@endsection 