@extends('layouts.app')

@section('title', 'Rapport Ponctualité & Assiduité')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold text-primary"><i class="bi bi-graph-up me-2"></i>Rapport Ponctualité & Assiduité</h1>
            <p class="text-muted">Analyse détaillée des indicateurs de performance avec calculs automatisés</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group shadow-sm">
                <a href="{{ route('rapports.export-options', ['type' => 'ponctualite-assiduite', 'periode' => $periode, 'date_debut' => $dateDebut]) }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-export"></i> Exporter
                </a>
                <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Options d'exportation</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Format d'exportation</h6></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('rapports.export-options', ['type' => 'ponctualite-assiduite', 'periode' => $periode, 'date_debut' => $dateDebut, 'format' => 'pdf']) }}">
                            <i class="bi bi-file-pdf me-2"></i> PDF
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('rapports.export-options', ['type' => 'ponctualite-assiduite', 'periode' => $periode, 'date_debut' => $dateDebut, 'format' => 'excel']) }}">
                            <i class="bi bi-file-excel me-2"></i> Excel
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('rapports.export-options', ['type' => 'ponctualite-assiduite', 'periode' => $periode, 'date_debut' => $dateDebut]) }}">
                            <i class="bi bi-gear me-2"></i> Options avancées
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Sélecteur de période -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="btn-group shadow-sm" role="group" aria-label="Sélecteur de période">
                    <button type="button" class="btn btn-outline-primary periode-btn {{ $periode == 'jour' ? 'active' : '' }}" data-periode="jour">Jour</button>
                    <button type="button" class="btn btn-outline-primary periode-btn {{ $periode == 'semaine' ? 'active' : '' }}" data-periode="semaine">Semaine</button>
                    <button type="button" class="btn btn-outline-primary periode-btn {{ $periode == 'mois' ? 'active' : '' }}" data-periode="mois">Mois</button>
                    <button type="button" class="btn btn-outline-primary periode-btn {{ $periode == 'annee' ? 'active' : '' }}" data-periode="annee">Année</button>
                </div>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2 shadow-sm" id="periode-precedente" title="Période précédente">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <span class="fw-medium badge bg-primary text-white px-3 py-2 shadow-sm" id="periode-actuelle">{{ $periodeLabel }}</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2 shadow-sm" id="periode-suivante" title="Période suivante">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" id="aujourdhui">
                        <i class="bi bi-calendar-check"></i> Aujourd'hui
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres supplémentaires -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary"><i class="bi bi-funnel me-2"></i>Filtres d'analyse</h5>
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filtresCollapse" aria-expanded="true" aria-controls="filtresCollapse">
                <i class="bi bi-sliders"></i> Options
            </button>
        </div>
        <div class="collapse show" id="filtresCollapse">
            <div class="card-body bg-light">
                <form action="{{ route('rapports.ponctualite-assiduite') }}" method="GET" class="row g-3">
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <input type="hidden" name="date_debut" value="{{ $dateDebut }}">
                    
                    <div class="col-md-3">
                        <label for="service_id" class="form-label fw-medium">Service</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-building"></i></span>
                            <select class="form-select" id="service_id" name="service_id">
                                <option value="">Tous les services</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}" {{ $serviceId == $service->id ? 'selected' : '' }}>
                                        {{ $service->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="departement_id" class="form-label fw-medium">Département</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-diagram-3"></i></span>
                            <select class="form-select" id="departement_id" name="departement_id">
                                <option value="">Tous les départements</option>
                                @foreach($departements as $departement)
                                    <option value="{{ $departement->id }}" {{ $departementId == $departement->id ? 'selected' : '' }}>
                                        {{ $departement->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="performance" class="form-label fw-medium">Performance</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-speedometer2"></i></span>
                            <select class="form-select" id="performance" name="performance">
                                <option value="">Toutes les performances</option>
                                <option value="excellent" {{ $performance == 'excellent' ? 'selected' : '' }}>Excellente (>95%)</option>
                                <option value="bon" {{ $performance == 'bon' ? 'selected' : '' }}>Bonne (80-95%)</option>
                                <option value="moyen" {{ $performance == 'moyen' ? 'selected' : '' }}>Moyenne (60-80%)</option>
                                <option value="faible" {{ $performance == 'faible' ? 'selected' : '' }}>Faible (<60%)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="afficher_graphiques" name="afficher_graphiques" {{ $afficherGraphiques ? 'checked' : '' }}>
                            <label class="form-check-label" for="afficher_graphiques"><i class="bi bi-bar-chart-line me-1"></i> Afficher les graphiques</label>
                        </div>
                    </div>
                    <div class="col-md-12 text-end mt-3">
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="bi bi-search"></i> Appliquer les filtres
                        </button>
                        <a href="{{ route('rapports.ponctualite-assiduite') }}" class="btn btn-outline-secondary shadow-sm ms-2">
                            <i class="bi bi-x-circle"></i> Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tableau des indicateurs -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary"><i class="bi bi-clipboard-data me-2"></i>Indicateurs de performance</h5>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary rounded-pill me-3">{{ count($statistiques) }} employés</span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle shadow-sm" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-sort-down"></i> Trier par
                    </button>
                    <ul class="dropdown-menu shadow-sm" aria-labelledby="dropdownMenuButton">
                        <li><a class="dropdown-item sort-link" href="#" data-sort="nom"><i class="bi bi-person me-2"></i>Nom</a></li>
                        <li><a class="dropdown-item sort-link" href="#" data-sort="service"><i class="bi bi-building me-2"></i>Service</a></li>
                        <li><a class="dropdown-item sort-link" href="#" data-sort="jours_travailles"><i class="bi bi-calendar-check me-2"></i>Jours travaillés</a></li>
                        <li><a class="dropdown-item sort-link" href="#" data-sort="ponctualite"><i class="bi bi-clock me-2"></i>Taux de ponctualité</a></li>
                        <li><a class="dropdown-item sort-link" href="#" data-sort="assiduite"><i class="bi bi-calendar-week me-2"></i>Taux d'assiduité</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 rapport-assiduite-table">
                    <thead class="table-light">
                        <tr>
                            <th class="bg-light sticky-col">Employé</th>
                            <th class="bg-light">Service</th>
                            <th class="bg-light">Grade</th>
                            <th class="bg-light">Fonction</th>
                            <th class="text-center bg-light">Jours prévus</th>
                            <th class="text-center bg-light">Jours travaillés</th>
                            <th class="text-center bg-light">Heures prévues</th>
                            <th class="text-center bg-light">Heures effectuées</th>
                            <th class="text-center bg-light">Heures d'absence</th>
                            <th class="text-center bg-light">Ponctualité</th>
                            <th class="text-center bg-light">Assiduité</th>
                            <th class="bg-light">Observations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($statistiques as $stat)
                        <tr class="stat-row" data-employe-id="{{ $stat->employe->id }}">
                            <td class="sticky-col">
                                <div class="d-flex align-items-center">
                                    @if($stat->employe->photo_profil && file_exists(public_path('storage/photos/' . $stat->employe->photo_profil)))
                                        <img src="{{ asset('storage/photos/' . $stat->employe->photo_profil) }}" 
                                            alt="Photo de {{ $stat->employe->prenom }}" 
                                            class="rounded-circle me-2" 
                                            style="width: 32px; height: 32px; object-fit: cover;">
                                    @else
                                        <div class="avatar-initials bg-primary bg-opacity-10 text-primary me-2">
                                            {{ strtoupper(substr($stat->employe->prenom ?? '', 0, 1)) }}{{ strtoupper(substr($stat->employe->nom ?? '', 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <span class="fw-medium">{{ $stat->employe->prenom }} {{ $stat->employe->nom }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $stat->employe->service->nom ?? '-' }}</td>
                            <td>{{ $stat->employe->grade ?? '-' }}</td>
                            <td>{{ $stat->employe->poste->nom ?? '-' }}</td>
                            <td class="text-center">{{ $stat->jours_prevus }}</td>
                            <td class="text-center fw-medium">{{ $stat->jours_travailles }}</td>
                            <td class="text-center">{{ $stat->heures_prevues }}</td>
                            <td class="text-center fw-medium">{{ $stat->heures_effectuees }}</td>
                            <td class="text-center {{ $stat->heures_absence > 0 ? 'text-danger' : '' }}">{{ $stat->heures_absence }}</td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="progress me-2" style="width: 60px; height: 8px;">
                                        <div class="progress-bar {{ getTauxClass($stat->taux_ponctualite) }}" role="progressbar" style="width: {{ $stat->taux_ponctualite }}%"></div>
                                    </div>
                                    <span class="fw-medium">{{ $stat->taux_ponctualite }}%</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="progress me-2" style="width: 60px; height: 8px;">
                                        <div class="progress-bar {{ getTauxClass($stat->taux_assiduite) }}" role="progressbar" style="width: {{ $stat->taux_assiduite }}%"></div>
                                    </div>
                                    <span class="fw-medium">{{ $stat->taux_assiduite }}%</span>
                                </div>
                            </td>
                            <td>
                                @if($stat->taux_ponctualite >= 95 && $stat->taux_assiduite >= 95)
                                    <span class="badge bg-success"><i class="bi bi-trophy me-1"></i>Excellent</span>
                                @elseif($stat->taux_ponctualite >= 90 && $stat->taux_assiduite >= 90)
                                    <span class="badge bg-primary"><i class="bi bi-clock-check me-1"></i>Très ponctuel</span>
                                @elseif($stat->taux_ponctualite < 80)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Retards fréquents</span>
                                @elseif($stat->taux_assiduite < 80)
                                    <span class="badge bg-danger"><i class="bi bi-calendar-x me-1"></i>Absences fréquentes</span>
                                @else
                                    <span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i>Performance moyenne</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-muted small">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="me-3"><i class="bi bi-info-circle me-1"></i> Les taux sont calculés automatiquement en fonction des heures de pointage</span>
                </div>
                <div>
                    <span class="badge bg-success me-1">Excellent</span>
                    <span class="badge bg-primary me-1">Bon</span>
                    <span class="badge bg-warning text-dark me-1">Moyen</span>
                    <span class="badge bg-danger">Faible</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques (conditionnels) -->
    @if($afficherGraphiques)
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-clock-history me-2"></i>Taux de ponctualité</h5>
                    <span class="badge bg-light text-primary border">Période: {{ $periodeLabel }}</span>
                </div>
                <div class="card-body">
                    <div class="chart-container" id="ponctualiteChart"></div>
                </div>
                <div class="card-footer bg-white text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Pourcentage d'arrivées à l'heure par rapport aux horaires prévus
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-calendar-check me-2"></i>Taux d'assiduité</h5>
                    <span class="badge bg-light text-primary border">Période: {{ $periodeLabel }}</span>
                </div>
                <div class="card-body">
                    <div class="chart-container" id="assiduiteChart"></div>
                </div>
                <div class="card-footer bg-white text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Pourcentage de jours travaillés par rapport aux jours prévus
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-bar-chart-line me-2"></i>Comparatif des performances</h5>
                    <button class="btn btn-sm btn-outline-primary" id="toggleChartView">
                        <i class="bi bi-arrow-repeat"></i> Changer de vue
                    </button>
                </div>
                <div class="card-body">
                    <div class="chart-container" id="comparatifChart"></div>
                </div>
                <div class="card-footer bg-white text-muted small">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-info-circle me-1"></i> Comparaison des taux de ponctualité et d'assiduité pour chaque employé</span>
                        <span class="text-end">
                            <span class="badge bg-primary me-1">Ponctualité</span>
                            <span class="badge bg-success">Assiduité</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    /* Styles pour le tableau responsive */
    .rapport-assiduite-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .sticky-col {
        position: sticky;
        left: 0;
        background-color: var(--bs-card-bg);
        z-index: 1;
        border-right: 1px solid var(--bs-border-color);
        min-width: 220px;
        box-shadow: 2px 0 5px rgba(0,0,0,0.05);
    }
    
    [data-bs-theme="dark"] .sticky-col {
        box-shadow: 2px 0 5px rgba(0,0,0,0.2);
    }
    
    .table-responsive {
        overflow-x: auto;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        max-height: 70vh;
        position: relative;
    }
    
    /* Styles pour les boutons de période */
    .periode-btn.active {
        background-color: #4e73df;
        color: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Styles pour les cellules du tableau */
    .rapport-assiduite-table th, .rapport-assiduite-table td {
        padding: 0.75rem;
        vertical-align: middle;
    }
    
    .rapport-assiduite-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        font-weight: 600;
        color: var(--bs-emphasis-color);
        border-bottom: 2px solid var(--bs-primary-rgb, #4e73df);
        background-color: var(--bs-table-striped-bg);
    }
    
    .stat-row:hover {
        background-color: var(--bs-table-hover-bg);
        cursor: pointer;
    }
    
    /* Style pour l'avatar avec initiales */
    .avatar-initials {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: bold;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Styles pour les graphiques */
    .chart-container {
        height: 350px;
        margin-bottom: 20px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        border-radius: 0.35rem;
        background-color: var(--bs-card-bg);
    }
    
    [data-bs-theme="dark"] .chart-container {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.2);
    }
    
    .chart-header {
        padding: 1rem;
        border-bottom: 1px solid var(--bs-border-color);
    /* Styles spécifiques pour les graphiques en mode sombre */
    [data-bs-theme="dark"] .apexcharts-tooltip {
        background-color: #162340 !important;
        border-color: #2a3c61 !important;
        color: #e5eaf2 !important;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2) !important;
    }
    
    [data-bs-theme="dark"] .apexcharts-tooltip-title {
        background-color: #1d2c4f !important;
        border-color: #2a3c61 !important;
        color: #a3b8d9 !important;
    }
    
    [data-bs-theme="dark"] .apexcharts-xaxistooltip,
    [data-bs-theme="dark"] .apexcharts-yaxistooltip {
        color: #f8f9fc;
    }
    
    .dark-mode .table-light {
        background-color: #3a3a3a;
        color: #f8f9fc;
    }
    
    .dark-mode .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.075);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.3/dist/apexcharts.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variables pour la gestion de la période
        let periode = '{{ $periode }}';
        let dateDebut = '{{ $dateDebut }}';
        
        // Gestion des boutons de période
        const periodeBtns = document.querySelectorAll('.periode-btn');
        periodeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                periode = this.dataset.periode;
                chargerRapport();
            });
        });
        
        // Gestion des boutons de navigation
        document.getElementById('periode-precedente').addEventListener('click', function() {
            naviguerPeriode('precedente');
        });
        
        document.getElementById('periode-suivante').addEventListener('click', function() {
            naviguerPeriode('suivante');
        });
        
        document.getElementById('aujourdhui').addEventListener('click', function() {
            dateDebut = '{{ \Carbon\Carbon::now()->format("Y-m-d") }}';
            chargerRapport();
        });
        
        // Gestion du tri
        document.querySelectorAll('.sort-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const sortBy = this.dataset.sort;
                const url = new URL(window.location.href);
                url.searchParams.set('sort_by', sortBy);
                
                // Inverser l'ordre si on clique sur la même colonne
                if (url.searchParams.get('sort_by') === sortBy) {
                    const currentOrder = url.searchParams.get('sort_order') || 'asc';
                    url.searchParams.set('sort_order', currentOrder === 'asc' ? 'desc' : 'asc');
                } else {
                    url.searchParams.set('sort_order', 'asc');
                }
                
                window.location.href = url.toString();
            });
        });
        
        // Fonction pour naviguer entre les périodes
        function naviguerPeriode(direction) {
            const date = new Date(dateDebut);
            
            if (periode === 'jour') {
                date.setDate(date.getDate() + (direction === 'precedente' ? -1 : 1));
            } else if (periode === 'semaine') {
                date.setDate(date.getDate() + (direction === 'precedente' ? -7 : 7));
            } else if (periode === 'mois') {
                date.setMonth(date.getMonth() + (direction === 'precedente' ? -1 : 1));
            } else if (periode === 'annee') {
                date.setFullYear(date.getFullYear() + (direction === 'precedente' ? -1 : 1));
            }
            
            dateDebut = date.toISOString().split('T')[0];
            chargerRapport();
        }
        
        // Fonction pour charger le rapport avec les nouveaux paramètres
        function chargerRapport() {
            const url = new URL('{{ route("rapports.ponctualite-assiduite") }}', window.location.origin);
            url.searchParams.append('periode', periode);
            url.searchParams.append('date_debut', dateDebut);
            
            // Conserver les autres filtres
            const serviceId = document.getElementById('service_id').value;
            if (serviceId) url.searchParams.append('service_id', serviceId);
            
            const departementId = document.getElementById('departement_id').value;
            if (departementId) url.searchParams.append('departement_id', departementId);
            
            const performance = document.getElementById('performance').value;
            if (performance) url.searchParams.append('performance', performance);
            
            const afficherGraphiques = document.getElementById('afficher_graphiques').checked;
            if (afficherGraphiques) url.searchParams.append('afficher_graphiques', '1');
            
            window.location.href = url.toString();
        }
        
        // Initialisation des graphiques si nécessaire
        @if($afficherGraphiques)
        // Les données pour les graphiques sont préparées dans le contrôleur
        
        initCharts();
        @endif
        
        // Fonction pour initialiser les graphiques
        function initCharts() {
            // Données pour les graphiques
            const employes = @json($employesNoms);
            const tauxPonctualite = @json($tauxPonctualiteData);
            const tauxAssiduite = @json($tauxAssiduiteData);
            
            // Configuration du graphique de ponctualité
            const ponctualiteOptions = {
                series: [{
                    name: 'Taux de ponctualité',
                    data: tauxPonctualite
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: true,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: true,
                            reset: true
                        }
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        dataLabels: {
                            position: 'top',
                        },
                        borderRadius: 4,
                        barHeight: '70%'
                    }
                },
                colors: ['#4e73df'],
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val + "%";
                    },
                    offsetX: 20,
                    style: {
                        fontSize: '12px',
                        fontWeight: 'bold',
                        colors: ['#000']
                    }
                },
                xaxis: {
                    categories: employes,
                    labels: {
                        formatter: function (val) {
                            return val + "%";
                        }
                    },
                    max: 100
                },
                yaxis: {
                    labels: {
                        show: true,
                        style: {
                            fontWeight: 'medium'
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val + "%";
                        }
                    },
                    theme: 'light',
                    marker: {
                        show: true
                    }
                },
                grid: {
                    borderColor: '#e0e0e0',
                    strokeDashArray: 4,
                    xaxis: {
                        lines: {
                            show: true
                        }
                    }
                }
            };
            
            // Initialisation du graphique de ponctualité
            const ponctualiteChart = new ApexCharts(document.querySelector("#ponctualiteChart"), ponctualiteOptions);
            ponctualiteChart.render();
            
            // Configuration du graphique d'assiduité
            const assiduiteOptions = {
                series: [{
                    name: 'Taux d\'assiduité',
                    data: tauxAssiduite
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false
                        }
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: true,
                            delay: 150
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 350
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '70%',
                        borderRadius: 5,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val + '%';
                    },
                    offsetY: -20,
                    style: {
                        fontSize: '12px',
                        colors: ["#304758"]
                    }
                },
                xaxis: {
                    categories: Object.values(employes),
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    max: 100,
                    title: {
                        text: 'Taux d\'assiduité (%)'
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: "vertical",
                        shadeIntensity: 0.25,
                        gradientToColors: undefined,
                        inverseColors: true,
                        opacityFrom: 1,
                        opacityTo: 0.85,
                        stops: [50, 100]
                    }
                },
                colors: ['#1cc88a']
            };
            
            const assiduiteChart = new ApexCharts(document.getElementById('assiduiteChart'), assiduiteOptions);
            assiduiteChart.render();
        }
        
        // Synchronisation avec le mode sombre global de l'application
        function updateChartTheme() {
            // Vérifier le thème actuel
            const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
            
            // Mettre à jour les graphiques si nécessaire
            if (window.ponctualiteChart) {
                ponctualiteChart.updateOptions({
                    theme: {
                        mode: currentTheme === 'dark' ? 'dark' : 'light',
                        palette: 'palette1'
                    },
                    grid: {
                        borderColor: currentTheme === 'dark' ? '#2a3c61' : '#e0e0e0'
                    },
                    xaxis: {
                        labels: {
                            style: {
                                colors: currentTheme === 'dark' ? '#a3b8d9' : '#718096'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: currentTheme === 'dark' ? '#a3b8d9' : '#718096'
                            }
                        }
                    }
                });
            }
            
            if (window.assiduiteChart) {
                assiduiteChart.updateOptions({
                    theme: {
                        mode: currentTheme === 'dark' ? 'dark' : 'light',
                        palette: 'palette1'
                    },
                    grid: {
                        borderColor: currentTheme === 'dark' ? '#2a3c61' : '#e0e0e0'
                    },
                    xaxis: {
                        labels: {
                            style: {
                                colors: currentTheme === 'dark' ? '#a3b8d9' : '#718096'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: currentTheme === 'dark' ? '#a3b8d9' : '#718096'
                            }
                        }
                    }
                });
            }
        }
        
        // Observer les changements de thème
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'data-bs-theme') {
                    updateChartTheme();
                }
            });
        });
        
        // Observer les changements d'attribut sur l'élément HTML
        observer.observe(document.documentElement, { attributes: true });
        
        // Appliquer le thème initial aux graphiques
        updateChartTheme();
        }
    });
</script>
@endpush

@php
// Fonction helper pour déterminer la classe CSS en fonction du taux
function getTauxClass($taux) {
    if ($taux >= 90) return 'bg-success';
    if ($taux >= 80) return 'bg-primary';
    if ($taux >= 60) return 'bg-warning';
    return 'bg-danger';
}
@endphp
