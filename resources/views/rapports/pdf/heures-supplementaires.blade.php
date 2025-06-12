@extends('rapports.pdf.layouts.master')

@section('title', 'Rapport des heures supplémentaires')

@section('content')
<div class="container">
    <h2 class="text-center mb-4">Rapport des heures supplémentaires</h2>
    <p class="text-center mb-4">Période du {{ $dateDebut->format('d/m/Y') }} au {{ $dateFin->format('d/m/Y') }}</p>

    @if($heuresSupplementaires->count() > 0)
        <div class="mb-4">
            <table class="table table-bordered">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th>Employé</th>
                        <th>Département</th>
                        <th>Poste</th>
                        <th>Date</th>
                        <th>Fin prévue</th>
                        <th>Départ réel</th>
                        <th>Heures supp.</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($heuresSupplementaires as $hs)
                        <tr>
                            <td>{{ $hs->employe->nom }} {{ $hs->employe->prenom }}</td>
                            <td>{{ $hs->employe->poste->departement->nom ?? 'N/A' }}</td>
                            <td>{{ $hs->employe->poste->nom ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($hs->date)->format('d/m/Y') }}</td>
                            <td>{{ $hs->heure_fin_prevue }}</td>
                            <td>{{ $hs->heure_depart_reelle }}</td>
                            <td>{{ floor($hs->heures_supplementaires / 60) }}h{{ $hs->heures_supplementaires % 60 }}</td>
                            <td>{{ ucfirst($hs->source) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Résumé des heures supplémentaires -->
        <div class="mt-4">
            <h4>Résumé des heures supplémentaires</h4>
            <div class="row">
                <div class="col-md-4">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total des heures supplémentaires</h5>
                            <p class="card-text">
                                @php
                                    $totalMinutes = $heuresSupplementaires->sum('heures_supplementaires');
                                    $heures = floor($totalMinutes / 60);
                                    $minutes = $totalMinutes % 60;
                                @endphp
                                {{ $heures }}h{{ $minutes }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Moyenne par employé</h5>
                            <p class="card-text">
                                @php
                                    $employesUniques = $heuresSupplementaires->pluck('employe.id')->unique()->count();
                                    if ($employesUniques > 0) {
                                        $moyenneMinutes = round($totalMinutes / $employesUniques);
                                        $heuresMoyenne = floor($moyenneMinutes / 60);
                                        $minutesMoyenne = $moyenneMinutes % 60;
                                        echo $heuresMoyenne . 'h' . $minutesMoyenne;
                                    } else {
                                        echo '0h00';
                                    }
                                @endphp
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Nombre d'employés concernés</h5>
                            <p class="card-text">{{ $employesUniques }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            Aucune heure supplémentaire trouvée pour la période sélectionnée.
        </div>
    @endif

    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
</div>
@endsection
