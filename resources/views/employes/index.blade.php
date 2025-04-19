@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-between mb-4">
        <div class="col-md-6">
            <h1>Gestion des employés</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('employes.create') }}" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle"></i> Nouvel employé
            </a>
            <a href="{{ route('employes.export') }}" class="btn btn-success me-2">
                <i class="bi bi-file-excel"></i> Exporter
            </a>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-earmark-arrow-up"></i> Importer
            </button>
        </div>
    </div>

    <!-- Messages de notification -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Formulaire de recherche et filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('employes.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Rechercher..." name="search" value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="poste_id" class="form-select">
                        <option value="">Tous les postes</option>
                        @foreach($postes as $poste)
                            <option value="{{ $poste->id }}" {{ request('poste_id') == $poste->id ? 'selected' : '' }}>
                                {{ $poste->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="actif" {{ request('statut') == 'actif' ? 'selected' : '' }}>Actif</option>
                        <option value="inactif" {{ request('statut') == 'inactif' ? 'selected' : '' }}>Inactif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des employés -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a href="{{ route('employes.index', ['sort' => 'matricule', 'direction' => request('sort') === 'matricule' && request('direction') === 'asc' ? 'desc' : 'asc'] + request()->except(['sort', 'direction'])) }}">
                                    Matricule
                                    @if(request('sort') === 'matricule')
                                        <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>
                                <a href="{{ route('employes.index', ['sort' => 'nom', 'direction' => request('sort') === 'nom' && request('direction') === 'asc' ? 'desc' : 'asc'] + request()->except(['sort', 'direction'])) }}">
                                    Nom
                                    @if(request('sort') === 'nom')
                                        <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>
                                <a href="{{ route('employes.index', ['sort' => 'email', 'direction' => request('sort') === 'email' && request('direction') === 'asc' ? 'desc' : 'asc'] + request()->except(['sort', 'direction'])) }}">
                                    Email
                                    @if(request('sort') === 'email')
                                        <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Poste</th>
                            <th>
                                <a href="{{ route('employes.index', ['sort' => 'date_embauche', 'direction' => request('sort') === 'date_embauche' && request('direction') === 'asc' ? 'desc' : 'asc'] + request()->except(['sort', 'direction'])) }}">
                                    Date d'embauche
                                    @if(request('sort') === 'date_embauche')
                                        <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employes as $employe)
                            <tr>
                                <td>{{ $employe->matricule }}</td>
                                <td>{{ $employe->prenom }} {{ $employe->nom }}</td>
                                <td>{{ $employe->email }}</td>
                                <td>{{ $employe->poste->nom }}</td>
                                <td>{{ \Carbon\Carbon::parse($employe->date_embauche)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $employe->statut === 'actif' ? 'success' : 'danger' }}">
                                        {{ $employe->statut }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('employes.show', $employe) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('employes.edit', $employe) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="event.preventDefault(); 
                                                         document.getElementById('delete-form-{{ $employe->id }}').submit();">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <form id="delete-form-{{ $employe->id }}" action="{{ route('employes.destroy', $employe) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Aucun employé trouvé</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $employes->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
    
    <!-- Modal Import -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Importer des employés</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('employes.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="file" class="form-label">Fichier Excel</label>
                            <input type="file" class="form-control" id="file" name="file" required>
                        </div>
                        <p class="text-muted">
                            Veuillez utiliser le modèle d'importation pour vous assurer que votre fichier est au bon format.
                            <a href="{{ route('employes.export-template') }}" class="text-decoration-none">
                                Télécharger le modèle <i class="bi bi-download"></i>
                            </a>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Importer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection