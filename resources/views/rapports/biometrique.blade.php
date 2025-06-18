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
                            <h6 class="text-muted mb-1">Total des pointages biométriques</h6>
                            <h2 class="mb-0 fw-bold">{{ $totalPointages }}</h2>
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
                            <i class="bi bi-clock-history text-success fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Pointages complets (arrivée/départ)</h6>
                            <h2 class="mb-0 fw-bold">{{ $totalPointagesArriveeDepart }}</h2>
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
                            <h6 class="text-muted mb-1">Employés avec pointages</h6>
                            <h2 class="mb-0 fw-bold">{{ $totalEmployesConcernés }}</h2>
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
        <div class="col-md-8">
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
                                            <input class="form-check-input" type="checkbox" value="1" id="skip_existing" name="skip_existing" checked>
                                            <label class="form-check-label" for="skip_existing">
                                                Ignorer les pointages existants
                                            </label>
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
    </div>

    <!-- Tableau des pointages -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary"><i class="bi bi-table me-2"></i>Liste des pointages biométriques</h5>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary rounded-pill me-3">{{ $pointages->total() }} pointages</span>
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
                            <td>{{ $pointage->id }}</td>
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
                                <span class="badge bg-{{ $pointage->retard ? 'danger' : 'success' }} rounded-pill">
                                    {{ substr($pointage->heure_arrivee, 0, 5) }}
                                </span>
                            </td>
                            <td>
                                @if($pointage->heure_depart)
                                <span class="badge bg-{{ $pointage->depart_anticipe ? 'warning' : 'info' }} rounded-pill">
                                    {{ substr($pointage->heure_depart, 0, 5) }}
                                </span>
                                @else
                                <span class="badge bg-secondary rounded-pill">Non défini</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-center">
                                    <span class="badge bg-info">Terminal 1</span>
                                    <br>
                                    <small class="text-muted">Facial mobile</small>
                                </div>
                            </td>
                            <td>
                                @if($pointage->retard && $pointage->depart_anticipe)
                                <span class="badge bg-danger">Retard + Départ anticipé</span>
                                @elseif($pointage->retard)
                                <span class="badge bg-warning text-dark">Retard</span>
                                @elseif($pointage->depart_anticipe)
                                <span class="badge bg-warning text-dark">Départ anticipé</span>
                                @else
                                <span class="badge bg-success">Conforme</span>
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
                                    <i class="bi bi-inbox text-muted fs-1 mb-2"></i>
                                    <h5 class="text-muted">Aucun pointage biométrique trouvé</h5>
                                    <p class="text-muted">Utilisez le formulaire d'importation pour ajouter des données biométriques</p>
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
                    Détails du pointage biométrique #{{ $pointage->id }}
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
                                <td>{{ $pointage->retard ? 'Oui' : 'Non' }}</td>
                            </tr>
                            <tr>
                                <th>Départ anticipé</th>
                                <td>{{ $pointage->depart_anticipe ? 'Oui' : 'Non' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">Données biométriques</h6>
                        <div class="bg-light p-3 rounded">
                            <pre class="mb-0" style="max-height: 200px; overflow-y: auto;">{{ json_encode(json_decode($pointage->meta_data), JSON_PRETTY_PRINT) }}</pre>
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
<div class="modal fade show" id="anomaliesModal" tabindex="-1" aria-labelledby="anomaliesModalLabel" style="display: block;" aria-modal="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="anomaliesModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Rapport d'anomalies d'importation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>{{ count(session('import_anomalies')) }}</strong> anomalie(s) détectée(s) lors de l'importation.
                    Ces lignes n'ont pas été importées :
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Ligne</th>
                                <th>Contenu brut</th>
                                <th>Erreur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('import_anomalies') as $anomalie)
                            <tr>
                                <td><span class="badge bg-danger">{{ $anomalie['line_number'] }}</span></td>
                                <td><code>{{ $anomalie['raw_line'] }}</code></td>
                                <td class="text-danger">{{ $anomalie['error'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-fermer le modal des anomalies après 30 secondes
        @if(session('import_anomalies'))
        setTimeout(function() {
            const anomaliesModal = document.getElementById('anomaliesModal');
            if (anomaliesModal) {
                const modalBackdrop = document.querySelector('.modal-backdrop');
                anomaliesModal.style.display = 'none';
                if (modalBackdrop) {
                    modalBackdrop.remove();
                }
            }
                 }, 30000);
        @endif
        
        // Rendre les en-têtes de tableau fixes lors du défilement
        const tableHeaders = document.querySelectorAll('.sticky-top');
        if (tableHeaders.length > 0) {
            document.addEventListener('scroll', function() {
                tableHeaders.forEach(header => {
                    const rect = header.getBoundingClientRect();
                    if (rect.top <= 0) {
                        header.classList.add('fixed-header');
                    } else {
                        header.classList.remove('fixed-header');
                    }
                });
            });
        }
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
</style>
@endpush
