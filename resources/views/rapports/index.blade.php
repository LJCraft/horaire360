@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold">Rapports d'assiduité</h1>
            <p class="text-muted">Suivi et analyse des présences, retards et absences des employés</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="{{ route('rapports.export.pdf') }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-pdf"></i> Exporter PDF
                </a>
                <a href="{{ route('rapports.export.excel') }}" class="btn btn-outline-success">
                    <i class="bi bi-file-excel"></i> Exporter Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Cartes des statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class="bi bi-people text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-uppercase text-muted mb-0 small">Employés</h6>
                            <h2 class="mt-1 mb-0">{{ $totalEmployes }}</h2>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Employés avec statut actif</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="bi bi-calendar-check text-success fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-uppercase text-muted mb-0 small">Présences</h6>
                            <h2 class="mt-1 mb-0">{{ $totalPresences }}</h2>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Ce mois-ci</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                            <i class="bi bi-clock-history text-warning fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-uppercase text-muted mb-0 small">Retards</h6>
                            <h2 class="mt-1 mb-0">{{ $totalRetards }}</h2>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">{{ round(($totalRetards / max($totalPresences, 1)) * 100, 1) }}% des présences</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                            <i class="bi bi-graph-up text-info fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-uppercase text-muted mb-0 small">Assiduité</h6>
                            <h2 class="mt-1 mb-0">{{ $pourcentageAssiduite }}%</h2>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Présences sans retard/départ anticipé</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Types de rapports -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Types de rapports disponibles</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('rapports.presences') }}" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                    <div class="card-body text-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                            <i class="bi bi-calendar-check text-primary fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold">Rapport des présences</h5>
                                        <p class="text-muted mb-0">Suivi détaillé des pointages avec filtres par date, employé et poste</p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 text-center">
                                        <span class="btn btn-sm btn-primary">
                                            <i class="bi bi-arrow-right"></i> Consulter
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('rapports.absences') }}" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                    <div class="card-body text-center">
                                        <div class="rounded-circle bg-danger bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                            <i class="bi bi-calendar-x text-danger fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold">Rapport des absences</h5>
                                        <p class="text-muted mb-0">Analyse des absences par employé, poste et période avec statistiques</p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 text-center">
                                        <span class="btn btn-sm btn-danger">
                                            <i class="bi bi-arrow-right"></i> Consulter
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('rapports.retards') }}" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                    <div class="card-body text-center">
                                        <div class="rounded-circle bg-warning bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                            <i class="bi bi-clock-history text-warning fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold">Rapport des retards</h5>
                                        <p class="text-muted mb-0">Suivi des retards par employé avec calcul des tendances et fréquences</p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 text-center">
                                        <span class="btn btn-sm btn-warning">
                                            <i class="bi bi-arrow-right"></i> Consulter
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique d'assiduité -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Tendance d'assiduité (7 derniers jours)</h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary active" data-chart-type="line">Ligne</button>
                        <button type="button" class="btn btn-outline-primary" data-chart-type="bar">Barres</button>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="assiduiteTendance"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Données pour le graphique
        const donneesPeriode = @json($donneesPeriode);
        const donneesPresences = @json($donneesPresences);
        const donneesRetards = @json($donneesRetards);
        
        // Graphique de tendance d'assiduité
        const ctx = document.getElementById('assiduiteTendance').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: donneesPeriode,
                datasets: [
                    {
                        label: 'Présences',
                        data: donneesPresences,
                        backgroundColor: 'rgba(52, 152, 219, 0.2)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: 'rgba(52, 152, 219, 1)'
                    },
                    {
                        label: 'Retards',
                        data: donneesRetards,
                        backgroundColor: 'rgba(243, 156, 18, 0.2)',
                        borderColor: 'rgba(243, 156, 18, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: 'rgba(243, 156, 18, 1)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#333',
                        bodyColor: '#666',
                        borderColor: '#ddd',
                        borderWidth: 1,
                        padding: 10,
                        boxWidth: 10,
                        boxHeight: 10,
                        boxPadding: 3,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y;
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
        
        // Changement de type de graphique
        document.querySelectorAll('[data-chart-type]').forEach(button => {
            button.addEventListener('click', function() {
                // Mettre à jour les classes pour l'UI
                document.querySelectorAll('[data-chart-type]').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
                
                // Changer le type de graphique
                const chartType = this.getAttribute('data-chart-type');
                myChart.config.type = chartType;
                myChart.update();
            });
        });
    });
</script>

<style>
    .hover-shadow {
        transition: all 0.3s ease;
    }
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
</style>
@endpush 