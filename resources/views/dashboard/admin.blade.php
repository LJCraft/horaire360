@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Tableau de bord administrateur</h1>
            
            @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            
            @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            
            @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Erreurs de validation :</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
        </div>
    </div>

    <!-- Cartes de statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Employés</h6>
                            <h2 class="mt-2 mb-0">{{ $stats['employes'] }}</h2>
                        </div>
                        <div class="fs-1">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small>{{ $stats['employes_actifs'] }} actifs</small>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('employes.index') }}" class="text-white text-decoration-none small">
                        Voir tous les employés <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Postes</h6>
                            <h2 class="mt-2 mb-0">{{ $stats['postes'] }}</h2>
                        </div>
                        <div class="fs-1">
                            <i class="bi bi-briefcase"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small>{{ count($postes) > 0 ? $postes->max('employes_count') : 0 }} employés max par poste</small>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('postes.index') }}" class="text-white text-decoration-none small">
                        Voir tous les postes <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Nouveaux</h6>
                            <h2 class="mt-2 mb-0">{{ $stats['nouveaux'] }}</h2>
                        </div>
                        <div class="fs-1">
                            <i class="bi bi-person-plus"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small>Derniers 30 jours</small>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('employes.index', ['sort' => 'date_embauche', 'direction' => 'desc']) }}" class="text-white text-decoration-none small">
                        Voir les nouveaux employés <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- <div class="col-md-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Itération</h6>
                            <h2 class="mt-2 mb-0">1/4</h2>
                        </div>
                        <div class="fs-1">
                            <i class="bi bi-gear"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small>Gestion des employés</small>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <span class="text-white text-decoration-none small">
                        Fonctionnalités actuelles <i class="bi bi-check2-circle"></i>
                    </span>
                </div>
            </div>
        </div> -->
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Plannings</h6>
                            <h2 class="mt-2 mb-0">{{ \App\Models\Planning::count() }}</h2>
                        </div>
                        <div class="fs-1">
                            <i class="bi bi-calendar-week"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small>{{ \App\Models\Planning::where('date_debut', '<=', \Carbon\Carbon::today())->where('date_fin', '>=', \Carbon\Carbon::today())->count() }} en cours</small>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('plannings.index') }}" class="text-white text-decoration-none small">
                        Voir tous les plannings <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Sans planning</h6>
                            <h2 class="mt-2 mb-0">{{ \App\Models\Employe::where('statut', 'actif')->whereNotIn('id', function($query) { $query->select('employe_id')->from('plannings'); })->count() }}</h2>
                        </div>
                        <div class="fs-1">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small>Employés actifs sans planning</small>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('plannings.create') }}" class="text-dark text-decoration-none small">
                        Créer un planning <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Graphique répartition des employés par poste -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Répartition des employés par poste</h5>
                </div>
                <div class="card-body">
                    @if(count($postes) > 0)
                        <div class="chart-container" style="position: relative; height:300px;">
                            <canvas id="posteChart"></canvas>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-bar-chart text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-3 text-muted">Aucune donnée disponible</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Liste des employés récemment ajoutés -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Employés récemment ajoutés</h5>
                    <a href="{{ route('employes.create') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> Ajouter
                    </a>
                </div>
                <div class="card-body p-0">
                    @if(count($recent_employes) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recent_employes as $employe)
                                <a href="{{ route('employes.show', $employe) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">{{ $employe->prenom }} {{ $employe->nom }}</h6>
                                        <small>{{ $employe->created_at->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-1">{{ $employe->poste->nom }}</p>
                                    <small class="text-muted">
                                        <i class="bi bi-envelope me-1"></i> {{ $employe->email }}
                                    </small>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-3 text-muted">Aucun employé récemment ajouté</p>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white">
                    <a href="{{ route('employes.index') }}" class="text-decoration-none">
                        Voir tous les employés <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Accès rapides -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Accès rapides</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('employes.create') }}" class="text-decoration-none">
                                <div class="p-3 bg-light rounded">
                                    <i class="bi bi-person-plus-fill text-primary" style="font-size: 2rem;"></i>
                                    <h6 class="mt-3 mb-0">Nouvel employé</h6>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('postes.create') }}" class="text-decoration-none">
                                <div class="p-3 bg-light rounded">
                                    <i class="bi bi-briefcase-fill text-success" style="font-size: 2rem;"></i>
                                    <h6 class="mt-3 mb-0">Nouveau poste</h6>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('plannings.create') }}" class="text-decoration-none">
                                <div class="p-3 bg-light rounded">
                                    <i class="bi bi-calendar-plus text-info" style="font-size: 2rem;"></i>
                                    <h6 class="mt-3 mb-0">Créer planning</h6>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('rapports.index') }}" class="text-decoration-none">
                                <div class="p-3 bg-light rounded">
                                    <i class="bi bi-file-earmark-text text-warning" style="font-size: 2rem;"></i>
                                    <h6 class="mt-3 mb-0">Rapports</h6>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(count($postes) > 0)
        // Données pour le graphique
        const postes = @json($postes->pluck('nom'));
        const employesCounts = @json($postes->pluck('employes_count'));
        
        // Définir des couleurs professionnelles
        const baseColors = [
            'rgba(54, 162, 235, 0.8)',   // Bleu
            'rgba(75, 192, 192, 0.8)',   // Turquoise
            'rgba(255, 159, 64, 0.8)',   // Orange
            'rgba(153, 102, 255, 0.8)',  // Violet
            'rgba(255, 99, 132, 0.8)',   // Rose
            'rgba(46, 204, 113, 0.8)',   // Vert
            'rgba(52, 73, 94, 0.8)',     // Bleu foncé
            'rgba(243, 156, 18, 0.8)',   // Jaune
            'rgba(231, 76, 60, 0.8)',    // Rouge
            'rgba(155, 89, 182, 0.8)'    // Pourpre
        ];
        
        // Générer suffisamment de couleurs
        const colors = [];
        const borderColors = [];
        for (let i = 0; i < postes.length; i++) {
            colors.push(baseColors[i % baseColors.length]);
            borderColors.push(baseColors[i % baseColors.length].replace('0.8', '1'));
        }
        
        // Créer le total pour calculer les pourcentages
        const total = employesCounts.reduce((a, b) => Number(a) + Number(b), 0);
        
        // Créer les pourcentages pour l'affichage
        const percentages = employesCounts.map(count => {
            const percentage = (count / total) * 100;
            return percentage.toFixed(1) + '%';
        });
        
        // Distribution par poste (graphique à barres)
        const barCtx = document.getElementById('posteChart').getContext('2d');
        const barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: postes,
                datasets: [{
                    label: 'Nombre d\'employés',
                    data: employesCounts,
                    backgroundColor: colors,
                    borderColor: borderColors,
                    borderWidth: 1,
                    borderRadius: 4,
                    barThickness: 40,
                    maxBarThickness: 60
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const percentage = (value / total * 100).toFixed(1);
                                return `Nombre: ${value} (${percentage}%)`;
                            }
                        }
                    },
                    datalabels: {
                        color: '#000',
                        anchor: 'end',
                        align: 'top',
                        offset: 0,
                        font: {
                            weight: 'bold'
                        },
                        formatter: (value) => {
                            return value > 0 ? value : '';
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        grid: {
                            drawBorder: false
                        },
                        title: {
                            display: true,
                            text: 'Nombre d\'employés',
                            font: {
                                size: 14
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                }
            }
        });
        
        // Créer un deuxième graphique en camembert pour la distribution en pourcentage
        const pieCanvas = document.createElement('canvas');
        pieCanvas.id = 'postePieChart';
        document.querySelector('.chart-container').appendChild(pieCanvas);
        
        const pieCtx = document.getElementById('postePieChart').getContext('2d');
        const pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: postes,
                datasets: [{
                    data: employesCounts,
                    backgroundColor: colors,
                    borderColor: borderColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 15,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const percentage = (value / total * 100).toFixed(1);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%',
                radius: '90%',
                animation: {
                    animateRotate: true,
                    animateScale: true
                }
            }
        });
        
        // Ajouter des boutons pour basculer entre les graphiques
        const chartContainer = document.querySelector('.chart-container');
        const chartToggleButtons = document.createElement('div');
        chartToggleButtons.className = 'btn-group mt-3';
        chartToggleButtons.style.display = 'flex';
        chartToggleButtons.style.justifyContent = 'center';
        chartToggleButtons.innerHTML = `
            <button type="button" class="btn btn-sm btn-primary active" data-chart="bar">Graphique à barres</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-chart="pie">Graphique circulaire</button>
        `;
        chartContainer.parentNode.insertBefore(chartToggleButtons, chartContainer.nextSibling);
        
        // Masquer le graphique circulaire par défaut
        pieCanvas.style.display = 'none';
        
        // Ajouter des événements aux boutons
        const buttons = chartToggleButtons.querySelectorAll('button');
        buttons.forEach(button => {
            button.addEventListener('click', function() {
                buttons.forEach(btn => {
                    btn.classList.remove('active');
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                });
                this.classList.add('active');
                this.classList.add('btn-primary');
                this.classList.remove('btn-outline-primary');
                
                const chartType = this.getAttribute('data-chart');
                if (chartType === 'bar') {
                    document.getElementById('posteChart').style.display = 'block';
                    document.getElementById('postePieChart').style.display = 'none';
                } else {
                    document.getElementById('posteChart').style.display = 'none';
                    document.getElementById('postePieChart').style.display = 'block';
                }
            });
        });
        
        // Ajouter une petite statistique récapitulative
        const statsRecap = document.createElement('div');
        statsRecap.className = 'mt-3 text-center small text-muted';
        statsRecap.innerHTML = `
            <p class="mb-0">Total: <strong>${total} employés</strong> répartis sur <strong>${postes.length} postes</strong></p>
            <p class="mb-0">Moyenne: <strong>${(total / postes.length).toFixed(1)} employés</strong> par poste</p>
        `;
        chartContainer.parentNode.insertBefore(statsRecap, chartToggleButtons.nextSibling);
        
        @endif
    });
</script>
@endpush