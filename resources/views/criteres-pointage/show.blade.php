@extends('layouts.app')

@section('title', 'Détails du critère de pointage')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i> Détails du critère de pointage
                    </h5>
                    <div>
                        <a href="{{ route('criteres-pointage.edit', $criterePointage) }}" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <a href="{{ route('criteres-pointage.index') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Informations générales</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped">
                                        <tbody>
                                            <tr>
                                                <th style="width: 40%">Niveau de configuration</th>
                                                <td>
                                                    @if ($criterePointage->niveau === 'individuel')
                                                        <span class="badge bg-info">Individuel</span>
                                                    @else
                                                        <span class="badge bg-primary">Départemental</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Cible</th>
                                                <td>
                                                    @if ($criterePointage->niveau === 'individuel')
                                                        {{ $criterePointage->employe->nom }} {{ $criterePointage->employe->prenom }}
                                                    @else
                                                        {{ $criterePointage->departement->nom }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Période</th>
                                                <td>
                                                    @if ($criterePointage->periode === 'jour')
                                                        <span class="badge bg-secondary">Jour</span>
                                                    @elseif ($criterePointage->periode === 'semaine')
                                                        <span class="badge bg-secondary">Semaine</span>
                                                    @else
                                                        <span class="badge bg-secondary">Mois</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Date de début</th>
                                                <td>{{ $criterePointage->date_debut->format('d/m/Y') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Date de fin</th>
                                                <td>{{ $criterePointage->date_fin->format('d/m/Y') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Statut</th>
                                                <td>
                                                    @if ($criterePointage->actif)
                                                        <span class="badge bg-success">Actif</span>
                                                    @else
                                                        <span class="badge bg-danger">Inactif</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Créé par</th>
                                                <td>{{ $criterePointage->createur->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>Date de création</th>
                                                <td>{{ $criterePointage->created_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Dernière modification</th>
                                                <td>{{ $criterePointage->updated_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Paramètres de pointage</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped">
                                        <tbody>
                                            <tr>
                                                <th style="width: 40%">Nombre de pointages requis</th>
                                                <td>
                                                    {{ $criterePointage->nombre_pointages }}
                                                    @if ($criterePointage->nombre_pointages == 1)
                                                        <span class="text-muted">(présence uniquement)</span>
                                                    @else
                                                        <span class="text-muted">(arrivée et départ)</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Tolérance avant</th>
                                                <td>{{ $criterePointage->tolerance_avant }} minutes</td>
                                            </tr>
                                            <tr>
                                                <th>Tolérance après</th>
                                                <td>{{ $criterePointage->tolerance_apres }} minutes</td>
                                            </tr>
                                            <tr>
                                                <th>Durée de pause</th>
                                                <td>{{ $criterePointage->duree_pause }} minutes</td>
                                            </tr>
                                            <tr>
                                                <th>Source de pointage</th>
                                                <td>
                                                    @if ($criterePointage->source_pointage === 'biometrique')
                                                        <span class="badge bg-info">Biométrique uniquement</span>
                                                    @elseif ($criterePointage->source_pointage === 'manuel')
                                                        <span class="badge bg-warning text-dark">Manuel uniquement</span>
                                                    @else
                                                        <span class="badge bg-secondary">Tous types de pointage</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    
                                    <div class="alert alert-info mt-3">
                                        <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i> Règles appliquées</h6>
                                        <hr>
                                        @if ($criterePointage->nombre_pointages == 1)
                                            <p>
                                                <strong>Mode 1 pointage :</strong> L'employé doit effectuer un seul pointage dans la journée, qui doit se situer dans la plage horaire suivante :
                                            </p>
                                            <ul>
                                                <li>Plage de pointage valide : [heure début - {{ $criterePointage->tolerance_avant }} min] → [heure fin + {{ $criterePointage->tolerance_apres }} min]</li>
                                            </ul>
                                            <p>
                                                Si le pointage est valide :
                                            </p>
                                            <ul>
                                                <li>L'employé est considéré comme présent pour la journée entière</li>
                                                <li>Les heures prévues et les heures faites sont calculées à partir du planning, moins la pause de {{ $criterePointage->duree_pause }} minutes</li>
                                            </ul>
                                        @else
                                            <p>
                                                <strong>Mode 2 pointages :</strong> L'employé doit effectuer deux pointages (arrivée et départ) dans les plages horaires suivantes :
                                            </p>
                                            <ul>
                                                <li>Plage d'arrivée valide : [heure début - {{ $criterePointage->tolerance_avant }} min] → [heure début + {{ $criterePointage->tolerance_apres }} min]</li>
                                                <li>Plage de départ valide : [heure fin - {{ $criterePointage->tolerance_apres }} min] → [heure fin + {{ $criterePointage->tolerance_apres }} min]</li>
                                            </ul>
                                            <p>
                                                Si les deux pointages sont valides :
                                            </p>
                                            <ul>
                                                <li>L'employé est considéré comme présent</li>
                                                <li>Les heures faites sont calculées à partir des pointages réels, moins la pause de {{ $criterePointage->duree_pause }} minutes</li>
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
