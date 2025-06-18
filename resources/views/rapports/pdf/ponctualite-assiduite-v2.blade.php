@extends('rapports.pdf.layouts.master')

@section('title', 'Rapport Ponctualité & Assiduité – ' . ucfirst($periode))

@section('content')
<div class="container">
    <h1>Rapport Ponctualité & Assiduité – {{ $periodeLabel }}</h1>
    
    <div class="rapport-info">
        <p><strong>Période:</strong> {{ $periodeLabel }}</p>
        <p><strong>Nombre d'employés:</strong> {{ count($statistiques) }}</p>
        <p><strong>Date d'édition:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th class="employe-col">Employé</th>
                <th>Département</th>
                <th>Grade</th>
                <th>Poste</th>
                <th class="numeric-col">Jours prévus</th>
                <th class="numeric-col">Jours réalisés</th>
                <th class="numeric-col">Heures prévues</th>
                <th class="numeric-col">Heures faites</th>
                <th class="numeric-col">Heures absence</th>
                <th class="numeric-col">Taux ponctualité</th>
                <th class="numeric-col">Taux assiduité</th>
                <th>Observation RH</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statistiques as $stat)
            <tr>
                <td class="employe-col">{{ $stat->employe->nom }} {{ $stat->employe->prenom }}</td>
                <td>{{ $stat->employe->poste ? $stat->employe->poste->departement : 'Non défini' }}</td>
                <td>{{ $stat->employe->grade ? $stat->employe->grade->nom : 'Non défini' }}</td>
                <td>{{ $stat->employe->poste ? $stat->employe->poste->nom : 'Non défini' }}</td>
                <td class="numeric-col">{{ $stat->jours_prevus ?? '' }}</td>
                <td class="numeric-col">{{ $stat->jours_realises }}</td>
                <td class="numeric-col">{{ $stat->heures_prevues ?? '' }}</td>
                <td class="numeric-col">{{ $stat->heures_faites }}</td>
                <td class="numeric-col">{{ $stat->heures_absence }}</td>
                <td class="numeric-col taux-col {{ getTauxClass($stat->taux_ponctualite) }}">
                    {{ number_format($stat->taux_ponctualite, 1) }}%
                </td>
                <td class="numeric-col taux-col {{ getTauxClass($stat->taux_assiduite) }}">
                    {{ number_format($stat->taux_assiduite, 1) }}%
                </td>
                <td class="observation-col">
                    {{-- === CONTRAINTE : Vider systématiquement la colonne Observation RH === --}}
                    {{ $stat->observation_rh ?? '' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="summary">
        <h2>Résumé des indicateurs</h2>
        <div class="summary-stats">
            @php
                $tauxPonctualiteMoyen = $statistiques->avg('taux_ponctualite');
                $tauxAssiduiteMoyen = $statistiques->avg('taux_assiduite');
                // Filtrer les valeurs null avant la somme selon la règle métier
                $totalJoursPrevus = $statistiques->whereNotNull('jours_prevus')->sum('jours_prevus');
                $totalJoursRealises = $statistiques->sum('jours_realises');
                $totalHeuresPrevues = $statistiques->whereNotNull('heures_prevues')->sum('heures_prevues');
                $totalHeuresFaites = $statistiques->sum('heures_faites');
                $totalHeuresAbsence = $statistiques->sum('heures_absence');
            @endphp
            
            <div class="summary-item">
                <span class="summary-label">Taux moyen de ponctualité:</span>
                <span class="summary-value {{ getTauxClass($tauxPonctualiteMoyen) }}">{{ number_format($tauxPonctualiteMoyen, 1) }}%</span>
            </div>
            
            <div class="summary-item">
                <span class="summary-label">Taux moyen d'assiduité:</span>
                <span class="summary-value {{ getTauxClass($tauxAssiduiteMoyen) }}">{{ number_format($tauxAssiduiteMoyen, 1) }}%</span>
            </div>
            
            <div class="summary-item">
                <span class="summary-label">Total jours prévus:</span>
                <span class="summary-value">{{ $totalJoursPrevus }}</span>
            </div>
            
            <div class="summary-item">
                <span class="summary-label">Total jours réalisés:</span>
                <span class="summary-value">{{ $totalJoursRealises }}</span>
            </div>
            
            <div class="summary-item">
                <span class="summary-label">Total heures prévues:</span>
                <span class="summary-value">{{ $totalHeuresPrevues }}</span>
            </div>
            
            <div class="summary-item">
                <span class="summary-label">Total heures faites:</span>
                <span class="summary-value">{{ $totalHeuresFaites }}</span>
            </div>
            
            <div class="summary-item">
                <span class="summary-label">Total heures d'absence:</span>
                <span class="summary-value">{{ $totalHeuresAbsence }}</span>
            </div>
        </div>
    </div>
    
    <div class="footer-notes">
        <p><strong>Légende des taux:</strong></p>
        <ul>
            <li>Excellent (≥ 90%): Performance optimale</li>
            <li>Bon (≥ 80%): Performance satisfaisante</li>
            <li>Moyen (≥ 70%): Amélioration nécessaire</li>
            <li>Faible (< 70%): Intervention requise</li>
        </ul>
        <p><strong>Notes:</strong></p>
        <ul>
            <li>Le taux de ponctualité est calculé comme le pourcentage de jours sans retard par rapport au nombre total de jours travaillés.</li>
            <li>Le taux d'assiduité est calculé comme le pourcentage d'heures faites par rapport aux heures prévues.</li>
        </ul>
    </div>
</div>
@endsection

@section('styles')
<style>
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 9pt;
        line-height: 1.3;
        color: #333;
    }
    
    h1 {
        font-size: 16pt;
        color: #2c3e50;
        margin-bottom: 15px;
        text-align: center;
    }
    
    h2 {
        font-size: 12pt;
        color: #2c3e50;
        margin-top: 20px;
        margin-bottom: 10px;
    }
    
    .rapport-info {
        margin-bottom: 15px;
        font-size: 9pt;
    }
    
    .rapport-info p {
        margin: 3px 0;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
        font-size: 7pt;
    }
    
    table, th, td {
        border: 1px solid #ddd;
    }
    
    th, td {
        padding: 3px;
        text-align: left;
    }
    
    th {
        background-color: #f2f2f2;
        font-weight: bold;
    }
    
    .employe-col {
        width: 12%;
        font-weight: bold;
    }
    
    .numeric-col {
        text-align: center;
        width: 6%;
    }
    
    .taux-col {
        font-weight: bold;
    }
    
    .taux-col.excellent {
        background-color: #d4edda;
        color: #155724;
    }
    
    .taux-col.bon {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    
    .taux-col.moyen {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .taux-col.faible {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .observation-col {
        width: 15%;
        font-size: 6pt;
    }
    
    .summary {
        margin-top: 20px;
        border: 1px solid #ddd;
        padding: 10px;
        background-color: #f9f9f9;
    }
    
    .summary-stats {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }
    
    .summary-item {
        width: 48%;
        margin-bottom: 5px;
    }
    
    .summary-label {
        font-weight: bold;
    }
    
    .summary-value {
        margin-left: 5px;
    }
    
    .summary-value.excellent {
        color: #155724;
        font-weight: bold;
    }
    
    .summary-value.bon {
        color: #0c5460;
        font-weight: bold;
    }
    
    .summary-value.moyen {
        color: #856404;
        font-weight: bold;
    }
    
    .summary-value.faible {
        color: #721c24;
        font-weight: bold;
    }
    
    .footer-notes {
        font-size: 7pt;
        color: #666;
        border-top: 1px solid #eee;
        padding-top: 10px;
        margin-top: 20px;
    }
    
    .footer-notes ul {
        margin: 5px 0;
        padding-left: 20px;
    }
    
    .footer-notes li {
        margin-bottom: 2px;
    }
    
    /* Gestion des sauts de page */
    .page-break {
        page-break-after: always;
    }
    
    /* Pour éviter les sauts de page au milieu des lignes */
    tr {
        page-break-inside: avoid;
    }
</style>
@endsection

@php
// Fonction helper pour déterminer la classe CSS en fonction du taux
function getTauxClass($taux) {
    if ($taux >= 90) {
        return 'excellent';
    } elseif ($taux >= 80) {
        return 'bon';
    } elseif ($taux >= 70) {
        return 'moyen';
    } else {
        return 'faible';
    }
}
@endphp
