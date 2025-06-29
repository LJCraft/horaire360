@extends('layouts.app')

@section('content')
<div class="container-fluid">
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Configuration des Appareils Biométriques</h1>
            <p class="text-muted mb-0">Gérez vos appareils de pointage biométriques</p>
        </div>
        <a href="{{ route('biometric-devices.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Ajouter un Appareil
        </a>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Appareils</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_devices'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-device-hdd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Actifs</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active_devices'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Connectés</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['connected_devices'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-wifi fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Déconnectés</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_devices'] - $stats['connected_devices'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des appareils -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Appareils Configurés</h6>
        </div>
        <div class="card-body">
            @if($devices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Marque</th>
                                <th>Type</th>
                                <th>Adresse</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($devices as $device)
                                <tr>
                                    <td>{{ $device->name }}</td>
                                    <td><span class="badge bg-secondary">{{ strtoupper($device->brand) }}</span></td>
                                    <td>
                                        @if($device->connection_type === 'ip')
                                            <span class="badge bg-info">IP</span>
                                        @else
                                            <span class="badge bg-warning">API</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($device->connection_type === 'ip')
                                            {{ $device->ip_address }}:{{ $device->port }}
                                        @else
                                            {{ parse_url($device->api_url, PHP_URL_HOST) }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($device->connection_status === 'connected')
                                            <span class="badge bg-success">Connecté</span>
                                        @elseif($device->connection_status === 'disconnected')
                                            <span class="badge bg-warning">Déconnecté</span>
                                        @elseif($device->connection_status === 'error')
                                            <span class="badge bg-danger">Erreur</span>
                                        @else
                                            <span class="badge bg-secondary">Non testé</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary btn-test-connection" data-device-id="{{ $device->id }}" title="Tester la connexion">
                                            <i class="bi bi-wifi"></i>
                                        </button>
                                        @if($device->connection_status === 'connected')
                                            <button class="btn btn-sm btn-warning btn-disconnect" data-device-id="{{ $device->id }}" data-device-name="{{ $device->name }}" title="Déconnecter cet appareil">
                                                <i class="bi bi-plug"></i>
                                            </button>
                                        @endif
                                        <button class="btn btn-sm btn-danger btn-delete" data-device-id="{{ $device->id }}" data-device-name="{{ $device->name }}" title="Supprimer cet appareil">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-device-hdd fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-500">Aucun appareil configuré</h5>
                    <a href="{{ route('biometric-devices.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Ajouter un Appareil
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Vérification du chargement des dépendances
console.log('=== DIAGNOSTIC APPAREILS BIOMETRIQUES ===');
console.log('jQuery chargé:', typeof $ !== 'undefined');
console.log('Bootstrap chargé:', typeof bootstrap !== 'undefined');
console.log('Document ready état:', document.readyState);

// Fonction pour tester la connexion d'un appareil
function testConnection(deviceId, button) {
    console.log('🔗 Test de connexion pour appareil ID:', deviceId);
    
    if (!deviceId) {
        alert('❌ Erreur: ID de l\'appareil manquant');
        return;
    }
    
    // Récupérer le token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        alert('❌ Erreur: Token CSRF manquant');
        console.error('Token CSRF non trouvé dans la page');
        return;
    }
    
    // Désactiver le bouton et changer l'icône
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i>';
    
    const url = `/biometric-devices/${deviceId}/test-connection`;
    console.log('🌐 URL de test:', url);
    
    // Requête AJAX
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        console.log('📡 Réponse reçue:', response.status, response.statusText);
        return response.json();
    })
         .then(data => {
         console.log('📊 Données de réponse:', data);
         button.disabled = false;
         button.innerHTML = '<i class="bi bi-wifi"></i>';
         
         if (data.success) {
             alert('✅ ' + data.message);
         } else {
             alert('❌ ' + (data.message || 'Test de connexion échoué'));
         }
         
         // Recharger la page pour voir le nouveau statut (succès OU échec)
         console.log('🔄 Rechargement de la page pour mettre à jour le statut...');
         window.location.reload();
     })
    .catch(error => {
        console.error('💥 Erreur lors du test:', error);
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-wifi"></i>';
        alert('❌ Erreur lors du test de connexion: ' + error.message);
    });
}

// Fonction pour déconnecter un appareil
function disconnectDevice(deviceId, deviceName, button) {
    console.log('🔌 Déconnexion pour appareil ID:', deviceId, 'Nom:', deviceName);
    
    if (!deviceId) {
        alert('❌ Erreur: ID de l\'appareil manquant');
        return;
    }
    
    // Récupérer le token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        alert('❌ Erreur: Token CSRF manquant');
        console.error('Token CSRF non trouvé dans la page');
        return;
    }
    
    // Confirmation de déconnexion
    if (!confirm(`⚠️ Êtes-vous sûr de vouloir déconnecter l'appareil "${deviceName}" ?\n\nL'appareil ne sera plus synchronisé jusqu'à une nouvelle connexion.`)) {
        return;
    }
    
    // Désactiver le bouton et changer l'icône
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i>';
    
    const url = `/biometric-devices/${deviceId}/disconnect`;
    console.log('🌐 URL de déconnexion:', url);
    
    // Requête AJAX
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        console.log('📡 Réponse reçue:', response.status, response.statusText);
        return response.json();
    })
    .then(data => {
        console.log('📊 Données de réponse:', data);
        
        if (data.success) {
            alert('✅ ' + data.message);
            // Recharger la page pour mettre à jour le statut
            window.location.reload();
        } else {
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-plug"></i>';
            alert('❌ ' + (data.message || 'Erreur lors de la déconnexion'));
        }
    })
    .catch(error => {
        console.error('💥 Erreur lors de la déconnexion:', error);
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-plug"></i>';
        alert('❌ Erreur lors de la déconnexion: ' + error.message);
    });
}

// Fonction pour supprimer un appareil
function deleteDevice(deviceId, deviceName, button) {
    console.log('🗑️ Suppression pour appareil ID:', deviceId, 'Nom:', deviceName);
    
    if (!deviceId) {
        alert('❌ Erreur: ID de l\'appareil manquant');
        return;
    }
    
    // Récupérer le token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        alert('❌ Erreur: Token CSRF manquant');
        console.error('Token CSRF non trouvé dans la page');
        return;
    }
    
    // Confirmation de suppression
    if (!confirm(`⚠️ Êtes-vous sûr de vouloir supprimer l'appareil "${deviceName}" ?\n\nCette action est irréversible.`)) {
        return;
    }
    
    // Désactiver le bouton et changer l'icône
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i>';
    
    const url = `/biometric-devices/${deviceId}`;
    console.log('🌐 URL de suppression:', url);
    
    // Requête AJAX
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        console.log('📡 Réponse reçue:', response.status, response.statusText);
        return response.json();
    })
    .then(data => {
        console.log('📊 Données de réponse:', data);
        
        if (data.success) {
            alert('✅ ' + data.message);
            // Recharger la page pour mettre à jour la liste
            window.location.reload();
        } else {
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-trash"></i>';
            alert('❌ ' + (data.message || 'Erreur lors de la suppression'));
        }
    })
    .catch(error => {
        console.error('💥 Erreur lors de la suppression:', error);
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-trash"></i>';
        alert('❌ Erreur lors de la suppression: ' + error.message);
    });
}


// Initialisation quand le DOM est prêt
function initBiometricDevices() {
    console.log('🚀 Initialisation des appareils biométriques');
    
    // Compter les boutons
    const testButtons = document.querySelectorAll('.btn-test-connection');
    const disconnectButtons = document.querySelectorAll('.btn-disconnect');
    const deleteButtons = document.querySelectorAll('.btn-delete');
    
    console.log('🔍 Boutons de test trouvés:', testButtons.length);
    console.log('🔌 Boutons de déconnexion trouvés:', disconnectButtons.length);
    console.log('🗑️ Boutons de suppression trouvés:', deleteButtons.length);
    
    // Attacher les événements aux boutons de test
    testButtons.forEach(function(button, index) {
        console.log(`📌 Attachement événement test bouton ${index + 1}`);
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('🖱️ Clic détecté sur bouton de test');
            
            const deviceId = this.getAttribute('data-device-id');
            testConnection(deviceId, this);
        });
    });
    
    // Attacher les événements aux boutons de déconnexion
    disconnectButtons.forEach(function(button, index) {
        console.log(`📌 Attachement événement déconnexion bouton ${index + 1}`);
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('🖱️ Clic détecté sur bouton de déconnexion');
            
            const deviceId = this.getAttribute('data-device-id');
            const deviceName = this.getAttribute('data-device-name');
            disconnectDevice(deviceId, deviceName, this);
        });
    });
    
    // Attacher les événements aux boutons de suppression
    deleteButtons.forEach(function(button, index) {
        console.log(`📌 Attachement événement suppression bouton ${index + 1}`);
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('🖱️ Clic détecté sur bouton de suppression');
            
            const deviceId = this.getAttribute('data-device-id');
            const deviceName = this.getAttribute('data-device-name');
            deleteDevice(deviceId, deviceName, this);
        });
    });
    
    console.log('✅ Initialisation terminée');
}

// Lancer l'initialisation
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBiometricDevices);
} else {
    initBiometricDevices();
}

// Vérification supplémentaire avec jQuery si disponible
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        console.log('✅ jQuery prêt - vérification supplémentaire effectuée');
    });
}


</script>
@endpush