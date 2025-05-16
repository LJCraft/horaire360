@extends('rapports.pdf.layouts.master')

@section('content')
    <!-- Statistiques de ponctualité et assiduité -->
    <h2>Performance des employés</h2>
    
    @if(count($statistiques) > 0)
        <table class="table-striped">
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Service</th>
                    <th>Taux de ponctualité</th>
                    <th>Taux d'assiduité</th>
                    <th>Heures travaillées</th>
                    <th>Heures prévues</th>
                    <th>Retards</th>
                    <th>Départs anticipés</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statistiques as $stat)
                <tr>
                    <td>{{ $stat->employe->prenom }} {{ $stat->employe->nom }}</td>
                    <td>{{ $stat->employe->service ? $stat->employe->service->nom : 'N/A' }}</td>
                    <td class="text-center 
                        @if($stat->taux_ponctualite >= 90) text-success 
                        @elseif($stat->taux_ponctualite >= 75) text-warning 
                        @else text-danger @endif">
                        {{ $stat->taux_ponctualite }}%
                    </td>
                    <td class="text-center 
                        @if($stat->taux_assiduite >= 90) text-success 
                        @elseif($stat->taux_assiduite >= 75) text-warning 
                        @else text-danger @endif">
                        {{ $stat->taux_assiduite }}%
                    </td>
                    <td class="text-right">{{ $stat->heures_travaillees ?? 0 }}</td>
                    <td class="text-right">{{ $stat->heures_prevues }}</td>
                    <td class="text-center">{{ $stat->nombre_retards }}</td>
                    <td class="text-center">{{ $stat->nombre_departs_anticipes }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Aucune donnée disponible pour la période sélectionnée.</p>
    @endif

    <!-- Statistiques globales -->
    <h2>Statistiques globales</h2>
    <div class="stats-container">
        <table>
            <tr>
                @php
                    $totalEmployes = count($statistiques);
                    $totalRetards = $statistiques->sum('nombre_retards');
                    $totalDepartsAnticipes = $statistiques->sum('nombre_departs_anticipes');
                    $tauxPonctualiteMoyen = $totalEmployes > 0 ? round($statistiques->sum('taux_ponctualite') / $totalEmployes, 1) : 0;
                    $tauxAssiduiteMoyen = $totalEmployes > 0 ? round($statistiques->sum('taux_assiduite') / $totalEmployes, 1) : 0;
                @endphp
                <td style="width: 25%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                    <h3 style="margin: 0; font-size: 20px; color: #3498db;">{{ $tauxPonctualiteMoyen }}%</h3>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Taux de ponctualité moyen</p>
                </td>
                <td style="width: 25%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                    <h3 style="margin: 0; font-size: 20px; color: #27ae60;">{{ $tauxAssiduiteMoyen }}%</h3>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Taux d'assiduité moyen</p>
                </td>
                <td style="width: 25%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                    <h3 style="margin: 0; font-size: 20px; color: #e74c3c;">{{ $totalRetards }}</h3>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Total des retards</p>
                </td>
                <td style="width: 25%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                    <h3 style="margin: 0; font-size: 20px; color: #f39c12;">{{ $totalDepartsAnticipes }}</h3>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Total des départs anticipés</p>
                </td>
            </tr>
        </table>
    </div>

    <!-- Notes et observations -->
    <h2>Notes et observations</h2>
    <ul>
        <li>Le taux de ponctualité est calculé comme le pourcentage de jours sans retard par rapport au nombre total de jours travaillés.</li>
        <li>Le taux d'assiduité est calculé comme le pourcentage de jours travaillés par rapport au nombre total de jours ouvrables.</li>
        <li>Les performances sont classées comme suit :
            <ul>
                <li>Excellent : taux de ponctualité et d'assiduité ≥ 90%</li>
                <li>Bon : taux de ponctualité et d'assiduité ≥ 75%</li>
                <li>Moyen : taux de ponctualité et d'assiduité ≥ 60%</li>
                <li>Faible : taux de ponctualité et d'assiduité < 60%</li>
            </ul>
        </li>
    </ul>
@endsection
