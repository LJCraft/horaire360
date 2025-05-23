<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Export des pointages</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        header {
            padding: 10px 20px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 24px;
            margin: 0 0 10px 0;
            color: #2980b9;
        }
        h2 {
            font-size: 18px;
            margin: 15px 0 10px 0;
            color: #3498db;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .subtitle {
            font-size: 14px;
            color: #777;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background-color: #f8f9fa;
            padding: 8px;
            border: 1px solid #ddd;
            font-weight: bold;
            text-align: left;
            font-size: 12px;
        }
        table td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 12px;
        }
        .table-striped tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .text-center {
            text-align: center;
        }
        .text-danger {
            color: #e74c3c;
        }
        .text-warning {
            color: #f39c12;
        }
        .text-success {
            color: #27ae60;
        }
        .badge {
            display: inline-block;
            padding: 3px 7px;
            font-size: 10px;
            border-radius: 10px;
            color: #fff;
        }
        .badge-success {
            background-color: #27ae60;
        }
        .badge-warning {
            background-color: #f39c12;
            color: #000;
        }
        .badge-danger {
            background-color: #e74c3c;
        }
        .badge-secondary {
            background-color: #95a5a6;
        }
        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #777;
            padding: 10px 0;
            border-top: 1px solid #ddd;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <header>
        <div style="float: left; width: 50%;">
            <h1>Export des pointages</h1>
            <div class="subtitle">Rapport détaillé des présences</div>
        </div>
        <div style="float: right; width: 50%; text-align: right;">
            <p style="margin: 0; font-size: 12px;">
                <strong>Horaire360</strong><br>
                Date d'édition: {{ now()->format('d/m/Y à H:i') }}<br>
                Période: Toutes les dates
            </p>
        </div>
        <div style="clear: both;"></div>
    </header>
    
    <div class="container">
        <!-- Statistiques -->
        <div style="margin-bottom: 20px;">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 25%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                        <h3 style="margin: 0;">{{ $presences->count() }}</h3>
                        <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Présences totales</p>
                    </td>
                    <td style="width: 25%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                        <h3 style="margin: 0;" class="text-warning">{{ $presences->where('retard', true)->count() }}</h3>
                        <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Retards</p>
                    </td>
                    <td style="width: 25%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                        <h3 style="margin: 0;" class="text-danger">{{ $presences->where('depart_anticipe', true)->count() }}</h3>
                        <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Départs anticipés</p>
                    </td>
                    <td style="width: 25%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                        @php
                            $totalPresences = $presences->count();
                            $totalAnomaly = $presences->where('retard', true)->count() + $presences->where('depart_anticipe', true)->count();
                            $pourcentageAssiduite = ($totalPresences > 0) ? round(100 - (($totalAnomaly / $totalPresences) * 100), 1) : 0;
                        @endphp
                        <h3 style="margin: 0;" class="text-success">{{ $pourcentageAssiduite }}%</h3>
                        <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Taux d'assiduité</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <h2>Liste détaillée des présences</h2>
        <table class="table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employé</th>
                    <th>Poste</th>
                    <th>Arrivée</th>
                    <th>Départ</th>
                    <th class="text-center">Durée</th>
                    <th class="text-center">Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($presences as $presence)
                    <tr>
                        <td>{{ Carbon\Carbon::parse($presence->date)->format('d/m/Y') }}</td>
                        <td>{{ $presence->employe->prenom }} {{ $presence->employe->nom }}</td>
                        <td>{{ $presence->employe->poste->nom }}</td>
                        <td>{{ Carbon\Carbon::parse($presence->heure_arrivee)->format('H:i') }}</td>
                        <td>{{ $presence->heure_depart ? Carbon\Carbon::parse($presence->heure_depart)->format('H:i') : '-' }}</td>
                        <td class="text-center">
                            @if($presence->heure_depart)
                                @php
                                    $debut = Carbon\Carbon::parse($presence->heure_arrivee);
                                    $fin = Carbon\Carbon::parse($presence->heure_depart);
                                    if ($fin < $debut) {
                                        $fin->addDay();
                                    }
                                    $duree = $debut->diff($fin);
                                @endphp
                                {{ $duree->format('%H:%I') }}
                            @else
                                <span class="badge badge-secondary">En cours</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($presence->retard)
                                <span class="badge badge-warning">Retard</span>
                            @elseif($presence->depart_anticipe)
                                <span class="badge badge-danger">Départ anticipé</span>
                            @else
                                <span class="badge badge-success">Conforme</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Aucune présence trouvée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="page-break"></div>
        
        <h2>Notes et informations</h2>
        <ul>
            <li>Les retards sont définis comme un pointage effectué après l'heure prévue d'arrivée (tolérance de 10 minutes).</li>
            <li>Les départs anticipés sont définis comme un pointage de sortie effectué avant l'heure prévue de départ (tolérance de 10 minutes).</li>
            <li>Le taux d'assiduité correspond au pourcentage de présences sans retard ni départ anticipé.</li>
            <li>Ce rapport a été généré le {{ Carbon\Carbon::now()->format('d/m/Y à H:i') }}.</li>
        </ul>
    </div>

    <footer>
        <p>Rapport généré par Horaire360 - Système de gestion des plannings et présences</p>
    </footer>
    
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} / {PAGE_COUNT}";
            $size = 10;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) - 20;
            $y = $pdf->get_height() - 20;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html> 