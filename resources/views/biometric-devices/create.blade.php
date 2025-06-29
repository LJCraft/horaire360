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
                    <form action="{{ route('biometric-devices.store') }}" method="POST">
                        @csrf
                        
                        <!-- Informations générales -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom de l'appareil <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="brand" class="form-label">Marque <span class="text-danger">*</span></label>
                                    <select class="form-select" id="brand" name="brand" required>
                                        <option value="">Sélectionnez une marque</option>
                                        <option value="zkteco">ZKTeco</option>
                                        <option value="hikvision">Hikvision</option>
                                        <option value="anviz">Anviz</option>
                                        <option value="suprema">Suprema</option>
                                        <option value="generic">Générique</option>
                                    </select>
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
    const connectionType = document.getElementById('connection_type');
    const ipConfig = document.getElementById('ip_config');
    const apiConfig = document.getElementById('api_config');
    
    connectionType.addEventListener('change', function() {
        const type = this.value;
        
        // Masquer toutes les configurations
        ipConfig.style.display = 'none';
        apiConfig.style.display = 'none';
        
        // Afficher la configuration correspondante
        if (type === 'ip') {
            ipConfig.style.display = 'block';
        } else if (type === 'api') {
            apiConfig.style.display = 'block';
        }
    });
});
</script>
@endsection