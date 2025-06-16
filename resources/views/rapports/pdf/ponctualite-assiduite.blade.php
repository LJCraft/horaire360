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

@extends('rapports.pdf.layouts.master')

@section('title', 'Rapport Ponctualité & Assiduité – ' . ucfirst($periode ?? 'mensuel'))

@section('content')
<div class="container">
    <h1>Rapport Ponctualité & Assiduité – {{ $periodeLabel ?? 'Période mensuelle' }}</h1>
    
    <div class="rapport-info">
        <p><strong>Période:</strong> {{ $periodeLabel ?? ($dateDebut ? date('d/m/Y', strtotime($dateDebut)) : 'Début') . ' - ' . ($dateFin ? date('d/m/Y', strtotime($dateFin)) : 'Aujourd\'hui') }}</p>
    </div>
    
    <h2>Statistiques individuelles</h2>
    
    @if(count($statistiques) > 0)
        <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th class="employe-col">Employé</th>
                    <th>Département</th>
                    <th>Grade</th>
                    <th>Poste</th>
                    <th class="numeric-col">Jours prévus</th>
                    <th class="numeric-col">Jours travaillés</th>
                    <th class="numeric-col">Retards</th>
                    <th class="numeric-col">Départs anticipés</th>
                    <th class="numeric-col">Heures prévues</th>
                    <th class="numeric-col">Heures travaillées</th>
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
                    <td>{{ 
                        $stat->employe->departement ?? 
                        ($stat->employe->poste && $stat->employe->poste->departement ? $stat->employe->poste->departement : 'Non défini') 
                    }}</td>
                    <td>{{ 
                        is_object($stat->employe->grade) ? $stat->employe->grade->nom : 
                        (is_string($stat->employe->grade) ? $stat->employe->grade : 'Non défini') 
                    }}</td>
                    <td>{{ 
                        is_object($stat->employe->poste) ? $stat->employe->poste->nom : 
                        (is_string($stat->employe->poste) ? $stat->employe->poste : 'Non défini') 
                    }}</td>
                    <td class="numeric-col">{{ $stat->jours_prevus ?? '' }}</td>
                    <td class="numeric-col">{{ $stat->jours_travailles }}</td>
                    <td class="numeric-col">{{ $stat->nombre_retards ?? 0 }}</td>
                    <td class="numeric-col">{{ $stat->nombre_departs_anticipes ?? 0 }}</td>
                    <td class="numeric-col">{{ $stat->heures_prevues ?? '' }}</td>
                    <td class="numeric-col">{{ $stat->heures_travaillees ?? $stat->heures_effectuees ?? 0 }}</td>
                    <td class="numeric-col">{{ $stat->heures_absence ?? (($stat->heures_prevues ?? 0) - ($stat->heures_travaillees ?? $stat->heures_effectuees ?? 0)) }}</td>
                    <td class="numeric-col taux-col {{ getTauxClass($stat->taux_ponctualite) }}">{{ number_format($stat->taux_ponctualite, 1) }}%</td>
                    <td class="numeric-col taux-col {{ getTauxClass($stat->taux_assiduite) }}">{{ number_format($stat->taux_assiduite, 1) }}%</td>
                    <td class="observation-col">
                        {{-- === CONTRAINTE : Vider systématiquement la colonne Observation RH === --}}
                        {{ $stat->observation_rh ?? '' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <p>Aucune donnée disponible pour la période sélectionnée.</p>
    @endif
    
    <h2>Statistiques globales</h2>
    
    <div class="stats-container">
        <div style="overflow-x: auto;">
        <table>
            <tr>
                @php
                    $totalEmployes = count($statistiques);
                    
                    // Calculer le total des retards en vérifiant si la propriété existe
                    $totalRetards = 0;
                    foreach ($statistiques as $stat) {
                        if (isset($stat->nombre_retards)) {
                            $totalRetards += $stat->nombre_retards;
                        }
                    }
                    
                    // Calculer le total des départs anticipés en vérifiant si la propriété existe
                    $totalDepartsAnticipes = 0;
                    foreach ($statistiques as $stat) {
                        if (isset($stat->nombre_departs_anticipes)) {
                            $totalDepartsAnticipes += $stat->nombre_departs_anticipes;
                        }
                    }
                    
                    // Calculer les moyennes des taux
                    $tauxPonctualiteMoyen = $totalEmployes > 0 ? round($statistiques->sum('taux_ponctualite') / $totalEmployes, 1) : 0;
                    $tauxAssiduiteMoyen = $totalEmployes > 0 ? round($statistiques->sum('taux_assiduite') / $totalEmployes, 1) : 0;
                @endphp
                <th>Nombre d'employés</th>
                <td>{{ $totalEmployes }}</td>
                <th>Total retards</th>
                <td>{{ $totalRetards }}</td>
                <th>Total départs anticipés</th>
                <td>{{ $totalDepartsAnticipes }}</td>
                <th>Taux moyen de ponctualité</th>
                <td class="taux-col {{ getTauxClass($tauxPonctualiteMoyen) }}">{{ $tauxPonctualiteMoyen }}%</td>
                <th>Taux moyen d'assiduité</th>
                <td class="taux-col {{ getTauxClass($tauxAssiduiteMoyen) }}">{{ $tauxAssiduiteMoyen }}%</td>
            </tr>
        </table>
        </div>
    </div>
    
    <div class="footer-notes">
        <p><strong>Légende des taux:</strong></p>
        <ul>
            <li>Excellent (≥ 90%): Performance optimale</li>
            <li>Bon (≥ 80%): Performance satisfaisante</li>
            <li>Moyen (≥ 70%): Amélioration nécessaire</li>
            <li>Faible (< 70%): Performance insuffisante</li>
        </ul>
        
        <p><strong>Notes explicatives:</strong></p>
        <ul>
            <li>Le taux de ponctualité est calculé comme le pourcentage de jours sans retard par rapport au nombre total de jours travaillés.</li>
            <li>Le taux d'assiduité est calculé comme le pourcentage d'heures faites par rapport aux heures prévues.</li>
        </ul>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Styles pour format A4 */
    @page {
        size: A4 portrait;
        margin: 1.5cm 1cm;
    }
    
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 9pt;
        line-height: 1.3;
        color: #333;
        width: 100%;
        background: white;
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
        table-layout: fixed;
        max-width: 100%;
        overflow-wrap: break-word;
    }

    table, th, td {
        border: 1px solid #ddd;
    }

    th, td {
        padding: 2px;
        text-align: left;
        word-wrap: break-word;
        overflow-wrap: break-word;
        max-width: 100%;
    }

    th {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    .employe-col {
        width: 10%;
        font-weight: bold;
    }

    .numeric-col {
        text-align: center;
        width: 5%;
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
        width: 12%;
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
    
    /* Styles pour impression */
    @media print {
        body {
            width: 21cm;
            height: 29.7cm;
            margin: 0;
            padding: 0;
        }
        
        .container {
            padding: 10px;
        }
        
        table {
            page-break-inside: auto;
        }
        
        thead {
            display: table-header-group;
        }
        
        tfoot {
            display: table-footer-group;
        }
    }
</style>
@endsection
