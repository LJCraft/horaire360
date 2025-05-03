@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-md-5 bg-primary text-white p-5 d-flex flex-column justify-content-center">
                            <div class="text-center mb-5">
                                <img src="{{ asset('assets/icons/logo.svg') }}" alt="Horaire360" height="80" class="mb-4">
                                <h2 class="fs-4 fw-bold">Horaire360</h2>
                                <p class="mb-0">Gestion intelligente des présences</p>
                            </div>
                            
                            <div class="d-none d-md-block">
                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <span class="fw-bold">Gestion des employés</span>
                                    </div>
                                    <p class="text-white-50 small mb-0 ms-4">CRUD complet avec import/export</p>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <span class="fw-bold">Gestion des plannings</span>
                                    </div>
                                    <p class="text-white-50 small mb-0 ms-4">Planification facile et intuitive</p>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <span class="fw-bold">Suivi des présences</span>
                                    </div>
                                    <p class="text-white-50 small mb-0 ms-4">Importation et validation des pointages</p>
                                </div>
                                
                                <div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <span class="fw-bold">Rapports d'assiduité</span>
                                    </div>
                                    <p class="text-white-50 small mb-0 ms-4">Statistiques et exports PDF/Excel</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-7 p-5">
                            <h1 class="h4 fw-bold mb-4">{{ __('Connexion') }}</h1>
                            
                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <div class="mb-3">
                                    <label for="email" class="form-label">{{ __('Adresse email') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                        
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label for="password" class="form-label">{{ __('Mot de passe') }}</label>
                                        
                                        @if (Route::has('password.request'))
                                            <a class="small text-decoration-none" href="{{ route('password.request') }}">
                                                {{ __('Mot de passe oublié?') }}
                                            </a>
                                        @endif
                                    </div>
                                    
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
    
                                        @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-4 form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">
                                        {{ __('Se souvenir de moi') }}
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        {{ __('Se connecter') }}
                                    </button>
                                </div>
                            </form>
                            
                            <div class="mt-4 text-center">
                                <div class="alert alert-light py-2">
                                    <strong>Compte de test:</strong> admin@horaire360.com / password
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection