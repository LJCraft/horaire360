<!DOCTYPE html>
@php 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Horaire360') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
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
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('plannings.*') ? 'active' : '' }}" href="{{ route('plannings.create') }}">
                                    <i class="bi bi-calendar-week"></i> Plannings
                                </a>
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
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('rapports.biometrique') }}">
                                            <i class="bi bi-phone me-2"></i> Pointages biométriques
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('presences.importForm') }}">
                                            <i class="bi bi-upload me-2"></i> Importer des pointages
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('presences.template') }}">
                                            <i class="bi bi-file-earmark-excel me-2"></i> Télécharger le modèle
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('presences.export') }}">
                                            <i class="bi bi-download me-2"></i> Exporter les pointages
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('presences.export.excel') }}">
                                            <i class="bi bi-file-earmark-excel me-2"></i> Exporter en Excel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('presences.export.pdf') }}">
                                            <i class="bi bi-file-earmark-pdf me-2"></i> Exporter en PDF
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('rapports.*') ? 'active' : '' }}" href="{{ route('rapports.index') }}">
                                    <i class="bi bi-file-earmark-bar-graph"></i> Rapports
                                </a>
                            </li>
                            @else
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                    <i class="bi bi-house"></i> Accueil
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
</body>
</html>