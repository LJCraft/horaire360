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
                                    <td><span class="badge badge-secondary">{{ strtoupper($device->brand) }}</span></td>
                                    <td>
                                        @if($device->connection_type === 'ip')
                                            <span class="badge badge-info">IP</span>
                                        @else
                                            <span class="badge badge-warning">API</span>
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
$(document).ready(function() {
    $('.btn-test-connection').click(function() {
        const deviceId = $(this).data('device-id');
        const btn = $(this);
        
        btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i>');
        
        $.ajax({
            url: `/biometric-devices/${deviceId}/test-connection`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                btn.prop('disabled', false).html('<i class="bi bi-wifi"></i>');
                alert(response.message);
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-wifi"></i>');
                alert('Erreur lors du test de connexion');
            }
        });
    });
    
    $('.btn-delete').click(function() {
        const deviceId = $(this).data('device-id');
        const deviceName = $(this).data('device-name');
        const btn = $(this);
        
        // Confirmation de suppression
        if (confirm(`Êtes-vous sûr de vouloir supprimer l'appareil "${deviceName}" ?\n\nCette action est irréversible.`)) {
            btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i>');
            
            $.ajax({
                url: `/biometric-devices/${deviceId}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Recharger la page pour mettre à jour la liste
                    location.reload();
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                    alert('Erreur lors de la suppression de l\'appareil');
                }
            });
        }
    });
});
</script>
@endpush