@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Ajouter un Appareil Biométrique</h1>
            <p class="text-muted mb-0">Configurez un nouveau dispositif de pointage</p>
        </div>
        <a href="{{ route('biometric-devices.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-device-hdd"></i> Informations de l'appareil
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Affichage des erreurs de validation -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <h6><i class="bi bi-exclamation-triangle"></i> Erreurs de validation :</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Affichage des messages de succès/erreur -->
                    @if (session('success'))
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('biometric-devices.store') }}" method="POST">
                        @csrf
                        
                        <!-- Informations générales -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom de l'appareil <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="brand" class="form-label">Marque <span class="text-danger">*</span></label>
                                    <select class="form-select @error('brand') is-invalid @enderror" id="brand" name="brand" required>
                                        <option value="">Sélectionnez une marque</option>
                                        <option value="zkteco" {{ old('brand') === 'zkteco' ? 'selected' : '' }}>ZKTeco</option>
                                        <option value="hikvision" {{ old('brand') === 'hikvision' ? 'selected' : '' }}>Hikvision</option>
                                        <option value="anviz" {{ old('brand') === 'anviz' ? 'selected' : '' }}>Anviz</option>
                                        <option value="suprema" {{ old('brand') === 'suprema' ? 'selected' : '' }}>Suprema</option>
                                        <option value="api-facial" {{ old('brand') === 'api-facial' ? 'selected' : '' }}>API-FACIAL</option>
                                        <option value="generic" {{ old('brand') === 'generic' ? 'selected' : '' }}>Générique</option>
                                    </select>
                                    @error('brand')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="model" class="form-label">Modèle</label>
                                    <input type="text" class="form-control" id="model" name="model">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="device_id" class="form-label">ID de l'appareil <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('device_id') is-invalid @enderror" 
                                           id="device_id" name="device_id" value="{{ old('device_id') }}" required
                                           placeholder="1">
                                    <div class="form-text">
                                        L'ID configuré sur l'appareil (doit correspondre exactement)
                                    </div>
                                    @error('device_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="connection_type" class="form-label">Type de connexion <span class="text-danger">*</span></label>
                                    <select class="form-select" id="connection_type" name="connection_type" required>
                                        <option value="">Sélectionnez le type</option>
                                        <option value="ip">Connexion IP/TCP</option>
                                        <option value="api">API REST</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Configuration IP -->
                        <div id="ip_config" style="display: none;">
                            <hr>
                            <h6 class="text-primary">Configuration IP/TCP</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ip_address" class="form-label">Adresse IP</label>
                                        <input type="text" class="form-control" id="ip_address" name="ip_address" placeholder="192.168.1.100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="port" class="form-label">Port</label>
                                        <input type="number" class="form-control" id="port" name="port" value="4370">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Nom d'utilisateur</label>
                                        <input type="text" class="form-control" id="username" name="username" placeholder="admin">
                                        <div class="form-text">
                                            Nom d'utilisateur pour l'authentification (optionnel)
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Mot de passe</label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe de l'appareil">
                                        <div class="form-text">
                                            Requis pour l'authentification sur l'appareil ZKTeco
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configuration API -->
                        <div id="api_config" style="display: none;">
                            <hr>
                            <h6 class="text-primary">Configuration API REST</h6>
                            <div class="mb-3">
                                <label for="api_url" class="form-label">URL de l'API</label>
                                <input type="url" class="form-control" id="api_url" name="api_url" placeholder="https://api.example.com">
                            </div>
                        </div>

                        <!-- Configuration API-FACIAL spécifique -->
                        <div id="api_facial_config" style="display: none;">
                            <hr>
                            <h6 class="text-primary">Configuration API-FACIAL</h6>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Application mobile de reconnaissance faciale</strong><br>
                                Connectez votre app mobile comme un appareil biométrique distant avec synchronisation automatique.
                            </div>
                            <div class="mb-3">
                                <label for="api_facial_url" class="form-label">URL de l'API de pointages <span class="text-danger">*</span></label>
                                <input type="url" class="form-control @error('api_facial_url') is-invalid @enderror" id="api_facial_url" name="api_facial_url" 
                                       value="{{ old('api_facial_url') }}" placeholder="https://votre-api.com/pointages?nameEntreprise=VotreEntreprise">
                                @error('api_facial_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Format attendu : <code>https://votre-api.com/pointages?nameEntreprise=VotreEntreprise</code>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="api_facial_token" class="form-label">Token d'authentification (optionnel)</label>
                                        <input type="text" class="form-control @error('api_facial_token') is-invalid @enderror" id="api_facial_token" name="api_facial_token" 
                                               value="{{ old('api_facial_token') }}" placeholder="Bearer token ou clé API">
                                        @error('api_facial_token')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="api_facial_format" class="form-label">Format de réponse</label>
                                        <select class="form-select" id="api_facial_format" name="api_facial_format">
                                            <option value="json">JSON (par défaut)</option>
                                            <option value="xml">XML</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Options avancées -->
                        <hr>
                        <h6 class="text-primary">Options avancées</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sync_interval" class="form-label">Intervalle de synchronisation (secondes)</label>
                                    <input type="number" class="form-control" id="sync_interval" name="sync_interval" value="300" min="60">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" checked>
                                        <label class="form-check-label" for="active">Appareil actif</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('biometric-devices.index') }}" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Créer l'appareil</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const brandSelect = document.getElementById('brand');
    const connectionType = document.getElementById('connection_type');
    const ipConfig = document.getElementById('ip_config');
    const apiConfig = document.getElementById('api_config');
    const apiFacialConfig = document.getElementById('api_facial_config');
    
    // Gestion du changement de marque
    brandSelect.addEventListener('change', function() {
        const brand = this.value;
        
        // Masquer les alertes d'erreur existantes quand on change de marque
        const alertErrors = document.querySelectorAll('.alert-danger');
        alertErrors.forEach(alert => alert.style.display = 'none');
        
        if (brand === 'api-facial') {
            // Pour API-FACIAL, forcer le type de connexion API et masquer le sélecteur
            connectionType.value = 'api';
            // Ne pas disabled, juste masquer visuellement pour que la valeur soit envoyée
            connectionType.style.pointerEvents = 'none'; // Empêche les clics
            connectionType.closest('.mb-3').style.display = 'none';
            
            // Afficher la configuration spécifique API-FACIAL
            showApiFacialConfig();
        } else {
            // Pour les autres marques, réactiver le sélecteur de type
            connectionType.style.pointerEvents = 'auto'; // Réactive les clics
            connectionType.closest('.mb-3').style.display = 'block';
            
            // Masquer la configuration API-FACIAL
            apiFacialConfig.style.display = 'none';
            
            // Gérer l'affichage normal selon le type de connexion
            handleConnectionTypeChange();
        }
    });
    
    // Gestion du changement de type de connexion
    connectionType.addEventListener('change', handleConnectionTypeChange);
    
    function handleConnectionTypeChange() {
        const type = connectionType.value;
        const brand = brandSelect.value;
        
        // Masquer toutes les configurations
        ipConfig.style.display = 'none';
        apiConfig.style.display = 'none';
        apiFacialConfig.style.display = 'none';
        
        // Afficher la configuration correspondante
        if (brand === 'api-facial') {
            showApiFacialConfig();
        } else if (type === 'ip') {
            ipConfig.style.display = 'block';
        } else if (type === 'api') {
            apiConfig.style.display = 'block';
        }
    }
    
    function showApiFacialConfig() {
        apiFacialConfig.style.display = 'block';
        // Masquer les autres configurations
        ipConfig.style.display = 'none';
        apiConfig.style.display = 'none';
    }
    
    // Réafficher la configuration correcte si on revient après une erreur de validation
    @if(old('brand') === 'api-facial')
        // API-FACIAL était sélectionné, réafficher sa configuration
        connectionType.value = 'api';
        connectionType.style.pointerEvents = 'none';
        connectionType.closest('.mb-3').style.display = 'none';
        showApiFacialConfig();
    @elseif(old('brand') && old('connection_type'))
        // Autre marque avec type de connexion, réafficher la configuration appropriée
        handleConnectionTypeChange();
    @endif
});
</script>
@endsection