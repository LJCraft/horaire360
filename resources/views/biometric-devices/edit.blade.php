@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Modifier l'Appareil Biométrique</h1>
            <p class="text-muted mb-0">Modifiez la configuration de "{{ $device->name }}"</p>
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

                    <form action="{{ route('biometric-devices.update', $device->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Informations générales -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom de l'appareil <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $device->name) }}" required>
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
                                        @foreach($brands as $key => $label)
                                            <option value="{{ $key }}" {{ old('brand', $device->brand) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
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
                                    <input type="text" class="form-control" id="model" name="model" value="{{ old('model', $device->model) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="connection_type" class="form-label">Type de connexion <span class="text-danger">*</span></label>
                                    <select class="form-select" id="connection_type" name="connection_type" required>
                                        <option value="">Sélectionnez le type</option>
                                        <option value="ip" {{ old('connection_type', $device->connection_type) === 'ip' ? 'selected' : '' }}>Connexion IP/TCP</option>
                                        <option value="api" {{ old('connection_type', $device->connection_type) === 'api' ? 'selected' : '' }}>API REST</option>
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
                                        <input type="text" class="form-control" id="ip_address" name="ip_address" value="{{ old('ip_address', $device->ip_address) }}" placeholder="192.168.1.100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="port" class="form-label">Port</label>
                                        <input type="number" class="form-control" id="port" name="port" value="{{ old('port', $device->port ?? 4370) }}">
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
                                <input type="url" class="form-control" id="api_url" name="api_url" value="{{ old('api_url', $device->api_url) }}" placeholder="https://api.example.com">
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
                                       value="{{ old('api_facial_url', $device->brand === 'api-facial' ? $device->api_url : '') }}" placeholder="https://votre-api.com/pointages?nameEntreprise=VotreEntreprise">
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
                                               value="{{ old('api_facial_token', $device->brand === 'api-facial' ? $device->username : '') }}" placeholder="Bearer token ou clé API">
                                        @error('api_facial_token')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="api_facial_format" class="form-label">Format de réponse</label>
                                        <select class="form-select" id="api_facial_format" name="api_facial_format">
                                            <option value="json" {{ old('api_facial_format', $device->brand === 'api-facial' ? $device->password : 'json') === 'json' ? 'selected' : '' }}>JSON (par défaut)</option>
                                            <option value="xml" {{ old('api_facial_format', $device->brand === 'api-facial' ? $device->password : 'json') === 'xml' ? 'selected' : '' }}>XML</option>
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
                                    <input type="number" class="form-control" id="sync_interval" name="sync_interval" value="{{ old('sync_interval', $device->sync_interval ?? 300) }}" min="60">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" {{ old('active', $device->active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">Appareil actif</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informations sur le statut -->
                        <hr>
                        <h6 class="text-primary">Informations de connexion</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Statut de connexion</label>
                                    <div>
                                        @if($device->connection_status === 'connected')
                                            <span class="badge bg-success">Connecté</span>
                                        @elseif($device->connection_status === 'disconnected')
                                            <span class="badge bg-warning">Déconnecté</span>
                                        @elseif($device->connection_status === 'error')
                                            <span class="badge bg-danger">Erreur</span>
                                        @else
                                            <span class="badge bg-secondary">Non testé</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Dernière synchronisation</label>
                                    <div class="text-muted">
                                        {{ $device->last_sync_at ? $device->last_sync_at->format('d/m/Y H:i') : 'Jamais' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Dernier test de connexion</label>
                                    <div class="text-muted">
                                        {{ $device->last_connection_test_at ? $device->last_connection_test_at->format('d/m/Y H:i') : 'Jamais' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($device->last_error)
                            <div class="alert alert-warning">
                                <strong>Dernière erreur :</strong> {{ $device->last_error }}
                            </div>
                        @endif

                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('biometric-devices.index') }}" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Mettre à jour l'appareil</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const brandSelect = document.getElementById('brand');
    const connectionTypeSelect = document.getElementById('connection_type');
    const ipConfig = document.getElementById('ip_config');
    const apiConfig = document.getElementById('api_config');
    const apiFacialConfig = document.getElementById('api_facial_config');

    function toggleConfigurations() {
        const brand = brandSelect.value;
        const connectionType = connectionTypeSelect.value;

        // Masquer toutes les configurations
        ipConfig.style.display = 'none';
        apiConfig.style.display = 'none';
        apiFacialConfig.style.display = 'none';

        if (brand === 'api-facial') {
            // Pour API-FACIAL, forcer le type de connexion à 'api' et afficher la config spécifique
            connectionTypeSelect.value = 'api';
            connectionTypeSelect.disabled = true;
            apiFacialConfig.style.display = 'block';
        } else {
            // Pour les autres marques, réactiver le sélecteur de type de connexion
            connectionTypeSelect.disabled = false;
            
            if (connectionType === 'ip') {
                ipConfig.style.display = 'block';
            } else if (connectionType === 'api') {
                apiConfig.style.display = 'block';
            }
        }
    }

    // Événements
    brandSelect.addEventListener('change', toggleConfigurations);
    connectionTypeSelect.addEventListener('change', toggleConfigurations);

    // Initialiser l'affichage au chargement de la page
    toggleConfigurations();
});
</script>
@endpush
@endsection 