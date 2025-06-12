@extends('rapports.pdf.layouts.master')

@section('content')
    <!-- Statistiques générales -->
    <h2>Statistiques générales</h2>
    <div class="stats-container">
        <div class="stat-box" style="width: 30%;">
            <h3 class="text-info">{{ $totalJoursOuvrables }}</h3>
            <p>Jours ouvrables</p>
        </div>
        <div class="stat-box" style="width: 30%;">
            <h3 class="text-danger">{{ $totalAbsences }}</h3>
            <p>Jours d'absence</p>
        </div>
        <div class="stat-box" style="width: 30%;">
            <h3 class="text-warning">{{ $tauxGlobalAbsenteisme }}%</h3>
            <p>Taux d'absentéisme</p>
        </div>
    </div>

    <!-- Tableau des absences par employé -->
    <h2>Absences par employé</h2>
    @if(count($absences) > 0)
        <div style="overflow-x: auto;">
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
        </div>
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
