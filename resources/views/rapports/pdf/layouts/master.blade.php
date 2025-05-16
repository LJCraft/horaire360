<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $titre ?? 'Rapport' }} - Horaire360</title>
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
            font-size: 24px;
            margin: 0 0 5px 0;
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
            margin-bottom: 5px;
        }
        .periode-badge {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-top: 5px;
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
        <div class="logo-container">
            <!-- Logo texte simple au lieu d'une image pour éviter les problèmes avec DomPDF -->
            <div style="font-size: 24px; font-weight: bold; color: #3498db;">H360</div>
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
</body>
</html>
