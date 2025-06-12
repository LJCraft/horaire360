<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Liste des employés</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h1 {
            font-size: 18px;
            color: #4361EE;
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background-color: #4361EE;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 8px;
        }
        td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            color: #666;
        }
        .badge-success {
            background-color: #2FC18C;
            color: white;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
        .badge-danger {
            background-color: #F45C5D;
            color: white;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <h1>Liste des employés - Horaire360</h1>
    
    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Poste</th>
                <th>Date d'embauche</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employes as $employe)
                <tr>
                    <td>{{ $employe->matricule }}</td>
                    <td>{{ $employe->prenom }} {{ $employe->nom }}</td>
                    <td>{{ $employe->email }}</td>
                    <td>{{ $employe->poste->nom }}</td>
                    <td>{{ \Carbon\Carbon::parse($employe->date_embauche)->format('d/m/Y') }}</td>
                    <td>
                        @if($employe->statut === 'actif')
                            <span class="badge-success">actif</span>
                        @else
                            <span class="badge-danger">inactif</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        Document généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html> 