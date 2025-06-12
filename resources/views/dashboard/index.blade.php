@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Tableau de bord</h1>
    
    <!-- Résumé des statistiques -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total des employés</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalEmployes }}</div>
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
                                Employés actifs</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $employesActifs }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                                Présences du mois</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $statistiquesPresence['total'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                                Retards du mois</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $statistiquesPresence['retards'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row">
        <!-- Graphique de répartition des employés par poste -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Répartition des employés par poste</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="employesByPosteChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphique des présences par jour -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Présences des 30 derniers jours</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="presencesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques de présence -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistiques de présence du mois</h6>
                </div>
                <div class="card-body">
                    <h4 class="small font-weight-bold">Retards <span class="float-right">{{ $statistiquesPresence['retards'] }}</span></h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $statistiquesPresence['total'] > 0 ? ($statistiquesPresence['retards'] / $statistiquesPresence['total'] * 100) : 0 }}%"></div>
                    </div>
                    <h4 class="small font-weight-bold">Départs anticipés <span class="float-right">{{ $statistiquesPresence['departs_anticipes'] }}</span></h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $statistiquesPresence['total'] > 0 ? ($statistiquesPresence['departs_anticipes'] / $statistiquesPresence['total'] * 100) : 0 }}%"></div>
                    </div>
                    <h4 class="small font-weight-bold">Présences normales <span class="float-right">{{ $statistiquesPresence['total'] - $statistiquesPresence['retards'] - $statistiquesPresence['departs_anticipes'] }}</span></h4>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $statistiquesPresence['total'] > 0 ? (($statistiquesPresence['total'] - $statistiquesPresence['retards'] - $statistiquesPresence['departs_anticipes']) / $statistiquesPresence['total'] * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Actions rapides</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('employes.index') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-users mr-2"></i>Gérer les employés
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('presences.index') }}" class="btn btn-info btn-block">
                                <i class="fas fa-clipboard-check mr-2"></i>Gérer les présences
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('plannings.index') }}" class="btn btn-success btn-block">
                                <i class="fas fa-calendar-alt mr-2"></i>Gérer les plannings
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('postes.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-briefcase mr-2"></i>Gérer les postes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique de répartition des employés par poste
    var chartLabels = @json($employesParPoste->pluck('poste'));
    var chartData = @json($employesParPoste->pluck('total'));
    
    new Chart(document.getElementById("employesByPosteChart"), {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartData,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
            },
            legend: {
                display: true,
                position: 'bottom'
            },
            cutoutPercentage: 70,
        },
    });

    // Graphique des présences par jour
    var presenceLabels = @json($presencesParJour->pluck('jour'));
    var presenceData = @json($presencesParJour->pluck('total'));
    
    new Chart(document.getElementById("presencesChart"), {
        type: 'line',
        data: {
            labels: presenceLabels,
            datasets: [{
                label: "Présences",
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: presenceData,
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    time: {
                        unit: 'date'
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                }],
                yAxes: [{
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        beginAtZero: true
                    },
                    gridLines: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }],
            },
            legend: {
                display: false
            },
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                titleMarginBottom: 10,
                titleFontColor: '#6e707e',
                titleFontSize: 14,
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                intersect: false,
                mode: 'index',
                caretPadding: 10,
            }
        }
    });
});
</script>
@endsection 