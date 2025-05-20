@extends('rapports.pdf.layouts.master')

@section('content')
    <h2>Rapport Global de Présence – {{ $periodeLabel ?? $titre }}</h2>
    
    <div class="rapport-info">
        @php
            // Utiliser directement les dates fournies par le contrôleur
            $dateFinAffichage = \Carbon\Carbon::parse($dateFin)->copy();
            $periodeAffichage = ($dateDebut ? \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') : 'Début') . ' - ' . 
                               ($dateFinAffichage ? $dateFinAffichage->format('d/m/Y') : 'Aujourd\'hui');
        @endphp
        <p><strong>Période:</strong> {{ $periodeLabel ?? $periodeAffichage }}</p>
        <p><strong>Nombre d'employés:</strong> {{ count($employes) }}</p>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="table-striped">
            <thead>
                <tr>
                    <th class="employe-col">Employé</th>
                    @foreach($jours as $jour)
                    <th colspan="2" class="date-col">{{ \Carbon\Carbon::parse($jour)->format('d-M') }}</th>
                    @endforeach
                </tr>
                <tr>
                    <th></th>
                    @foreach($jours as $jour)
                    <th class="ar-col">AR</th>
                    <th class="dp-col">DP</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($employes as $employe)
                <tr>
                    <td class="employe-col">{{ $employe->nom }} {{ $employe->prenom }}</td>
            
                    @foreach($jours as $jour)
                        @php
                            $presence = $presences->where('employe_id', $employe->id)
                                                ->where('date', $jour)
                                                ->first();
                            
                            // Vérifier si c'est un jour de repos selon le planning
                            $jourSemaine = \Carbon\Carbon::parse($jour)->dayOfWeek;
                            $estRepos = false;
                            
                            // Vérifier dans les plannings si ce jour est marqué comme repos
                            $planning = \App\Models\Planning::where('employe_id', $employe->id)
                                ->where(function($query) use ($jour) {
                                    $query->where('date_debut', '<=', $jour)
                                          ->where('date_fin', '>=', $jour);
                                })
                                ->first();
                                
                            if ($planning) {
                                // Vérifier si ce jour de la semaine est un jour de repos dans le planning
                                $joursSemaine = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
                                $jourNom = $joursSemaine[$jourSemaine];
                                
                                if ($planning->{$jourNom . '_repos'} === 1) {
                                    $estRepos = true;
                                }
                            }
                        @endphp
                        
                        @if($estRepos)
                            <td class="ar-col">R</td>
                            <td class="dp-col">R</td>
                        @else
                            <td class="ar-col">
                                {{ $presence && $presence->heure_arrivee ? \Carbon\Carbon::parse($presence->heure_arrivee)->format('H:i') : '-' }}
                            </td>
                            <td class="dp-col">
                                {{ $presence && $presence->heure_depart ? \Carbon\Carbon::parse($presence->heure_depart)->format('H:i') : '-' }}
                            </td>
                        @endif
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="page-break"></div>
    
    <h2>Notes et informations</h2>
    <ul>
        <li><strong>Légende:</strong> AR: Heure d'arrivée | DP: Heure de départ | R : Jour de repos selon le planning | - : Aucun pointage</li>
        <li>Ce rapport présente uniquement les heures de pointage sans analyse ni calcul.</li>
        <li>Période analysée : du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}</li>
    </ul>
@endsection

@section('styles')
<style>
    .rapport-info {
        margin-bottom: 15px;
        font-size: 8pt;
    }
    
    .rapport-info p {
        margin: 3px 0;
    }
    
    .employe-col {
        text-align: left;
        width: 15%;
        font-weight: bold;
    }
    
    .date-col {
        font-size: 6pt;
        background-color: #e9ecef;
    }
    
    .ar-col, .dp-col {
        width: 4%;
        font-size: 6pt;
    }
    
    .ar-col {
        background-color: #f8f9fa;
    }
    
    /* Gestion des sauts de page */
    .page-break {
        page-break-after: always;
    }
    
    /* Pour éviter les sauts de page au milieu des lignes */
    tr {
        page-break-inside: avoid;
    }
</style>
@endsection
