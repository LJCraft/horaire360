@extends('rapports.pdf.layouts.master')

@section('content')
    <!-- DEBUG: Affichage des variables pour diagnostic -->
    <div style="background: #f0f0f0; padding: 10px; margin-bottom: 20px; font-size: 8pt;">
        <strong>DEBUG INFO:</strong><br>
        employesGroupes défini: {{ isset($employesGroupes) ? 'OUI' : 'NON' }}<br>
        @if(isset($employesGroupes))
            Type de employesGroupes: {{ gettype($employesGroupes) }}<br>
            Nombre d'éléments: {{ is_array($employesGroupes) ? count($employesGroupes) : 'N/A' }}<br>
            @if(is_array($employesGroupes) && count($employesGroupes) > 0)
                Premier élément: {{ json_encode($employesGroupes[0] ?? 'vide') }}<br>
            @endif
        @endif
        employes défini: {{ isset($employes) ? 'OUI' : 'NON' }}<br>
        @if(isset($employes))
            Nombre d'employés: {{ count($employes) }}<br>
        @endif
        jours défini: {{ isset($jours) ? 'OUI' : 'NON' }}<br>
        @if(isset($jours))
            Nombre de jours: {{ count($jours) }}<br>
        @endif
    </div>

    <h2>Rapport Global de Présence – {{ $periodeLabel ?? $titre }}</h2>
    
    <div class="rapport-info">
        @php
            // Utiliser directement les dates fournies par le contrôleur
            $dateFinAffichage = \Carbon\Carbon::parse($dateFin)->copy();
            $periodeAffichage = ($dateDebut ? \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') : 'Début') . ' - ' . 
                               ($dateFinAffichage ? $dateFinAffichage->format('d/m/Y') : 'Aujourd\'hui');
            
            // Compter le nombre total d'employés
            $totalEmployes = 0;
            if(isset($employesGroupes) && is_array($employesGroupes)) {
                foreach($employesGroupes as $groupe) {
                    if(isset($groupe['type']) && $groupe['type'] === 'departement_header') {
                        $totalEmployes += count($groupe['employes'] ?? []);
                    }
                }
            }
        @endphp
        <p><strong>Période:</strong> {{ $periodeLabel ?? $periodeAffichage }}</p>
        <p><strong>Nombre d'employés:</strong> {{ $totalEmployes }}</p>
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
                @if(isset($employesGroupes) && is_array($employesGroupes))
                    @foreach($employesGroupes as $groupe)
                        @if(isset($groupe['type']) && $groupe['type'] === 'departement_header')
                        <!-- En-tête du département -->
                        <tr class="departement-header">
                            <td colspan="{{ 1 + (count($jours) * 2) }}" class="departement-title">
                                {{ $groupe['numero_departement'] }}. Département {{ strtoupper($groupe['nom_departement']) }}
                            </td>
                        </tr>
                        
                        <!-- Employés du département -->
                        @foreach($groupe['employes'] as $employe)
                        <tr>
                            <td class="employe-col">
                                <div class="employe-info">
                                    <strong>{{ strtoupper($employe->nom) }} {{ ucwords(strtolower($employe->prenom)) }}</strong>
                                    @if($employe->poste)
                                        <br><small style="font-size: 6pt; color: #666;">{{ $employe->poste->nom }}</small>
                                    @endif
                                </div>
                            </td>
                    
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
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td colspan="{{ 1 + (count($jours ?? []) * 2) }}" class="text-center">
                            Aucun employé trouvé pour cette période.
                        </td>
                    </tr>
                @endif
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
        padding: 6px 4px;
        vertical-align: top;
    }
    
    .employe-info {
        line-height: 1.3;
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
    
    /* Styles pour les en-têtes de département */
    .departement-header {
        background-color: #f8f9fa;
    }
    
    .departement-title {
        text-align: center;
        font-weight: bold;
        font-size: 9pt;
        padding: 10px 8px;
        background-color: #4e73df;
        color: white;
        border: 2px solid #2e59d9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
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
