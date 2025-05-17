@extends('layouts.app')

@section('title', 'Rapport Global de Présence – ' . ucfirst($periode))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold text-primary"><i class="bi bi-clock-history me-2"></i>Rapport Global de Présence</h1>
            <p class="text-muted">Affichage des heures de pointage pour chaque employé sans calcul ni évaluation</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="{{ route('rapports.export-pdf', ['type' => 'global-multi-periode-v2']) }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-pdf"></i> PDF
                </a>
                <a href="{{ route('rapports.export-excel', ['type' => 'global-multi-periode-v2']) }}" class="btn btn-outline-success">
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
            <h5 class="mb-0 text-primary"><i class="bi bi-table me-2"></i>Heures de pointage</h5>
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
                                <td class="text-center">
                                    {{ $presence && $presence->heure_arrivee ? \Carbon\Carbon::parse($presence->heure_arrivee)->format('H:i') : '-' }}
                                </td>
                                <td class="text-center">
                                    {{ $presence && $presence->heure_depart ? \Carbon\Carbon::parse($presence->heure_depart)->format('H:i') : '-' }}
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
                    <span class="me-3"><i class="bi bi-info-circle me-1"></i> AR: Heure d'arrivée | DP: Heure de départ</span>
                </div>
                <div>
                    <span>Dernière mise à jour: {{ now()->format('d/m/Y H:i') }}</span>
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
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .rapport-global-table th,
    .rapport-global-table td {
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
    
    /* Styles pour les en-têtes de date */
    .date-header {
        background-color: #f1f5f9;
        font-weight: 500;
        border-bottom: 1px solid #dee2e6;
    }
    
    /* Styles pour les sous-en-têtes */
    .sub-header {
        background-color: #f8f9fa;
        font-weight: normal;
        font-size: 0.85rem;
    }
    
    .ar-header {
        border-right: 1px dashed #dee2e6;
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
    
    /* Styles pour l'impression */
    @media print {
        body {
            font-size: 10pt;
            color: #000;
        }
        
        .container-fluid {
            width: 100%;
            padding: 0;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .card-header, .card-footer {
            background-color: #fff !important;
        }
        
        .sticky-col {
            position: static;
            background-color: #fff !important;
            border-right: 1px solid #000;
        }
        
        .date-header, .sub-header {
            background-color: #f9f9f9 !important;
            color: #000 !important;
        }
        
        .table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        
        .table th, .table td {
            border: 1px solid #ddd !important;
            padding: 4px !important;
        }
        
        .avatar-initials {
            border: 1px solid #ddd;
            color: #000 !important;
            background-color: #f9f9f9 !important;
        }
        
        .btn-group, .btn, #periode-precedente, #periode-suivante, #aujourdhui {
            display: none !important;
        }
        
        h1 {
            font-size: 18pt !important;
            margin-bottom: 10px !important;
        }
        
        h5 {
            font-size: 12pt !important;
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variables pour la gestion de la période
        let periode = '{{ $periode }}';
        let dateDebut = '{{ $dateDebut }}';
        
        // Gestion des boutons de période
        const periodeBtns = document.querySelectorAll('.periode-btn');
        periodeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const nouvellePeriode = this.dataset.periode;
                window.location.href = `{{ route('rapports.global-multi-periode-v2') }}?periode=${nouvellePeriode}&date=${dateDebut}`;
            });
        });
        
        // Gestion de la navigation entre périodes
        document.getElementById('periode-precedente').addEventListener('click', function() {
            naviguerPeriode('precedente');
        });
        
        document.getElementById('periode-suivante').addEventListener('click', function() {
            naviguerPeriode('suivante');
        });
        
        document.getElementById('aujourdhui').addEventListener('click', function() {
            window.location.href = `{{ route('rapports.global-multi-periode-v2') }}?periode=${periode}`;
        });
        
        function naviguerPeriode(direction) {
            let date = new Date(dateDebut);
            
            if (periode === 'jour') {
                date.setDate(date.getDate() + (direction === 'precedente' ? -1 : 1));
            } else if (periode === 'semaine') {
                date.setDate(date.getDate() + (direction === 'precedente' ? -7 : 7));
            } else if (periode === 'mois') {
                date.setMonth(date.getMonth() + (direction === 'precedente' ? -1 : 1));
            }
            
            const nouvelleDate = date.toISOString().split('T')[0];
            window.location.href = `{{ route('rapports.global-multi-periode-v2') }}?periode=${periode}&date=${nouvelleDate}`;
        }
    });
</script>
@endpush
