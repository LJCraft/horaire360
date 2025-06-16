@extends('layouts.app')

@section('title', 'Rapport Ponctualité & Assiduité – ' . ucfirst($periode))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold text-primary"><i class="bi bi-graph-up me-2"></i>Rapport Ponctualité & Assiduité</h1>
            <p class="text-muted">Analyse détaillée des indicateurs de performance RH avec calculs automatisés</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="{{ route('rapports.export-pdf', ['type' => 'ponctualite-assiduite-v2']) }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-pdf"></i> PDF
                </a>
                <a href="{{ route('rapports.export-excel', ['type' => 'ponctualite-assiduite-v2']) }}" class="btn btn-outline-success">
                    <i class="bi bi-file-excel"></i> Excel
                </a>
                <button type="button" class="btn btn-outline-secondary" id="toggle-graphiques">
                    <i class="bi bi-bar-chart"></i> Graphiques
                </button>
            </div>
        </div>
    </div>

    <!-- Sélecteur de période et filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary"><i class="bi bi-funnel me-2"></i>Filtres d'analyse</h5>
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filtresCollapse" aria-expanded="true" aria-controls="filtresCollapse">
                <i class="bi bi-sliders"></i> Options
            </button>
        </div>
        <div class="collapse show" id="filtresCollapse">
            <div class="card-body">
                <form action="{{ route('rapports.ponctualite-assiduite-v2') }}" method="GET" id="filtresForm">
                    <div class="row g-3">
                        <!-- Sélecteur de période -->
                        <div class="col-md-4">
                            <label for="periode" class="form-label">Période</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-calendar-range"></i></span>
                                <select class="form-select" id="periode" name="periode">
                                    <option value="jour" {{ $periode == 'jour' ? 'selected' : '' }}>Jour</option>
                                    <option value="semaine" {{ $periode == 'semaine' ? 'selected' : '' }}>Semaine</option>
                                    <option value="mois" {{ $periode == 'mois' ? 'selected' : '' }}>Mois</option>
                                </select>
                            </div>
                        </div>

                        <!-- Date de début -->
                        <div class="col-md-4">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-calendar-date"></i></span>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ $dateDebut }}">
                            </div>
                        </div>

                        <!-- Date de fin (visible uniquement pour période personnalisée) -->
                        <div class="col-md-4" id="date_fin_container">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-calendar-date"></i></span>
                                <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ $dateFin }}">
                            </div>
                        </div>

                        <!-- Filtre par employé -->
                        <div class="col-md-4">
                            <label for="employe_id" class="form-label">Employé</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                                <select class="form-select" id="employe_id" name="employe_id">
                                    <option value="">Tous les employés</option>
                                    @foreach($tousEmployes as $emp)
                                        <option value="{{ $emp->id }}" {{ $employeId == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->prenom }} {{ $emp->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Filtre par département -->
                        <div class="col-md-4">
                            <label for="departement_id" class="form-label">Département</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-diagram-3"></i></span>
                                <select class="form-select" id="departement_id" name="departement_id">
                                    <option value="">Tous les départements</option>
                                    @foreach($departements as $departement)
                                        <option value="{{ is_array($departement) ? $departement['id'] : $departement }}" 
                                            {{ $departementId == (is_array($departement) ? $departement['id'] : $departement) ? 'selected' : '' }}>
                                            {{ is_array($departement) ? $departement['nom'] : $departement }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Filtre par service -->
                        <div class="col-md-4">
                            <label for="service_id" class="form-label">Service</label>
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

                        <!-- Boutons d'action -->
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="reset-filtres">
                                <i class="bi bi-x-circle"></i> Réinitialiser
                            </button>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-search"></i> Appliquer les filtres
                            </button>
                        </div>
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
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-sort-down"></i> Trier par
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                        <li><a class="dropdown-item sort-link" href="#" data-sort="nom"><i class="bi bi-person me-2"></i>Nom</a></li>
                        <li><a class="dropdown-item sort-link" href="#" data-sort="departement"><i class="bi bi-diagram-3 me-2"></i>Département</a></li>
                        <li><a class="dropdown-item sort-link" href="#" data-sort="ponctualite"><i class="bi bi-clock me-2"></i>Taux de ponctualité</a></li>
                        <li><a class="dropdown-item sort-link" href="#" data-sort="assiduite"><i class="bi bi-calendar-check me-2"></i>Taux d'assiduité</a></li>
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
                            <td>{{ $stat->employe->grade ? $stat->employe->grade->nom : 'Non défini' }}</td>
                            <td>{{ $stat->employe->poste ? $stat->employe->poste->nom : 'Non défini' }}</td>
                            <td class="text-center">{{ $stat->jours_prevus }}</td>
                            <td class="text-center">{{ $stat->jours_realises }}</td>
                            <td class="text-center">{{ $stat->heures_prevues }}</td>
                            <td class="text-center">{{ $stat->heures_faites }}</td>
                            <td class="text-center">{{ $stat->heures_absence }}</td>
                            <td class="text-center">
                                @php
                                    $tauxPonctualite = $stat->taux_ponctualite;
                                    $classePonctualite = getTauxClass($tauxPonctualite);
                                @endphp
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="progress-circle {{ $classePonctualite }}" data-value="{{ $tauxPonctualite }}">
                                        <span>{{ number_format($tauxPonctualite, 1) }}%</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                @php
                                    $tauxAssiduite = $stat->taux_assiduite;
                                    $classeAssiduite = getTauxClass($tauxAssiduite);
                                @endphp
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="progress-circle {{ $classeAssiduite }}" data-value="{{ $tauxAssiduite }}">
                                        <span>{{ number_format($tauxAssiduite, 1) }}%</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{-- === CONTRAINTE : Vider systématiquement la colonne Observation RH === --}}
                                {{ $stat->observation_rh ?? '' }}
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
                    <span>Période: {{ $periodeLabel }} | Dernière mise à jour: {{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques (conditionnels) -->
    <div class="row graphiques-container" style="display: {{ $afficherGraphiques ? 'flex' : 'none' }};">
        <!-- Graphique de ponctualité -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-primary"><i class="bi bi-clock me-2"></i>Taux de ponctualité</h5>
                </div>
                <div class="card-body">
                    <div id="chartPonctualite" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        
        <!-- Graphique d'assiduité -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-primary"><i class="bi bi-calendar-check me-2"></i>Taux d'assiduité</h5>
                </div>
                <div class="card-body">
                    <div id="chartAssiduite" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Styles pour le tableau responsive */
    .rapport-assiduite-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .rapport-assiduite-table th,
    .rapport-assiduite-table td {
        padding: 0.75rem;
        vertical-align: middle;
        border-top: 1px solid #dee2e6;
    }
    
    /* Style pour la colonne fixe */
    .sticky-col {
        position: sticky;
        left: 0;
        background-color: #fff;
        z-index: 1;
        border-right: 1px solid #dee2e6;
        min-width: 200px;
    }
    
    thead .sticky-col {
        background-color: #f8f9fa;
        z-index: 2;
    }
    
    /* Style pour les initiales */
    .avatar-initials {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        font-size: 0.8rem;
        font-weight: 500;
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
    }
    
    .progress-circle::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: conic-gradient(var(--circle-color) var(--progress), #f1f1f1 0);
    }
    
    .progress-circle::after {
        content: '';
        position: absolute;
        top: 4px;
        left: 4px;
        width: calc(100% - 8px);
        height: calc(100% - 8px);
        border-radius: 50%;
        background-color: white;
    }
    
    .progress-circle span {
        position: relative;
        z-index: 1;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    .progress-circle.excellent {
        --circle-color: #28a745;
    }
    
    .progress-circle.bon {
        --circle-color: #17a2b8;
    }
    
    .progress-circle.moyen {
        --circle-color: #ffc107;
    }
    
    .progress-circle.faible {
        --circle-color: #dc3545;
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variables pour la gestion de la période
        let periode = '{{ $periode }}';
        let dateDebut = '{{ $dateDebut }}';
        let dateFin = '{{ $dateFin }}';
        
        // Initialisation des cercles de progression
        initProgressCircles();
        
        // Initialisation des graphiques si nécessaire
        if ({{ $afficherGraphiques ? 'true' : 'false' }}) {
            initCharts();
        }
        
        // Gestion du bouton de toggle des graphiques
        document.getElementById('toggle-graphiques').addEventListener('click', function() {
            const graphiquesContainer = document.querySelector('.graphiques-container');
            if (graphiquesContainer.style.display === 'none') {
                graphiquesContainer.style.display = 'flex';
                initCharts(); // Initialiser les graphiques lors de l'affichage
            } else {
                graphiquesContainer.style.display = 'none';
            }
        });
        
        // Gestion du reset des filtres
        document.getElementById('reset-filtres').addEventListener('click', function() {
            window.location.href = '{{ route("rapports.ponctualite-assiduite-v2") }}';
        });
        
        // Gestion du tri du tableau
        const sortLinks = document.querySelectorAll('.sort-link');
        sortLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const sortBy = this.dataset.sort;
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('sort', sortBy);
                window.location.href = currentUrl.toString();
            });
        });
        
        // Gestion de la période et des dates
        document.getElementById('periode').addEventListener('change', function() {
            const selectedPeriode = this.value;
            const dateFinContainer = document.getElementById('date_fin_container');
            
            if (selectedPeriode === 'jour') {
                dateFinContainer.style.display = 'none';
            } else {
                dateFinContainer.style.display = 'block';
            }
        });
        
        // Initialisation de l'affichage du conteneur de date de fin
        if (periode === 'jour') {
            document.getElementById('date_fin_container').style.display = 'none';
        }
        
        // Fonction pour initialiser les cercles de progression
        function initProgressCircles() {
            document.querySelectorAll('.progress-circle').forEach(circle => {
                const value = parseFloat(circle.dataset.value);
                circle.style.setProperty('--progress', `${value}%`);
            });
        }
        
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
                    height: 300,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 4
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: employes,
                    labels: {
                        style: {
                            fontSize: '10px'
                        },
                        trim: true,
                        maxHeight: 50
                    }
                },
                yaxis: {
                    title: {
                        text: 'Pourcentage (%)'
                    },
                    min: 0,
                    max: 100
                },
                fill: {
                    opacity: 1,
                    colors: ['#4e73df']
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val + "%"
                        }
                    }
                },
                theme: {
                    mode: document.body.classList.contains('dark-mode') ? 'dark' : 'light'
                }
            };
            
            // Configuration du graphique d'assiduité
            const assiduiteOptions = {
                series: [{
                    name: 'Taux d\'assiduité',
                    data: tauxAssiduite
                }],
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 4
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: employes,
                    labels: {
                        style: {
                            fontSize: '10px'
                        },
                        trim: true,
                        maxHeight: 50
                    }
                },
                yaxis: {
                    title: {
                        text: 'Pourcentage (%)'
                    },
                    min: 0,
                    max: 100
                },
                fill: {
                    opacity: 1,
                    colors: ['#1cc88a']
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val + "%"
                        }
                    }
                },
                theme: {
                    mode: document.body.classList.contains('dark-mode') ? 'dark' : 'light'
                }
            };
            
            // Création des graphiques
            const chartPonctualite = new ApexCharts(document.querySelector("#chartPonctualite"), ponctualiteOptions);
            const chartAssiduite = new ApexCharts(document.querySelector("#chartAssiduite"), assiduiteOptions);
            
            chartPonctualite.render();
            chartAssiduite.render();
        }
    });
</script>
@endpush

@php
// Fonction helper pour déterminer la classe CSS en fonction du taux
function getTauxClass($taux) {
    if ($taux >= 90) {
        return 'excellent';
    } elseif ($taux >= 80) {
        return 'bon';
    } elseif ($taux >= 70) {
        return 'moyen';
    } else {
        return 'faible';
    }
}
@endphp
