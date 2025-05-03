<!DOCTYPE html>
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
                            <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('presences.*') ? 'active' : '' }}" href="{{ route('presences.index') }}">
                                <i class="bi bi-fingerprint"></i> Présences
                            </a>
                        </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('rapports.*') ? 'active' : '' }}" href="#">
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
                    Version 1.0 - Itération 1
                </p>
            </div>
        </footer>
    </div>
    
    @stack('scripts')
</body>
</html>