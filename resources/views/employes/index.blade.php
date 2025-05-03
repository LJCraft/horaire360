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
                        <ul class="mb-0">
                            @foreach(session('import_errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
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
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employes as $employe)
                            <tr class="{{ $employe->created_at && $employe->created_at->gt(now()->subMinutes(5)) ? 'table-success' : '' }}">
                                <td>{{ $employe->matricule }}</td>
                                <td>
                                    {{ $employe->prenom }} {{ $employe->nom }}
                                    @if($employe->created_at && $employe->created_at->gt(now()->subMinutes(5)))
                                        <span class="badge bg-success ms-1">Nouveau</span>
                                    @endif
                                </td>
                                <td>{{ $employe->email }}</td>
                                <td>{{ $employe->poste->nom }}</td>
                                <td>{{ \Carbon\Carbon::parse($employe->date_embauche)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $employe->statut === 'actif' ? 'success' : 'danger' }}">
                                        {{ $employe->statut }}
                                    </span>
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
                    <div class="modal-body">
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
                            
                            @if(session('import_errors'))
                                <div class="text-danger mt-2">
                                    <p class="mb-1"><i class="bi bi-exclamation-triangle-fill"></i> Des erreurs se sont produites lors de la dernière importation :</p>
                                    <ul class="mb-0 small">
                                        @foreach(session('import_errors') as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
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
                            <div class="card-header bg-info text-white">
                                <i class="bi bi-info-circle"></i> Résultat de l'analyse
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Structure du fichier :</strong>
                                    <ul>
                                        <li>${fileInfo.rows} lignes détectées (dont une ligne d'en-tête)</li>
                                        <li>${fileInfo.columns} colonnes</li>
                                    </ul>
                                </div>
                    `;
                    
                    // En-têtes détectés
                    html += `<div class="mb-3">
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
                    html += `</tr></tbody></table></div>`;
                    
                    // Colonnes requises
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