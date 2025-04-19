@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-between mb-4">
        <div class="col-md-6">
            <h1>Calendrier des plannings</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('plannings.index') }}" class="btn btn-secondary me-2">
                <i class="bi bi-list"></i> Liste des plannings
            </a>
            <a href="{{ route('plannings.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nouveau planning
            </a>
        </div>
    </div>

    <!-- Sélecteur de mois -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('plannings.calendrier') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="mois" class="form-label">Mois</label>
                    <select class="form-select" id="mois" name="mois">
                        @php
                            $moisNoms = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                        @endphp
                        @foreach($moisNoms as $index => $nomMois)
                            <option value="{{ $index + 1 }}" {{ (int)$mois === $index + 1 ? 'selected' : '' }}>
                                {{ $nomMois }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="annee" class="form-label">Année</label>
                    <select class="form-select" id="annee" name="annee">
                        @php
                            $anneeActuelle = (int)date('Y');
                            $anneesDisponibles = range($anneeActuelle - 1, $anneeActuelle + 2);
                        @endphp
                        @foreach($anneesDisponibles as $anneeOption)
                            <option value="{{ $anneeOption }}" {{ (int)$annee === $anneeOption ? 'selected' : '' }}>
                                {{ $anneeOption }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Afficher</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Calendrier -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">{{ $dateDebut->format('F Y') }}</h3>
        </div>
        <div class="card-body p-0">
            @php
                // Jours de la semaine
                $weekDays = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                
                // Regrouper les plannings par employé
                $planningsParEmploye = $plannings->groupBy('employe_id');
                
                // Récupérer la liste des employés qui ont un planning ce mois-ci
                $employesIds = $planningsParEmploye->keys();
                $employesDuMois = $employes->whereIn('id', $employesIds);
                
                // Première date à afficher (début de la semaine du premier jour du mois)
                $startDisplay = $dateDebut->copy()->startOfWeek();
                // Dernière date à afficher (fin de la semaine du dernier jour du mois)
                $endDisplay = $dateFin->copy()->endOfWeek();
                
                // Nombre de jours à afficher
                $daysCount = $startDisplay->diffInDays($endDisplay) + 1;
                
                // Tableau des jours du mois
                $days = [];
                $currentDay = $startDisplay->copy();
                
                for ($i = 0; $i < $daysCount; $i++) {
                    $days[] = $currentDay->copy();
                    $currentDay->addDay();
                }
            @endphp
            
            <div class="table-responsive">
                <table class="table table-bordered calendar-table mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th style="width: 200px;">Employé</th>
                            @foreach($days as $day)
                                <th class="text-center {{ $day->isWeekend() ? 'bg-light' : '' }} {{ $day->isToday() ? 'bg-primary text-white' : '' }}" style="min-width: 40px;">
                                    <div>{{ $day->format('d') }}</div>
                                    <small>{{ substr($weekDays[$day->dayOfWeek == 0 ? 6 : $day->dayOfWeek - 1], 0, 1) }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employesDuMois as $employe)
                            <tr>
                                <td class="align-middle">
                                    <div class="fw-bold">{{ $employe->prenom }} {{ $employe->nom }}</div>
                                    <small class="text-muted">{{ $employe->poste->nom }}</small>
                                </td>
                                
                                @foreach($days as $day)
                                    @php
                                        $cellClass = '';
                                        $cellContent = '';
                                        $tooltip = '';
                                        
                                        // Trouver les plannings qui incluent ce jour
                                        $planningsEmploye = $planningsParEmploye->get($employe->id, collect());
                                        $planningDuJour = $planningsEmploye->first(function($planning) use ($day) {
                                            return $day->between($planning->date_debut, $planning->date_fin);
                                        });
                                        
                                        if ($planningDuJour) {
                                            // Déterminer si c'est un jour de travail
                                            $dayOfWeek = $day->dayOfWeek == 0 ? 7 : $day->dayOfWeek;
                                            $detailJour = $planningDuJour->details->firstWhere('jour', $dayOfWeek);
                                            
                                            if ($detailJour) {
                                                if ($detailJour->jour_repos) {
                                                    $cellClass = 'bg-light';
                                                    $cellContent = '<span class="badge bg-secondary">R</span>';
                                                    $tooltip = 'Repos';
                                                } elseif ($detailJour->jour_entier) {
                                                    $cellClass = 'bg-success bg-opacity-25';
                                                    $cellContent = '<span class="badge bg-success">J</span>';
                                                    $tooltip = 'Journée complète';
                                                } elseif ($detailJour->heure_debut && $detailJour->heure_fin) {
                                                    $cellClass = 'bg-primary bg-opacity-25';
                                                    $heureDebut = substr($detailJour->heure_debut, 0, 5);
                                                    $heureFin = substr($detailJour->heure_fin, 0, 5);
                                                    $cellContent = '<small>' . $heureDebut . '<br>' . $heureFin . '</small>';
                                                    $tooltip = $heureDebut . ' - ' . $heureFin;
                                                    
                                                    if ($detailJour->note) {
                                                        $tooltip .= ' - ' . $detailJour->note;
                                                    }
                                                }
                                            }
                                        }
                                        
                                        // Si c'est hors du mois actuel, on grise la cellule
                                        if (!$day->isSameMonth($dateDebut)) {
                                            $cellClass .= ' text-muted';
                                        }
                                        
                                        // Si c'est aujourd'hui, on met en évidence
                                        if ($day->isToday()) {
                                            $cellClass .= ' today';
                                        }
                                    @endphp
                                    
                                    <td class="text-center {{ $cellClass }}" data-bs-toggle="tooltip" title="{{ $tooltip }}">
                                        {!! $cellContent !!}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        
                        @if($employesDuMois->isEmpty())
                            <tr>
                                <td colspan="{{ count($days) + 1 }}" class="text-center py-5">
                                    <p class="text-muted">
                                        <i class="bi bi-calendar-x" style="font-size: 2rem;"></i><br>
                                        Aucun planning trouvé pour cette période
                                    </p>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-center">
                <div class="legend-item me-3">
                    <span class="badge bg-primary bg-opacity-25">&nbsp;</span>
                    <small>Horaire spécifique</small>
                </div>
                <div class="legend-item me-3">
                    <span class="badge bg-success bg-opacity-25">&nbsp;</span>
                    <small>Journée complète</small>
                </div>
                <div class="legend-item">
                    <span class="badge bg-light">&nbsp;</span>
                    <small>Repos</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-table th, .calendar-table td {
    vertical-align: middle;
    height: 50px;
    padding: 5px;
}

.calendar-table td.today {
    border: 2px solid #4361ee !important;
}

.legend-item {
    display: flex;
    align-items: center;
}

.legend-item .badge {
    width: 20px;
    height: 15px;
    margin-right: 5px;
}
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Activer les tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush
@endsection