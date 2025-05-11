@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold">Rapport des absences</h1>
            <p class="text-muted">Suivi des jours sans pointage par employé sur la période sélectionnée</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="{{ route('rapports.export.pdf', ['type' => 'absences']) }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-pdf"></i> PDF
                </a>
                <a href="{{ route('rapports.export.excel', ['type' => 'absences']) }}" class="btn btn-outline-success">
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
            <form action="{{ route('rapports.absences') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="date_debut" class="form-label">Date de début</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ $dateDebut }}">
                </div>
                <div class="col-md-4">
                    <label for="date_fin" class="form-label">Date de fin</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ $dateFin }}">
                </div>
                <div class="col-md-2">
                    <label for="employe_id" class="form-label">Employé</label>
                    <select class="form-select" id="employe_id" name="employe_id">
                        <option value="">Tous les employés</option>
                        @foreach($employesList as $employe)
                            <option value="{{ $employe->id }}" {{ $employeId == $employe->id ? 'selected' : '' }}>
                                {{ $employe->prenom }} {{ $employe->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
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
                    <a href="{{ route('rapports.absences') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Résumé des absences -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="rounded-circle bg-danger bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-calendar-x text-danger fs-4"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $totalAbsences }}</h3>
                    <p class="text-muted">Jours d'absence totaux</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="rounded-circle bg-info bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-calendar-week text-info fs-4"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $totalJoursOuvrables }}</h3>
                    <p class="text-muted">Jours ouvrables</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="rounded-circle bg-primary bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-graph-up text-primary fs-4"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $tauxGlobalAbsenteisme }}%</h3>
                    <p class="text-muted">Taux global d'absentéisme</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique d'absentéisme -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Taux d'absentéisme par employé</h5>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-primary active" data-chart-type="bar">Barres</button>
                <button type="button" class="btn btn-outline-primary" data-chart-type="pie">Camembert</button>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container" style="position: relative; height: 300px;">
                <canvas id="absenteismeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tableau détaillé des absences -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Détail des absences par employé</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employé</th>
                            <th>Poste</th>
                            <th class="text-center">Jours ouvrables</th>
                            <th class="text-center">Jours d'absence</th>
                            <th class="text-center">Taux d'absentéisme</th>
                            <th>Détails</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($absences as $absence)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($absence['employe']->photo_profil && file_exists(public_path('storage/photos/' . $absence['employe']->photo_profil)))
                                            <img src="{{ asset('storage/photos/' . $absence['employe']->photo_profil) }}" 
                                                alt="Photo de {{ $absence['employe']->prenom }}" 
                                                class="rounded-circle me-2" 
                                                style="width: 30px; height: 30px; object-fit: cover;">
                                        @else
                                            <div class="avatar-initials bg-danger bg-opacity-10 text-danger me-2">
                                                {{ strtoupper(substr($absence['employe']->prenom, 0, 1)) }}{{ strtoupper(substr($absence['employe']->nom, 0, 1)) }}
                                            </div>
                                        @endif
                                        {{ $absence['employe']->prenom }} {{ $absence['employe']->nom }}
                                    </div>
                                </td>
                                <td>{{ $absence['employe']->poste->nom }}</td>
                                <td class="text-center">{{ $absence['jours_ouvrables'] }}</td>
                                <td class="text-center">
                                    @if($absence['jours_absence'] > 0)
                                        <span class="badge bg-danger">{{ $absence['jours_absence'] }}</span>
                                    @else
                                        <span class="badge bg-success">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <div class="progress flex-grow-1" style="height: 8px; max-width: 100px;">
                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                style="width: {{ $absence['taux_absenteisme'] }}%;" 
                                                aria-valuenow="{{ $absence['taux_absenteisme'] }}" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                        <span class="ms-2">{{ $absence['taux_absenteisme'] }}%</span>
                                    </div>
                                </td>
                                <td>
                                    @if(count($absence['dates_absence']) > 0)
                                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#absence-{{ $absence['employe']->id }}">
                                            <i class="bi bi-calendar-date"></i> Voir les dates
                                        </button>
                                    @else
                                        <span class="badge bg-light text-dark">Aucune absence</span>
                                    @endif
                                </td>
                            </tr>
                            @if(count($absence['dates_absence']) > 0)
                                <tr class="collapse bg-light" id="absence-{{ $absence['employe']->id }}">
                                    <td colspan="6" class="p-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($absence['dates_absence'] as $date)
                                                <span class="badge bg-danger bg-opacity-10 text-danger p-2">
                                                    <i class="bi bi-calendar-x me-1"></i>
                                                    {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-search fs-3 d-block mb-2"></i>
                                        Aucune donnée d'absence pour les critères sélectionnés
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Préparation des données pour les graphiques
        const employesData = @json(array_map(function($absence) {
            return $absence['employe']->prenom . ' ' . $absence['employe']->nom;
        }, $absencesData ?? []));
        
        const tauxAbsenteismeData = @json(array_map(function($absence) {
            return $absence['taux_absenteisme'];
        }, $absencesData ?? []));
        
        const joursAbsenceData = @json(array_map(function($absence) {
            return $absence['jours_absence'];
        }, $absencesData ?? []));
        
        // Couleurs pour le graphique
        const backgroundColors = [
            'rgba(220, 53, 69, 0.8)',
            'rgba(220, 53, 69, 0.7)',
            'rgba(220, 53, 69, 0.6)',
            'rgba(220, 53, 69, 0.5)',
            'rgba(220, 53, 69, 0.4)',
            'rgba(220, 53, 69, 0.3)',
            'rgba(220, 53, 69, 0.2)',
        ];
        
        // Créer le graphique d'absentéisme
        const ctx = document.getElementById('absenteismeChart').getContext('2d');
        const absenteismeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: employesData,
                datasets: [{
                    label: 'Taux d\'absentéisme (%)',
                    data: tauxAbsenteismeData,
                    backgroundColor: backgroundColors,
                    borderColor: 'rgba(220, 53, 69, 1)',
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
                                const label = context.dataset.label || '';
                                const value = context.raw;
                                const jours = joursAbsenceData[context.dataIndex];
                                return `${label}: ${value}% (${jours} jours)`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Taux d\'absentéisme (%)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        
        // Changement de type de graphique
        document.querySelectorAll('[data-chart-type]').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('[data-chart-type]').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
                
                const chartType = this.getAttribute('data-chart-type');
                absenteismeChart.config.type = chartType;
                
                // Ajuster les options selon le type de graphique
                if (chartType === 'pie') {
                    absenteismeChart.options.plugins.legend.display = true;
                    absenteismeChart.options.scales.y.display = false;
                } else {
                    absenteismeChart.options.plugins.legend.display = false;
                    absenteismeChart.options.scales.y.display = true;
                }
                
                absenteismeChart.update();
            });
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
</style>
@endpush 