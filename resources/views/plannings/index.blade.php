@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-between mb-4">
        <div class="col-md-6">
            <h1>Gestion des plannings</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('plannings.create') }}" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle"></i> Nouveau planning
            </a>
            <a href="{{ route('plannings.calendrier') }}" class="btn btn-info me-2">
                <i class="bi bi-calendar-week"></i> Calendrier
            </a>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('plannings.export') }}">Exporter tous les plannings</a></li>
                    <li><a class="dropdown-item" href="{{ route('plannings.export-template') }}">Télécharger le modèle</a></li>
                </ul>
            </div>
            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload"></i> Importer
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Formulaire de recherche et filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('plannings.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="employe_id" class="form-label">Employé</label>
                    <select name="employe_id" id="employe_id" class="form-select">
                        <option value="">Tous les employés</option>
                        @foreach($employes as $employe)
                            <option value="{{ $employe->id }}" {{ request('employe_id') == $employe->id ? 'selected' : '' }}>
                                {{ $employe->nom }} {{ $employe->prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="statut" class="form-label">Statut</label>
                    <select name="statut" id="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="en_cours" {{ request('statut') == 'en_cours' ? 'selected' : '' }}>En cours</option>
                        <option value="a_venir" {{ request('statut') == 'a_venir' ? 'selected' : '' }}>À venir</option>
                        <option value="termine" {{ request('statut') == 'termine' ? 'selected' : '' }}>Terminé</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="date_debut" class="form-label">Date de début (min)</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ request('date_debut') }}">
                </div>
                
                <div class="col-md-3">
                    <label for="date_fin" class="form-label">Date de fin (max)</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ request('date_fin') }}">
                </div>
                
                <div class="col-12">
                    <div class="float-end">
                        <a href="{{ route('plannings.index') }}" class="btn btn-outline-secondary me-2">Réinitialiser</a>
                        <button type="submit" class="btn btn-primary">Filtrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des plannings -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a href="{{ route('plannings.index', ['sort' => 'id', 'direction' => request('sort') === 'id' && request('direction') === 'asc' ? 'desc' : 'asc'] + request()->except(['sort', 'direction'])) }}">
                                    ID
                                    @if(request('sort') === 'id')
                                        <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Employé</th>
                            <th>
                                <a href="{{ route('plannings.index', ['sort' => 'date_debut', 'direction' => request('sort') === 'date_debut' && request('direction') === 'asc' ? 'desc' : 'asc'] + request()->except(['sort', 'direction'])) }}">
                                    Début
                                    @if(request('sort') === 'date_debut')
                                        <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>
                                <a href="{{ route('plannings.index', ['sort' => 'date_fin', 'direction' => request('sort') === 'date_fin' && request('direction') === 'asc' ? 'desc' : 'asc'] + request()->except(['sort', 'direction'])) }}">
                                    Fin
                                    @if(request('sort') === 'date_fin')
                                        <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Titre</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plannings as $planning)
                            @if($planning)
                                <tr>
                                    <td>{{ $planning->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($planning->employe && $planning->employe->photo_profil && file_exists(public_path('storage/photos/' . $planning->employe->photo_profil)))
                                                <img src="{{ asset('storage/photos/' . $planning->employe->photo_profil) }}" 
                                                    alt="Photo de {{ $planning->employe->prenom }}" 
                                                    class="rounded-circle me-2" 
                                                    style="width: 32px; height: 32px; object-fit: cover;">
                                            @elseif($planning->employe)
                                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                    style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                    {{ strtoupper(substr($planning->employe->prenom, 0, 1)) }}{{ strtoupper(substr($planning->employe->nom, 0, 1)) }}
                                                </div>
                                            @else
                                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                    style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                    ?
                                                </div>
                                            @endif
                                            {{ $planning->employe ? $planning->employe->prenom . ' ' . $planning->employe->nom : 'Employé inconnu' }}
                                        </div>
                                    </td>
                                    <td>{{ $planning->date_debut ? $planning->date_debut->format('d/m/Y') : '' }}</td>
                                    <td>{{ $planning->date_fin ? $planning->date_fin->format('d/m/Y') : '' }}</td>
                                    <td>{{ $planning->titre }}</td>
                                    <td>
                                        @if($planning->statut === 'en_cours')
                                            <span class="badge bg-success">En cours</span>
                                        @elseif($planning->statut === 'a_venir')
                                            <span class="badge bg-info">À venir</span>
                                        @else
                                            <span class="badge bg-secondary">Terminé</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('plannings.show', ['planning' => $planning->id]) }}" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('plannings.edit', $planning) }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="if(confirm('Êtes-vous sûr de vouloir supprimer ce planning?')) { 
                                                        document.getElementById('delete-form-{{ $planning->id }}').submit(); 
                                                    }">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <form id="delete-form-{{ $planning->id }}" action="{{ route('plannings.destroy', $planning) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Aucun planning trouvé</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $plannings->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Import -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Importer des plannings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('plannings.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="file" class="form-label">Fichier Excel</label>
                            <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <p class="text-muted">
                            Veuillez utiliser le modèle d'importation pour vous assurer que votre fichier est au bon format.
                            <a href="{{ route('plannings.export-template') }}" class="text-decoration-none">
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