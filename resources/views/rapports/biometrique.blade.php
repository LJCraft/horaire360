@extends('layouts.app')

@section('title', 'Rapport des Pointages Biométriques')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold text-primary"><i class="bi bi-fingerprint me-2"></i>Rapport des Pointages Biométriques</h1>
            <p class="text-muted">Analyse détaillée des pointages effectués via le système biométrique</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group shadow-sm">
                <a href="{{ route('rapports.export-options', ['type' => 'biometrique']) }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-pdf"></i> PDF
                </a>
                <a href="{{ route('rapports.export-options', ['type' => 'biometrique', 'format' => 'excel']) }}" class="btn btn-outline-success">
                    <i class="bi bi-file-excel"></i> Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Alertes et notifications -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    @endif

    @if(session('info'))
    <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-x-circle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    @endif

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class="bi bi-fingerprint text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Total des journées</h6>
                            <h2 class="mb-0 fw-bold">{{ $pointages->total() }}</h2>
                            <small class="text-muted">Pointages regroupés</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Journées complètes</h6>
                            <h2 class="mb-0 fw-bold">{{ $totalPointagesArriveeDepart }}</h2>
                            <small class="text-muted">Arrivée ET départ</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                            <i class="bi bi-people text-info fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Employés concernés</h6>
                            <h2 class="mb-0 fw-bold">{{ $totalEmployesConcernés }}</h2>
                            <small class="text-muted">Via terminal mobile</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Résultats d'importation -->
    @if(isset($importStats))
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0 text-primary"><i class="bi bi-upload me-2"></i>Résultats de la dernière importation</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="border rounded p-3 text-center">
                        <h6 class="text-muted">Total traité</h6>
                        <h3 class="mb-0">{{ $importStats['total'] ?? 0 }}</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 text-center">
                        <h6 class="text-success">Importés</h6>
                        <h3 class="mb-0 text-success">{{ $importStats['imported'] ?? 0 }}</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 text-center">
                        <h6 class="text-info">Ignorés</h6>
                        <h3 class="mb-0 text-info">{{ $importStats['skipped'] ?? 0 }}</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 text-center">
                        <h6 class="text-danger">Erreurs</h6>
                        <h3 class="mb-0 text-danger">{{ $importStats['errors'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row mb-4">
        <!-- Filtres -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-funnel me-2"></i>Filtres</h5>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filtresCollapse" aria-expanded="true" aria-controls="filtresCollapse">
                        <i class="bi bi-sliders"></i> Options
                    </button>
                </div>
                <div class="collapse show" id="filtresCollapse">
                    <div class="card-body">
                        <form action="{{ route('rapports.biometrique') }}" method="GET" id="filtreForm">
                            <div class="mb-3">
                                <label for="date_debut" class="form-label">Date de début</label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ $dateDebut->format('Y-m-d') }}">
                            </div>
                            <div class="mb-3">
                                <label for="date_fin" class="form-label">Date de fin</label>
                                <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ $dateFin->format('Y-m-d') }}">
                            </div>
                            <div class="mb-3">
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
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Appliquer les filtres
                                </button>
                                <a href="{{ route('rapports.biometrique') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Réinitialiser
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Importation -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-upload me-2"></i>Importer des données biométriques (.dat)</h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="modal" data-bs-target="#formatInfoModal">
                            <i class="bi bi-info-circle"></i> Format
                        </button>
                        <a href="{{ route('presences.downloadDatTemplate') }}" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-download"></i> Modèle
                        </a>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#importCollapse" aria-expanded="true" aria-controls="importCollapse">
                        <i class="bi bi-upload"></i> Importer
                    </button>
                    </div>
                </div>
                <div class="collapse show" id="importCollapse">
                    <div class="card-body">
                        <form action="{{ route('presences.importBiometrique') }}" method="POST" enctype="multipart/form-data" id="importForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="fichier_biometrique" class="form-label">Fichier de données biométriques</label>
                                        <input type="file" class="form-control" id="fichier_biometrique" name="fichier_biometrique" accept=".dat,.txt" required>
                                        <div class="form-text">Format accepté: <strong>.dat</strong> uniquement</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="skip_existing" class="form-label">Options d'importation</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1" id="skip_existing" name="skip_existing">
                                            <label class="form-check-label" for="skip_existing">
                                                Ignorer les pointages existants
                                            </label>
                                            <div class="form-text text-warning">
                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                Si coché, les pointages pour des dates déjà existantes seront ignorés
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-upload"></i> Importer les données
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Synchronisation -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-success"><i class="bi bi-arrow-repeat me-2"></i>Synchronisation Mobile</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-phone text-success" style="font-size: 2rem;"></i>
                    </div>
                    <p class="text-muted small mb-3">Synchroniser les pointages depuis l'application mobile</p>
                    
                    <div class="mb-2">
                        <button class="btn btn-sm btn-outline-info w-100" type="button" data-bs-toggle="modal" data-bs-target="#syncInfoModal">
                            <i class="bi bi-info-circle me-1"></i>Comment ça marche ?
                        </button>
                    </div>
                    
                    <button id="syncBtn" class="btn btn-success w-100" onclick="synchroniserMobile()">
                        <i class="bi bi-arrow-repeat me-1"></i>
                        <span id="syncBtnText">Synchroniser</span>
                    </button>
                    
                    <!-- Statut de synchronisation -->
                    <div id="syncStatus" class="mt-3 d-none">
                        <div class="spinner-border spinner-border-sm text-success me-1" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <small class="text-muted">Synchronisation en cours...</small>
                    </div>
                    
                    <!-- Résultat -->
                    <div id="syncResult" class="mt-3 d-none">
                        <div class="alert alert-sm mb-0" id="syncAlert">
                            <div id="syncMessage"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des pointages -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary"><i class="bi bi-table me-2"></i>Pointages par journée (.dat)</h5>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary rounded-pill me-3">{{ $pointages->total() }} journées</span>
                <small class="text-muted">Regroupé par employé/date</small>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>ID</th>
                            <th>Employé</th>
                            <th>Date</th>
                            <th>Arrivée</th>
                            <th>Départ</th>
                            <th>Terminal</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pointages as $pointage)
                        @php
                            $metaData = json_decode($pointage->meta_data, true);
                        @endphp
                        <tr>
                            <td>
                                <span class="badge bg-primary">{{ $pointage->employe->id }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($pointage->employe->photo)
                                    <img src="{{ asset('storage/' . $pointage->employe->photo) }}" alt="Photo" class="rounded-circle me-2" width="32" height="32">
                                    @else
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person text-secondary"></i>
                                    </div>
                                    @endif
                                    <div>
                                        <div class="fw-medium">{{ $pointage->employe->prenom }} {{ $pointage->employe->nom }}</div>
                                        <small class="text-muted">{{ $pointage->employe->poste->nom ?? 'Sans poste' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($pointage->date)->format('d/m/Y') }}</td>
                            <td>
                                @if($pointage->heure_arrivee)
                                    @php
                                        // Extraire l'heure d'arrivée
                                        $heureArrivee = $pointage->heure_arrivee;
                                        if (preg_match('/(\d{2}:\d{2}:\d{2})/', $heureArrivee, $matches)) {
                                            $heureArrivee = substr($matches[1], 0, 5); // HH:MM
                                        } else {
                                            $heureArrivee = substr($heureArrivee, 0, 5);
                                        }
                                    @endphp
                                <span class="badge bg-{{ $pointage->retard ? 'danger' : 'success' }} rounded-pill">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>{{ $heureArrivee }}
                                </span>
                                @else
                                    <span class="badge bg-secondary rounded-pill">Non défini</span>
                                @endif
                            </td>
                            <td>
                                @if($pointage->heure_depart)
                                    @php
                                        // Extraire l'heure de départ
                                        $heureDepart = $pointage->heure_depart;
                                        if (preg_match('/(\d{2}:\d{2}:\d{2})/', $heureDepart, $matches)) {
                                            $heureDepart = substr($matches[1], 0, 5); // HH:MM
                                        } else {
                                            $heureDepart = substr($heureDepart, 0, 5);
                                        }
                                    @endphp
                                <span class="badge bg-{{ $pointage->depart_anticipe ? 'warning' : 'info' }} rounded-pill">
                                        <i class="bi bi-box-arrow-right me-1"></i>{{ $heureDepart }}
                                </span>
                                @else
                                    <span class="badge bg-secondary rounded-pill">En cours</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-center">
                                    @php
                                        $metaDataTable = json_decode($pointage->meta_data, true);
                                    @endphp
                                    <div class="d-flex align-items-center justify-content-center">
                                        <span class="badge bg-info">Terminal 1</span>
                                        @if(isset($metaDataTable['geolocation']))
                                            <i class="bi bi-geo-alt-fill text-success ms-1" title="Position GPS disponible"></i>
                                    @endif
                                    </div>
                                    @php
                                        $source = $pointage->source_pointage ?? 'manuel';
                                        $sourceLabels = [
                                            'manuel' => ['label' => 'Saisie manuelle', 'class' => 'secondary'],
                                            'biometrique' => ['label' => 'Import .dat', 'class' => 'primary'],
                                            'synchronisation' => ['label' => 'Sync mobile', 'class' => 'success'],
                                        ];
                                        $sourceInfo = $sourceLabels[$source] ?? ['label' => ucfirst($source), 'class' => 'secondary'];
                                    @endphp
                                    <small class="badge bg-{{ $sourceInfo['class'] }} rounded-pill">{{ $sourceInfo['label'] }}</small>
                                </div>
                            </td>
                            <td>
                                @php
                                    // Vérifier s'il y a un planning défini pour cet employé à cette date
                                    $date = \Carbon\Carbon::parse($pointage->date);
                                    $jourSemaine = $date->dayOfWeekIso;
                                    
                                    $planning = \App\Models\Planning::where('employe_id', $pointage->employe_id)
                                        ->where('date_debut', '<=', $pointage->date)
                                        ->where('date_fin', '>=', $pointage->date)
                                        ->where('actif', true)
                                        ->first();
                                    
                                    $planningDetail = null;
                                    if ($planning) {
                                        $planningDetail = $planning->details()
                                            ->where('jour', $jourSemaine)
                                            ->where('jour_repos', false)
                                            ->first();
                                    }
                                    
                                    $aPlanningDefini = $planning && $planningDetail;
                                @endphp
                                
                                @if($aPlanningDefini)
                                @if($pointage->retard && $pointage->depart_anticipe)
                                <span class="badge bg-danger">Retard + Départ anticipé</span>
                                @elseif($pointage->retard)
                                <span class="badge bg-warning text-dark">Retard</span>
                                @elseif($pointage->depart_anticipe)
                                <span class="badge bg-warning text-dark">Départ anticipé</span>
                                @else
                                <span class="badge bg-success">Conforme</span>
                                    @endif
                                @else
                                    <span class="badge bg-secondary">Planning non défini</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('presences.edit', $pointage->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#detailsModal{{ $pointage->id }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-calendar-x text-muted fs-1 mb-2"></i>
                                    <h5 class="text-muted">Aucune journée de pointage trouvée</h5>
                                    <p class="text-muted">Importez un fichier .dat pour voir les journées de travail</p>
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
                    Affichage de {{ $pointages->firstItem() ?? 0 }} à {{ $pointages->lastItem() ?? 0 }} sur {{ $pointages->total() }} pointages
                </div>
                <div>
                    {{ $pointages->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals pour les détails -->
@foreach($pointages as $pointage)
<div class="modal fade" id="detailsModal{{ $pointage->id }}" tabindex="-1" aria-labelledby="detailsModalLabel{{ $pointage->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel{{ $pointage->id }}">
                    Détails du pointage biométrique - Employé #{{ $pointage->employe->id }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-primary">Informations générales</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Employé</th>
                                <td>{{ $pointage->employe->prenom }} {{ $pointage->employe->nom }}</td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td>{{ \Carbon\Carbon::parse($pointage->date)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Heure d'arrivée</th>
                                <td>{{ $pointage->heure_arrivee }}</td>
                            </tr>
                            <tr>
                                <th>Heure de départ</th>
                                <td>{{ $pointage->heure_depart ?? 'Non défini' }}</td>
                            </tr>
                            <tr>
                                <th>Retard</th>
                                <td>
                                    @php
                                        // Vérifier s'il y a un planning défini pour cet employé à cette date
                                        $date = \Carbon\Carbon::parse($pointage->date);
                                        $jourSemaine = $date->dayOfWeekIso;
                                        
                                        $planning = \App\Models\Planning::where('employe_id', $pointage->employe_id)
                                            ->where('date_debut', '<=', $pointage->date)
                                            ->where('date_fin', '>=', $pointage->date)
                                            ->where('actif', true)
                                            ->first();
                                        
                                        $planningDetail = null;
                                        if ($planning) {
                                            $planningDetail = $planning->details()
                                                ->where('jour', $jourSemaine)
                                                ->where('jour_repos', false)
                                                ->first();
                                        }
                                        
                                        $aPlanningDefini = $planning && $planningDetail;
                                    @endphp
                                    
                                    @if($aPlanningDefini)
                                        {{ $pointage->retard ? 'Oui' : 'Non' }}
                                    @else
                                        <span class="text-muted">Planning non défini</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Départ anticipé</th>
                                <td>
                                    @if($aPlanningDefini)
                                        {{ $pointage->depart_anticipe ? 'Oui' : 'Non' }}
                                    @else
                                        <span class="text-muted">Planning non défini</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">Données biométriques (.dat)</h6>
                        <div class="bg-light p-3 rounded">
                            @php
                                $metaData = json_decode($pointage->meta_data, true);
                            @endphp
                            <table class="table table-sm mb-0">
                                <tr>
                                    <th style="width: 40%;">ID Employé</th>
                                    <td><span class="badge bg-primary">{{ $pointage->employe->id }}</span></td>
                                </tr>
                                <tr>
                                    <th>Nom complet</th>
                                    <td>{{ $pointage->employe->prenom }} {{ $pointage->employe->nom }}</td>
                                </tr>
                                <tr>
                                    <th>Type pointage</th>
                                    <td>
                                        @if(isset($metaData['type_pointage']))
                                            <span class="badge bg-{{ $metaData['type_pointage'] == 1 ? 'success' : 'info' }}">
                                                {{ $metaData['type_pointage'] == 1 ? 'Entrée (1)' : 'Sortie (0)' }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Non défini</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Terminal</th>
                                    <td>
                                        <span class="badge bg-info">Terminal 1</span>
                                        <br><small class="text-muted">Reconnaissance faciale mobile</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Source</th>
                                    <td>
                                        @php
                                            $source = $pointage->source_pointage ?? 'manuel';
                                            $sourceDisplay = [
                                                'manuel' => ['icon' => 'bi-pencil', 'text' => 'Saisie manuelle', 'class' => 'secondary'],
                                                'import_dat' => ['icon' => 'bi-file-earmark-text', 'text' => 'Fichier .dat importé', 'class' => 'primary'],
                                                'synchronisation' => ['icon' => 'bi-phone', 'text' => 'Synchronisation mobile', 'class' => 'success'],
                                                'biometrique' => ['icon' => 'bi-file-earmark-text', 'text' => 'Import .dat', 'class' => 'primary']
                                            ];
                                            $display = $sourceDisplay[$source] ?? ['icon' => 'bi-question', 'text' => ucfirst($source), 'class' => 'secondary'];
                                        @endphp
                                        <i class="{{ $display['icon'] }} text-{{ $display['class'] }} me-1"></i>
                                        {{ $display['text'] }}
                                        
                                        @if($source === 'synchronisation' && isset($metaData['sync_session']))
                                            <br><small class="text-muted">Session: {{ substr($metaData['sync_session'], -8) }}</small>
                                        @endif
                                        
                                        @if($source === 'synchronisation' && isset($metaData['source_app']))
                                            <br><small class="text-muted">App: {{ $metaData['source_app'] }}</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Authentification</th>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Validée à 100%
                                        </span>
                                    </td>
                                </tr>
                                                                @if(isset($metaData['geolocation']))
                                <tr>
                                    <th>Position GPS</th>
                                    <td>
                                        <div class="d-flex align-items-start">
                                            <i class="bi bi-geo-alt text-primary me-2 mt-1"></i>
                                            <div class="flex-grow-1">
                                                <div class="small mb-2">
                                                    <strong>Latitude:</strong> {{ number_format($metaData['geolocation']['latitude'], 6) }}<br>
                                                    <strong>Longitude:</strong> {{ number_format($metaData['geolocation']['longitude'], 6) }}
                                                    @if(isset($metaData['geolocation']['accuracy']))
                                                    <br><span class="text-muted">Précision: ±{{ $metaData['geolocation']['accuracy'] }}m</span>
                                                    @endif
                                                    @if(isset($metaData['geolocation']['altitude']))
                                                    <br><span class="text-muted">Altitude: {{ $metaData['geolocation']['altitude'] }}m</span>
                                                    @endif
                                                </div>
                                                
                                                <!-- Carte Leaflet -->
                                                <div id="map-{{ $pointage->id }}" style="height: 200px; width: 100%; border-radius: 8px; border: 1px solid #ddd;" class="mb-2"></div>
                                                
                                                <div class="d-flex gap-2">
                                                    <a href="https://www.google.com/maps?q={{ $metaData['geolocation']['latitude'] }},{{ $metaData['geolocation']['longitude'] }}" 
                                                       target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-google me-1"></i>Google Maps
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-success" onclick="centerMap{{ $pointage->id }}()">
                                                        <i class="bi bi-geo-alt-fill me-1"></i>Centrer
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <th>Ligne originale</th>
                                    <td>
                                        @php
                                            // Reconstituer la ligne .dat originale correctement
                                            $employeId = $pointage->employe->id;
                                            $date = $pointage->date;
                                            $heure = $pointage->heure_arrivee ?? $pointage->heure_depart;
                                            $typePointage = isset($metaData['type_pointage']) ? $metaData['type_pointage'] : 
                                                           (!empty($pointage->heure_arrivee) ? '1' : '0');
                                            $terminalId = '1';
                                            
                                            $ligneOriginale = $employeId . '  ' . $date . '  ' . $heure . '  ' . $typePointage . '  ' . $terminalId;
                                        @endphp
                                        <code style="font-size: 0.85em; color: #d63384;">
                                            {{ $ligneOriginale }}
                                        </code>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary">Commentaire</h6>
                        <p>{{ $pointage->commentaire ?? 'Aucun commentaire' }}</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="{{ route('presences.edit', $pointage->id) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
            </div>
        </div>
    </div>
</div>
@endforeach

<!-- Modal d'information sur le format .dat -->
<div class="modal fade" id="formatInfoModal" tabindex="-1" aria-labelledby="formatInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formatInfoModalLabel">
                    <i class="bi bi-info-circle text-info me-2"></i>Format du fichier .dat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <h6><i class="bi bi-file-text me-2"></i>Format officiel attendu</h6>
                    <p>Le fichier est <strong>sans en-tête</strong>, avec colonnes séparées par des <strong>espaces multiples</strong> :</p>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Position</th>
                                <th>Nom</th>
                                <th>Format / Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>ID_Employe</td>
                                <td>Numérique – doit exister dans la base RH</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Date</td>
                                <td>YYYY-MM-DD</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Heure</td>
                                <td>HH:MM:SS</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>Type_Pointage</td>
                                <td>1 = Entrée, 0 = Sortie</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>Terminal_ID</td>
                                <td>1 = App mobile (reconnaissance faciale)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-warning">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Exemple de fichier valide</h6>
                    <pre class="mb-0"><code>1023  2025-06-12  08:01:15  1  1
1023  2025-06-12  17:10:02  0  1
9999  2025-06-12  08:03:45  1  1</code></pre>
                </div>

                <div class="alert alert-danger">
                    <h6><i class="bi bi-x-circle me-2"></i>Comportement en cas d'erreur</h6>
                    <ul class="mb-0">
                        <li>Les lignes avec des erreurs ne sont pas importées</li>
                        <li>Un rapport d'anomalies détaillé est généré</li>
                        <li>L'importation continue pour les lignes valides</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('presences.downloadDatTemplate') }}" class="btn btn-success">
                    <i class="bi bi-download"></i> Télécharger un modèle
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal des anomalies d'importation (affiché seulement s'il y a des anomalies) -->
@if(session('import_anomalies'))
@php
    $anomalies = session('import_anomalies');
    $totalAnomalies = count($anomalies);
    $maxDisplay = 10; // Limiter l'affichage pour optimiser les performances
@endphp
<div class="modal fade show" id="anomaliesModal" tabindex="-1" aria-labelledby="anomaliesModalLabel" style="display: block;" aria-modal="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="anomaliesModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Rapport d'anomalies - Importation en cours
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer" onclick="closeAnomaliesModal()"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="alert alert-warning">
                    <strong>{{ $totalAnomalies }}</strong> anomalie(s) détectée(s) lors de l'importation actuelle.
                    <br><small class="text-muted">
                        <i class="bi bi-clock me-1"></i>Fichier importé le {{ now()->format('d/m/Y à H:i:s') }}
                    </small>
                    <hr class="my-2">
                    Ces lignes n'ont pas été importées :
                </div>

                @if($totalAnomalies > $maxDisplay)
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Affichage des {{ $maxDisplay }} premières anomalies ({{ $totalAnomalies }} au total).
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="toggleAllAnomalies()">
                        <span id="toggleText">Afficher tout</span>
                    </button>
                </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 10%;">Ligne</th>
                                <th style="width: 50%;">Contenu brut</th>
                                <th style="width: 40%;">Erreur</th>
                            </tr>
                        </thead>
                        <tbody id="anomaliesTableBody">
                            @foreach($anomalies as $index => $anomalie)
                            <tr class="anomalie-row {{ $index >= $maxDisplay ? 'd-none' : '' }}" data-index="{{ $index }}">
                                <td><span class="badge bg-danger">{{ $anomalie['line_number'] }}</span></td>
                                <td><code style="font-size: 0.85em;">{{ \Illuminate\Support\Str::limit($anomalie['raw_line'], 50) }}</code></td>
                                <td class="text-danger" style="font-size: 0.9em;">{{ $anomalie['error'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-muted small me-auto">
                    <i class="bi bi-lightbulb me-1"></i>
                    Conseil : Utilisez le modèle sans commentaires pour éviter ces erreurs
                </div>
                <form action="{{ route('rapports.biometrique') }}" method="GET" class="me-2" style="display: inline;">
                    <input type="hidden" name="clear_anomalies" value="1">
                    <button type="submit" class="btn btn-outline-warning">
                        <i class="bi bi-trash me-1"></i>Nettoyer
                    </button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeAnomaliesModal()">
                    <i class="bi bi-x-lg me-1"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show" id="anomaliesBackdrop"></div>
@endif

<!-- Modal d'information sur la synchronisation -->
<div class="modal fade" id="syncInfoModal" tabindex="-1" aria-labelledby="syncInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="syncInfoModalLabel">
                    <i class="bi bi-arrow-repeat me-2"></i>Synchronisation Mobile - Guide
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">🎯 Qu'est-ce que c'est ?</h6>
                        <p class="small">
                            La synchronisation mobile permet de récupérer automatiquement les pointages 
                            effectués via l'application mobile de reconnaissance faciale par les employés.
                        </p>
                        
                        <h6 class="text-primary mt-3">🔄 Comment ça fonctionne ?</h6>
                        <ol class="small">
                            <li><strong>Mapping intelligent</strong> : Reconnaît automatiquement différents formats de données</li>
                            <li><strong>Validation stricte</strong> : Vérifie l'existence des employés et la validité des données</li>
                            <li><strong>Géolocalisation</strong> : Capture et stocke la position GPS des pointages mobiles</li>
                            <li><strong>Détection de doublons</strong> : Évite les pointages en double entre sources</li>
                            <li><strong>Calcul automatique</strong> : Applique les règles de planning (retards, heures sup)</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success">✅ Formats acceptés</h6>
                        <div class="bg-light p-2 rounded small">
                            <strong>Format 1:</strong> Avec géolocalisation<br>
                            <code>{"userId": 123, "timestamp": "2025-01-21T08:05:30Z", "type": "entry", "location": {"lat": 48.8566, "lng": 2.3522}}</code><br><br>
                            
                            <strong>Format 2:</strong> Position GPS séparée<br>
                            <code>{"emp_id": "123", "date": "2025-01-21", "hour": "08:05:30", "status": 1, "latitude": 48.8566, "longitude": 2.3522}</code><br><br>
                            
                            <strong>Format 3:</strong> Coordonnées dans gps<br>
                            <code>{"employee_id": 123, "action": "checkin", "gps": {"lat": 48.8566, "lng": 2.3522}}</code>
                        </div>
                        
                        <h6 class="text-warning mt-3">⚠️ Sécurité</h6>
                        <ul class="small">
                            <li>Authentification requise (session web ou token API)</li>
                            <li>Traçabilité complète des synchronisations</li>
                            <li>Protection contre les appels malveillants</li>
                            <li>Logs détaillés de toutes les opérations</li>
                        </ul>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <h6><i class="bi bi-lightbulb me-2"></i>Mode démonstration</h6>
                    <p class="mb-0 small">
                        Le bouton "Synchroniser" utilise actuellement des données de test pour démontrer le fonctionnement. 
                        En production, il se connectera directement à l'API de l'application mobile.
                    </p>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6 class="text-primary">📊 Rapport de synchronisation</h6>
                        <p class="small">
                            Après chaque synchronisation, un rapport détaillé affiche :
                        </p>
                        <div class="row text-center">
                            <div class="col-3">
                                <div class="card border-primary">
                                    <div class="card-body py-2">
                                        <i class="bi bi-inbox text-primary fs-4"></i>
                                        <div class="small"><strong>Reçus</strong></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card border-success">
                                    <div class="card-body py-2">
                                        <i class="bi bi-check-circle text-success fs-4"></i>
                                        <div class="small"><strong>Insérés</strong></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card border-info">
                                    <div class="card-body py-2">
                                        <i class="bi bi-arrow-up-circle text-info fs-4"></i>
                                        <div class="small"><strong>Mis à jour</strong></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card border-warning">
                                    <div class="card-body py-2">
                                        <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                                        <div class="small"><strong>Ignorés</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="synchroniserMobile()">
                    <i class="bi bi-arrow-repeat me-1"></i>Essayer maintenant
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de résultats de synchronisation -->
<div class="modal fade" id="syncResultsModal" tabindex="-1" aria-labelledby="syncResultsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" id="syncModalHeader">
                <h5 class="modal-title" id="syncResultsModalLabel">
                    <i class="bi bi-check-circle text-success me-2"></i>Résultats de la synchronisation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="syncModalBody">
                <!-- Le contenu sera injecté dynamiquement -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="actualiserPage()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Actualiser la page
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Configuration CSRF pour les requêtes AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Fonction de synchronisation mobile
    async function synchroniserMobile() {
        const syncBtn = document.getElementById('syncBtn');
        const syncBtnText = document.getElementById('syncBtnText');
        const syncStatus = document.getElementById('syncStatus');
        const syncResult = document.getElementById('syncResult');
        
        // Désactiver le bouton et afficher le statut de chargement
        syncBtn.disabled = true;
        syncBtnText.textContent = 'Synchronisation...';
        syncStatus.classList.remove('d-none');
        syncResult.classList.add('d-none');
        
        try {
            // Charger les données de test depuis le fichier JSON
            // En production, ces données viendront directement de l'application mobile
            let donneesTest;
            try {
                const testResponse = await fetch('/test_sync_mobile.json');
                donneesTest = await testResponse.json();
            } catch (testError) {
                // Fallback si le fichier n'est pas disponible
                donneesTest = [
                    {"userId": 1, "timestamp": new Date().toISOString(), "type": "entry"},
                    {"userId": 2, "timestamp": new Date().toISOString(), "type": "entry"},
                    {"userId": 1, "timestamp": new Date(Date.now() + 8*60*60*1000).toISOString(), "type": "exit"}
                ];
            }
            
            const response = await fetch('/api/sync/biometric', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    data: donneesTest,
                    source_app: 'mobile_demo',
                    version: '1.0.0'
                })
            });
            
            const result = await response.json();
            
            // Masquer le statut de chargement
            syncStatus.classList.add('d-none');
            
            if (response.ok && result.status === 'success') {
                afficherResultatSync(result, true);
            } else {
                afficherResultatSync(result, false);
            }
            
        } catch (error) {
            console.error('Erreur de synchronisation:', error);
            syncStatus.classList.add('d-none');
            afficherResultatSync({
                message: 'Erreur de connexion au serveur',
                error: error.message
            }, false);
        } finally {
            // Réactiver le bouton
                setTimeout(() => {
                syncBtn.disabled = false;
                syncBtnText.textContent = 'Synchroniser';
            }, 2000);
        }
    }
    
    // Fonction pour afficher les résultats de synchronisation
    function afficherResultatSync(result, success) {
        const syncResult = document.getElementById('syncResult');
        const syncAlert = document.getElementById('syncAlert');
        const syncMessage = document.getElementById('syncMessage');
        
        // Mise à jour des classes d'alerte
        syncAlert.className = success ? 'alert alert-success alert-sm mb-0' : 'alert alert-danger alert-sm mb-0';
        
        if (success) {
            syncMessage.innerHTML = `
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <strong>Synchronisation réussie!</strong>
                </div>
                <small class="text-muted">${result.message}</small>
            `;
            
            // Afficher le modal détaillé
            setTimeout(() => {
                afficherModalResultats(result);
            }, 1000);
        } else {
            syncMessage.innerHTML = `
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                    <strong>Erreur de synchronisation</strong>
                </div>
                <small class="text-muted">${result.message || result.error || 'Erreur inconnue'}</small>
            `;
        }
        
        syncResult.classList.remove('d-none');
        
        // Masquer automatiquement après 5 secondes
        setTimeout(() => {
            syncResult.classList.add('d-none');
        }, 5000);
    }
    
    // Fonction pour afficher le modal détaillé des résultats
    function afficherModalResultats(result) {
        const modalHeader = document.getElementById('syncModalHeader');
        const modalBody = document.getElementById('syncModalBody');
        const modal = new bootstrap.Modal(document.getElementById('syncResultsModal'));
        
        // Mise à jour du header selon le statut
        modalHeader.className = result.status === 'success' ? 'modal-header bg-light' : 'modal-header bg-light';
        
        let contentHtml = `
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card border-0 bg-primary text-white text-center">
                        <div class="card-body py-2">
                            <h4 class="mb-0">${result.received || 0}</h4>
                            <small>Reçus</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-success text-white text-center">
                        <div class="card-body py-2">
                            <h4 class="mb-0">${result.inserted || 0}</h4>
                            <small>Insérés</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-info text-white text-center">
                        <div class="card-body py-2">
                            <h4 class="mb-0">${result.updated || 0}</h4>
                            <small>Mis à jour</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-warning text-white text-center">
                        <div class="card-body py-2">
                            <h4 class="mb-0">${result.ignored || 0}</h4>
                            <small>Ignorés</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <p class="text-muted mb-1">
                    <i class="bi bi-clock me-1"></i>
                    Temps de traitement: ${result.processing_time_ms || 0}ms
                </p>
                <p class="text-muted mb-0">
                    <i class="bi bi-hash me-1"></i>
                    Session: ${result.session_id || 'N/A'}
                </p>
            </div>
        `;
        
        // Afficher les conflits s'il y en a
        if (result.conflicts && result.conflicts.length > 0) {
            contentHtml += `
                <div class="alert alert-warning">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Conflits détectés (${result.conflicts.length})</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Ligne</th>
                                    <th>Raison</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            result.conflicts.slice(0, 5).forEach(conflict => {
                contentHtml += `
                    <tr>
                        <td><span class="badge bg-warning">${conflict.line}</span></td>
                        <td class="small">${conflict.reason}</td>
                    </tr>
                `;
            });
            
            if (result.conflicts.length > 5) {
                contentHtml += `
                    <tr>
                        <td colspan="2" class="text-center text-muted small">
                            ... et ${result.conflicts.length - 5} autre(s) conflit(s)
                        </td>
                    </tr>
                `;
            }
            
            contentHtml += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
        
        // Afficher les erreurs s'il y en a
        if (result.errors && result.errors.length > 0) {
            contentHtml += `
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-circle me-2"></i>Erreurs détectées (${result.errors.length})</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Ligne</th>
                                    <th>Message d'erreur</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            result.errors.slice(0, 5).forEach(error => {
                contentHtml += `
                    <tr>
                        <td><span class="badge bg-danger">${error.line}</span></td>
                        <td class="small text-danger">${error.message}</td>
                    </tr>
                `;
            });
            
            if (result.errors.length > 5) {
                contentHtml += `
                    <tr>
                        <td colspan="2" class="text-center text-muted small">
                            ... et ${result.errors.length - 5} autre(s) erreur(s)
                        </td>
                    </tr>
                `;
            }
            
            contentHtml += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
        
        modalBody.innerHTML = contentHtml;
        modal.show();
    }

    // Fonction optimisée pour fermer le modal des anomalies
    function closeAnomaliesModal() {
        const modal = document.getElementById('anomaliesModal');
        const backdrop = document.getElementById('anomaliesBackdrop');
        
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
        }
        if (backdrop) {
            backdrop.remove();
        }
        
        // Nettoyer les classes sur le body
        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = '';
        document.body.style.overflow = '';
    }
    
    // Fonction pour basculer l'affichage de toutes les anomalies
    function toggleAllAnomalies() {
        const hiddenRows = document.querySelectorAll('.anomalie-row.d-none');
        const toggleText = document.getElementById('toggleText');
        const toggleBtn = toggleText.parentElement;
        
        if (hiddenRows.length > 0) {
            // Afficher toutes les lignes
            hiddenRows.forEach(row => row.classList.remove('d-none'));
            toggleText.textContent = 'Masquer les détails';
            toggleBtn.className = 'btn btn-sm btn-outline-secondary ms-2';
        } else {
            // Masquer les lignes supplémentaires
            const allRows = document.querySelectorAll('.anomalie-row');
            allRows.forEach((row, index) => {
                if (index >= 10) {
                    row.classList.add('d-none');
                }
            });
            toggleText.textContent = 'Afficher tout';
            toggleBtn.className = 'btn btn-sm btn-outline-primary ms-2';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Auto-fermer le modal des anomalies après 45 secondes (augmenté)
        @if(session('import_anomalies'))
        setTimeout(function() {
            closeAnomaliesModal();
        }, 45000);
        @endif
        
        // Ajout du listener pour fermer avec Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('anomaliesModal');
                if (modal && modal.style.display === 'block') {
                    closeAnomaliesModal();
                }
            }
        });
        
        // Rendre les en-têtes de tableau fixes lors du défilement (optimisé)
        const tableHeaders = document.querySelectorAll('.sticky-top');
        if (tableHeaders.length > 0) {
            let ticking = false;
            
            function updateHeaders() {
                tableHeaders.forEach(header => {
                    const rect = header.getBoundingClientRect();
                    if (rect.top <= 0) {
                        header.classList.add('fixed-header');
                    } else {
                        header.classList.remove('fixed-header');
                    }
                });
                ticking = false;
            }
            
            document.addEventListener('scroll', function() {
                if (!ticking) {
                    requestAnimationFrame(updateHeaders);
                    ticking = true;
                }
            });
        }
    });

    // Fonction pour actualiser la page après synchronisation
    function actualiserPage() {
        window.location.reload();
    }

    // Variables globales pour les cartes Leaflet
    const maps = {};
    const markers = {};
    
    // Fonction pour initialiser les cartes Leaflet
    function initLeafletMaps() {
        @foreach($pointages as $pointage)
            @php
                $metaData = json_decode($pointage->meta_data, true);
            @endphp
            @if(isset($metaData['geolocation']))
                @php
                    $lat = $metaData['geolocation']['latitude'];
                    $lng = $metaData['geolocation']['longitude'];
                    $accuracy = isset($metaData['geolocation']['accuracy']) ? $metaData['geolocation']['accuracy'] : null;
                @endphp
                
                // Attendre que le modal soit ouvert avant d'initialiser la carte
                document.getElementById('detailsModal{{ $pointage->id }}').addEventListener('shown.bs.modal', function () {
                    if (!maps['{{ $pointage->id }}']) {
                        // Initialiser la carte
                        const map = L.map('map-{{ $pointage->id }}', {
                            zoomControl: true,
                            attributionControl: true
                        }).setView([{{ $lat }}, {{ $lng }}], 16);
                        
                        // Ajouter le layer de carte (OpenStreetMap)
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors',
                            maxZoom: 19
                        }).addTo(map);
                        
                        // Créer l'icône personnalisée
                        const customIcon = L.divIcon({
                            html: '<i class="bi bi-geo-alt-fill text-danger" style="font-size: 24px;"></i>',
                            className: 'custom-marker',
                            iconSize: [24, 24],
                            iconAnchor: [12, 24]
                        });
                        
                        // Ajouter le marqueur
                        const marker = L.marker([{{ $lat }}, {{ $lng }}], {icon: customIcon}).addTo(map);
                        
                        // Popup avec les informations
                        const popupContent = `
                            <div class="text-center">
                                <strong>{{ $pointage->employe->prenom }} {{ $pointage->employe->nom }}</strong><br>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($pointage->date)->format('d/m/Y') }}</small><br>
                                <span class="badge bg-primary mt-1">Pointage {{ isset($metaData['type_pointage']) && $metaData['type_pointage'] == 1 ? 'Entrée' : 'Sortie' }}</span>
                                @if($accuracy)
                                <br><small class="text-muted">Précision: ±{{ $accuracy }}m</small>
                                @endif
                            </div>
                        `;
                        marker.bindPopup(popupContent).openPopup();
                        
                        @if($accuracy)
                        // Ajouter un cercle de précision si disponible
                        const accuracyCircle = L.circle([{{ $lat }}, {{ $lng }}], {
                            radius: {{ $accuracy }},
                            fillColor: '#007bff',
                            fillOpacity: 0.1,
                            color: '#007bff',
                            weight: 2,
                            opacity: 0.6
                        }).addTo(map);
                        @endif
                        
                        // Stocker les références
                        maps['{{ $pointage->id }}'] = map;
                        markers['{{ $pointage->id }}'] = marker;
                        
                        // Redimensionner la carte après un court délai
                        setTimeout(() => {
                            map.invalidateSize();
                        }, 100);
                    }
                });
            @endif
        @endforeach
    }
    
    // Fonctions pour centrer les cartes
    @foreach($pointages as $pointage)
        @php
            $metaData = json_decode($pointage->meta_data, true);
        @endphp
        @if(isset($metaData['geolocation']))
            function centerMap{{ $pointage->id }}() {
                if (maps['{{ $pointage->id }}']) {
                    maps['{{ $pointage->id }}'].setView([{{ $metaData['geolocation']['latitude'] }}, {{ $metaData['geolocation']['longitude'] }}], 18);
                    if (markers['{{ $pointage->id }}']) {
                        markers['{{ $pointage->id }}'].openPopup();
                    }
                }
            }
        @endif
    @endforeach
    
    // Initialiser les cartes quand le DOM est chargé
    document.addEventListener('DOMContentLoaded', function() {
        initLeafletMaps();
    });
</script>
@endpush

@push('styles')
<style>
    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 1020;
    }
    
    .fixed-header {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    /* Styles pour les tableaux */
    .table th {
        font-weight: 600;
        white-space: nowrap;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    /* Styles pour les cartes Leaflet */
    .custom-marker {
        background: none !important;
        border: none !important;
    }
    
    .leaflet-popup-content-wrapper {
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .leaflet-popup-content {
        margin: 12px 16px;
        font-family: 'Nunito', sans-serif;
    }
    
    .leaflet-control-zoom {
        border: none !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
    }
    
    .leaflet-control-zoom a {
        border-radius: 4px !important;
        border: none !important;
        font-weight: bold !important;
        color: #007bff !important;
    }
    
    .leaflet-control-zoom a:hover {
        background-color: #007bff !important;
        color: white !important;
    }
</style>
@endpush
