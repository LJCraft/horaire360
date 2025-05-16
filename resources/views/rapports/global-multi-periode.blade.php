@extends('layouts.app')

@section('title', 'Rapport Global - Vue Multi-Période')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold text-primary">Rapport Global - Vue Multi-Période</h1>
            <p class="text-muted">Affichage des heures de pointage pour chaque employé sans calcul ni évaluation</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="{{ route('rapports.export-options', ['type' => 'global-multi-periode']) }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-pdf"></i> PDF
                </a>
                <a href="{{ route('rapports.export-options', ['type' => 'global-multi-periode', 'format' => 'excel']) }}" class="btn btn-outline-success">
                    <i class="bi bi-file-excel"></i> Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Sélecteur de période -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="btn-group" role="group" aria-label="Sélecteur de période">
                    <button type="button" class="btn btn-outline-primary periode-btn {{ $periode == 'jour' ? 'active' : '' }}" data-periode="jour">Jour</button>
                    <button type="button" class="btn btn-outline-primary periode-btn {{ $periode == 'semaine' ? 'active' : '' }}" data-periode="semaine">Semaine</button>
                    <button type="button" class="btn btn-outline-primary periode-btn {{ $periode == 'mois' ? 'active' : '' }}" data-periode="mois">Mois</button>
                    <button type="button" class="btn btn-outline-primary periode-btn {{ $periode == 'annee' ? 'active' : '' }}" data-periode="annee">Année</button>
                </div>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="periode-precedente" title="Période précédente">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <span class="fw-medium badge bg-primary text-white px-3 py-2" id="periode-actuelle">{{ $periodeLabel }}</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="periode-suivante" title="Période suivante">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="aujourdhui">
                        <i class="bi bi-calendar-check"></i> Aujourd'hui
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des pointages -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary"><i class="bi bi-clock-history me-2"></i>Heures de pointage</h5>
            <span class="badge bg-primary rounded-pill">{{ count($employes) }} employés</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 rapport-global-table">
                    <thead class="table-light">
                        <tr>
                            <th class="sticky-col">Nom & prénom</th>
                            @foreach($jours as $jour)
                            <th class="text-center date-header" colspan="2">{{ \Carbon\Carbon::parse($jour)->format('d-M-Y') }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            <th class="sticky-col"></th>
                            @foreach($jours as $jour)
                            <th class="text-center sub-header ar-header">AR</th>
                            <th class="text-center sub-header dp-header">DP</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employes as $employe)
                        <tr>
                            <td class="sticky-col">
                                <div class="d-flex align-items-center">
                                    @if($employe->photo_profil && file_exists(public_path('storage/photos/' . $employe->photo_profil)))
                                        <img src="{{ asset('storage/photos/' . $employe->photo_profil) }}" 
                                            alt="Photo de {{ $employe->prenom }}" 
                                            class="rounded-circle me-2" 
                                            style="width: 32px; height: 32px; object-fit: cover;">
                                    @else
                                        <div class="avatar-initials bg-primary bg-opacity-10 text-primary me-2">
                                            {{ strtoupper(substr($employe->prenom ?? '', 0, 1)) }}{{ strtoupper(substr($employe->nom ?? '', 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <span class="fw-medium">{{ $employe->prenom }} {{ $employe->nom }}</span>
                                    </div>
                                </div>
                            </td>
                            @foreach($jours as $jour)
                                @php
                                    $presence = $presences->where('employe_id', $employe->id)
                                                        ->where('date', $jour)
                                                        ->first();
                                @endphp
                                <td class="text-center pointage-cell">
                                    @if($presence && $presence->heure_arrivee)
                                        <span class="pointage-time">{{ \Carbon\Carbon::parse($presence->heure_arrivee)->format('H:i') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center pointage-cell">
                                    @if($presence && $presence->heure_depart)
                                        <span class="pointage-time">{{ \Carbon\Carbon::parse($presence->heure_depart)->format('H:i') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-muted small">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="me-3"><strong>AR</strong> = Heure d'arrivée</span>
                    <span><strong>DP</strong> = Heure de départ</span>
                </div>
                <div>
                    <i class="bi bi-info-circle me-1"></i> Affichage brut des heures de pointage sans calcul ni évaluation
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Styles pour le tableau responsive */
    .rapport-global-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .sticky-col {
        position: sticky;
        left: 0;
        background-color: white;
        z-index: 1;
        border-right: 1px solid #dee2e6;
        min-width: 220px;
        box-shadow: 2px 0 5px rgba(0,0,0,0.05);
    }
    
    .table-responsive {
        overflow-x: auto;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        max-height: 70vh;
        position: relative;
    }
    
    /* Styles pour les boutons de période */
    .periode-btn.active {
        background-color: #4e73df;
        color: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Styles pour les cellules du tableau */
    .rapport-global-table th, .rapport-global-table td {
        padding: 0.75rem;
        vertical-align: middle;
    }
    
    .rapport-global-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
    }
    
    .rapport-global-table thead tr:first-child th {
        top: 0;
    }
    
    .rapport-global-table thead tr:nth-child(2) th {
        top: 49px; /* Ajustez cette valeur selon la hauteur de votre première ligne d'en-tête */
    }
    
    .date-header {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 2px solid #4e73df;
        color: #4e73df;
    }
    
    .sub-header {
        font-size: 0.85rem;
        background-color: #f1f3f9;
    }
    
    .ar-header {
        color: #2c7be5;
    }
    
    .dp-header {
        color: #e63757;
    }
    
    .pointage-cell {
        transition: background-color 0.2s;
    }
    
    .pointage-cell:hover {
        background-color: #f8f9fa;
    }
    
    .pointage-time {
        font-weight: 500;
        font-family: 'Courier New', monospace;
    }
    
    /* Style pour l'avatar avec initiales */
    .avatar-initials {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: bold;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Styles responsifs */
    @media (max-width: 768px) {
        .sticky-col {
            min-width: 180px;
        }
        
        .rapport-global-table th, .rapport-global-table td {
            padding: 0.5rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variables pour la gestion de la période
        let periode = '{{ $periode }}';
        let dateDebut = '{{ $dateDebut }}';
        let dateFin = '{{ $dateFin }}';
        
        // Gestion des boutons de période
        const periodeBtns = document.querySelectorAll('.periode-btn');
        periodeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                periode = this.dataset.periode;
                chargerRapport();
            });
        });
        
        // Gestion des boutons de navigation
        document.getElementById('periode-precedente').addEventListener('click', function() {
            naviguerPeriode('precedente');
        });
        
        document.getElementById('periode-suivante').addEventListener('click', function() {
            naviguerPeriode('suivante');
        });
        
        document.getElementById('aujourdhui').addEventListener('click', function() {
            dateDebut = '{{ \Carbon\Carbon::now()->format("Y-m-d") }}';
            dateFin = dateDebut;
            chargerRapport();
        });
        
        // Fonction pour naviguer entre les périodes
        function naviguerPeriode(direction) {
            const date = new Date(dateDebut);
            
            if (periode === 'jour') {
                date.setDate(date.getDate() + (direction === 'precedente' ? -1 : 1));
            } else if (periode === 'semaine') {
                date.setDate(date.getDate() + (direction === 'precedente' ? -7 : 7));
            } else if (periode === 'mois') {
                date.setMonth(date.getMonth() + (direction === 'precedente' ? -1 : 1));
            } else if (periode === 'annee') {
                date.setFullYear(date.getFullYear() + (direction === 'precedente' ? -1 : 1));
            }
            
            dateDebut = date.toISOString().split('T')[0];
            chargerRapport();
        }
        
        // Fonction pour charger le rapport avec les nouveaux paramètres
        function chargerRapport() {
            const url = new URL('{{ route("rapports.global-multi-periode") }}', window.location.origin);
            url.searchParams.append('periode', periode);
            url.searchParams.append('date_debut', dateDebut);
            
            window.location.href = url.toString();
        }
    });
</script>
@endpush
