@extends('rapports.pdf.layouts.master')

@section('content')
    <!-- Statistiques générales -->
    <h2>Statistiques générales</h2>
    <div class="stats-container">
        <table>
            <tr>
                <td style="width: 50%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                    <h3 style="margin: 0; font-size: 20px; color: #e74c3c;">{{ $totalRetards }}</h3>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Nombre total de retards</p>
                </td>
                <td style="width: 50%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                    <h3 style="margin: 0; font-size: 20px; color: #f39c12;">{{ round($totalRetards / max(1, count($retardsParEmploye)), 1) }}</h3>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Moyenne de retards par employé</p>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tableau des retards par employé -->
    <h2>Retards par employé</h2>
    @if(count($retardsParEmploye) > 0)
        <table class="table-striped">
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Poste</th>
                    <th>Nombre de retards</th>
                    <th>Durée moyenne</th>
                </tr>
            </thead>
            <tbody>
                @foreach($retardsParEmploye as $retard)
                    <tr>
                        <td>{{ $retard['employe']->prenom }} {{ $retard['employe']->nom }}</td>
                        <td>{{ $retard['employe']->poste->nom ?? 'Non défini' }}</td>
                        <td>{{ $retard['nombre_retards'] }}</td>
                        <td>
                            @if(isset($retard['duree_totale_minutes']) && $retard['nombre_retards'] > 0)
                                {{ round($retard['duree_totale_minutes'] / $retard['nombre_retards']) }} min
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Aucun retard n'a été enregistré pour la période sélectionnée.</p>
    @endif

    <!-- Liste détaillée des retards -->
    <h2>Liste détaillée des retards</h2>
    @if(count($retards) > 0)
        <table class="table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employé</th>
                    <th>Heure prévue</th>
                    <th>Heure d'arrivée</th>
                    <th>Durée du retard</th>
                </tr>
            </thead>
            <tbody>
                @foreach($retards as $retard)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($retard->date)->format('d/m/Y') }}</td>
                        <td>{{ $retard->employe->prenom }} {{ $retard->employe->nom }}</td>
                        <td>{{ $retard->heure_prevue ?? 'N/A' }}</td>
                        <td>{{ $retard->heure_arrivee }}</td>
                        <td>
                            @if($retard->heure_prevue && $retard->heure_arrivee)
                                @php
                                    $heurePrevue = \Carbon\Carbon::parse($retard->heure_prevue);
                                    $heureArrivee = \Carbon\Carbon::parse($retard->heure_arrivee);
                                    $dureeRetard = $heureArrivee->diffInMinutes($heurePrevue);
                                @endphp
                                {{ $dureeRetard }} min
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Aucun retard n'a été enregistré pour la période sélectionnée.</p>
    @endif

    <!-- Notes et observations -->
    <h2>Notes et observations</h2>
    <ul>
        <li>Un retard est comptabilisé lorsqu'un employé arrive après l'heure prévue selon son planning.</li>
        <li>La durée du retard est calculée comme la différence entre l'heure d'arrivée et l'heure prévue.</li>
        <li>Les retards répétés peuvent indiquer des problèmes de transport ou d'organisation personnelle.</li>
    </ul>
@endsection
