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

<!-- Modal d'importation de données biométriques -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="importModalLabel"><i class="bi bi-upload me-2"></i> Importer des données biométriques</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('presences.importBiometrique') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label">Fichier à importer</label>
                        <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="format" class="form-label">Format du fichier</label>
                        <select class="form-select @error('format') is-invalid @enderror" id="format" name="format" required>
                            <option value="">Sélectionner un format</option>
                            <option value="json">JSON</option>
                            <option value="csv">CSV</option>
                        </select>
                        @error('format')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="skip_existing" name="skip_existing">
                        <label class="form-check-label" for="skip_existing">Ignorer les pointages existants</label>
                        <div class="form-text">Si cette option est cochée, les pointages existants ne seront pas mis à jour.</div>
                    </div>

                    <!-- Zone de résultats de vérification -->
                    <div id="verificationResults" class="mb-3 d-none">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <i class="bi bi-info-circle me-2"></i> Résultats de vérification
                            </div>
                            <div class="card-body" id="verificationContent">
                                <!-- Les résultats de vérification seront insérés ici -->
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light mb-3">
                        <div class="card-header">Formats acceptés</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Format JSON</h6>
                                    <pre class="small bg-white p-2 rounded">
{
  "employee_id": 1,
  "timestamp": "2025-05-15T08:01:23",
  "type": "check-in",
  "location": {
    "latitude": 45.5017,
    "longitude": -73.5673
  },
  "biometric_score": 0.95
}</pre>
                                </div>
                                <div class="col-md-6">
                                    <h6>Format CSV</h6>
                                    <p class="small">Colonnes requises :</p>
                                    <ul class="small">
                                        <li>employee_id</li>
                                        <li>timestamp</li>
                                        <li>type (check-in/check-out)</li>
                                        <li>latitude</li>
                                        <li>longitude</li>
                                        <li>biometric_score</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="downloadJsonTemplate()">
                                        <i class="bi bi-file-earmark-code me-1"></i> Télécharger modèle JSON
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="downloadCsvTemplate()">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Télécharger modèle CSV
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-info me-2" id="verifyFileBtn" onclick="verifyFile()">
                            <i class="bi bi-check2-circle me-2"></i> Vérifier le fichier
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-arrow-up me-2"></i> Importer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection 

@push('scripts')
<script>
    function downloadJsonTemplate() {
        const jsonTemplate = [
            {
                "employee_id": 1,
                "timestamp": "2025-05-15T08:01:23",
                "type": "check-in",
                "location": {
                    "latitude": 45.5017,
                    "longitude": -73.5673,
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
                "employee_id": 1,
                "timestamp": "2025-05-15T17:02:45",
                "type": "check-out",
                "location": {
                    "latitude": 45.5018,
                    "longitude": -73.5670,
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
1,2025-05-15T08:01:23,check-in,45.5017,-73.5673,10,0.95,mobile-app-001
1,2025-05-15T17:02:45,check-out,45.5018,-73.5670,15,0.92,mobile-app-001
2,2025-05-15T08:10:05,check-in,45.5020,-73.5680,8,0.97,mobile-app-002
2,2025-05-15T17:15:30,check-out,45.5022,-73.5678,12,0.94,mobile-app-002`;
        
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
    });

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
        contentDiv.innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split fs-4 text-info"></i><p>Vérification en cours...</p></div>';
        resultsDiv.classList.remove('d-none');
        verifyBtn.disabled = true;
        
        // Vérifier que les champs requis sont remplis
        if (!fileInput.files[0] || !formatSelect.value) {
            contentDiv.innerHTML = '<div class="alert alert-danger mb-0">Veuillez sélectionner un fichier et un format.</div>';
            verifyBtn.disabled = false;
            return;
        }
        
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
                html += `<div class="alert alert-success mb-3">Le fichier est valide et contient ${data.stats.total} enregistrements.</div>`;
                
                // Détails des enregistrements
                html += '<div class="table-responsive"><table class="table table-sm table-striped">';
                html += '<thead><tr><th>N°</th><th>Employé</th><th>Date/Heure</th><th>Type</th><th>Valide</th></tr></thead><tbody>';
                
                data.records.forEach((record, index) => {
                    const badgeClass = record.valid ? 'bg-success' : 'bg-danger';
                    const badgeIcon = record.valid ? 'check-circle' : 'exclamation-circle';
                    const validText = record.valid ? 'Valide' : `Invalide: ${record.error}`;
                    
                    html += `<tr>
                        <td>${index + 1}</td>
                        <td>${record.employee_id}</td>
                        <td>${record.timestamp}</td>
                        <td>${record.type}</td>
                        <td><span class="badge ${badgeClass}"><i class="bi bi-${badgeIcon}"></i> ${validText}</span></td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
                
                // Résumé des statistiques
                html += `<div class="alert alert-info">
                    <strong>Résumé :</strong> ${data.stats.valid} valides, ${data.stats.invalid} invalides
                </div>`;
                
                if (data.stats.invalid > 0) {
                    html += '<div class="alert alert-warning">Corrigez les erreurs dans votre fichier avant de procéder à l\'importation.</div>';
                } else {
                    html += '<div class="alert alert-success">Vous pouvez procéder à l\'importation.</div>';
                }
            } else {
                html = `<div class="alert alert-danger">${data.message}</div>`;
            }
            
            contentDiv.innerHTML = html;
        })
        .catch(error => {
            contentDiv.innerHTML = `<div class="alert alert-danger">Erreur lors de la vérification : ${error.message}</div>`;
        })
        .finally(() => {
            verifyBtn.disabled = false;
        });
    }
</script>
@endpush 