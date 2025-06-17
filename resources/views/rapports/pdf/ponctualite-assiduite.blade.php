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

@section('title', 'Rapport Synthèse d\'Assiduité et de Ponctualité')

@section('content')
<div class="container">
    <h1>RAPPORT SYNTHESE D'ASSIDUITE ET DE PONCTUALITE</h1>
    
    <div class="rapport-info">
        <p><strong>PERIODE ALLANT DU :</strong> {{ $dateDebut ? (is_string($dateDebut) ? $dateDebut : $dateDebut->format('Y-m-d')) : '' }} AU {{ $dateFin ? (is_string($dateFin) ? $dateFin : $dateFin->format('Y-m-d')) : '' }}</p>
    </div>
    
    @if(count($statistiques) > 0)
        <div style="overflow-x: auto;">
        <table class="rapport-table">
            <thead>
                <tr class="header-main">
                    <th rowspan="3" class="numero-col">N°</th>
                    <th rowspan="3" class="noms-col">Noms et prenoms</th>
                    <th rowspan="3" class="grade-col">Grade</th>
                    <th rowspan="3" class="fonction-col">Fonction</th>
                    <th colspan="5" class="assiduite-header">Assiduité</th>
                    <th colspan="4" class="ponctualite-header">Ponctualité</th>
                    <th rowspan="3" class="taux-ponctualite-col">Taux de ponctualité</th>
                    <th rowspan="3" class="observations-col">Observations</th>
                </tr>
                <tr class="header-sub1">
                    <th rowspan="2" class="heures-hebdo">Heures<br>Hebdo</th>
                    <th rowspan="2" class="heures-mensuel">Heures<br>Mensuel</th>
                    <th rowspan="2" class="heures-faites">Heures<br>Faites</th>
                    <th rowspan="2" class="heures-absence">Heures<br>Absence</th>
                    <th rowspan="2" class="taux-assiduite">Taux<br>d'assiduité</th>
                    <th colspan="2" class="frequence-header">Fréquence</th>
                    <th rowspan="2" class="freq-faites">Freq.Faites</th>
                    <th rowspan="2" class="freq-naites">FréqNaites</th>
                </tr>
                <tr class="header-sub2">
                    <th class="freq-dues">Dues</th>
                    <th class="freq-mensuel">Mensuel</th>
                </tr>
            </thead>
            <tbody>
                @php $numeroGlobal = 1; @endphp
                @foreach($statistiques as $departementData)
                    @if($departementData['type'] === 'departement_header')
                        {{-- En-tête de département --}}
                        <tr class="departement-header">
                            <td colspan="15" class="departement-title">{{ $departementData['numero_departement'] }}. Département {{ strtoupper($departementData['nom_departement']) }}</td>
                        </tr>
                        {{-- Employés du département --}}
                        @foreach($departementData['employes'] as $stat)
                        <tr>
                            <td class="numero-col">{{ $numeroGlobal++ }}</td>
                            <td class="noms-col">{{ strtoupper($stat['employe_nom']) }} {{ ucwords(strtolower($stat['employe_prenom'])) }}</td>
                            <td class="grade-col">{{ $stat['grade'] }}</td>
                            <td class="fonction-col">{{ strtoupper($stat['fonction']) }}</td>
                            <td class="numeric-col">{{ $stat['jours_prevus'] }}</td>
                            <td class="numeric-col">{{ $stat['heures_prevues'] }}</td>
                            <td class="numeric-col">{{ $stat['heures_effectuees'] }}</td>
                            <td class="numeric-col">{{ $stat['heures_absence'] }}</td>
                            <td class="numeric-col">{{ number_format($stat['taux_assiduite'], 0) }}</td>
                            <td class="numeric-col">{{ $stat['frequence_hebdo'] }}</td>
                            <td class="numeric-col">{{ $stat['frequence_mensuelle'] }}</td>
                            <td class="numeric-col">{{ $stat['nombre_retards'] }}</td>
                            <td class="numeric-col">{{ $stat['frequence_naites'] }}</td>
                            <td class="numeric-col">{{ number_format($stat['taux_ponctualite'], 0) }}</td>
                            <td class="observations-col">{{ $stat['observation_rh'] }}</td>
                        </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <p>Aucune donnée disponible pour la période sélectionnée.</p>
    @endif
</div>
@endsection

@section('styles')
<style>
    /* Styles pour format A4 paysage */
    @page {
        size: A4 landscape;
        margin: 1cm 0.5cm;
    }
    
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 7pt;
        line-height: 1.2;
        color: #000;
        width: 100%;
        background: white;
    }
    
    h1 {
        font-size: 14pt;
        font-weight: bold;
        color: #000;
        margin-bottom: 10px;
        text-align: center;
        text-decoration: underline;
    }
    
    h2 {
        font-size: 10pt;
        font-weight: bold;
        color: #000;
        margin-top: 15px;
        margin-bottom: 8px;
        text-decoration: underline;
    }
    
    .rapport-info {
        margin-bottom: 15px;
        font-size: 9pt;
        text-align: center;
        font-weight: bold;
    }
    
    .rapport-info p {
        margin: 3px 0;
    }

    .rapport-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
        font-size: 6pt;
        border: 2px solid #000;
    }

    .rapport-table th,
    .rapport-table td {
        border: 1px solid #000;
        padding: 2px;
        text-align: center;
        vertical-align: middle;
        font-weight: normal;
    }

    .rapport-table thead th {
        background-color: #fff;
        font-weight: bold;
        font-size: 6pt;
    }

    /* En-têtes principaux */
    .header-main {
        font-weight: bold;
    }
    
    .assiduite-header {
        background-color: #f0f0f0;
    }
    
    .ponctualite-header {
        background-color: #f0f0f0;
    }
    
    .frequence-header {
        background-color: #f0f0f0;
    }

    /* Largeurs des colonnes */
    .numero-col {
        width: 3%;
        font-weight: bold;
        text-align: center;
    }

    .noms-col {
        width: 18%;
        text-align: left;
        padding-left: 4px;
        font-weight: bold;
    }

    .grade-col {
        width: 5%;
        font-weight: bold;
        text-align: center;
    }

    .fonction-col {
        width: 10%;
        font-weight: bold;
        text-align: center;
    }

    .numeric-col {
        width: 4%;
        text-align: center;
    }

    .heures-hebdo {
        width: 4%;
        text-align: center;
    }

    .heures-mensuel {
        width: 5%;
        text-align: center;
    }

    .heures-faites {
        width: 4%;
        text-align: center;
    }

    .taux-assiduite {
        width: 4%;
        text-align: center;
    }

    .freq-dues {
        width: 4%;
        text-align: center;
    }

    .freq-mensuel {
        width: 4%;
        text-align: center;
    }

    .freq-faites {
        width: 4%;
        text-align: center;
    }

    .freq-naites {
        width: 4%;
        text-align: center;
    }

    .taux-ponctualite-col {
        width: 6%;
        text-align: center;
    }

    .observations-col {
        width: 15%;
        text-align: left;
        padding-left: 4px;
    }

    /* Styles pour les en-têtes de département */
    .departement-header {
        background-color: #f8f9fa;
    }

    .departement-title {
        font-weight: bold;
        font-size: 8pt;
        text-align: center;
        padding: 4px 8px;
        background-color: #e9ecef;
        border: 2px solid #000;
    }

    .heures-absence {
        width: 5%;
        text-align: center;
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
            width: 29.7cm;
            height: 21cm;
            margin: 0;
            padding: 0;
        }
        
        .container {
            padding: 5px;
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
