@extends('layouts.app')

@section('title', 'Tableau de bord administrateur')

@section('head')
<!-- ApexCharts CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.3/dist/apexcharts.css">
@endsection

@section('content')
<div class="container-fluid">
    <!-- En-tête du tableau de bord -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord administrateur
        </h1>
        <div class="d-flex align-items-center">
            <!-- Bouton de basculement du mode jour/nuit -->
            <div class="theme-toggle-wrapper me-3">
                <button class="theme-toggle-btn rounded-circle p-2 d-flex align-items-center justify-content-center" id="themeToggle" title="Basculer entre le mode jour et nuit">
                    <i class="bi bi-sun-fill theme-toggle-icon" id="themeIcon"></i>
                </button>
            </div>
            
            <style>
                .theme-toggle-btn {
                    width: 40px;
                    height: 40px;
                    border: none;
                    background: linear-gradient(145deg, #f0f0f0, #e6e6e6);
                    box-shadow: 5px 5px 10px #d1d1d1, -5px -5px 10px #ffffff;
                    transition: all 0.3s ease;
                    cursor: pointer;
                }
                
                [data-bs-theme="dark"] .theme-toggle-btn {
                    background: linear-gradient(145deg, #1d2c4f, #162340);
                    box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2), -5px -5px 10px rgba(42, 60, 97, 0.3);
                }
                
                .theme-toggle-btn:hover {
                    transform: translateY(-2px);
                }
                
                .theme-toggle-btn:active {
                    transform: translateY(1px);
                    box-shadow: 3px 3px 6px #d1d1d1, -3px -3px 6px #ffffff;
                }
                
                [data-bs-theme="dark"] .theme-toggle-btn:active {
                    box-shadow: 3px 3px 6px rgba(0, 0, 0, 0.2), -3px -3px 6px rgba(42, 60, 97, 0.3);
                }
                
                .theme-toggle-icon {
                    font-size: 1.2rem;
                    color: #ffc107; /* couleur du soleil */
                    transition: all 0.3s ease;
                }
                
                [data-bs-theme="dark"] .theme-toggle-icon {
                    color: #4f8eff; /* couleur de la lune */
                }
                
                /* Animation de rotation lors du changement de thème */
                .theme-toggle-btn.rotating .theme-toggle-icon {
                    animation: rotate-icon 0.5s ease-in-out;
                }
                
                @keyframes rotate-icon {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
            
            <button class="btn btn-sm btn-outline-secondary" id="refreshDashboard">
                <i class="fas fa-sync-alt me-1"></i> Actualiser
            </button>
            <div class="dropdown d-inline-block ms-2">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="exportOptions" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-download me-1"></i> Exporter
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportOptions">
                    <li><a class="dropdown-item" href="#" id="exportPDF"><i class="far fa-file-pdf me-2"></i>PDF</a></li>
                    <li><a class="dropdown-item" href="#" id="exportExcel"><i class="far fa-file-excel me-2"></i>Excel</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Cartes de statistiques principales -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Employés</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalEmployes">{{ $stats['employes'] }}</div>
                            <div class="small">
                                <span class="text-success"><span id="employesActifs">{{ $stats['employes_actifs'] }}</span> actifs</span> / 
                                <span class="text-danger"><span id="employesInactifs">{{ $stats['employes'] - $stats['employes_actifs'] }}</span> inactifs</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Postes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalPostes">{{ $stats['postes'] }}</div>
                            <div class="small">{{ count($postes) > 0 ? $postes->first()->nom : 'Aucun' }} et plus</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Présences du jour</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="presencesToday">
                                {{ $statsPresence['presencesAujourdhui'] }}
                            </div>
                            <div class="small" id="presencesDetail">
                                <span class="text-success">{{ $statsPresence['presencesAujourdhui'] - $statsPresence['retardsAujourdhui'] }} à l'heure</span> / 
                                <span class="text-warning">{{ $statsPresence['retardsAujourdhui'] }} en retard</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Nouveaux employés (30j)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['nouveaux'] }}</div>
                            <div class="small">{{ round(($stats['employes'] > 0 ? $stats['nouveaux'] / $stats['employes'] * 100 : 0), 1) }}% de croissance</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                        </div>

    <!-- Graphiques et tableaux -->
    <div class="row">
        <!-- Graphique de répartition des employés par poste -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Répartition des employés par poste</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="employesChartOptions" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="employesChartOptions">
                            <div class="dropdown-header">Options de graphique:</div>
                            <a class="dropdown-item chart-type" data-type="donut" href="#"><i class="fas fa-circle fa-sm fa-fw me-2"></i>Anneau</a>
                            <a class="dropdown-item chart-type" data-type="pie" href="#"><i class="fas fa-chart-pie fa-sm fa-fw me-2"></i>Camembert</a>
                            <a class="dropdown-item chart-type" data-type="bar" href="#"><i class="fas fa-chart-bar fa-sm fa-fw me-2"></i>Barres</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" id="downloadEmployesChart"><i class="fas fa-download fa-sm fa-fw me-2"></i>Télécharger</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <div id="employesChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte des employés récemment ajoutés -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Employés récemment ajoutés</h6>
                    <a href="{{ route('employes.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-users me-1"></i> Tous
                    </a>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @forelse($recent_employes as $employe)
                            <div class="list-group-item px-0 border-bottom">
                                <div class="d-flex align-items-center">
                                    @if($employe->photo_profil && file_exists(public_path('storage/photos/' . $employe->photo_profil)))
                                        <img src="{{ asset('storage/photos/' . $employe->photo_profil) }}" 
                                            alt="Photo de {{ $employe->prenom }}" 
                                            class="rounded-circle me-3" 
                                            style="width: 48px; height: 48px; object-fit: cover;">
                                    @else
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                            style="width: 48px; height: 48px; font-size: 1.2rem;">
                                            {{ strtoupper(substr($employe->prenom, 0, 1)) }}{{ strtoupper(substr($employe->nom, 0, 1)) }}
                                        </div>
                                    @endif
                        <div>
                                        <h6 class="mb-0">{{ $employe->prenom }} {{ $employe->nom }}</h6>
                                        <p class="text-muted small mb-0">{{ $employe->poste->nom ?? 'Non assigné' }}</p>
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-calendar-alt me-1"></i> Ajouté {{ $employe->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <div class="ms-auto">
                                        <a href="{{ route('employes.show', $employe) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                        </div>
                        </div>
                    </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="fas fa-user-plus fa-3x text-gray-300 mb-3"></i>
                                <p>Aucun employé récemment ajouté</p>
                                <a href="{{ route('employes.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus-circle me-1"></i> Ajouter un employé
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Graphique des présences sur les 30 derniers jours -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Présences des 30 derniers jours</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="presencesChartOptions" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="presencesChartOptions">
                            <div class="dropdown-header">Options de graphique:</div>
                            <a class="dropdown-item presence-chart-type" data-type="area" href="#"><i class="fas fa-chart-area fa-sm fa-fw me-2"></i>Aire</a>
                            <a class="dropdown-item presence-chart-type" data-type="line" href="#"><i class="fas fa-chart-line fa-sm fa-fw me-2"></i>Ligne</a>
                            <a class="dropdown-item presence-chart-type" data-type="bar" href="#"><i class="fas fa-chart-bar fa-sm fa-fw me-2"></i>Barres</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" id="downloadPresencesChart"><i class="fas fa-download fa-sm fa-fw me-2"></i>Télécharger</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <div id="presencesChart" style="min-height: 300px;"></div>
                        </div>
                </div>
            </div>
        </div>

        <!-- Statistiques de présence -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistiques de présence</h6>
                </div>
                <div class="card-body">
                    <h4 class="small font-weight-bold">Taux de présence <span class="float-end">{{ $statsPresence['tauxPresence'] }}%</span></h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $statsPresence['tauxPresence'] }}%"></div>
                                    </div>
                    
                    <h4 class="small font-weight-bold">Taux de retard <span class="float-end">{{ $statsPresence['tauxRetard'] }}%</span></h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $statsPresence['tauxRetard'] }}%"></div>
                        </div>
                    
                    <h4 class="small font-weight-bold">Taux d'absence <span class="float-end">{{ $statsPresence['tauxAbsence'] }}%</span></h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $statsPresence['tauxAbsence'] }}%"></div>
                        </div>
                    
                    <!-- Graphique de répartition des présences -->
                    <div id="presenceRepartitionChart" style="min-height: 200px; margin-top: 1.5rem;"></div>
                    
                    <div class="text-center mt-4">
                        <a href="{{ route('presences.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-clipboard-list me-1"></i> Gérer les présences
                        </a>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accès rapides -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Accès rapides</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('employes.index') }}" class="card h-100 border-left-primary shadow py-2 text-decoration-none">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Gérer les employés</div>
                                            <div class="small">Ajouter, modifier, supprimer</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('presences.index') }}" class="card h-100 border-left-info shadow py-2 text-decoration-none">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Gérer les présences</div>
                                            <div class="small">Suivi et pointage</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('plannings.index') }}" class="card h-100 border-left-success shadow py-2 text-decoration-none">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Gérer les plannings</div>
                                            <div class="small">Horaires et calendrier</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('rapports.index') }}" class="card h-100 border-left-warning shadow py-2 text-decoration-none">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Rapports</div>
                                            <div class="small">Statistiques et analyses</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ApexCharts JS - Assurez-vous qu'il est chargé avant votre script -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.3/dist/apexcharts.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation des graphiques du tableau de bord...');
    
    // Gestion du mode jour/nuit avec animations élégantes
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const htmlElement = document.documentElement;
    
    // Vérifier s'il y a une préférence sauvegardée
    const savedTheme = localStorage.getItem('horaire360-theme');
    if (savedTheme) {
        htmlElement.setAttribute('data-bs-theme', savedTheme);
        updateThemeIcon(savedTheme);
    }
    
    // Fonction pour mettre à jour l'icône selon le thème avec animation
    function updateThemeIcon(theme) {
        // Ajouter la classe d'animation
        themeToggle.classList.add('rotating');
        
        // Délai pour permettre à l'animation de se dérouler avant de changer l'icône
        setTimeout(() => {
            if (theme === 'dark') {
                themeIcon.classList.remove('bi-sun-fill');
                themeIcon.classList.add('bi-moon-stars-fill');
            } else {
                themeIcon.classList.remove('bi-moon-stars-fill');
                themeIcon.classList.add('bi-sun-fill');
            }
            
            // Retirer la classe d'animation après la fin de l'animation
            setTimeout(() => {
                themeToggle.classList.remove('rotating');
            }, 500);
        }, 100);
    }
    
    // Événement de clic pour basculer le thème avec feedback visuel
    themeToggle.addEventListener('click', function() {
        // Déterminer le thème actuel et le nouveau thème
        const currentTheme = htmlElement.getAttribute('data-bs-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Appliquer le nouveau thème
        htmlElement.setAttribute('data-bs-theme', newTheme);
        
        // Sauvegarder la préférence de l'utilisateur
        localStorage.setItem('horaire360-theme', newTheme);
        
        // Mettre à jour l'icône avec animation
        updateThemeIcon(newTheme);
        
        // Feedback visuel temporaire pour confirmer le changement
        const feedbackMessage = document.createElement('div');
        feedbackMessage.className = 'theme-feedback';
        feedbackMessage.textContent = newTheme === 'dark' ? 'Mode nuit activé' : 'Mode jour activé';
        document.body.appendChild(feedbackMessage);
        
        // Afficher le message avec animation
        setTimeout(() => {
            feedbackMessage.classList.add('visible');
            
            // Supprimer le message après 2 secondes
            setTimeout(() => {
                feedbackMessage.classList.remove('visible');
                setTimeout(() => {
                    document.body.removeChild(feedbackMessage);
                }, 300);
            }, 2000);
        }, 10);
    });
    
    // Ajouter le style pour le feedback visuel
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        .theme-feedback {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background-color: var(--bs-primary);
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            z-index: 9999;
        }
        
        .theme-feedback.visible {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        
        [data-bs-theme="dark"] .theme-feedback {
            background-color: #4f8eff;
            color: #ffffff;
        }
    `;
    document.head.appendChild(styleElement);
    
    // Vérification que ApexCharts est bien chargé
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts n\'est pas défini! La bibliothèque n\'est pas correctement chargée.');
        document.querySelectorAll('#employesChart, #presencesChart, #presenceRepartitionChart').forEach(function(container) {
            container.innerHTML = '<div class="alert alert-danger">Erreur: La bibliothèque ApexCharts n\'est pas chargée correctement.</div>';
        });
        return;
    }
    
    // Fonction pour créer les graphiques
    function createCharts(data) {
        console.log('Données reçues pour les graphiques:', data);
        
        // 1. Graphique de répartition des employés par poste (donut)
        if (data.postes.labels.length > 0) {
            try {
                var chart1 = new ApexCharts(document.getElementById('employesChart'), {
                    series: data.postes.values,
                    chart: {
                        type: 'donut',
                        height: 300
                    },
                    labels: data.postes.labels,
                    colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
                });
                
                chart1.render();
                
                // Type change handlers
                document.querySelectorAll('.chart-type').forEach(function(item) {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        var type = this.getAttribute('data-type');
                        
                        if (type === 'donut' || type === 'pie') {
                            chart1.updateOptions({
                                chart: { type: type },
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            size: type === 'donut' ? '55%' : '0%'
                                        }
                                    }
                                }
                            });
                        } else if (type === 'bar') {
                            chart1.updateOptions({
                                chart: { type: 'bar' },
                                xaxis: { categories: data.postes.labels }
                            });
                        }
                    });
                });
                
                // Download handler
                var downloadEmployesChart = document.getElementById('downloadEmployesChart');
                if (downloadEmployesChart) {
                    downloadEmployesChart.addEventListener('click', function(e) {
                        e.preventDefault();
                        chart1.dataURI().then(function(uri) {
                            var link = document.createElement('a');
                            link.href = uri.imgURI;
                            link.download = 'repartition-employes.png';
                            link.click();
                        });
                    });
                }
            } catch (error) {
                console.error('Erreur lors de la création du graphique 1:', error);
                document.getElementById('employesChart').innerHTML = 
                    '<div class="alert alert-danger">Erreur: ' + error.message + '</div>';
            }
        }
        
        // 2. Graphique des présences (area)
        if (data.presences.labels.length > 0) {
            try {
                var chart2 = new ApexCharts(document.getElementById('presencesChart'), {
                    series: [{
                        name: 'Présences',
                        data: data.presences.values
                    }],
                    chart: {
                        height: 300,
                        type: 'area'
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth'
                    },
                    xaxis: {
                        categories: data.presences.labels
                    }
                });
                
                chart2.render();
                
                // Type change handlers
                document.querySelectorAll('.presence-chart-type').forEach(function(item) {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        chart2.updateOptions({
                            chart: {
                                type: this.getAttribute('data-type')
                            }
                        });
                    });
                });
                
                // Download handler
                var downloadPresencesChart = document.getElementById('downloadPresencesChart');
                if (downloadPresencesChart) {
                    downloadPresencesChart.addEventListener('click', function(e) {
                        e.preventDefault();
                        chart2.dataURI().then(function(uri) {
                            var link = document.createElement('a');
                            link.href = uri.imgURI;
                            link.download = 'presences-30-jours.png';
                            link.click();
                        });
                    });
                }
            } catch (error) {
                console.error('Erreur lors de la création du graphique 2:', error);
                document.getElementById('presencesChart').innerHTML = 
                    '<div class="alert alert-danger">Erreur: ' + error.message + '</div>';
            }
        }
        
        // 3. Graphique de répartition des présences (radialBar)
        try {
            var chart3 = new ApexCharts(document.getElementById('presenceRepartitionChart'), {
                series: [
                    data.stats_presence.tauxPresence,
                    data.stats_presence.tauxRetard,
                    data.stats_presence.tauxAbsence
                ],
                chart: {
                    height: 250,
                    type: 'radialBar'
                },
                plotOptions: {
                    radialBar: {
                        dataLabels: {
                            name: {
                                show: true
                            },
                            value: {
                                show: true,
                                formatter: function(val) {
                                    return val + '%';
                                }
                            }
                        }
                    }
                },
                colors: ['#1cc88a', '#f6c23e', '#e74a3b'],
                labels: ['Présences', 'Retards', 'Absences']
            });
            
            chart3.render();
        } catch (error) {
            console.error('Erreur lors de la création du graphique 3:', error);
            document.getElementById('presenceRepartitionChart').innerHTML = 
                '<div class="alert alert-danger">Erreur: ' + error.message + '</div>';
        }
    }
    
    // Charger les données via AJAX
    function loadDashboardData() {
        // Afficher des indicateurs de chargement
        const loadingMessage = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Chargement des données...</p></div>';
        document.getElementById('employesChart').innerHTML = loadingMessage;
        document.getElementById('presencesChart').innerHTML = loadingMessage;
        document.getElementById('presenceRepartitionChart').innerHTML = loadingMessage;
        
        // Faire la requête AJAX - Utiliser l'API avec les données réelles de l'application
        fetch('/api/dashboard-data')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Données réelles chargées:', data);
                createCharts(data);
            })
            .catch(error => {
                console.error('Erreur lors du chargement des données:', error);
                const errorMessage = '<div class="alert alert-danger">Erreur lors du chargement des données: ' + error.message + '</div>';
                document.getElementById('employesChart').innerHTML = errorMessage;
                document.getElementById('presencesChart').innerHTML = errorMessage;
                document.getElementById('presenceRepartitionChart').innerHTML = errorMessage;
            });
    }
    
    // Charger les données au chargement de la page
    loadDashboardData();
    
    // Ajouter un gestionnaire d'événement pour le bouton de rafraîchissement
    const refreshButton = document.getElementById('refreshDashboard');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            // Ajouter un effet de rotation à l'icône
            var icon = this.querySelector('i');
            if (icon) icon.classList.add('fa-spin');
            this.disabled = true;
            
            // Charger les données
            loadDashboardData();
            
            // Réactiver le bouton après un court délai
            setTimeout(function() {
                if (icon) icon.classList.remove('fa-spin');
                refreshButton.disabled = false;
            }, 1000);
        });
    }
    
    // Gestionnaire pour les boutons d'export
    var exportPDF = document.getElementById('exportPDF');
    var exportExcel = document.getElementById('exportExcel');
    
    if (exportPDF) {
        exportPDF.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Export PDF en cours de développement');
        });
    }
    
    if (exportExcel) {
        exportExcel.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Export Excel en cours de développement');
        });
    }
    });
</script>
@endsection