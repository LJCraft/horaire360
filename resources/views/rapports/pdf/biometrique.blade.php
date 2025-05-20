@extends('rapports.pdf.layouts.master')

@section('content')
    <!-- Statistiques des pointages biométriques -->
    <h2>Statistiques des pointages biométriques</h2>
    <div class="stats-container">
        <div class="stat-box" style="width: 30%;">
            <h3 class="text-info">{{ $totalPointages ?? count($pointages) }}</h3>
            <p>Pointages biométriques</p>
        </div>
        <div class="stat-box" style="width: 30%;">
            <h3 class="text-success">{{ $scoreMoyenBiometrique ?? 'N/A' }}</h3>
            <p>Score biométrique moyen</p>
        </div>
        <div class="stat-box" style="width: 30%;">
            <h3 class="text-warning">{{ count($pointages->groupBy('employe_id')) }}</h3>
            <p>Employés concernés</p>
        </div>
    </div>
    
    <div style="overflow-x: auto;">
    <table class="table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Employé</th>
                <th>Date</th>
                <th>Arrivée</th>
                <th>Départ</th>
                <th>Score biométrique</th>
                <th>Appareil</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pointages as $pointage)
            @php
                $metaData = json_decode($pointage->meta_data, true);
                $scoreArrivee = isset($metaData['biometric_verification']['confidence_score']) 
                    ? number_format($metaData['biometric_verification']['confidence_score'] * 100, 1) . '%' 
                    : 'N/A';
            @endphp
            <tr>
                <td>{{ $pointage->id }}</td>
                <td>{{ $pointage->employe->nom }} {{ $pointage->employe->prenom }}</td>
                <td>{{ date('d/m/Y', strtotime($pointage->date)) }}</td>
                <td>{{ $pointage->heure_arrivee }}</td>
                <td>{{ $pointage->heure_depart ?: 'N/A' }}</td>
                <td class="text-center 
                    @if(isset($metaData['biometric_verification']['confidence_score']) && $metaData['biometric_verification']['confidence_score'] >= 0.9) text-success 
                    @elseif(isset($metaData['biometric_verification']['confidence_score']) && $metaData['biometric_verification']['confidence_score'] >= 0.7) text-warning 
                    @elseif(isset($metaData['biometric_verification']['confidence_score'])) text-danger @endif">
                    {{ $scoreArrivee }}
                </td>
                <td>{{ $metaData['device_info']['model'] ?? 'N/A' }}</td>
                <td>{{ $metaData['type'] ?? 'Standard' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">Aucun pointage biométrique trouvé pour cette période</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    
@endsection
