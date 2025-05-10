@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold">Rapport des retards</h1>
            <p class="text-muted">Analyse des retards par employé et période</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="{{ route('rapports.export.pdf', ['type' => 'retards']) }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-pdf"></i> PDF
                </a>
                <a href="{{ route('rapports.export.excel', ['type' => 'retards']) }}" class="btn btn-outline-success">
                    <i class="bi bi-file-excel"></i> Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Filtres</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('rapports.retards') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date_debut" class="form-label">Date de début</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ $dateDebut }}">
                </div>
                <div class="col-md-3">
                    <label for="date_fin" class="form-label">Date de fin</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ $dateFin }}">
                </div>
                <div class="col-md-3">
                    <label for="employe_id" class="form-label">Employé</label>
                    <select class="form-select" id="employe_id" name="employe_id">
                        <option value="">Tous les employés</option>
                        @foreach($employes as $employe)
                            <option value="{{ $employe->id }}" {{ $employeId == $employe->id ? 'selected' : '' }}>
                                {{ $employe->prenom }} {{ $employe->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="poste_id" class="form-label">Poste</label>
                    <select class="form-select" id="poste_id" name="poste_id">
                        <option value="">Tous les postes</option>
                        @foreach($postes as $poste)
                            <option value="{{ $poste->id }}" {{ $posteId == $poste->id ? 'selected' : '' }}>
                                {{ $poste->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filtrer
                    </button>
                    <a href="{{ route('rapports.retards') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Cartes des statistiques -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 text-center border-end">
                            <div class="rounded-circle bg-warning bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-clock-history text-warning fs-4"></i>
                            </div>
                            <h3 class="mb-0 fw-bold">{{ $totalRetards }}</h3>
                            <p class="text-muted">Retards totaux</p>
                        </div>
                        <div class="col-md-6 text-center">
                            <div class="rounded-circle bg-info bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-calculator text-info fs-4"></i>
                            </div>
                            <h3 class="mb-0 fw-bold">{{ $retardsMoyenParJour }}</h3>
                            <p class="text-muted">Retards moyens par jour</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-0">
                    <div class="d-flex h-100">
                        <div class="p-3 flex-grow-1 d-flex flex-column justify-content-center">
                            <h5 class="fw-bold">Répartition des retards</h5>
                            <p class="text-muted mb-3">Distribution par jour de la semaine</p>
                            <div class="chart-container" style="position: relative; height: 120px;">
                                <canvas id="retardsJoursChart"></canvas>
                            </div>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning d-flex flex-column justify-content-center align-items-center p-4">
                            <i class="bi bi-exclamation-triangle fs-1 mb-3"></i>
                            <div class="text-center">
                                <div class="fw-bold fs-5">Jour critique</div>
                                <span class="fs-4">Lundi</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Retards par employé -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Retards par employé</h5>
            <span class="badge bg-warning text-dark rounded-pill">Top {{ $retardsParEmploye->count() }} employés</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employé</th>
                            <th>Poste</th>
                            <th class="text-center">Nombre de retards</th>
                            <th class="text-center">% du total</th>
                            <th>Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($retardsParEmploye as $retard)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initials bg-warning bg-opacity-10 text-warning me-2">
                                            {{ substr($retard->prenom, 0, 1) }}{{ substr($retard->nom, 0, 1) }}
                                        </div>
                                        {{ $retard->prenom }} {{ $retard->nom }}
                                    </div>
                                </td>
                                <td>{{ $retard->poste }}</td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark">{{ $retard->nombre_retards }}</span>
                                </td>
                                <td class="text-center">
                                    @php 
                                        $pourcentage = $totalRetards > 0 ? round(($retard->nombre_retards / $totalRetards) * 100, 1) : 0;
                                    @endphp
                                    {{ $pourcentage }}%
                                </td>
                                <td>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                            style="width: {{ $pourcentage }}%;" 
                                            aria-valuenow="{{ $pourcentage }}" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-search fs-3 d-block mb-2"></i>
                                        Aucun retard trouvé pour les critères sélectionnés
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Le tableau affiche les employés ayant des retards sur la période sélectionnée
                </div>
                <div>
                    {{ $retardsParEmploye->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Liste détaillée des retards -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Liste détaillée des retards</h5>
            <span class="badge bg-primary rounded-pill">{{ $retards->total() }} résultats</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Employé</th>
                            <th>Heure prévue</th>
                            <th>Heure d'arrivée</th>
                            <th class="text-center">Retard</th>
                            <th>Commentaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($retards as $retard)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($retard->date)->format('d/m/Y') }}</td>
                                <td>
                                    @if($retard->employe)
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initials bg-warning bg-opacity-10 text-warning me-2">
                                            {{ substr($retard->employe->prenom, 0, 1) }}{{ substr($retard->employe->nom, 0, 1) }}
                                        </div>
                                        {{ $retard->employe->prenom }} {{ $retard->employe->nom }}
                                    </div>
                                    @else
                                    <span class="text-muted">Employé supprimé</span>
                                    @endif
                                </td>
                                @php
                                    $planning = null;
                                    $heurePrevue = '-';
                                    $heureArrivee = \Carbon\Carbon::parse($retard->heure_arrivee);
                                    $minutesRetard = 0;
                                    
                                    if ($retard->employe) {
                                        $planning = \App\Models\Planning::where('employe_id', $retard->employe_id)
                                            ->whereDate('date_debut', '<=', $retard->date)
                                            ->whereDate('date_fin', '>=', $retard->date)
                                            ->first();
                                        
                                        if ($planning) {
                                            $heurePrevue = \Carbon\Carbon::parse($planning->heure_debut)->format('H:i');
                                            $heurePlanifiee = \Carbon\Carbon::parse($planning->heure_debut);
                                            $minutesRetard = $heureArrivee->diffInMinutes($heurePlanifiee);
                                        }
                                    }
                                @endphp
                                <td>{{ $heurePrevue }}</td>
                                <td>{{ $heureArrivee->format('H:i') }}</td>
                                <td class="text-center">
                                    @if ($planning)
                                        <span class="badge bg-warning text-dark">
                                            {{ $minutesRetard }} min
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">Non calculé</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($retard->commentaire)
                                        <span class="text-muted small">{{ $retard->commentaire }}</span>
                                    @else
                                        <span class="text-muted small fst-italic">Aucun commentaire</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-search fs-3 d-block mb-2"></i>
                                        Aucun retard trouvé pour les critères sélectionnés
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $retards->firstItem() ?? 0 }} à {{ $retards->lastItem() ?? 0 }} sur {{ $retards->total() }} retards
                </div>
                <div>
                    {{ $retards->withQueryString()->links() }}
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
        // Données fictives pour la répartition par jour (à remplacer par des données réelles)
        const joursData = {
            labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven'],
            values: [35, 20, 15, 10, 20]
        };
        
        // Graphique de répartition par jour
        const ctx = document.getElementById('retardsJoursChart').getContext('2d');
        const retardsJoursChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: joursData.labels,
                datasets: [{
                    label: 'Retards par jour',
                    data: joursData.values,
                    backgroundColor: [
                        'rgba(243, 156, 18, 0.8)',
                        'rgba(243, 156, 18, 0.6)',
                        'rgba(243, 156, 18, 0.5)',
                        'rgba(243, 156, 18, 0.4)',
                        'rgba(243, 156, 18, 0.3)'
                    ],
                    borderColor: 'rgba(243, 156, 18, 1)',
                    borderWidth: 1
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
                                const total = joursData.values.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${value} retards (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        display: false
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>

<style>
    .avatar-initials {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
    }
    
    .pagination {
        margin-bottom: 0;
    }
</style>
@endpush 