@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Mon tableau de bord</h1>
        </div>
    </div>

    @if(!$employe)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Votre compte n'est pas associé à un profil employé. Veuillez contacter l'administrateur.
    </div>
    @else
    
    <!-- Informations de l'employé -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Mes informations</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="avatar-placeholder bg-light rounded-circle mx-auto mb-3" style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">
                                <i class="bi bi-person"></i>
                            </div>
                            <h5 class="mb-0">{{ $employe->prenom }} {{ $employe->nom }}</h5>
                            <p class="text-muted mb-0">{{ $employe->matricule }}</p>
                        </div>
                        <div class="col-md-8">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Poste</span>
                                    <span class="fw-bold">{{ $employe->poste->nom }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Département</span>
                                    <span class="fw-bold">{{ $employe->poste->departement }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Date d'embauche</span>
                                    <span class="fw-bold">{{ $employe->date_naissance instanceof \Carbon\Carbon ? $employe->date_naissance->format('d/m/Y') : (is_string($employe->date_naissance) ? \Carbon\Carbon::parse($employe->date_naissance)->format('d/m/Y') : '') }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Email</span>
                                    <span>{{ $employe->email }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Mon planning actuel</h5>
                </div>
                <div class="card-body">
                @if($planning)
                        <div class="mb-3">
                            <h6>Période: {{ $planning->date_debut->format('d/m/Y') }} au {{ $planning->date_fin->format('d/m/Y') }}</h6>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jour</th>
                                        <th>Horaire</th>
                                        <th>Durée</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                                        $today = \Carbon\Carbon::today()->dayOfWeek == 0 ? 7 : \Carbon\Carbon::today()->dayOfWeek;
                                    @endphp
                                    
                                    @foreach($jours as $index => $jour)
                                        @php 
                                            $jourIndex = $index + 1;
                                            $detail = $planning->details->firstWhere('jour', $jourIndex);
                                            $isToday = $jourIndex === $today;
                                        @endphp
                                        <tr class="{{ $isToday ? 'table-primary' : '' }}">
                                            <td>{{ $jour }} {{ $isToday ? '(Aujourd\'hui)' : '' }}</td>
                                            <td>
                                                @if(!$detail)
                                                    <span class="text-muted">Non défini</span>
                                                @elseif($detail->jour_repos)
                                                    <span class="badge bg-secondary">Repos</span>
                                                @elseif($detail->jour_entier)
                                                    <span class="badge bg-success">Journée complète</span>
                                                @else
                                                    {{ substr($detail->heure_debut, 0, 5) }} - {{ substr($detail->heure_fin, 0, 5) }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($detail)
                                                    {{ $detail->duree }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-end">Total hebdomadaire:</th>
                                        <th>{{ $planning->calculerHeuresTotales() }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-calendar3 text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-3 text-muted">Aucun planning n'est actuellement défini</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Derniers pointages et demandes -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Mes derniers pointages</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" disabled>
                            <i class="bi bi-list-ul"></i> Voir tout
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($presences) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Entrée</th>
                                        <th>Sortie</th>
                                        <th>Durée</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($presences as $presence)
                                    <tr>
                                        <td>{{ $presence->date->format('d/m/Y') }}</td>
                                        <td>{{ $presence->heure_entree }}</td>
                                        <td>{{ $presence->heure_sortie ?? '-' }}</td>
                                        <td>{{ $presence->duree ?? '-' }}</td>
                                        <td>
                                            @if($presence->statut === 'présent')
                                                <span class="badge bg-success">Présent</span>
                                            @elseif($presence->statut === 'retard')
                                                <span class="badge bg-warning">Retard</span>
                                            @elseif($presence->statut === 'absent')
                                                <span class="badge bg-danger">Absent</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $presence->statut }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-clock-history text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-3 text-muted">Aucun pointage enregistré</p>
                            <small class="text-muted">Le module de présence sera disponible dans l'itération 3</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Mes demandes</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" disabled>
                            <i class="bi bi-plus-circle"></i> Nouvelle demande
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <p class="mt-3 text-muted">Aucune demande en cours</p>
                        <small class="text-muted">La gestion des demandes sera disponible dans les prochaines itérations</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Widget résumé d'assiduité -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Mon assiduité du mois</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="p-3 rounded border">
                                <div class="fs-1 text-primary">0</div>
                                <div class="text-muted">Jours travaillés</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 rounded border">
                                <div class="fs-1 text-success">0h</div>
                                <div class="text-muted">Heures totales</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 rounded border">
                                <div class="fs-1 text-warning">0</div>
                                <div class="text-muted">Retards</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 rounded border">
                                <div class="fs-1 text-danger">0</div>
                                <div class="text-muted">Absences</div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <small class="text-muted">Les statistiques d'assiduité seront disponibles dans l'itération 4</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @endif
</div>
@endsection