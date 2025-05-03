@extends('layouts.app')

@section('title', 'Gestion des présences')

@section('page-title', 'Gestion des pointages')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="h3">Pointages</h1>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="{{ route('presences.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Ajouter un pointage
            </a>
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a href="{{ route('presences.importForm') }}" class="dropdown-item">
                    <i class="bi bi-upload"></i> Importer des pointages
                </a></li>
                <li><a href="{{ route('presences.template') }}" class="dropdown-item">
                    <i class="bi bi-file-earmark-excel"></i> Télécharger le modèle
                </a></li>
                <li><a href="{{ route('presences.export') }}" class="dropdown-item">
                    <i class="bi bi-download"></i> Exporter les pointages
                </a></li>
            </ul>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Filtres</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('presences.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="employe" class="form-label">Employé</label>
                <select class="form-select" id="employe" name="employe">
                    <option value="">Tous les employés</option>
                    @foreach($employes as $emp)
                        <option value="{{ $emp->id }}" {{ (isset($employe) && $employe == $emp->id) ? 'selected' : '' }}>
                            {{ $emp->prenom }} {{ $emp->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" value="{{ $date }}">
            </div>
            <div class="col-md-2">
                <label for="retard" class="form-label">Retard</label>
                <select class="form-select" id="retard" name="retard">
                    <option value="">Tous</option>
                    <option value="1" {{ (isset($retard) && $retard == '1') ? 'selected' : '' }}>Oui</option>
                    <option value="0" {{ (isset($retard) && $retard == '0') ? 'selected' : '' }}>Non</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="depart_anticipe" class="form-label">Départ anticipé</label>
                <select class="form-select" id="depart_anticipe" name="depart_anticipe">
                    <option value="">Tous</option>
                    <option value="1" {{ (isset($departAnticipe) && $departAnticipe == '1') ? 'selected' : '' }}>Oui</option>
                    <option value="0" {{ (isset($departAnticipe) && $departAnticipe == '0') ? 'selected' : '' }}>Non</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filtrer
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Employé</th>
                        <th>Date</th>
                        <th>Heure d'arrivée</th>
                        <th>Heure de départ</th>
                        <th>Durée</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($presences as $presence)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($presence->employe->prenom . ' ' . $presence->employe->nom) }}&size=32&color=7F9CF5&background=EBF4FF" alt="{{ $presence->employe->prenom }} {{ $presence->employe->nom }}" class="rounded-circle me-2">
                                    {{ $presence->employe->prenom }} {{ $presence->employe->nom }}
                                </div>
                            </td>
                            <td>{{ $presence->date->format('d/m/Y') }}</td>
                            <td>{{ $presence->heure_arrivee }}</td>
                            <td>{{ $presence->heure_depart ?: '-' }}</td>
                            <td>{{ $presence->duree ? number_format($presence->duree, 2) . ' h' : '-' }}</td>
                            <td>
                                @if($presence->retard)
                                    <span class="badge bg-warning">Retard</span>
                                @else
                                    <span class="badge bg-success">À l'heure</span>
                                @endif
                                
                                @if($presence->depart_anticipe && $presence->heure_depart)
                                    <span class="badge bg-warning">Départ anticipé</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('presences.show', $presence) }}" class="btn btn-info" data-bs-toggle="tooltip" title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('presences.edit', $presence) }}" class="btn btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger" onclick="confirmDelete('delete-presence-{{ $presence->id }}')" data-bs-toggle="tooltip" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form id="delete-presence-{{ $presence->id }}" action="{{ route('presences.destroy', $presence) }}" method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-3">Aucun pointage trouvé</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-end mt-3 px-3 pb-3">
            {{ $presences->links() }}
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer ce pointage ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">Supprimer</button>
            </div>
        </div>
    </div>
</div>
@endsection