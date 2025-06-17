@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-building me-2"></i>Planning par département
                    </h5>
                    @if($departementSelectionne)
                    <a href="{{ route('plannings.departement.create', ['departement' => $departementSelectionne]) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>Créer un planning départemental
                    </a>
                    @endif
                </div>

                <div class="card-body">
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

                    <form action="{{ route('plannings.departement.index') }}" method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label for="departement" class="form-label">Sélectionner un département</label>
                                <select name="departement" id="departement" class="form-select">
                                    <option value="">-- Choisir un département --</option>
                                    @foreach($departements as $departement)
                                    <option value="{{ $departement }}" {{ $departement == $departementSelectionne ? 'selected' : '' }}>
                                        {{ $departement }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-1"></i>Filtrer
                                </button>
                            </div>
                        </div>
                    </form>

                    @if($departementSelectionne)
                    <div class="mb-4">
                        <h4>Département : {{ $departementSelectionne }}</h4>
                        
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="card border-primary mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <i class="bi bi-briefcase me-1"></i>Postes ({{ $postes->count() }})
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group">
                                            @forelse($postes as $poste)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                {{ $poste->nom }}
                                                <span class="badge bg-primary rounded-pill">
                                                    {{ $poste->employes->where('statut', 'actif')->count() }} employés
                                                </span>
                                            </li>
                                            @empty
                                            <li class="list-group-item">Aucun poste trouvé</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="card border-success mb-3">
                                    <div class="card-header bg-success text-white">
                                        <i class="bi bi-people me-1"></i>Employés ({{ $employes->count() }})
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Employé</th>
                                                        <th>Poste</th>
                                                        <th>Planning actif</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($employes as $employe)
                                                    <tr>
                                                        <td>{{ $employe->nom_complet }}</td>
                                                        <td>{{ $employe->poste->nom }}</td>
                                                        <td>
                                                            @php
                                                                $planningActif = $plannings->where('employe_id', $employe->id)
                                                                    ->filter(function($planning) {
                                                                        return $planning->estEnCours();
                                                                    })
                                                                    ->first();
                                                            @endphp
                                                            
                                                            @if($planningActif)
                                                                <span class="badge bg-success">
                                                                    <i class="bi bi-check-circle me-1"></i>Oui
                                                                </span>
                                                                <small>{{ $planningActif->date_debut->format('d/m/Y') }} - {{ $planningActif->date_fin->format('d/m/Y') }}</small>
                                                            @else
                                                                <span class="badge bg-danger">
                                                                    <i class="bi bi-x-circle me-1"></i>Non
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($planningActif)
                                                                <a href="{{ route('plannings.show', $planningActif->id) }}" class="btn btn-sm btn-outline-primary">
                                                                    <i class="bi bi-eye me-1"></i>Voir
                                                                </a>
                                                            @else
                                                                <span class="text-muted">
                                                                    <i class="bi bi-eye-slash me-1"></i>Aucun planning
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center">Aucun employé trouvé</td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-info mb-3">
                        <div class="card-header bg-info text-white">
                            <i class="bi bi-calendar-week me-1"></i>Plannings départementaux
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Titre</th>
                                            <th>Début</th>
                                            <th>Fin</th>
                                            <th>Employés</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            // Regrouper les plannings par date de début, date de fin et titre
                                            $planningsGroupes = $plannings->groupBy(function($planning) {
                                                return $planning->date_debut->format('Y-m-d') . '_' . 
                                                       $planning->date_fin->format('Y-m-d') . '_' . 
                                                       $planning->titre;
                                            });
                                        @endphp

                                        @forelse($planningsGroupes as $groupe)
                                            @php
                                                $planning = $groupe->first();
                                                $nbEmployes = $groupe->count();
                                            @endphp
                                            <tr>
                                                <td>{{ $planning->titre }}</td>
                                                <td>{{ $planning->date_debut->format('d/m/Y') }}</td>
                                                <td>{{ $planning->date_fin->format('d/m/Y') }}</td>
                                                <td>{{ $nbEmployes }}</td>
                                                <td>
                                                    @if($planning->estEnCours())
                                                        <span class="badge bg-success">Actif</span>
                                                    @elseif($planning->estAVenir())
                                                        <span class="badge bg-warning text-dark">À venir</span>
                                                    @else
                                                        <span class="badge bg-secondary">Terminé</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('plannings.show', $planning->id) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye me-1"></i>Détails
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">Aucun planning départemental trouvé</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Veuillez sélectionner un département pour afficher ses plannings.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 