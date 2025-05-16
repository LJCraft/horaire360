@extends('rapports.pdf.layouts.master')

@section('content')
    <!-- Statistiques générales -->
    <h2>Statistiques générales</h2>
    <div class="stats-container">
        <table>
            <tr>
                <td style="width: 33%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                    <h3 style="margin: 0; font-size: 20px; color: #3498db;">{{ $totalJoursOuvrables }}</h3>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Jours ouvrables</p>
                </td>
                <td style="width: 33%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                    <h3 style="margin: 0; font-size: 20px; color: #e74c3c;">{{ $totalAbsences }}</h3>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Jours d'absence</p>
                </td>
                <td style="width: 33%; text-align: center; border: 1px solid #ddd; padding: 10px;">
                    <h3 style="margin: 0; font-size: 20px; color: #f39c12;">{{ $tauxGlobalAbsenteisme }}%</h3>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #777;">Taux d'absentéisme</p>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tableau des absences par employé -->
    <h2>Absences par employé</h2>
    @if(count($absences) > 0)
        <table class="table-striped">
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Poste</th>
                    <th>Jours ouvrables</th>
                    <th>Jours d'absence</th>
                    <th>Taux d'absentéisme</th>
                </tr>
            </thead>
            <tbody>
                @foreach($absences as $absence)
                    <tr>
                        <td>{{ $absence['employe']->prenom }} {{ $absence['employe']->nom }}</td>
                        <td>{{ $absence['employe']->poste->nom ?? 'Non défini' }}</td>
                        <td>{{ $absence['jours_ouvrables'] }}</td>
                        <td>{{ $absence['jours_absence'] }}</td>
                        <td>{{ $absence['taux_absenteisme'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Aucune absence n'a été enregistrée pour la période sélectionnée.</p>
    @endif

    <!-- Notes et observations -->
    <h2>Notes et observations</h2>
    <ul>
        <li>Les absences sont calculées comme les jours ouvrables sans pointage.</li>
        <li>Les jours ouvrables sont déterminés selon les plannings actifs des employés.</li>
        <li>Le taux d'absentéisme est calculé comme le ratio entre les jours d'absence et les jours ouvrables.</li>
        <li>Les employés sans absence ne sont pas inclus dans ce rapport.</li>
    </ul>
@endsection
