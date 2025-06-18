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
            <h3 class="text-success">{{ $totalEmployesConcernés ?? count($pointages->groupBy('employe_id')) }}</h3>
            <p>Employés avec pointages</p>
        </div>
        <div class="stat-box" style="width: 30%;">
            <h3 class="text-warning">100%</h3>
            <p>Authentification réussie</p>
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
                <th>Terminal</th>
                <th>Appareil</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pointages as $pointage)
            @php
                $metaData = json_decode($pointage->meta_data, true);
                
                // Extraire l'heure d'arrivée même si c'est un timestamp complet
                $heureArrivee = $pointage->heure_arrivee;
                if (preg_match('/(\d{2}:\d{2}:\d{2})/', $heureArrivee, $matches)) {
                    $heureArrivee = $matches[1];
                }
                
                // Extraire l'heure de départ même si c'est un timestamp complet
                $heureDepart = 'N/A';
                if ($pointage->heure_depart) {
                    $heureDepart = $pointage->heure_depart;
                    if (preg_match('/(\d{2}:\d{2}:\d{2})/', $heureDepart, $matches)) {
                        $heureDepart = $matches[1];
                    }
                }
            @endphp
            <tr>
                <td>{{ $pointage->id }}</td>
                <td>{{ $pointage->employe->nom }} {{ $pointage->employe->prenom }}</td>
                <td>{{ date('d/m/Y', strtotime($pointage->date)) }}</td>
                <td>{{ $heureArrivee }}</td>
                <td>{{ $heureDepart }}</td>
                <td class="text-center">Terminal 1</td>
                <td>{{ $metaData['device_info']['model'] ?? 'Reconnaissance faciale mobile' }}</td>
                <td>{{ $metaData['type'] ?? 'Biométrique' }}</td>
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
