@extends('layouts.app')

@section('title', 'Rapport Ponctualité & Assiduité – ' . ucfirst($periode))

@push('scripts')
<script src="{{ asset('js/rapport-assiduite.js') }}"></script>
<script src="{{ asset('js/rapport-assiduite-avance.js') }}"></script>
@endpush

@section('content')
<div class="container-fluid" id="rapport-container" data-periode="{{ $periode }}" data-date-debut="{{ $dateDebut }}">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold text-primary"><i class="bi bi-graph-up me-2"></i>Rapport Ponctualité & Assiduité</h1>
            <p class="text-muted">Analyse détaillée des indicateurs de performance RH avec calculs automatisés</p>
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
                        <label for="departement_id" class="form-label fw-medium">Département</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-diagram-3"></i></span>
                            <select class="form-select" id="departement_id" name="departement_id">
                                <option value="">Tous les départements</option>
                                @foreach($departements as $departement)
                                    <option value="{{ $departement->departement }}" {{ $departementId == $departement->departement ? 'selected' : '' }}>
                                        {{ $departement->departement }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="poste_id" class="form-label fw-medium">Poste</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-briefcase"></i></span>
                            <select class="form-select" id="poste_id" name="poste_id">
                                <option value="">Tous les postes</option>
                                @foreach($postes as $poste)
                                    <option value="{{ $poste->id }}" data-departement="{{ $poste->departement }}" {{ $posteId == $poste->id ? 'selected' : '' }} class="poste-option {{ $poste->departement ? 'dept-'.$poste->departement : 'no-dept' }}">
                                        {{ $poste->nom }} @if($poste->departement) ({{ $poste->departement }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-text text-muted small"><i class="bi bi-info-circle"></i> Sélectionnez d'abord un département</div>
                        @if(!empty($departementMessage))
                        <div class="form-text text-primary small mt-1"><i class="bi bi-check-circle"></i> {{ $departementMessage }}</div>
                        @endif
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
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="afficher_graphiques" name="afficher_graphiques" value="1" {{ $afficherGraphiques ? 'checked' : '' }}>
                            <label class="form-check-label" for="afficher_graphiques">Afficher les graphiques</label>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="use_ajax" name="use_ajax" value="1" checked>
                            <label class="form-check-label" for="use_ajax">Mise à jour dynamique des graphiques</label>
                            <small class="form-text text-muted d-block">Permet de mettre à jour les graphiques sans recharger la page</small>
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
                        <li><a class="dropdown-item sort-link" href="#" data-sort="poste"><i class="bi bi-briefcase me-2"></i>Poste</a></li>
                        <li><a class="dropdown-item sort-link" href="#" data-sort="grade"><i class="bi bi-award me-2"></i>Grade</a></li>
                        <li><a class="dropdown-item sort-link" href="#" data-sort="jours_travailles"><i class="bi bi-calendar-check me-2"></i>Jours travaillés</a></li>
                        <li><a class="dropdown-item sort-link" href="#" data-sort="ponctualite"><i class="bi bi-clock me-2"></i>Taux de ponctualité</a></li>
                        <li><a class="dropdown-item sort-link" href="#" data-sort="assiduite"><i class="bi bi-calendar-week me-2"></i>Taux d'assiduité</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive-container">
                <div class="table-wrapper">
                    <table class="table table-hover mb-0 rapport-assiduite-table">
                        <thead class="table-light sticky-header">
                            <tr>
                                <th class="bg-light sticky-col">Employé</th>
                                <th class="bg-light">Département</th>
                            <th class="bg-light">Grade</th>
                            <th class="bg-light">Poste</th>
                            <th class="bg-light text-center">Jours prévus</th>
                            <th class="bg-light text-center">Jours réalisés</th>
                            <th class="bg-light text-center">Heures prévues</th>
                            <th class="bg-light text-center">Heures faites</th>
                            <th class="bg-light text-center">Heures d'absence</th>
                            <th class="bg-light text-center">Taux ponctualité</th>
                            <th class="bg-light text-center">Taux assiduité</th>
                            <th class="bg-light">Observation RH</th>
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
                            <td>{{ $stat->employe->departement ?? 'Non défini' }}</td>
                            <td>{{ is_object($stat->employe->grade) ? $stat->employe->grade->nom : ($stat->employe->grade ?? 'Non défini') }}</td>
                            <td>{{ $stat->employe->poste ? $stat->employe->poste->nom : 'Non défini' }}</td>
                            <td class="text-center">{{ $stat->jours_prevus ?? $stat->jours_travailles }}</td>
                            <td class="text-center">{{ $stat->jours_realises ?? $stat->jours_travailles }}</td>
                            <td class="text-center">{{ $stat->heures_prevues ?? ($stat->jours_travailles * 8) }}</td>
                            <td class="text-center">{{ $stat->heures_effectuees ?? ($stat->jours_travailles * 8) }}</td>
                            <td class="text-center">{{ $stat->heures_absence ?? 0 }}</td>
                            <td class="text-center fw-medium">{{ $stat->taux_ponctualite }}%</td>
                            <td class="text-center fw-medium">{{ $stat->taux_assiduite }}%</td>
                            <td>
                                @php
                                    $observation = '';
                                    if ($stat->taux_ponctualite < 70) {
                                        $observation .= 'Problème de ponctualité. ';
                                    }
                                    if ($stat->taux_assiduite < 70) {
                                        $observation .= 'Assiduité insuffisante. ';
                                    }
                                    if (isset($stat->jours_realises) && isset($stat->jours_prevus) && $stat->jours_realises < ($stat->jours_prevus * 0.8)) {
                                        $observation .= 'Absences fréquentes. ';
                                    } elseif (isset($stat->nombre_retards) && $stat->nombre_retards > 3) {
                                        $observation .= 'Retards fréquents. ';
                                    }
                                    if (empty($observation)) {
                                        $observation = 'Performance satisfaisante';
                                    }
                                @endphp
                                {{ $observation }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white text-muted small">
            <div class="d-flex justify-content-between align-items-center">
                <span><i class="bi bi-info-circle me-1"></i> Les taux sont calculés automatiquement en fonction des heures de pointage</span>
            </div>
        </div>
    </div>

    <!-- Graphiques (conditionnels) -->
    @if($afficherGraphiques)
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-primary"><i class="bi bi-clock-history me-2"></i>Taux de ponctualité</h5>
                </div>
                <div class="card-body">
                    <div id="ponctualiteChart" class="chart-container" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-primary"><i class="bi bi-calendar-check me-2"></i>Taux d'assiduité</h5>
                </div>
                <div class="card-body">
                    <div id="assiduiteChart" class="chart-container" style="height: 300px;"></div>
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
    /* Styles pour le tableau responsive avec en-tête fixe et défilement horizontal */
    .table-responsive-container {
        position: relative;
        width: 90%;
        margin: 0 auto;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .table-wrapper {
        overflow-x: auto;
        max-height: 70vh;
        padding-bottom: 5px;
    }
    
    .rapport-assiduite-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border-spacing: 0;
    }
    
    .rapport-assiduite-table th,
    .rapport-assiduite-table td {
        padding: 0.75rem;
        vertical-align: middle;
        white-space: nowrap;
    }
    
    /* Styles pour l'en-tête fixe */
    .sticky-header {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    /* Style pour la colonne fixe */
    .sticky-col {
        position: sticky;
        left: 0;
        z-index: 9;
        background-color: #fff;
        box-shadow: 4px 0 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .sticky-header .sticky-col {
        z-index: 11;
        background-color: #f8f9fa;
    }
    
    /* Amélioration de l'apparence des cellules */
    .rapport-assiduite-table tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    /* Animation subtile pour le survol */
    .rapport-assiduite-table tbody tr {
        transition: background-color 0.2s ease;
    }
    
    /* Style pour la colonne fixe (employé) */
    .sticky-col {
        position: sticky;
        left: 0;
        background-color: #fff;
        z-index: 1;
        border-right: 1px solid #dee2e6;
        min-width: 200px;
    }
    
    [data-bs-theme="dark"] .sticky-col {
        box-shadow: 2px 0 5px rgba(0,0,0,0.2);
    }
    
    /* Styles pour les cercles de progression */
    .progress-circle {
        position: relative;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #f1f1f1;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
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
    /* Styles pour l'impression */
    @media print {
        body {
            font-size: 9pt;
            color: #000;
        }
        
        .container-fluid {
            width: 100%;
            padding: 0;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
            margin-bottom: 10px !important;
        }
        
        .card-header, .card-footer {
            background-color: #fff !important;
            padding: 8px !important;
        }
        
        .sticky-col {
            position: static;
            background-color: #fff !important;
            border-right: 1px solid #000;
        }
        
        .table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 8pt !important;
        }
        
        .table th, .table td {
            border: 1px solid #ddd !important;
            padding: 3px !important;
        }
        
        .avatar-initials {
            border: 1px solid #ddd;
            color: #000 !important;
            background-color: #f9f9f9 !important;
            width: 24px !important;
            height: 24px !important;
            font-size: 7pt !important;
        }
        
        .progress-circle {
            width: 24px !important;
            height: 24px !important;
        }
        
        .progress-circle span {
            font-size: 7pt !important;
        }
        
        .progress-circle::before {
            display: none !important;
        }
        
        .progress-circle.excellent span::before {
            content: '★ ';
            color: #000;
        }
        
        .progress-circle.bon span::before {
            content: '✓ ';
            color: #000;
        }
        
        .progress-circle.moyen span::before {
            content: '⚠ ';
            color: #000;
        }
        
        .progress-circle.faible span::before {
            content: '✗ ';
            color: #000;
        }
        
        .btn-group, .btn, .dropdown, #filtresCollapse, .graphiques-container {
            display: none !important;
        }
        
        h1 {
            font-size: 14pt !important;
            margin-bottom: 8px !important;
        }
        
        h5 {
            font-size: 10pt !important;
        }
        
        /* Forcer les sauts de page */
        .page-break {
            page-break-after: always;
        }
        
        /* Éviter les débordements horizontaux */
        .table-responsive {
            overflow-x: visible !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Filtrer les postes en fonction du département sélectionné
    $(document).ready(function() {
        // Récupérer les postes par département
        const postesByDepartement = {!! $postesByDepartementJson !!};
        
        // Fonction pour filtrer les postes
        function filtrerPostes() {
            const departementId = $('#departement_id').val();
            const posteSelect = $('#poste_id');
            const selectedPosteId = '{{ $posteId }}';
            const posteInfoDiv = $('.form-text.text-muted.small').first();
            
            // Vider la liste des postes
            posteSelect.empty();
            
            // Ajouter l'option par défaut
            posteSelect.append('<option value="">Tous les postes</option>');
            
            // Si un département est sélectionné, ajouter les postes correspondants
            if (departementId && postesByDepartement[departementId]) {
                $.each(postesByDepartement[departementId], function(index, poste) {
                    const selected = (poste.id == selectedPosteId) ? 'selected' : '';
                    posteSelect.append(`<option value="${poste.id}" ${selected}>${poste.nom}</option>`);
                });
                posteSelect.prop('disabled', false);
                
                // Mettre à jour le message d'information
                posteInfoDiv.html(`<i class="bi bi-info-circle"></i> Affichage des postes du département : ${departementId}`);
                posteInfoDiv.removeClass('text-muted').addClass('text-primary');
            } else if (!departementId) {
                // Si aucun département n'est sélectionné, ajouter tous les postes
                @foreach($postes as $poste)
                    const selected = ({{ $poste->id }} == selectedPosteId) ? 'selected' : '';
                    posteSelect.append(`<option value="{{ $poste->id }}" data-departement="{{ $poste->departement }}" ${selected}>{{ $poste->nom }} @if($poste->departement) ({{ $poste->departement }}) @endif</option>`);
                @endforeach
                posteSelect.prop('disabled', false);
                
                // Réinitialiser le message d'information
                posteInfoDiv.html(`<i class="bi bi-info-circle"></i> Sélectionnez d'abord un département`);
                posteInfoDiv.removeClass('text-primary').addClass('text-muted');
            } else {
                // Si le département n'a pas de postes
                posteSelect.prop('disabled', true);
                posteInfoDiv.html(`<i class="bi bi-exclamation-circle"></i> Aucun poste disponible pour ce département`);
                posteInfoDiv.removeClass('text-primary').addClass('text-warning');
            }
        }
        
        // Filtrer les postes au chargement de la page
        filtrerPostes();
        
        // Filtrer les postes lorsque le département change
        $('#departement_id').on('change', filtrerPostes);
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.3/dist/apexcharts.min.js"></script>

<!-- Script pour gérer la navigation entre les périodes -->
<script>
$(document).ready(function() {
    // Gérer les boutons de sélection de période (jour, semaine, mois)
    $('.periode-btn').on('click', function() {
        const nouvellePeriode = $(this).data('periode');
        const dateDebut = $('input[name="date_debut"]').val();
        
        // Rediriger vers la même page avec la nouvelle période
        window.location.href = `{{ route('rapports.ponctualite-assiduite') }}?periode=${nouvellePeriode}&date_debut=${dateDebut}`;
    });
    
    // Gérer le bouton "Aujourd'hui"
    $('#aujourdhui').on('click', function() {
        const periode = $('input[name="periode"]').val();
        const today = new Date().toISOString().split('T')[0]; // Format YYYY-MM-DD
        
        window.location.href = `{{ route('rapports.ponctualite-assiduite') }}?periode=${periode}&date_debut=${today}`;
    });
    
    // Gérer les boutons de navigation (précédent/suivant)
    $('#periode-precedente, #periode-suivante').on('click', function() {
        const direction = $(this).attr('id') === 'periode-precedente' ? -1 : 1;
        const periode = $('input[name="periode"]').val();
        const dateDebutActuelle = $('input[name="date_debut"]').val();
        
        // Calculer la nouvelle date de début en fonction de la période
        let dateObj = new Date(dateDebutActuelle);
        
        switch(periode) {
            case 'jour':
                dateObj.setDate(dateObj.getDate() + direction);
                break;
            case 'semaine':
                dateObj.setDate(dateObj.getDate() + (direction * 7));
                break;
            case 'mois':
                dateObj.setMonth(dateObj.getMonth() + direction);
                break;
        }
        
        const nouvelleDate = dateObj.toISOString().split('T')[0]; // Format YYYY-MM-DD
        window.location.href = `{{ route('rapports.ponctualite-assiduite') }}?periode=${periode}&date_debut=${nouvelleDate}`;
    });
});
</script>

<script>
// Données pour les graphiques (disponibles pour le script externe)
window.rapportData = {
    employes: @json($employesNoms),
    tauxPonctualite: @json($tauxPonctualiteData),
    tauxAssiduite: @json($tauxAssiduiteData),
    periode: '{{ $periode }}',
    dateDebut: '{{ $dateDebut }}',
    periodeLabel: '{{ $periodeLabel }}'
};

// Script d'initialisation pour connecter le JavaScript existant avec la page
$(document).ready(function() {
    // Initialisation des graphiques si nécessaire
    if (typeof initRapportAssiduite === 'function') {
        initRapportAssiduite();
    } else if (window.ApexCharts && window.rapportData) {
        // Initialisation de secours pour les graphiques
        if (document.getElementById('graphique-ponctualite')) {
            new ApexCharts(document.getElementById('graphique-ponctualite'), {
                series: [{
                    name: 'Taux de ponctualité',
                    data: window.rapportData.tauxPonctualite
                }],
                chart: { type: 'bar', height: 350 },
                plotOptions: { bar: { horizontal: false, columnWidth: '70%' } },
                dataLabels: { 
                    enabled: true,
                    formatter: function(val) { return val + '%'; }
                },
                xaxis: { categories: window.rapportData.employes },
                yaxis: { min: 0, max: 100 },
                colors: ['#4e73df']
            }).render();
        }
        
        if (document.getElementById('graphique-assiduite')) {
            new ApexCharts(document.getElementById('graphique-assiduite'), {
                series: [{
                    name: 'Taux d\'assiduité',
                    data: window.rapportData.tauxAssiduite
                }],
                chart: { type: 'bar', height: 350 },
                plotOptions: { bar: { horizontal: false, columnWidth: '70%' } },
                dataLabels: { 
                    enabled: true,
                    formatter: function(val) { return val + '%'; }
                },
                xaxis: { categories: window.rapportData.employes },
                yaxis: { min: 0, max: 100 },
                colors: ['#1cc88a']
            }).render();
        }
    }
    
    // SOLUTION SIMPLE POUR LE FILTRAGE DES POSTES PAR DÉPARTEMENT
    function filtrerPostesParDepartement() {
        var departementSelectionne = $('#departement_id').val();
        var messageElement = $('.form-text.small').first();
        var compteurPostesVisibles = 0;
        
        // Afficher/masquer les options en fonction du département sélectionné
        $('#poste_id option').each(function() {
            // Ignorer l'option "Tous les postes"
            if ($(this).val() === '') {
                $(this).show();
                return;
            }
            
            var departementPoste = $(this).data('departement');
            
            // Si aucun département n'est sélectionné OU si le département correspond
            if (!departementSelectionne || departementSelectionne === '' || departementPoste === departementSelectionne) {
                $(this).show();
                compteurPostesVisibles++;
            } else {
                $(this).hide();
                // Si l'option cachée est sélectionnée, réinitialiser la sélection
                if ($(this).is(':selected')) {
                    $('#poste_id').val('');
                }
            }
        });
        
        // Mettre à jour le message
        if (departementSelectionne && departementSelectionne !== '') {
            if (compteurPostesVisibles > 0) {
                messageElement.html(`<i class="bi bi-info-circle"></i> Affichage des postes du département : ${departementSelectionne} (${compteurPostesVisibles} postes)`);
                messageElement.removeClass('text-muted text-warning').addClass('text-primary');
            } else {
                messageElement.html(`<i class="bi bi-exclamation-circle"></i> Aucun poste disponible pour ce département`);
                messageElement.removeClass('text-muted text-primary').addClass('text-warning');
            }
        } else {
            messageElement.html(`<i class="bi bi-info-circle"></i> Sélectionnez d'abord un département`);
            messageElement.removeClass('text-primary text-warning').addClass('text-muted');
        }
    }
    
    // Appliquer le filtrage lors du changement de département
    $('#departement_id').on('change', filtrerPostesParDepartement);
    
    // Appliquer le filtrage initial
    filtrerPostesParDepartement();
    
    // Gérer les boutons de sélection de période (jour, semaine, mois)
    $('.periode-btn').on('click', function() {
        const nouvellePeriode = $(this).data('periode');
        const dateDebut = $('input[name="date_debut"]').val();
        window.location.href = `{{ route('rapports.ponctualite-assiduite') }}?periode=${nouvellePeriode}&date_debut=${dateDebut}`;
    });
    
    // Gérer les boutons de navigation (précédent, suivant)
    $('#periode-precedente, #periode-suivante').on('click', function() {
        const direction = $(this).attr('id') === 'periode-precedente' ? 'prev' : 'next';
        const periodeActuelle = $('input[name="periode"]').val();
        const dateActuelle = $('input[name="date_debut"]').val();
        window.location.href = `{{ route('rapports.ponctualite-assiduite') }}?periode=${periodeActuelle}&date_debut=${dateActuelle}&direction=${direction}`;
    });
    
    // Gérer le bouton "Aujourd'hui"
    $('#periode-aujourdhui').on('click', function() {
        const periodeActuelle = $('input[name="periode"]').val();
        window.location.href = `{{ route('rapports.ponctualite-assiduite') }}?periode=${periodeActuelle}`;
    });
});
</script>
<script src="{{ asset('js/rapport-assiduite.js') }}"></script>
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
