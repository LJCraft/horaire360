@extends('layouts.app')
@php 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; 
use Carbon\Carbon;
@endphp

@section('content')
<div class="container">
    <div class="row justify-content-between mb-4">
        <div class="col-md-6">
            <h1>Pointages biométriques</h1>
            <p class="text-muted">Analyse des pointages enregistrés via l'application mobile</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('rapports.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour aux rapports
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-funnel me-2"></i> Filtres
        </div>
        <div class="card-body">
            <form action="{{ route('rapports.biometrique') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="date_debut" class="form-label">Date de début</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ $dateDebut->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label for="date_fin" class="form-label">Date de fin</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ $dateFin->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
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
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Filtrer
                    </button>
                    <a href="{{ route('rapports.biometrique') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="display-4 mb-2">{{ $totalPointages }}</div>
                    <div class="text-muted">Pointages biométriques</div>
                </div>
                <div class="card-footer bg-primary text-white text-center">
                    <i class="bi bi-fingerprint me-1"></i> Total
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="display-4 mb-2">{{ $totalPointagesArriveeDepart }}</div>
                    <div class="text-muted">Pointages complets</div>
                </div>
                <div class="card-footer bg-success text-white text-center">
                    <i class="bi bi-check2-circle me-1"></i> Arrivée/Départ
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="display-4 mb-2">{{ number_format($totalPointages - $totalPointagesArriveeDepart) }}</div>
                    <div class="text-muted">Pointages partiels</div>
                </div>
                <div class="card-footer bg-warning text-white text-center">
                    <i class="bi bi-exclamation-triangle me-1"></i> Arrivée seule
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="display-4 mb-2">{{ $scoreMoyenBiometrique }}</div>
                    <div class="text-muted">Score biométrique moyen</div>
                </div>
                <div class="card-footer bg-info text-white text-center">
                    <i class="bi bi-person-badge me-1"></i> Confiance
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tableau des pointages -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-table me-2"></i> Liste des pointages biométriques
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Employé</th>
                            <th>Date</th>
                            <th>Arrivée</th>
                            <th>Départ</th>
                            <th>Statut</th>
                            <th>Score biométrique</th>
                            <th>Localisation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pointages as $pointage)
                            @php
                                $metaData = json_decode($pointage->meta_data, true);
                                $scoreArrivee = isset($metaData['biometric_verification']['confidence_score']) ? 
                                    $metaData['biometric_verification']['confidence_score'] : null;
                                $coordsArrivee = isset($metaData['location']) ? 
                                    $metaData['location']['latitude'] . ', ' . $metaData['location']['longitude'] : '-';
                            @endphp
                            <tr>
                                <td>
                                    @if($pointage->employe)
                                    <span class="fw-bold">{{ $pointage->employe->prenom }} {{ $pointage->employe->nom }}</span>
                                    @else
                                    <span class="text-muted">Employé supprimé</span>
                                    @endif
                                </td>
                                <td>{{ Carbon::parse($pointage->date)->format('d/m/Y') }}</td>
                                <td>{{ Carbon::parse($pointage->heure_arrivee)->format('H:i') }}</td>
                                <td>{{ $pointage->heure_depart ? Carbon::parse($pointage->heure_depart)->format('H:i') : '-' }}</td>
                                <td>
                                    @if($pointage->retard)
                                        <span class="badge bg-danger">Retard</span>
                                    @elseif($pointage->depart_anticipe)
                                        <span class="badge bg-warning text-dark">Départ anticipé</span>
                                    @else
                                        <span class="badge bg-success">OK</span>
                                    @endif
                                </td>
                                <td>
                                    @if($scoreArrivee)
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar progress-bar-striped 
                                                {{ $scoreArrivee >= 0.9 ? 'bg-success' : ($scoreArrivee >= 0.8 ? 'bg-info' : 'bg-warning') }}" 
                                                role="progressbar" 
                                                style="width: {{ $scoreArrivee * 100 }}%;"
                                                aria-valuenow="{{ $scoreArrivee * 100 }}" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                                {{ round($scoreArrivee * 100) }}%
                                            </div>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($coordsArrivee != '-')
                                        <a href="https://www.google.com/maps?q={{ $coordsArrivee }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-geo-alt"></i> Voir
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#infoModal{{ $pointage->id }}">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Modal d'informations détaillées -->
                            <div class="modal fade" id="infoModal{{ $pointage->id }}" tabindex="-1" aria-labelledby="infoModalLabel{{ $pointage->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="infoModalLabel{{ $pointage->id }}">
                                                Détails du pointage biométrique
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Informations générales</h6>
                                                    <table class="table table-bordered">
                                                        <tr>
                                                            <th>Employé</th>
                                                            <td>
                                                                @if($pointage->employe)
                                                                {{ $pointage->employe->prenom }} {{ $pointage->employe->nom }}
                                                                @else
                                                                <span class="text-muted">Employé supprimé</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Date</th>
                                                            <td>{{ Carbon::parse($pointage->date)->format('d/m/Y') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Arrivée</th>
                                                            <td>{{ Carbon::parse($pointage->heure_arrivee)->format('H:i') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Départ</th>
                                                            <td>{{ $pointage->heure_depart ? Carbon::parse($pointage->heure_depart)->format('H:i') : '-' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Commentaire</th>
                                                            <td>{{ $pointage->commentaire ?: '-' }}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Données biométriques</h6>
                                                    <table class="table table-bordered">
                                                        <tr>
                                                            <th>Score biométrique (arrivée)</th>
                                                            <td>{{ $scoreArrivee ? ($scoreArrivee * 100) . '%' : '-' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Coordonnées GPS (arrivée)</th>
                                                            <td>{{ $coordsArrivee }}</td>
                                                        </tr>
                                                        @if(isset($metaData['location']['accuracy']))
                                                        <tr>
                                                            <th>Précision GPS</th>
                                                            <td>{{ $metaData['location']['accuracy'] }} mètres</td>
                                                        </tr>
                                                        @endif
                                                        @if(isset($metaData['device_info']['device_id']))
                                                        <tr>
                                                            <th>Appareil</th>
                                                            <td>{{ $metaData['device_info']['device_id'] }}</td>
                                                        </tr>
                                                        @endif
                                                        @if(isset($metaData['checkout']))
                                                        <tr>
                                                            <th>Score biométrique (départ)</th>
                                                            <td>{{ isset($metaData['checkout']['biometric_verification']['confidence_score']) ? 
                                                                ($metaData['checkout']['biometric_verification']['confidence_score'] * 100) . '%' : '-' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Coordonnées GPS (départ)</th>
                                                            <td>{{ isset($metaData['checkout']['location']) ? 
                                                                $metaData['checkout']['location']['latitude'] . ', ' . 
                                                                $metaData['checkout']['location']['longitude'] : '-' }}</td>
                                                        </tr>
                                                        @endif
                                                    </table>
                                                </div>
                                            </div>
                                            @if(isset($metaData['location']))
                                            <div class="mt-3">
                                                <h6>Carte</h6>
                                                <div class="ratio ratio-16x9">
                                                    <iframe 
                                                        src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q={{ $metaData['location']['latitude'] }},{{ $metaData['location']['longitude'] }}&zoom=18" 
                                                        style="border:0;" 
                                                        allowfullscreen="" 
                                                        loading="lazy">
                                                    </iframe>
                                                </div>
                                                <div class="text-muted small mt-1">
                                                    Note: La clé API Google Maps utilisée est une clé de démonstration. Remplacez-la par votre propre clé en production.
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle me-2"></i> Aucun pointage biométrique trouvé pour cette période
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $pointages->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
</div>
@endsection 