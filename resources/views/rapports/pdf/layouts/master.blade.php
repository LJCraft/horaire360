<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titre ?? 'Rapport' }} - Horaire360</title>
    <style>
        /* Styles pour format A4 */
        @page {
            size: A4 portrait;
            margin: 2cm 1.5cm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            width: 100%;
            font-size: 9pt;
            line-height: 1.2;
            background: white;
        }
        .container {
            padding: 10px;
            max-width: 100%;
            box-sizing: border-box;
        }
        header {
            padding: 10px 20px;
            border-bottom: 2px solid #3498db;
            margin-bottom: 20px;
            background-color: #f8f9fa;
        }
        .logo-container {
            display: inline-block;
            vertical-align: middle;
            width: 15%;
        }
        .logo {
            max-width: 100%;
            height: auto;
        }
        .header-content {
            display: inline-block;
            vertical-align: middle;
            width: 50%;
        }
        .header-info {
            display: inline-block;
            vertical-align: middle;
            width: 30%;
            text-align: right;
            font-size: 12px;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 3px 0;
            color: #2980b9;
        }
        h2 {
            font-size: 14px;
            margin: 10px 0 8px 0;
            color: #3498db;
            border-bottom: 1px solid #eee;
            padding-bottom: 3px;
        }
        h3 {
            font-size: 12px;
            margin: 8px 0;
            color: #555;
        }
        .subtitle {
            font-size: 11px;
            color: #777;
            margin-bottom: 3px;
        }
        .periode-badge {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            margin-top: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
            max-width: 100%;
            overflow-wrap: break-word;
        }
        table th {
            background-color: #f8f9fa;
            padding: 4px;
            border: 1px solid #ddd;
            font-weight: bold;
            text-align: left;
            font-size: 8px;
        }
        table td {
            padding: 3px;
            border: 1px solid #ddd;
            font-size: 8px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
        }
        .table-striped tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 20px;
            width: 100%;
            box-sizing: border-box;
        }
        .stat-box {
            width: 22%;
            margin-right: 3%;
            padding: 6px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 3px;
            margin-bottom: 6px;
            text-align: center;
        }
        .stat-box h3 {
            margin: 0;
            font-size: 16px;
            color: #2980b9;
        }
        .stat-box p {
            margin: 3px 0 0 0;
            font-size: 8px;
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
            left: 0;
            right: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #777;
            padding: 5px 0;
            border-top: 1px solid #ddd;
            background-color: white;
        }
        .footer-page {
            font-size: 8px;
            text-align: right;
            margin-top: 5px;
            color: #777;
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
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
            
            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <!-- Utilisation du logo SVG de l'application -->
            <img src="{{ public_path('assets/icons/logo.svg') }}" alt="Horaire360" style="max-height: 40px; max-width: 100%;">
        </div>
        <div class="header-content">
            <h1>{{ $titre ?? 'Rapport' }}</h1>
            <div class="subtitle">{{ $sousTitre ?? 'Données détaillées' }}</div>
            @if(isset($periodeLabel))
                <div class="periode-badge">{{ $periodeLabel }}</div>
            @endif
        </div>
        <div class="header-info">
            <p style="margin: 0;">
                <strong>Horaire360</strong><br>
                Date d'édition: {{ now()->format('d/m/Y à H:i') }}<br>
                Période: {{ isset($dateDebut) ? \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') : '' }} 
                      - {{ isset($dateFin) ? \Carbon\Carbon::parse($dateFin)->format('d/m/Y') : '' }}
            </p>
        </div>
    </header>
    
    <div class="container">
        @yield('content')
    </div>

    <footer>
        <p>
            &copy; {{ date('Y') }} Horaire360 - Tous droits réservés<br>
            Ce document a été généré automatiquement et ne nécessite pas de signature.
        </p>
        <div class="footer-page">Page <span class="page">1</span></div>
    </footer>
    
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} / {PAGE_COUNT}";
            $size = 8;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) - 15;
            $y = $pdf->get_height() - 15;
            
            // Ajouter une callback pour chaque page
            $pdf->page_script('
                // Afficher le numéro de page sur chaque page
                $font = $fontMetrics->getFont("DejaVu Sans");
                $size = 8;
                $text = "Page " . $PAGE_NUM . " / " . $PAGE_COUNT;
                $width = $fontMetrics->getTextWidth($text, $font, $size);
                $x = ($pdf->get_width() - $width) - 15;
                $y = $pdf->get_height() - 15;
                $pdf->text($x, $y, $text, $font, $size);
            ');
        }
    </script>
</body>
</html>
