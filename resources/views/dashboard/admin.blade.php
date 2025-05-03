@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Tableau de bord administrateur</h1>
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
                            <a href="#" class="text-decoration-none">
                                <div class="p-3 bg-light rounded">
                                    <i class="bi bi-calendar-plus text-info" style="font-size: 2rem;"></i>
                                    <h6 class="mt-3 mb-0">Créer planning</h6>
                                    <small class="text-muted">(Itération 2)</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="#" class="text-decoration-none">
                                <div class="p-3 bg-light rounded">
                                    <i class="bi bi-file-earmark-text text-warning" style="font-size: 2rem;"></i>
                                    <h6 class="mt-3 mb-0">Rapports</h6>
                                    <small class="text-muted">(Itération 4)</small>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(count($postes) > 0)
        // Données pour le graphique
        const postes = @json($postes->pluck('nom'));
        const employesCounts = @json($postes->pluck('employes_count'));
        
        // Définir des couleurs de base
        const baseColors = [
            'rgba(67, 97, 238, 0.7)',
            'rgba(47, 193, 140, 0.7)',
            'rgba(252, 196, 25, 0.7)',
            'rgba(244, 92, 93, 0.7)',
            'rgba(156, 136, 255, 0.7)'
        ];
        
        // Générer suffisamment de couleurs
        const colors = [];
        for (let i = 0; i < postes.length; i++) {
            colors.push(baseColors[i % baseColors.length]);
        }
        
        // Créer le graphique
        const ctx = document.getElementById('posteChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: postes,
                datasets: [{
                    label: 'Nombre d\'employés',
                    data: employesCounts,
                    backgroundColor: colors,
                    borderColor: colors.map(c => c.replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        @endif
    });
</script>
@endpush