@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold">Rapport des présences</h1>
            <p class="text-muted">Analyse détaillée des pointages par employé et période</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="{{ route('rapports.export.pdf', ['type' => 'presences']) }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-pdf"></i> PDF
                </a>
                <a href="{{ route('rapports.export.excel', ['type' => 'presences']) }}" class="btn btn-outline-success">
                    <i class="bi bi-file-excel"></i> Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Filtres</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('rapports.presences') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date_debut" class="form-label">Date de début</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ $dateDebut }}">
                </div>
                <div class="col-md-3">
                    <label for="date_fin" class="form-label">Date de fin</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ $dateFin }}">
                </div>
                <div class="col-md-2">
                    <label for="employe_id" class="form-label">Employé</label>
                    <select class="form-select" id="employe_id" name="employe_id">
                        <option value="">Tous les employés</option>
                        @foreach($employes as $employe)
                            <option value="{{ $employe->id }}" {{ $employeId == $employe->id ? 'selected' : '' }}>
                                {{ $employe->prenom }} {{ $employe->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="poste_id" class="form-label">Poste</label>
                    <select class="form-select" id="poste_id" name="poste_id">
                        <option value="">Tous les postes</option>
                        @foreach($postes as $poste)
                            <option value="{{ $poste->id }}" {{ $posteId == $poste->id ? 'selected' : '' }}>
                                {{ $poste->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="tous" {{ $type == 'tous' ? 'selected' : '' }}>Toutes les présences</option>
                        <option value="retards" {{ $type == 'retards' ? 'selected' : '' }}>Retards uniquement</option>
                        <option value="departs_anticipes" {{ $type == 'departs_anticipes' ? 'selected' : '' }}>Départs anticipés</option>
                    </select>
                </div>
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filtrer
                    </button>
                    <a href="{{ route('rapports.presences') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Cartes des statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="rounded-circle bg-primary bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-calendar-check text-primary fs-4"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $totalPresences }}</h3>
                    <p class="text-muted">Présences totales</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="rounded-circle bg-warning bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-clock-history text-warning fs-4"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $totalRetards }}</h3>
                    <p class="text-muted">Retards</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="rounded-circle bg-danger bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-door-open text-danger fs-4"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $totalDepartsAnticipes }}</h3>
                    <p class="text-muted">Départs anticipés</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="rounded-circle bg-success bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-check-circle text-success fs-4"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $pourcentageAssiduite }}%</h3>
                    <p class="text-muted">Taux d'assiduité</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des présences -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Liste des présences</h5>
            <span class="badge bg-primary rounded-pill">{{ $presences->total() }} résultats</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Employé</th>
                            <th>Poste</th>
                            <th>Arrivée</th>
                            <th>Départ</th>
                            <th class="text-center">Durée</th>
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($presences as $presence)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($presence->date)->format('d/m/Y') }}</td>
                                <td>
                                    @if($presence->employe)
                                    <div class="d-flex align-items-center">
                                        @if($presence->employe->photo_profil && file_exists(public_path('storage/photos/' . $presence->employe->photo_profil)))
                                            <img src="{{ asset('storage/photos/' . $presence->employe->photo_profil) }}" 
                                                alt="Photo de {{ $presence->employe->prenom }}" 
                                                class="rounded-circle me-2" 
                                                style="width: 30px; height: 30px; object-fit: cover;">
                                        @else
                                            <div class="avatar-initials bg-primary bg-opacity-10 text-primary me-2">
                                                {{ strtoupper(substr($presence->employe->prenom ?? '', 0, 1)) }}{{ strtoupper(substr($presence->employe->nom ?? '', 0, 1)) }}
                                            </div>
                                        @endif
                                        {{ $presence->employe->prenom }} {{ $presence->employe->nom }}
                                    </div>
                                    @else
                                    <span class="text-muted">Employé supprimé</span>
                                    @endif
                                </td>
                                <td>
                                    @if($presence->employe && $presence->employe->poste)
                                    {{ $presence->employe->poste->nom }}
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($presence->heure_arrivee)->format('H:i') }}</td>
                                <td>{{ $presence->heure_depart ? \Carbon\Carbon::parse($presence->heure_depart)->format('H:i') : '-' }}</td>
                                <td class="text-center">
                                    @if($presence->heure_depart)
                                        @php
                                            $debut = \Carbon\Carbon::parse($presence->heure_arrivee);
                                            $fin = \Carbon\Carbon::parse($presence->heure_depart);
                                            if ($fin < $debut) {
                                                $fin->addDay();
                                            }
                                            $duree = $debut->diff($fin);
                                        @endphp
                                        {{ $duree->format('%H:%I') }}
                                    @else
                                        <span class="badge bg-secondary">En cours</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($presence->retard)
                                        <span class="badge bg-warning text-dark">Retard</span>
                                    @elseif($presence->depart_anticipe)
                                        <span class="badge bg-danger">Départ anticipé</span>
                                    @else
                                        <span class="badge bg-success">Conforme</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-search fs-3 d-block mb-2"></i>
                                        Aucune présence trouvée pour les critères sélectionnés
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $presences->firstItem() ?? 0 }} à {{ $presences->lastItem() ?? 0 }} sur {{ $presences->total() }} résultats
                </div>
                <div>
                    {{ $presences->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-initials {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
    }
    
    .pagination {
        margin-bottom: 0;
    }
</style>
@endpush 