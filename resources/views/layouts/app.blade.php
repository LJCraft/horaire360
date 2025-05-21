<!DOCTYPE html>
@php 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Horaire360') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --transition-speed: 0.3s;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            transition: background-color var(--transition-speed), color var(--transition-speed);
        }
        
        /* Mode nuit - Palette de couleurs élégante et professionnelle */
        [data-bs-theme="dark"] {
            /* Couleurs de fond principales */
            --bs-body-bg: #0c1526;
            --bs-body-color: #e5eaf2;
            --bs-card-bg: #162340;
            --bs-card-border-color: #2a3c61;
            
            /* Éléments d'interface */
            --bs-navbar-color: #e5eaf2;
            --bs-dropdown-bg: #162340;
            --bs-dropdown-link-color: #e5eaf2;
            --bs-dropdown-link-hover-bg: #2a3c61;
            --bs-table-bg: #162340;
            --bs-table-striped-bg: #1d2c4f;
            --bs-table-hover-bg: #2a3c61;
            --bs-modal-bg: #162340;
            --bs-border-color: #2a3c61;
            
            /* Couleurs de texte */
            --bs-secondary-color: #a3b8d9;
            --bs-emphasis-color: #ffffff;
            
            /* Couleurs d'accent */
            --bs-primary-rgb: 79, 142, 255;
            --bs-info-rgb: 66, 186, 255;
            --bs-success-rgb: 72, 187, 120;
            --bs-warning-rgb: 255, 193, 7;
            --bs-danger-rgb: 255, 88, 88;
            
            /* Couleurs spécifiques pour les graphiques */
            --chart-primary: rgba(79, 142, 255, 0.8);
            --chart-secondary: rgba(66, 186, 255, 0.8);
            --chart-success: rgba(72, 187, 120, 0.8);
            --chart-warning: rgba(255, 193, 7, 0.8);
            --chart-danger: rgba(255, 88, 88, 0.8);
        }
        
        /* Éléments principaux de l'interface en mode sombre */
        [data-bs-theme="dark"] .navbar {
            background-color: #0c1526 !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2), 0 2px 4px -1px rgba(0, 0, 0, 0.1) !important;
            border-bottom: 1px solid #2a3c61;
        }
        
        [data-bs-theme="dark"] .card {
            background-color: #162340;
            border-color: #2a3c61;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        [data-bs-theme="dark"] .card:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        [data-bs-theme="dark"] .card-header {
            background-color: #1d2c4f;
            border-color: #2a3c61;
        }
        
        /* Styles des tableaux en mode sombre */
        [data-bs-theme="dark"] .table {
            color: #e5eaf2;
            border-color: #2a3c61;
        }
        
        [data-bs-theme="dark"] .table-light {
            background-color: #1d2c4f;
            color: #e5eaf2;
        }
        
        /* En-têtes de tableau */
        [data-bs-theme="dark"] .table thead th,
        [data-bs-theme="dark"] .table thead td,
        [data-bs-theme="dark"] .table tfoot th,
        [data-bs-theme="dark"] .table tfoot td {
            background-color: #162340;
            color: #a3b8d9;
            border-color: #2a3c61;
            font-weight: 600;
            padding: 12px 8px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #4f8eff;
        }
        
        /* Pied de tableau */
        [data-bs-theme="dark"] .table tfoot th,
        [data-bs-theme="dark"] .table tfoot td {
            border-top: 2px solid #4f8eff;
            border-bottom: none;
            background-color: #162340;
        }
        
        /* Bordures et lignes des tableaux */
        [data-bs-theme="dark"] .table-bordered {
            border-color: #2a3c61;
        }
        
        [data-bs-theme="dark"] .table-bordered th,
        [data-bs-theme="dark"] .table-bordered td {
            border-color: #2a3c61;
        }
        
        /* Lignes alternées */
        [data-bs-theme="dark"] .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(29, 44, 79, 0.5);
        }
        
        /* Survol des lignes */
        [data-bs-theme="dark"] .table-hover tbody tr:hover {
            background-color: rgba(42, 60, 97, 0.7);
            color: #ffffff;
        }
        
        /* Style spécifique pour le footer en mode sombre */
        [data-bs-theme="dark"] footer {
            background-color: #0c1526 !important;
            border-top-color: #2a3c61 !important;
            color: #e5eaf2;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }
        
        [data-bs-theme="dark"] footer .text-muted {
            color: #a3b8d9 !important;
        }
        
        /* Amélioration des transitions pour une expérience plus fluide */
        [data-bs-theme="dark"] * {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
        }
        
        /* Amélioration des boutons en mode sombre */
        [data-bs-theme="dark"] .btn-outline-primary {
            border-color: #4f8eff;
            color: #4f8eff;
            transition: all 0.3s ease;
        }
        
        [data-bs-theme="dark"] .btn-outline-primary:hover {
            background-color: #4f8eff;
            color: #fff;
            box-shadow: 0 4px 8px rgba(79, 142, 255, 0.3);
            transform: translateY(-1px);
        }
        
        [data-bs-theme="dark"] .btn-primary {
            background-color: #4f8eff;
            border-color: #4f8eff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        [data-bs-theme="dark"] .btn-primary:hover {
            background-color: #3b7dff;
            border-color: #3b7dff;
            box-shadow: 0 4px 8px rgba(79, 142, 255, 0.3);
            transform: translateY(-1px);
        }
        
        /* Autres boutons en mode sombre */
        [data-bs-theme="dark"] .btn-secondary {
            background-color: #334155;
            border-color: #334155;
            transition: all 0.3s ease;
        }
        
        [data-bs-theme="dark"] .btn-secondary:hover {
            background-color: #475569;
            border-color: #475569;
            box-shadow: 0 4px 8px rgba(51, 65, 85, 0.3);
        }
        
        [data-bs-theme="dark"] .btn-success {
            background-color: #48bb78;
            border-color: #48bb78;
            transition: all 0.3s ease;
        }
        
        [data-bs-theme="dark"] .btn-success:hover {
            background-color: #38a169;
            border-color: #38a169;
            box-shadow: 0 4px 8px rgba(72, 187, 120, 0.3);
        }
        
        [data-bs-theme="dark"] .btn-danger {
            background-color: #ff5858;
            border-color: #ff5858;
            transition: all 0.3s ease;
        }
        
        [data-bs-theme="dark"] .btn-danger:hover {
            background-color: #e53e3e;
            border-color: #e53e3e;
            box-shadow: 0 4px 8px rgba(255, 88, 88, 0.3);
        }
        
        /* Amélioration des formulaires en mode sombre */
        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select {
            background-color: #162340;
            border-color: #2a3c61;
            color: #e5eaf2;
            transition: all 0.3s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        [data-bs-theme="dark"] .form-control:focus,
        [data-bs-theme="dark"] .form-select:focus {
            border-color: #4f8eff;
            box-shadow: 0 0 0 0.25rem rgba(79, 142, 255, 0.25);
            background-color: #1d2c4f;
        }
        
        [data-bs-theme="dark"] .form-control::placeholder {
            color: #a3b8d9;
            opacity: 0.7;
        }
        
        /* Labels et légendes de formulaires */
        [data-bs-theme="dark"] .form-label,
        [data-bs-theme="dark"] .form-text {
            color: #a3b8d9;
        }
        
        /* Validation des formulaires */
        [data-bs-theme="dark"] .form-control.is-valid,
        [data-bs-theme="dark"] .form-select.is-valid {
            border-color: #48bb78;
        }
        
        [data-bs-theme="dark"] .form-control.is-invalid,
        [data-bs-theme="dark"] .form-select.is-invalid {
            border-color: #ff5858;
        }
        
        [data-bs-theme="dark"] .text-gray-800 {
            color: #d0d0d0 !important;
        }
        
        [data-bs-theme="dark"] .text-gray-300 {
            color: #6c757d !important;
        }
        
        [data-bs-theme="dark"] .text-muted {
            color: #a0a0a0 !important;
        }
        
        [data-bs-theme="dark"] .border-left-primary,
        [data-bs-theme="dark"] .border-left-success,
        [data-bs-theme="dark"] .border-left-info,
        [data-bs-theme="dark"] .border-left-warning,
        [data-bs-theme="dark"] .border-left-danger {
            border-left: 0.25rem solid;
        }
        
        [data-bs-theme="dark"] .border-left-primary {
            border-left-color: #4e73df;
        }
        
        [data-bs-theme="dark"] .border-left-success {
            border-left-color: #1cc88a;
        }
        
        [data-bs-theme="dark"] .border-left-info {
            border-left-color: #36b9cc;
        }
        
        [data-bs-theme="dark"] .border-left-warning {
            border-left-color: #f6c23e;
        }
        
        [data-bs-theme="dark"] .border-left-danger {
            border-left-color: #e74a3b;
        }
        
        /* Animation de transition */
        .theme-toggle-icon {
            transition: transform 0.5s ease;
        }
        
        .theme-toggle-icon.rotate {
            transform: rotate(360deg);
        }
        
        /* Styles existants */
        .sidebar {
            min-height: calc(100vh - 56px);
        }
        
        .photo-container {
            position: relative;
            cursor: pointer;
        }
        
        .photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .photo-container:hover .photo-overlay {
            opacity: 1;
        }
        
        .change-photo-btn {
            display: block;
            width: 100%;
            height: 100%;
            text-decoration: none;
        }
        
        .photo-overlay i {
            font-size: 2rem;
        }
        
        /* Style pour le bouton de basculement du thème */
        .theme-toggle-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .theme-toggle-btn::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0) 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .theme-toggle-btn:hover::before {
            opacity: 1;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/dashboard') }}">
                    <img src="{{  asset('assets/icons/logo.svg')}}" alt="Horaire360" height="40" class="me-2">
                    <span>Horaire360</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            @if(auth()->user()->isAdmin())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                    <i class="bi bi-house"></i> Tableau de bord
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('employes.*') ? 'active' : '' }}" href="{{ route('employes.index') }}">
                                    <i class="bi bi-people"></i> Employés
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('postes.*') ? 'active' : '' }}" href="{{ route('postes.index') }}">
                                    <i class="bi bi-briefcase"></i> Postes
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->routeIs('plannings.*') ? 'active' : '' }}" href="#" id="planningsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-calendar-week"></i> Plannings
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="planningsDropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('plannings.create') }}">
                                            <i class="bi bi-person me-2"></i> Planning individuel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('plannings.departement.index') }}">
                                            <i class="bi bi-building me-2"></i> Planning par département
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('plannings.departement.calendrier') }}">
                                            <i class="bi bi-calendar-week me-2"></i> Calendrier
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('plannings.export') }}">
                                            <i class="bi bi-download me-2"></i> Exporter
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->routeIs('presences.*') || request()->routeIs('rapports.biometrique') ? 'active' : '' }}" href="#" id="presencesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-fingerprint"></i> Présences
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="presencesDropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('presences.index') }}">
                                            <i class="bi bi-list me-2"></i> Liste des pointages
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('presences.create') }}">
                                            <i class="bi bi-plus-circle me-2"></i> Ajouter un pointage
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('rapports.biometrique') }}">
                                            <i class="bi bi-phone me-2"></i> Pointages biométriques
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('rapports.*') ? 'active' : '' }}" href="{{ route('rapports.index') }}">
                                    <i class="bi bi-file-earmark-bar-graph"></i> Rapports
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                    <i class="bi bi-people-fill"></i> Utilisateurs
                                </a>
                            </li>
                            @else
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard.*') ? 'active' : '' }}" href="{{ route('dashboard.employe', auth()->user()->employe) }}">
                                    <i class="bi bi-house"></i> Tableau de bord 
                                </a>
                            </li>
                            <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('plannings.*') ? 'active' : '' }}" href="{{ route('plannings.show', auth()->user()->employe) }}">
                            <i class="bi bi-calendar-check"></i> Mon planning
                                </a>
                            </li>
                            <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('presences.*') ? 'active' : '' }}" href="{{ route('presences.show', auth()->user()->employe) }}">
                                    <i class="bi bi-clock-history"></i> Mes présences
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">
                                    <i class="bi bi-inbox"></i> Mes demandes
                                </a>
                            </li>
                            @endif
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Se connecter') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item">
                                <a class="nav-link text-info" href="#" data-bs-toggle="modal" data-bs-target="#helpModal">
                                    <i class="bi bi-question-circle"></i> Aide
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="#">
                                        <i class="bi bi-person me-2"></i> Mon profil
                                    </a>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right me-2"></i> {{ __('Déconnexion') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
        
        <footer class="bg-white py-4 mt-5 border-top">
            <div class="container text-center">
                <div class="mb-2">
                    <img src="{{  asset('assets/icons/logo.svg')}}" alt="Horaire360" height="30">
                </div>
                <p class="text-muted mb-0">
                    &copy; {{ date('Y') }} Horaire360 - Gestion intelligente des présences
                </p>
                <p class="text-muted small">
                    Version 1.0 
                </p>
            </div>
        </footer>
    </div>
    
    <!-- Script pour appliquer le mode jour/nuit sur toutes les pages -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Vérifier s'il y a une préférence de thème sauvegardée
            const savedTheme = localStorage.getItem('horaire360-theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-bs-theme', savedTheme);
                console.log('Thème appliqué depuis localStorage:', savedTheme);
            }
        });
    </script>
    
    <!-- Modal d'aide à l'importation des pointages -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="helpModalLabel">Guide d'importation des pointages</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Structure du fichier</h6>
                            <p>Le fichier d'importation doit contenir les colonnes suivantes :</p>
                            <ul>
                                <li><strong>employe_id*</strong> - ID numérique de l'employé</li>
                                <li><strong>date*</strong> - Format YYYY-MM-DD</li>
                                <li><strong>heure_arrivee*</strong> - Format HH:MM</li>
                                <li><strong>heure_depart</strong> - Format HH:MM (optionnel)</li>
                                <li><strong>commentaire</strong> - Texte libre (optionnel)</li>
                            </ul>
                            <p class="small text-muted"><em>* Champs obligatoires</em></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Exemple de fichier</h6>
                            <table class="table table-sm table-bordered">
                                <thead class="table-primary">
                                    <tr>
                                        <th>employe_id</th>
                                        <th>date</th>
                                        <th>heure_arrivee</th>
                                        <th>heure_depart</th>
                                        <th>commentaire</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>2023-06-01</td>
                                        <td>09:05</td>
                                        <td>17:30</td>
                                        <td>Journée normale</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>2023-06-01</td>
                                        <td>08:55</td>
                                        <td>16:45</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Note importante :</strong> Le système détectera automatiquement les retards et départs anticipés en fonction des plannings existants.
                    </div>
                    <div class="mt-3">
                        <h6 class="fw-bold">Processus d'importation</h6>
                        <ol>
                            <li>Téléchargez le modèle en cliquant sur "Télécharger le modèle"</li>
                            <li>Remplissez les données selon le format indiqué</li>
                            <li>Enregistrez le fichier en format XLSX, XLS ou CSV</li>
                            <li>Importez le fichier via le formulaire</li>
                            <li>Vérifiez les résultats de l'importation</li>
                        </ol>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('presences.template') }}" class="btn btn-info">
                        <i class="bi bi-download me-1"></i> Télécharger le modèle
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    
    @stack('scripts')
    
    <!-- jQuery et Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>