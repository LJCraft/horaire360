@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-between mb-4">
        <div class="col-md-6">
            <h1>Gestion des employés</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('employes.create') }}" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle"></i> Nouvel employé
            </a>
            <div class="btn-group me-2">
                <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-file-earmark-arrow-down"></i> Exporter
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('employes.export') }}"><i class="bi bi-file-excel"></i> Format Excel</a></li>
                    <li><a class="dropdown-item" href="{{ route('employes.export-pdf') }}"><i class="bi bi-file-pdf"></i> Format PDF</a></li>
                    <li><a class="dropdown-item" href="{{ route('employes.export', ['format' => 'csv']) }}"><i class="bi bi-file-text"></i> Format CSV</a></li>
                </ul>
            </div>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-earmark-arrow-up"></i> Importer
            </button>
        </div>
    </div>

    <!-- Messages de notification -->
    @if(session('success') && str_contains(session('success'), 'importé'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>{{ session('success') }}</strong>
            <div class="mt-2">
                <i class="bi bi-info-circle"></i>
                Les nouveaux employés sont marqués en vert dans la liste et affichés sur la première page.
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @elseif(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>{{ session('warning') }}</strong>
            @if(session('import_errors'))
                <button class="btn btn-sm btn-outline-dark ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#importErrorsCollapse" aria-expanded="false" aria-controls="importErrorsCollapse">
                    Voir les détails
                </button>
                <div class="collapse mt-2" id="importErrorsCollapse">
                    <div class="card card-body">
                        <div class="accordion accordion-flush" id="importErrorsAccordion">
                            <!-- Regrouper les erreurs similaires -->
                            @php
                                $errors = session('import_errors');
                                $errorCategories = [
                                    'email' => [],
                                    'matricule' => [],
                                    'date' => [],
                                    'champ' => [],
                                    'autres' => []
                                ];
                                
                                foreach ($errors as $error) {
                                    $errorLower = strtolower($error);
                                    if (strpos($errorLower, 'email') !== false) {
                                        $errorCategories['email'][] = $error;
                                    } elseif (strpos($errorLower, 'matricule') !== false) {
                                        $errorCategories['matricule'][] = $error;
                                    } elseif (strpos($errorLower, 'date') !== false) {
                                        $errorCategories['date'][] = $error;
                                    } elseif (strpos($errorLower, 'obligatoire') !== false || strpos($errorLower, 'manquant') !== false) {
                                        $errorCategories['champ'][] = $error;
                                    } else {
                                        $errorCategories['autres'][] = $error;
                                    }
                                }
                            @endphp
                            
                            <!-- Erreurs liées aux emails -->
                            @if(count($errorCategories['email']) > 0)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#emailErrors">
                                        <i class="bi bi-envelope-exclamation me-2"></i> 
                                        Erreurs d'emails ({{ count($errorCategories['email']) }})
                                    </button>
                                </h2>
                                <div id="emailErrors" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <ul class="mb-0 small">
                                            @foreach($errorCategories['email'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            <!-- Erreurs liées aux matricules -->
                            @if(count($errorCategories['matricule']) > 0)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#matriculeErrors">
                                        <i class="bi bi-upc me-2"></i> 
                                        Erreurs de matricules ({{ count($errorCategories['matricule']) }})
                                    </button>
                                </h2>
                                <div id="matriculeErrors" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <ul class="mb-0 small">
                                            @foreach($errorCategories['matricule'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            <!-- Erreurs liées aux dates -->
                            @if(count($errorCategories['date']) > 0)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dateErrors">
                                        <i class="bi bi-calendar-x me-2"></i> 
                                        Erreurs de dates ({{ count($errorCategories['date']) }})
                                    </button>
                                </h2>
                                <div id="dateErrors" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <ul class="mb-0 small">
                                            @foreach($errorCategories['date'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            <!-- Erreurs liées aux champs obligatoires -->
                            @if(count($errorCategories['champ']) > 0)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#champErrors">
                                        <i class="bi bi-exclamation-diamond me-2"></i> 
                                        Champs obligatoires manquants ({{ count($errorCategories['champ']) }})
                                    </button>
                                </h2>
                                <div id="champErrors" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <ul class="mb-0 small">
                                            @foreach($errorCategories['champ'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            <!-- Autres erreurs -->
                            @if(count($errorCategories['autres']) > 0)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#autresErrors">
                                        <i class="bi bi-question-circle me-2"></i> 
                                        Autres erreurs ({{ count($errorCategories['autres']) }})
                                    </button>
                                </h2>
                                <div id="autresErrors" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <ul class="mb-0 small">
                                            @foreach($errorCategories['autres'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Messages d'erreur -->
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Formulaire de recherche et filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('employes.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Rechercher..." name="search" value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="poste_id" class="form-select">
                        <option value="">Tous les postes</option>
                        @foreach($postes as $poste)
                            <option value="{{ $poste->id }}" {{ request('poste_id') == $poste->id ? 'selected' : '' }}>
                                {{ $poste->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="actif" {{ request('statut') == 'actif' ? 'selected' : '' }}>Actif</option>
                        <option value="inactif" {{ request('statut') == 'inactif' ? 'selected' : '' }}>Inactif</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="acces" class="form-select">
                        <option value="">Tous les accès</option>
                        <option value="avec" {{ request('acces') == 'avec' ? 'selected' : '' }}>Avec accès</option>
                        <option value="sans" {{ request('acces') == 'sans' ? 'selected' : '' }}>Sans accès</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex">
                        <button type="submit" class="btn btn-primary flex-grow-1">Filtrer</button>
                        @if(request()->anyFilled(['search', 'poste_id', 'statut', 'acces']))
                            <a href="{{ route('employes.index') }}" class="btn btn-outline-secondary ms-1" title="Effacer les filtres">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiques de filtrage -->
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-bar-chart-line me-2"></i> Statistiques
            </h5>
            <span class="badge bg-primary">{{ $employes->count() }} sur {{ $stats['filtered'] }} employés affichés</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Résultats de la recherche</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['filtered'] }} employés</div>
                                    <div class="small">
                                        <span class="text-success">{{ $stats['actifs'] }} actifs</span> / 
                                        <span class="text-danger">{{ $stats['inactifs'] }} inactifs</span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-search fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total des employés</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] }} employés</div>
                                    <div class="small">
                                        <span class="text-success">{{ $stats['totalActifs'] }} actifs</span> / 
                                        <span class="text-danger">{{ $stats['totalInactifs'] }} inactifs</span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($stats['poste'])
                <div class="col-md-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        {{ $stats['poste']['nom'] }}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['poste']['total'] }} employés</div>
                                    <div class="small">
                                        <span class="text-success">{{ $stats['poste']['actifs'] }} actifs</span> / 
                                        <span class="text-danger">{{ $stats['poste']['inactifs'] }} inactifs</span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-briefcase fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                <div class="col-md-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Accès au système</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ request('acces') ? ($stats['avecAcces'] + $stats['sansAcces']) : $stats['filtered'] }} employés</div>
                                    <div class="small">
                                        <span class="text-success">{{ $stats['avecAcces'] }} avec accès</span> / 
                                        <span class="text-secondary">{{ $stats['sansAcces'] }} sans accès</span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-shield-lock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des employés -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a href="{{ route('employes.index', ['sort' => 'matricule', 'direction' => request('sort') === 'matricule' && request('direction') === 'asc' ? 'desc' : 'asc'] + request()->except(['sort', 'direction'])) }}">
                                    Matricule
                                    @if(request('sort') === 'matricule')
                                        <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>
                                <a href="{{ route('employes.index', ['sort' => 'nom', 'direction' => request('sort') === 'nom' && request('direction') === 'asc' ? 'desc' : 'asc'] + request()->except(['sort', 'direction'])) }}">
                                    Nom
                                    @if(request('sort') === 'nom')
                                        <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>
                                <a href="{{ route('employes.index', ['sort' => 'email', 'direction' => request('sort') === 'email' && request('direction') === 'asc' ? 'desc' : 'asc'] + request()->except(['sort', 'direction'])) }}">
                                    Email
                                    @if(request('sort') === 'email')
                                        <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Poste</th>
                            <th>
                                <a href="{{ route('employes.index', ['sort' => 'date_embauche', 'direction' => request('sort') === 'date_embauche' && request('direction') === 'asc' ? 'desc' : 'asc'] + request()->except(['sort', 'direction'])) }}">
                                    Date d'embauche
                                    @if(request('sort') === 'date_embauche')
                                        <i class="bi bi-arrow-{{ request('direction') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Statut</th>
                            <th>Accès système</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employes as $employe)
                            <tr class="{{ $employe->created_at && $employe->created_at->gt(now()->subMinutes(5)) ? 'table-success' : '' }}">
                                <td>{{ $employe->matricule }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($employe->photo_profil && file_exists(public_path('storage/photos/' . $employe->photo_profil)))
                                            <img src="{{ asset('storage/photos/' . $employe->photo_profil) }}" 
                                                alt="Photo de {{ $employe->prenom }}" 
                                                class="rounded-circle me-2" 
                                                style="width: 30px; height: 30px; object-fit: cover;">
                                        @else
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                {{ strtoupper(substr($employe->prenom, 0, 1)) }}{{ strtoupper(substr($employe->nom, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div>
                                            {{ $employe->prenom }} {{ $employe->nom }}
                                            @if($employe->created_at && $employe->created_at->gt(now()->subMinutes(5)))
                                                <span class="badge bg-success ms-1">Nouveau</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $employe->email }}</td>
                                <td>{{ $employe->poste->nom }}</td>
                                <td>{{ \Carbon\Carbon::parse($employe->date_embauche)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $employe->statut === 'actif' ? 'success' : 'danger' }}">
                                        {{ $employe->statut }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($employe->utilisateur_id)
                                        <span class="badge bg-success" title="Possède un compte utilisateur">
                                            <i class="bi bi-check-circle-fill me-1"></i> Actif
                                        </span>
                                    @else
                                        <span class="badge bg-secondary" title="Aucun compte utilisateur">
                                            <i class="bi bi-x-circle-fill me-1"></i> Non
                                        </span>
                                        <form action="{{ route('users.create-from-employee', $employe) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" 
                                               class="btn btn-sm btn-outline-primary ms-1" 
                                               onclick="return confirm('Créer un compte utilisateur pour {{ $employe->prenom }} {{ $employe->nom }} ?');"
                                               title="Créer un compte utilisateur">
                                                <i class="bi bi-person-plus-fill"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('employes.show', $employe) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('employes.edit', $employe) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="event.preventDefault(); 
                                                         document.getElementById('delete-form-{{ $employe->id }}').submit();">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <form id="delete-form-{{ $employe->id }}" action="{{ route('employes.destroy', $employe) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Aucun employé trouvé</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $employes->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
    
    <!-- Modal Import -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Importer des employés</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('employes.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> Instructions</h6>
                            <p class="mb-1">Pour une importation réussie, assurez-vous que :</p>
                            <ul class="mb-0">
                                <li>Le fichier est au format Excel (.xlsx, .xls) ou CSV</li>
                                <li>La première ligne contient les entêtes de colonnes</li>
                                <li>Les champs obligatoires sont présents : Nom, Prénom, Email, Date d'embauche, Poste</li>
                                <li>Les dates sont au format YYYY-MM-DD (2023-05-12) ou format Excel</li>
                                <li>Les adresses email sont uniques et valides</li>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <label for="file" class="form-label">Fichier Excel</label>
                            <div class="input-group">
                                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
                                <button type="button" class="btn btn-outline-info" id="check-file-btn">
                                    <i class="bi bi-check-circle"></i> Vérifier
                                </button>
                            </div>
                            <div class="form-text">Sélectionnez votre fichier et cliquez sur "Vérifier" pour analyser sa structure.</div>
                            
                            <div id="file-check-results" class="mt-3" style="display: none;">
                                <!-- Résultats de la vérification seront affichés ici -->
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-light">
                                <i class="bi bi-download"></i> Télécharger le modèle
                            </div>
                            <div class="card-body">
                                <p class="text-muted">
                                    Pour faciliter l'importation, utilisez notre modèle préformaté. Il contient toutes les colonnes nécessaires et un exemple.
                                </p>
                                <a href="{{ route('employes.export-template') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-file-excel"></i> Télécharger le modèle Excel
                                </a>
                            </div>
                        </div>
                        
                        @if(session('import_errors'))
                            <div class="text-danger mt-2">
                                <p class="mb-1"><i class="bi bi-exclamation-triangle-fill"></i> Des erreurs se sont produites lors de la dernière importation :</p>
                                <div class="accordion accordion-flush" id="importErrorsAccordionModal">
                                    <!-- Regrouper les erreurs similaires -->
                                    @php
                                        $errors = session('import_errors');
                                        $errorCategories = [
                                            'email' => [],
                                            'matricule' => [],
                                            'date' => [],
                                            'champ' => [],
                                            'autres' => []
                                        ];
                                        
                                        foreach ($errors as $error) {
                                            $errorLower = strtolower($error);
                                            if (strpos($errorLower, 'email') !== false) {
                                                $errorCategories['email'][] = $error;
                                            } elseif (strpos($errorLower, 'matricule') !== false) {
                                                $errorCategories['matricule'][] = $error;
                                            } elseif (strpos($errorLower, 'date') !== false) {
                                                $errorCategories['date'][] = $error;
                                            } elseif (strpos($errorLower, 'obligatoire') !== false || strpos($errorLower, 'manquant') !== false) {
                                                $errorCategories['champ'][] = $error;
                                            } else {
                                                $errorCategories['autres'][] = $error;
                                            }
                                        }
                                    @endphp
                                    
                                    <!-- Erreurs liées aux emails -->
                                    @if(count($errorCategories['email']) > 0)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#emailErrorsModal">
                                                <i class="bi bi-envelope-exclamation me-2"></i> 
                                                Erreurs d'emails ({{ count($errorCategories['email']) }})
                                            </button>
                                        </h2>
                                        <div id="emailErrorsModal" class="accordion-collapse collapse">
                                            <div class="accordion-body">
                                                <ul class="mb-0 small">
                                                    @foreach($errorCategories['email'] as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <!-- Erreurs liées aux matricules -->
                                    @if(count($errorCategories['matricule']) > 0)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#matriculeErrorsModal">
                                                <i class="bi bi-upc me-2"></i> 
                                                Erreurs de matricules ({{ count($errorCategories['matricule']) }})
                                            </button>
                                        </h2>
                                        <div id="matriculeErrorsModal" class="accordion-collapse collapse">
                                            <div class="accordion-body">
                                                <ul class="mb-0 small">
                                                    @foreach($errorCategories['matricule'] as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <!-- Erreurs liées aux dates -->
                                    @if(count($errorCategories['date']) > 0)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dateErrorsModal">
                                                <i class="bi bi-calendar-x me-2"></i> 
                                                Erreurs de dates ({{ count($errorCategories['date']) }})
                                            </button>
                                        </h2>
                                        <div id="dateErrorsModal" class="accordion-collapse collapse">
                                            <div class="accordion-body">
                                                <ul class="mb-0 small">
                                                    @foreach($errorCategories['date'] as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <!-- Erreurs liées aux champs obligatoires -->
                                    @if(count($errorCategories['champ']) > 0)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#champErrorsModal">
                                                <i class="bi bi-exclamation-diamond me-2"></i> 
                                                Champs obligatoires manquants ({{ count($errorCategories['champ']) }})
                                            </button>
                                        </h2>
                                        <div id="champErrorsModal" class="accordion-collapse collapse">
                                            <div class="accordion-body">
                                                <ul class="mb-0 small">
                                                    @foreach($errorCategories['champ'] as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <!-- Autres erreurs -->
                                    @if(count($errorCategories['autres']) > 0)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#autresErrorsModal">
                                                <i class="bi bi-question-circle me-2"></i> 
                                                Autres erreurs ({{ count($errorCategories['autres']) }})
                                            </button>
                                        </h2>
                                        <div id="autresErrorsModal" class="accordion-collapse collapse">
                                            <div class="accordion-body">
                                                <ul class="mb-0 small">
                                                    @foreach($errorCategories['autres'] as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="importOptionsMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-file-earmark-arrow-up"></i> Importer
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="importOptionsMenu">
                                <li>
                                    <button type="submit" class="dropdown-item">Méthode standard</button>
                                </li>
                                <li>
                                    <button type="button" id="direct-import-btn" class="dropdown-item">Méthode alternative (essayer en cas d'erreur)</button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </form>
                <!-- Formulaire caché pour l'importation directe -->
                <form id="direct-import-form" action="{{ route('employes.import-direct') }}" method="POST" enctype="multipart/form-data" style="display: none;">
                    @csrf
                    <input type="file" id="direct-import-file" name="file">
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du bouton d'importation directe
    const directImportBtn = document.getElementById('direct-import-btn');
    const directImportForm = document.getElementById('direct-import-form');
    const directImportFile = document.getElementById('direct-import-file');
    const mainFileInput = document.getElementById('file');
    
    if (directImportBtn && directImportForm && directImportFile && mainFileInput) {
        directImportBtn.addEventListener('click', function() {
            // Copier le fichier sélectionné du formulaire principal
            if (mainFileInput.files && mainFileInput.files.length > 0) {
                // Créer un DataTransfer pour copier le fichier
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(mainFileInput.files[0]);
                directImportFile.files = dataTransfer.files;
                
                // Soumettre le formulaire d'importation directe
                directImportForm.submit();
            } else {
                alert('Veuillez sélectionner un fichier à importer');
            }
        });
    }
    
    // Gestion de la vérification du fichier avant import
    const checkFileBtn = document.getElementById('check-file-btn');
    const fileInput = document.getElementById('file');
    const resultsContainer = document.getElementById('file-check-results');
    
    if (checkFileBtn && fileInput && resultsContainer) {
        checkFileBtn.addEventListener('click', function() {
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Veuillez sélectionner un fichier à vérifier');
                return;
            }
            
            // Créer un formulaire pour envoyer le fichier
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('_token', '{{ csrf_token() }}');
            
            // Afficher un indicateur de chargement
            resultsContainer.style.display = 'block';
            resultsContainer.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Analyse du fichier en cours...</p>
                </div>
            `;
            
            // Envoyer la requête
            fetch('{{ route("employes.check-file") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher les résultats
                    const fileInfo = data.file_info;
                    let html = `
                        <div class="card border-info">
                            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-info-circle"></i> Résultat de l'analyse
                                </div>
                                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#fileAnalysisDetails" aria-expanded="false">
                                    <i class="bi bi-arrows-collapse"></i> Détails
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Structure du fichier :</strong>
                                    <ul class="mb-1">
                                        <li>${fileInfo.rows} lignes détectées (dont une ligne d'en-tête)</li>
                                        <li>${fileInfo.columns} colonnes</li>
                                    </ul>
                                </div>
                    `;
                    
                    // Colonnes requises - afficher en premier car c'est le plus important
                    if (fileInfo.missing_columns && fileInfo.missing_columns.length > 0) {
                        html += `
                            <div class="alert alert-danger">
                                <strong>Attention ! Colonnes obligatoires manquantes :</strong>
                                <ul class="mb-0">
                        `;
                        
                        fileInfo.missing_columns.forEach(col => {
                            html += `<li>${col}</li>`;
                        });
                        
                        html += `
                                </ul>
                                <p class="mt-2 mb-0">Votre fichier ne pourra pas être importé sans ces colonnes.</p>
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> Toutes les colonnes obligatoires sont présentes.
                            </div>
                        `;
                    }
                    
                    // En-têtes détectés - dans une partie collapse pour ne pas prendre trop d'espace
                    html += `<div class="collapse" id="fileAnalysisDetails">
                        <div class="mb-3">
                        <strong>En-têtes détectés :</strong>
                        <table class="table table-sm table-bordered mt-2">
                            <thead>
                                <tr>
                    `;
                    
                    // Afficher les en-têtes
                    for (const [col, header] of Object.entries(fileInfo.headers)) {
                        const isRequired = ['nom', 'prenom', 'email', 'date_embauche', 'poste'].includes(header?.toLowerCase());
                        const colorClass = isRequired ? 'table-primary' : '';
                        html += `<th class="${colorClass}">${col}</th>`;
                    }
                    
                    html += `</tr></thead><tbody><tr>`;
                    
                    // Afficher le contenu des en-têtes
                    for (const [col, header] of Object.entries(fileInfo.headers)) {
                        const isRequired = ['nom', 'prenom', 'email', 'date_embauche', 'poste'].includes(header?.toLowerCase());
                        const colorClass = isRequired ? 'table-primary' : '';
                        const displayHeader = header || '<vide>';
                        html += `<td class="${colorClass}">${displayHeader}</td>`;
                    }
                    
                    html += `</tr>`;
                    
                    // Exemple de données
                    html += `<tr>`;
                    for (const [col, value] of Object.entries(fileInfo.sample_data)) {
                        const isRequired = ['nom', 'prenom', 'email', 'date_embauche', 'poste'].includes(fileInfo.headers[col]?.toLowerCase());
                        const colorClass = isRequired ? 'table-primary' : '';
                        const displayValue = value || '<vide>';
                        html += `<td class="${colorClass}">${displayValue}</td>`;
                    }
                    html += `</tr></tbody></table></div>
                    </div>`; // Fin de la partie collapse
                    
                    html += `</div></div>`;
                    resultsContainer.innerHTML = html;
                } else {
                    // Afficher l'erreur
                    resultsContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultsContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> Une erreur s'est produite lors de l'analyse du fichier.
                    </div>
                `;
            });
        });
    }
});
</script>
@endpush