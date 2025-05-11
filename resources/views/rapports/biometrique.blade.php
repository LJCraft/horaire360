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
            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload"></i> Importer des données
            </button>
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
    
    <!-- Résultats d'importation si disponibles -->
    @if(isset($importStats))
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-cloud-arrow-down-fill me-2"></i> Résultats d'importation
            </div>
            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseImportDetails" aria-expanded="false">
                <i class="bi bi-chevron-down"></i> Détails
            </button>
        </div>
        <div class="card-body">
            <div class="row text-center mb-4">
                <div class="col-md-3">
                    <div class="border-end">
                        <h3 class="mb-0">{{ $importStats['total'] }}</h3>
                        <p class="text-muted">Total traités</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border-end">
                        <h3 class="mb-0 text-success">{{ $importStats['imported'] }}</h3>
                        <p class="text-muted">Importés</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border-end">
                        <h3 class="mb-0 text-warning">{{ $importStats['skipped'] }}</h3>
                        <p class="text-muted">Ignorés</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div>
                        <h3 class="mb-0 text-danger">{{ $importStats['errors'] }}</h3>
                        <p class="text-muted">Erreurs</p>
                    </div>
                </div>
            </div>
            
            <div class="collapse" id="collapseImportDetails">
                <h5>Détails de l'importation</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Statut</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($importStats['details'] as $detail)
                            <tr>
                                <td>
                                    @if($detail['type'] === 'success')
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i></span>
                                    @elseif($detail['type'] === 'info')
                                        <span class="badge bg-info"><i class="bi bi-info-circle"></i></span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-circle"></i></span>
                                    @endif
                                </td>
                                <td>{{ $detail['message'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
    
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
                                    <div class="d-flex align-items-center">
                                        @if($pointage->employe->photo_profil && file_exists(public_path('storage/photos/' . $pointage->employe->photo_profil)))
                                            <img src="{{ asset('storage/photos/' . $pointage->employe->photo_profil) }}" 
                                                alt="Photo de {{ $pointage->employe->prenom }}" 
                                                class="rounded-circle me-2" 
                                                style="width: 30px; height: 30px; object-fit: cover;">
                                        @else
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary me-2" 
                                                 style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                {{ strtoupper(substr($pointage->employe->prenom, 0, 1)) }}{{ strtoupper(substr($pointage->employe->nom, 0, 1)) }}
                                            </div>
                                        @endif
                                        <span class="fw-bold">{{ $pointage->employe->prenom }} {{ $pointage->employe->nom }}</span>
                                    </div>
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
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mapModal{{ $pointage->id }}">
                                            <i class="bi bi-geo-alt"></i> Voir
                                        </button>
                                        
                                        <!-- Modal pour la carte -->
                                        <div class="modal fade" id="mapModal{{ $pointage->id }}" tabindex="-1" aria-labelledby="mapModalLabel{{ $pointage->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="mapModalLabel{{ $pointage->id }}">Localisation du pointage</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        @php
                                                            $locationParts = explode(',', $coordsArrivee);
                                                            $latitude = trim($locationParts[0]);
                                                            $longitude = trim($locationParts[1]);
                                                            $accuracy = isset($metaData['location']['accuracy']) ? $metaData['location']['accuracy'] : 0;
                                                        @endphp
                                                        <div class="map-container">
                                                            <div id="map-pointage-{{ $pointage->id }}" class="location-map" 
                                                                 data-leaflet-map 
                                                                 data-lat="{{ $latitude }}" 
                                                                 data-lng="{{ $longitude }}"
                                                                 data-accuracy="{{ $accuracy }}"
                                                                 data-type="arrival"
                                                                 @if($pointage->employe && $pointage->employe->photo_profil && file_exists(public_path('storage/photos/' . $pointage->employe->photo_profil)))
                                                                 data-photo="{{ asset('storage/photos/' . $pointage->employe->photo_profil) }}"
                                                                 @endif
                                                                 data-initials="{{ $pointage->employe ? strtoupper(substr($pointage->employe->prenom, 0, 1) . substr($pointage->employe->nom, 0, 1)) : '??' }}"
                                                                 data-title="Pointage de {{ $pointage->employe ? $pointage->employe->prenom . ' ' . $pointage->employe->nom : 'Employé' }}"></div>
                                                        </div>
                                                        <div class="mt-3">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <p><strong>Coordonnées :</strong> {{ $coordsArrivee }}</p>
                                                                    @if(isset($metaData['location']['accuracy']))
                                                                    <p><strong>Précision :</strong> {{ $metaData['location']['accuracy'] }} mètres</p>
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p><strong>Date :</strong> {{ Carbon::parse($pointage->date)->format('d/m/Y') }}</p>
                                                                    <p><strong>Heure :</strong> {{ Carbon::parse($pointage->heure_arrivee)->format('H:i') }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
                                                <div class="map-container">
                                                    <div id="map-details-{{ $pointage->id }}" class="location-map"
                                                         data-leaflet-map
                                                         data-lat="{{ $metaData['location']['latitude'] }}"
                                                         data-lng="{{ $metaData['location']['longitude'] }}"
                                                         data-accuracy="{{ $metaData['location']['accuracy'] ?? 0 }}"
                                                         data-type="arrival"
                                                         @if($pointage->employe && $pointage->employe->photo_profil && file_exists(public_path('storage/photos/' . $pointage->employe->photo_profil)))
                                                         data-photo="{{ asset('storage/photos/' . $pointage->employe->photo_profil) }}"
                                                         @endif
                                                         data-initials="{{ $pointage->employe ? strtoupper(substr($pointage->employe->prenom, 0, 1) . substr($pointage->employe->nom, 0, 1)) : '??' }}"
                                                         data-title="Localisation (arrivée)"></div>
                                                </div>
                                                @if(isset($metaData['checkout']) && isset($metaData['checkout']['location']))
                                                <div class="map-container mt-2">
                                                    <div id="map-details-checkout-{{ $pointage->id }}" class="location-map"
                                                         data-leaflet-map
                                                         data-lat="{{ $metaData['checkout']['location']['latitude'] }}"
                                                         data-lng="{{ $metaData['checkout']['location']['longitude'] }}"
                                                         data-accuracy="{{ $metaData['checkout']['location']['accuracy'] ?? 0 }}"
                                                         data-type="departure"
                                                         @if($pointage->employe && $pointage->employe->photo_profil && file_exists(public_path('storage/photos/' . $pointage->employe->photo_profil)))
                                                         data-photo="{{ asset('storage/photos/' . $pointage->employe->photo_profil) }}"
                                                         @endif
                                                         data-initials="{{ $pointage->employe ? strtoupper(substr($pointage->employe->prenom, 0, 1) . substr($pointage->employe->nom, 0, 1)) : '??' }}"
                                                         data-title="Localisation (départ)"></div>
                                                </div>
                                                @endif
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

<!-- Modal d'importation de données biométriques -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title" id="importModalLabel"><i class="bi bi-upload me-1"></i> Importer données biométriques</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                @if(session('error'))
                    <div class="alert alert-danger py-2">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('presences.importBiometrique') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-8">
                            <div class="mb-2">
                                <label for="file" class="form-label small mb-1">Fichier</label>
                                <input type="file" class="form-control form-control-sm @error('file') is-invalid @enderror" id="file" name="file" required>
                                @error('file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label for="format" class="form-label small mb-1">Format</label>
                                <select class="form-select form-select-sm @error('format') is-invalid @enderror" id="format" name="format" required>
                                    <option value="">Sélectionner</option>
                                    <option value="json">JSON</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" id="skip_existing" name="skip_existing">
                        <label class="form-check-label small" for="skip_existing">Ignorer les pointages existants</label>
                    </div>

                    <!-- Zone de résultats de vérification -->
                    <div id="verificationResults" class="mb-2 d-none">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white py-1 small">
                                <i class="bi bi-info-circle me-1"></i> Résultats de vérification
                            </div>
                            <div class="card-body p-2 small" id="verificationContent">
                                <!-- Les résultats de vérification seront insérés ici -->
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleFormatHelp">
                                <i class="bi bi-info-circle"></i> Formats
                            </button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-info me-1" id="verifyFileBtn" onclick="verifyFile()">
                                <i class="bi bi-check2-circle"></i> Vérifier
                            </button>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-cloud-arrow-up"></i> Importer
                            </button>
                        </div>
                    </div>
                    
                    <!-- Aide sur les formats (caché par défaut) -->
                    <div id="formatHelp" class="mt-2 p-2 bg-light rounded small d-none">
                        <div class="d-flex flex-column">
                            <div class="mb-2">
                                <strong>Format CSV:</strong> 
                                <ul class="mb-1">
                                    <li>Utiliser des <strong>virgules</strong> comme séparateurs</li>
                                    <li>Inclure une ligne d'en-tête avec les noms des colonnes</li>
                                    <li>Colonnes requises: employee_id, timestamp, type, latitude, longitude, biometric_score</li>
                                    <li>Types de pointage: check-in (arrivée) ou check-out (départ)</li>
                                </ul>
                            </div>
                            <div>
                                <strong>Format JSON:</strong> Voir <a href="#" onclick="downloadJsonTemplate(); return false;">modèle</a>
                            </div>
                        </div>
                        <div class="mt-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="downloadCsvTemplate()">
                                <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="downloadJsonTemplate()">
                                <i class="bi bi-file-earmark-code"></i> JSON
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection 

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
<style>
    .map-container {
        height: 300px;
        border-radius: 4px;
        overflow: hidden;
    }
    .location-map {
        height: 100%;
        width: 100%;
    }
    .map-popup {
        font-size: 14px;
    }
    /* Styles pour les marqueurs personnalisés */
    .custom-marker {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .marker-photo {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 0 5px rgba(0,0,0,0.3);
    }
    .marker-initials {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #3498db;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
        border: 2px solid #fff;
        box-shadow: 0 0 5px rgba(0,0,0,0.3);
    }
    .marker-badge {
        position: absolute;
        bottom: -5px;
        right: -5px;
        background-color: #e74c3c;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        border: 1px solid white;
    }
    .marker-badge.arrival {
        background-color: #2ecc71;
    }
    .marker-badge.departure {
        background-color: #e74c3c;
    }
</style>
@endpush

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>
     
<script>
    // Code existant pour les téléchargements de modèles
    function downloadJsonTemplate() {
        const jsonTemplate = [
            {
                "employee_id": 2,
                "timestamp": "2025-05-15T08:01:23",
                "type": "check-in",
                "location": {
                    "latitude": 3.8667,
                    "longitude": 11.5167,
                    "accuracy": 10
                },
                "biometric_verification": {
                    "hash": "abc123",
                    "confidence_score": 0.95
                },
                "device_info": {
                    "device_id": "mobile-app-001"
                }
            },
            {
                "employee_id": 2,
                "timestamp": "2025-05-15T17:02:45",
                "type": "check-out",
                "location": {
                    "latitude": 3.8669,
                    "longitude": 11.5170,
                    "accuracy": 15
                },
                "biometric_verification": {
                    "hash": "def456",
                    "confidence_score": 0.92
                },
                "device_info": {
                    "device_id": "mobile-app-001"
                }
            }
        ];
        
        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(jsonTemplate, null, 2));
        const downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", "biometric_template.json");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    }
    
    function downloadCsvTemplate() {
        const csvContent = `employee_id,timestamp,type,latitude,longitude,accuracy,biometric_score,device_id
2,2025-05-15T08:01:23,check-in,3.8667,11.5167,10,0.95,mobile-app-001
2,2025-05-15T17:02:45,check-out,3.8669,11.5170,15,0.92,mobile-app-001
3,2025-05-15T08:10:05,check-in,4.0511,9.7679,8,0.97,mobile-app-002
3,2025-05-15T17:15:30,check-out,4.0513,9.7682,12,0.94,mobile-app-002`;
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", url);
        downloadAnchorNode.setAttribute("download", "biometric_template.csv");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    }

    // Vérifier s'il y a des messages flash à afficher et ouvrir le modal si nécessaire
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('error') && session('form_modal') === 'import')
            var importModal = new bootstrap.Modal(document.getElementById('importModal'));
            importModal.show();
        @endif

        // Gérer l'affichage/masquage de l'aide sur les formats
        document.getElementById('toggleFormatHelp').addEventListener('click', function() {
            const formatHelp = document.getElementById('formatHelp');
            formatHelp.classList.toggle('d-none');
        });
        
        // Initialiser toutes les cartes Leaflet
        document.querySelectorAll('[data-leaflet-map]').forEach(function(element) {
            const mapId = element.id;
            const lat = parseFloat(element.getAttribute('data-lat'));
            const lng = parseFloat(element.getAttribute('data-lng'));
            const title = element.getAttribute('data-title') || 'Localisation';
            const employePhoto = element.getAttribute('data-photo');
            const employeInitials = element.getAttribute('data-initials');
            const pointageType = element.getAttribute('data-type'); // 'arrival' ou 'departure'
            
            if (isNaN(lat) || isNaN(lng)) {
                element.innerHTML = '<div class="alert alert-warning">Coordonnées invalides</div>';
                return;
            }
            
            // Créer la carte
            const map = L.map(mapId).setView([lat, lng], 16);
            
            // Ajouter la couche OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Créer une icône personnalisée avec la photo ou les initiales
            const customIcon = L.divIcon({
                className: 'custom-marker',
                html: createMarkerHtml(employePhoto, employeInitials, pointageType),
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            });
            
            // Ajouter un marqueur avec l'icône personnalisée
            const marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);
            marker.bindPopup(`<div class="map-popup"><strong>${title}</strong><br>Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)}</div>`).openPopup();
            
            // Ajouter un cercle pour montrer la précision (si disponible)
            const accuracy = parseFloat(element.getAttribute('data-accuracy'));
            if (!isNaN(accuracy) && accuracy > 0) {
                L.circle([lat, lng], {
                    color: pointageType === 'departure' ? '#e74c3c' : '#2ecc71',
                    fillColor: pointageType === 'departure' ? '#e74c3c' : '#2ecc71',
                    fillOpacity: 0.1,
                    radius: accuracy
                }).addTo(map);
            }
        });
    });

    /**
     * Crée le HTML pour le marqueur personnalisé
     */
    function createMarkerHtml(photoUrl, initials, type) {
        const typeLabel = type === 'departure' ? 'D' : 'A';
        const typeCls = type === 'departure' ? 'departure' : 'arrival';
        
        if (photoUrl) {
            return `
                <div class="custom-marker">
                    <img src="${photoUrl}" alt="Photo employé" class="marker-photo">
                    <span class="marker-badge ${typeCls}">${typeLabel}</span>
                </div>`;
        } else {
            return `
                <div class="custom-marker">
                    <div class="marker-initials">${initials || '?'}</div>
                    <span class="marker-badge ${typeCls}">${typeLabel}</span>
                </div>`;
        }
    }

    /**
     * Vérifier le contenu du fichier avant l'importation
     */
    function verifyFile() {
        const fileInput = document.getElementById('file');
        const formatSelect = document.getElementById('format');
        const resultsDiv = document.getElementById('verificationResults');
        const contentDiv = document.getElementById('verificationContent');
        const verifyBtn = document.getElementById('verifyFileBtn');
        
        // Réinitialiser et afficher la zone de résultats
        contentDiv.innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split text-info"></i> Vérification...</div>';
        resultsDiv.classList.remove('d-none');
        verifyBtn.disabled = true;
        
        // Vérifier que les champs requis sont remplis
        if (!fileInput.files[0] || !formatSelect.value) {
            contentDiv.innerHTML = '<div class="alert alert-danger py-1 mb-0">Veuillez sélectionner un fichier et un format.</div>';
            verifyBtn.disabled = false;
            return;
        }
        
        // Afficher des informations sur le fichier pour le débogage
        const fileDetails = `<div class="small text-muted mb-1">Taille du fichier: ${(fileInput.files[0].size / 1024).toFixed(2)} KB, Type: ${fileInput.files[0].type}</div>`;
        
        // Si c'est un JSON, vérifier le contenu localement d'abord
        if (formatSelect.value === 'json') {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const jsonContent = JSON.parse(e.target.result);
                    if (!Array.isArray(jsonContent)) {
                        contentDiv.innerHTML = `${fileDetails}<div class="alert alert-danger py-1 mb-0">Le fichier JSON doit contenir un tableau d'objets.</div>`;
                        verifyBtn.disabled = false;
                        return;
                    }
                    if (jsonContent.length > 0) {
                        // Afficher un petit échantillon pour validation manuelle
                        const sampleJson = `<div class="small text-muted mb-1">Vérification de ${jsonContent.length} enregistrements. Premier élément :</div>
                        <pre class="small bg-light p-1">${JSON.stringify(jsonContent[0], null, 2)}</pre>`;
                        contentDiv.innerHTML = fileDetails + sampleJson + '<div class="text-center mt-2"><i class="bi bi-hourglass-split text-info"></i> Envoi au serveur pour validation...</div>';
                    }
                } catch (error) {
                    contentDiv.innerHTML = `${fileDetails}<div class="alert alert-danger py-1 mb-0">Erreur lors de l'analyse JSON: ${error.message}</div>`;
                    verifyBtn.disabled = false;
                    return;
                }
                
                // Continuer avec la vérification serveur
                sendVerificationRequest();
            };
            reader.readAsText(fileInput.files[0]);
        } else {
            // Pour CSV, envoyer directement au serveur
            sendVerificationRequest();
        }
        
        function sendVerificationRequest() {
            // Créer un FormData pour l'envoi
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('format', formatSelect.value);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            // Envoyer la requête de vérification
            fetch('/presences/verify-biometrique', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                let html = '';
                
                if (data.success) {
                    const isAllValid = data.stats.invalid === 0;
                    const statusClass = isAllValid ? 'success' : 'warning';
                    const statusIcon = isAllValid ? 'check-circle' : 'exclamation-triangle';
                    
                    html = `${fileDetails}<div class="alert alert-${statusClass} py-1 mb-2">
                        <i class="bi bi-${statusIcon}"></i> ${data.stats.valid} valides, ${data.stats.invalid} invalides sur ${data.stats.total}
                    </div>`;
                    
                    // Si des erreurs existent, afficher un échantillon
                    if (!isAllValid && data.records.length > 0) {
                        html += '<div class="small text-muted mb-1">Exemples d\'erreurs :</div>';
                        html += '<ul class="ps-3 mb-0">';
                        
                        // Afficher seulement les entrées invalides
                        data.records.filter(r => !r.valid).slice(0, 3).forEach(record => {
                            html += `<li>${record.error} (ID:${record.employee_id})</li>`;
                        });
                        
                        html += '</ul>';
                        
                        // Afficher les premières données pour débogage
                        html += '<div class="small text-muted mt-2">Détails du premier enregistrement:</div>';
                        html += '<pre class="small bg-light p-1">' + JSON.stringify(data.records[0], null, 2) + '</pre>';
                    } else if (isAllValid && data.records.length > 0) {
                        // Montrer un exemple de record valide aussi
                        html += '<div class="small text-muted mt-2">Exemple d\'enregistrement valide:</div>';
                        html += '<pre class="small bg-light p-1">' + JSON.stringify(data.records[0], null, 2) + '</pre>';
                    }
                } else {
                    html = `${fileDetails}<div class="alert alert-danger py-1 mb-0">${data.message}</div>`;
                }
                
                contentDiv.innerHTML = html;
            })
            .catch(error => {
                contentDiv.innerHTML = `<div class="alert alert-danger py-1 mb-0">Erreur: ${error.message}</div>`;
            })
            .finally(() => {
                verifyBtn.disabled = false;
            });
        }
    }
</script>
@endpush 