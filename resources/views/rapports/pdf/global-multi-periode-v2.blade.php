@extends('rapports.pdf.layouts.master')

@section('title', 'Rapport Global de Présence – ' . ucfirst($periode))

@section('content')
<div class="container">
    <h1>Rapport Global de Présence – {{ $periodeLabel }}</h1>
    
    <div class="rapport-info">
        <p><strong>Période:</strong> {{ $periodeLabel }}</p>
        <p><strong>Nombre d'employés:</strong> {{ count($employes) }}</p>
        <p><strong>Date d'édition:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th class="employe-col">Employé</th>
                @foreach($jours as $jour)
                <th colspan="2" class="date-col">{{ \Carbon\Carbon::parse($jour)->format('d-M-Y') }}</th>
                @endforeach
            </tr>
            <tr>
                <th></th>
                @foreach($jours as $jour)
                <th class="ar-col">AR</th>
                <th class="dp-col">DP</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($employes as $employe)
            <tr>
                <td class="employe-col">{{ $employe->nom }} {{ $employe->prenom }}</td>
                @foreach($jours as $jour)
                    @php
                        $presence = $presences->where('employe_id', $employe->id)
                                            ->where('date', $jour)
                                            ->first();
                    @endphp
                    <td class="ar-col">
                        {{ $presence && $presence->heure_arrivee ? \Carbon\Carbon::parse($presence->heure_arrivee)->format('H:i') : '-' }}
                    </td>
                    <td class="dp-col">
                        {{ $presence && $presence->heure_depart ? \Carbon\Carbon::parse($presence->heure_depart)->format('H:i') : '-' }}
                    </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer-notes">
        <p><strong>Légende:</strong> AR: Heure d'arrivée | DP: Heure de départ | - : Aucun pointage</p>
        <p>Ce rapport présente uniquement les heures de pointage sans analyse ni calcul.</p>
    </div>
</div>
@endsection

@section('styles')
<style>
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 10pt;
        line-height: 1.3;
        color: #333;
    }
    
    h1 {
        font-size: 16pt;
        color: #2c3e50;
        margin-bottom: 15px;
        text-align: center;
    }
    
    .rapport-info {
        margin-bottom: 20px;
        font-size: 9pt;
    }
    
    .rapport-info p {
        margin: 3px 0;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 8pt;
    }
    
    table, th, td {
        border: 1px solid #ddd;
    }
    
    th, td {
        padding: 4px;
        text-align: center;
    }
    
    th {
        background-color: #f2f2f2;
        font-weight: bold;
    }
    
    .employe-col {
        text-align: left;
        width: 15%;
        font-weight: bold;
    }
    
    .date-col {
        font-size: 7pt;
        background-color: #e9ecef;
    }
    
    .ar-col, .dp-col {
        width: 5%;
    }
    
    .ar-col {
        background-color: #f8f9fa;
    }
    
    .footer-notes {
        font-size: 8pt;
        color: #666;
        border-top: 1px solid #eee;
        padding-top: 10px;
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
