<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $titre }}</title>
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
        .logo {
            height: 50px;
            margin-bottom: 10px;
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
        h3 {
            font-size: 16px;
            margin: 10px 0;
            color: #555;
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
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .stat-box {
            width: 22%;
            margin-right: 3%;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            text-align: center;
        }
        .stat-box h3 {
            margin: 0;
            font-size: 24px;
            color: #2980b9;
        }
        .stat-box p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #777;
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
        .text-info {
            color: #3498db;
        }
        .page-break {
            page-break-after: always;
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
        .footer-page {
            font-size: 10px;
            text-align: right;
            margin-top: 10px;
            color: #777;
        }
    </style>
</head>
<body>
    <header>
        <div style="float: left; width: 50%;">
            <h1>{{ $titre }}</h1>
            <div class="subtitle">{{ $sousTitre }}</div>
        </div>
        <div style="float: right; width: 50%; text-align: right;">
            <p style="margin: 0; font-size: 12px;">
                <strong>Horaire360</strong><br>
                Date d'édition: {{ now()->format('d/m/Y à H:i') }}<br>
                Période: {{ $dateDebut }} - {{ $dateFin }}
            </p>
        </div>
        <div style="clear: both;"></div>
    </header>
    
    <div class="container">
        @if(isset($statistiques) && count($statistiques) > 0)
            <h2>Statistiques</h2>
            <table>
                <tr>
                    @foreach($statistiques as $stat)
                        <td style="width: {{ 100 / count($statistiques) }}%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                            <h3 style="margin: 0; font-size: 20px; color: {{ $stat['couleur'] ?? '#3498db' }};">{{ $stat['valeur'] }}</h3>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">{{ $stat['libelle'] }}</p>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endif
        
        @if(isset($contenu))
            {!! $contenu !!}
        @endif

        @if(isset($graphique) && $graphique)
            <div class="page-break"></div>
            <h2>Visualisations graphiques</h2>
            <div style="text-align: center; margin: 20px 0;">
                <img src="{{ $graphique }}" style="max-width: 100%;">
            </div>
        @endif

        @if(isset($donnees) && count($donnees) > 0)
            <h2>Données détaillées</h2>
            <table class="table-striped">
                <thead>
                    <tr>
                        @foreach($colonnes as $colonne)
                            <th>{{ $colonne }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($donnees as $ligne)
                        <tr>
                            @foreach($colonnes as $cle => $valeur)
                                <td>{{ $ligne[$cle] ?? '-' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(isset($notes) && count($notes) > 0)
            <h2>Notes et observations</h2>
            <ul>
                @foreach($notes as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <footer>
        <p>Rapport généré par Horaire360 - Système de gestion des plannings et présences</p>
    </footer>
    
    <div class="footer-page">
        Page <span class="page"></span>
    </div>

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