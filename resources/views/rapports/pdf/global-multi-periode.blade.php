<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $titre }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #4e73df;
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 30px;
        }
        .text-success {
            color: #28a745;
        }
        .text-warning {
            color: #ffc107;
        }
        .text-danger {
            color: #dc3545;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .employee-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .employee-header {
            background-color: #eef2ff;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .employee-name {
            font-weight: bold;
            font-size: 14px;
            color: #4e73df;
        }
        .employee-info {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titre }}</h1>
        <p>Période : {{ $dateDebut ? date('d/m/Y', strtotime($dateDebut)) : 'Début' }} - {{ $dateFin ? date('d/m/Y', strtotime($dateFin)) : 'Aujourd\'hui' }}</p>
    </div>
    
    @foreach($employes as $employe)
    <div class="employee-section">
        <div class="employee-header">
            <div class="employee-name">{{ $employe->nom }} {{ $employe->prenom }}</div>
            <div class="employee-info">
                Service: {{ $employe->service ? $employe->service->nom : 'Non assigné' }} | 
                Poste: {{ $employe->poste ? $employe->poste->titre : 'Non assigné' }}
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Arrivée</th>
                    <th>Départ</th>
                    <th>Heures travaillées</th>
                    <th>Retard</th>
                    <th>Départ anticipé</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employe->presences as $presence)
                <tr>
                    <td>{{ date('d/m/Y', strtotime($presence->date)) }}</td>
                    <td>{{ $presence->heure_arrivee }}</td>
                    <td>{{ $presence->heure_depart ?: 'N/A' }}</td>
                    <td class="text-right">{{ $presence->heures_travaillees ?: 'N/A' }}</td>
                    <td class="text-center @if($presence->retard) text-danger @endif">
                        {{ $presence->retard ? 'Oui' : 'Non' }}
                    </td>
                    <td class="text-center @if($presence->depart_anticipe) text-danger @endif">
                        {{ $presence->depart_anticipe ? 'Oui' : 'Non' }}
                    </td>
                    <td>{{ $presence->statut }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">Aucun pointage trouvé pour cette période</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endforeach
    
    <div class="footer">
        <p>Document généré le {{ date('d/m/Y à H:i') }} | Horaire360 - Gestion intelligente des présences</p>
    </div>
</body>
</html>
