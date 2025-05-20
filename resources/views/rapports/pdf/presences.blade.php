@extends('rapports.pdf.layouts.master')

@section('content')
        <!-- Statistiques -->
        <div class="stats-container">
            <div class="stat-box">
                <h3>{{ $totalPresences }}</h3>
                <p>Présences totales</p>
            </div>
            <div class="stat-box">
                <h3 class="text-warning">{{ $totalRetards }}</h3>
                <p>Retards</p>
            </div>
            <div class="stat-box">
                <h3 class="text-danger">{{ $totalDepartsAnticipes }}</h3>
                <p>Départs anticipés</p>
            </div>
            <div class="stat-box">
                <h3 class="text-success">{{ $pourcentageAssiduite }}%</h3>
                <p>Taux d'assiduité</p>
            </div>
        </div>
        
        <h2>Liste détaillée des présences</h2>
        <div style="overflow-x: auto;">
        <table class="table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employé</th>
                    <th>Poste</th>
                    <th>Arrivée</th>
                    <th>Départ</th>
                    <th class="text-center">Durée</th>
                    <th class="text-center">Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($presences as $presence)
                    <tr>
                        <td>{{ Carbon\Carbon::parse($presence->date)->format('d/m/Y') }}</td>
                        <td>
                            @if($presence->employe)
                                {{ $presence->employe->prenom }} {{ $presence->employe->nom }}
                            @else
                                <span class="text-danger">Employé supprimé</span>
                            @endif
                        </td>
                        <td>
                            @if($presence->employe && $presence->employe->poste)
                                {{ $presence->employe->poste->nom }}
                            @else
                                <span class="text-danger">-</span>
                            @endif
                        </td>
                        <td>{{ Carbon\Carbon::parse($presence->heure_arrivee)->format('H:i') }}</td>
                        <td>{{ $presence->heure_depart ? Carbon\Carbon::parse($presence->heure_depart)->format('H:i') : '-' }}</td>
                        <td class="text-center">
                            @if($presence->heure_depart)
                                @php
                                    $debut = Carbon\Carbon::parse($presence->heure_arrivee);
                                    $fin = Carbon\Carbon::parse($presence->heure_depart);
                                    if ($fin < $debut) {
                                        $fin->addDay();
                                    }
                                    $duree = $debut->diff($fin);
                                @endphp
                                {{ $duree->format('%H:%I') }}
                            @else
                                <span class="badge badge-secondary">En cours</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($presence->retard)
                                <span class="badge badge-warning">Retard</span>
                            @elseif($presence->depart_anticipe)
                                <span class="badge badge-danger">Départ anticipé</span>
                            @else
                                <span class="badge badge-success">Conforme</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Aucune présence trouvée pour les critères sélectionnés</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        
        <div class="page-break"></div>
        
        <h2>Notes et informations</h2>
        <ul>
            <li>Les retards sont définis comme un pointage effectué après l'heure prévue d'arrivée.</li>
            <li>Les départs anticipés sont définis comme un pointage de sortie effectué avant l'heure prévue de départ.</li>
            <li>Le taux d'assiduité correspond au pourcentage de présences sans retard ni départ anticipé.</li>
            <li>Période analysée : du {{ Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}</li>
        </ul>
    </div>

    <!-- Le footer est déjà inclus dans le layout master -->
@endsection