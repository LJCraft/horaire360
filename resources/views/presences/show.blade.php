@extends('layouts.app')

@section('title', 'Détails du pointage')

@section('page-title', 'Détails du pointage')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="h3">Détails du pointage</h1>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="{{ route('presences.edit', $presence) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Modifier
            </a>
            <a href="{{ route('presences.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Informations sur le pointage</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tbody>
                        <tr>
                            <th style="width: 30%">Employé</th>
                            <td>
                                <a href="{{ route('employes.show', $presence->employe) }}">
                                    {{ $presence->employe->prenom }} {{ $presence->employe->nom }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <td>{{ $presence->date->format('d/m/Y') }} ({{ $presence->date->locale('fr')->isoFormat('dddd') }})</td>
                        </tr>
                        <tr>
                            <th>Heure d'arrivée</th>
                            <td>{{ $presence->heure_arrivee }}</td>
                        </tr>
                        <tr>
                            <th>Heure de départ</th>
                            <td>{{ $presence->heure_depart ?: 'Non renseignée' }}</td>
                        </tr>
                        <tr>
                            <th>Durée</th>
                            <td>{{ $presence->duree ? number_format($presence->duree, 2) . ' heures' : 'Non calculable' }}</td>
                        </tr>
                        <tr>
                            <th>Statut</th>
                            <td>
                                @if($presence->retard)
                                    <span class="badge bg-warning">Retard</span>
                                @else
                                    <span class="badge bg-success">À l'heure</span>
                                @endif
                                
                                @if($presence->depart_anticipe && $presence->heure_depart)
                                    <span class="badge bg-warning">Départ anticipé</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Commentaire</th>
                            <td>{{ $presence->commentaire ?: 'Aucun commentaire' }}</td>
                        </tr>
                        <tr>
                            <th>Créé le</th>
                            <td>{{ $presence->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Dernière mise à jour</th>
                            <td>{{ $presence->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('presences.edit', $presence) }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Modifier ce pointage
                    </a>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete('delete-presence-{{ $presence->id }}')">
                        <i class="bi bi-trash"></i> Supprimer ce pointage
                    </button>
                    <form id="delete-presence-{{ $presence->id }}" action="{{ route('presences.destroy', $presence) }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
        
        @if($planning)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Planning associé</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tbody>
                        <tr>
                            <th>Horaire prévu</th>
                            <td>{{ $planning->heure_debut }} - {{ $planning->heure_fin }}</td>
                        </tr>
                        <tr>
                            <th>Durée prévue</th>
                            <td>{{ $planning->duree }} heures</td>
                        </tr>
                        <tr>
                            <th>Écart d'arrivée</th>
                            <td>
                                @php
                                    $heureArrivee = \Carbon\Carbon::parse($presence->heure_arrivee);
                                    $heureDebutPlanning = \Carbon\Carbon::parse($planning->heure_debut);
                                    $diffMinutes = $heureArrivee->diffInMinutes($heureDebutPlanning, false);
                                @endphp
                                
                                @if($diffMinutes > 0)
                                    <span class="text-danger">{{ $diffMinutes }} minutes de retard</span>
                                @elseif($diffMinutes < 0)
                                    <span class="text-success">{{ abs($diffMinutes) }} minutes d'avance</span>
                                @else
                                    <span class="text-success">À l'heure exacte</span>
                                @endif
                            </td>
                        </tr>
                        @if($presence->heure_depart)
                        <tr>
                            <th>Écart de départ</th>
                            <td>
                                @php
                                    $heureDepart = \Carbon\Carbon::parse($presence->heure_depart);
                                    $heureFinPlanning = \Carbon\Carbon::parse($planning->heure_fin);
                                    $diffMinutes = $heureDepart->diffInMinutes($heureFinPlanning, false);
                                @endphp
                                
                                @if($diffMinutes < 0)
                                    <span class="text-danger">{{ abs($diffMinutes) }} minutes avant l'heure prévue</span>
                                @elseif($diffMinutes > 0)
                                    <span class="text-success">{{ $diffMinutes }} minutes après l'heure prévue</span>
                                @else
                                    <span class="text-success">À l'heure exacte</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                <div class="d-grid mt-3">
                    <a href="{{ route('plannings.show', $planning) }}" class="btn btn-outline-primary">
                        <i class="bi bi-calendar-week"></i> Voir le planning
                    </a>
                </div>
            </div>
        </div>
        @else
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Aucun planning n'a été trouvé pour cette date et cet employé.
                </div>
            </div>
        </div>
        @endif
        
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Informations sur l'employé</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    @if($presence->employe->photo_profil && file_exists(public_path('storage/photos/' . $presence->employe->photo_profil)))
                        <img src="{{ asset('storage/photos/' . $presence->employe->photo_profil) }}" 
                            alt="Photo de {{ $presence->employe->prenom }} {{ $presence->employe->nom }}" 
                            class="rounded-circle" 
                            style="width: 100px; height: 100px; object-fit: cover;">
                    @else
                        <div class="bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                            style="width: 100px; height: 100px; font-size: 2.5rem;">
                            {{ strtoupper(substr($presence->employe->prenom, 0, 1)) }}{{ strtoupper(substr($presence->employe->nom, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <h5 class="text-center">{{ $presence->employe->prenom }} {{ $presence->employe->nom }}</h5>
                <p class="text-center text-muted">{{ $presence->employe->poste->titre }}</p>
                <hr>
                <div class="d-grid gap-2">
                    <a href="{{ route('employes.show', $presence->employe) }}" class="btn btn-outline-primary">
                        <i class="bi bi-person"></i> Voir la fiche employé
                    </a>
                    <a href="{{ route('presences.index', ['employe' => $presence->employe->id]) }}" class="btn btn-outline-primary">
                        <i class="bi bi-clock"></i> Voir tous les pointages
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer ce pointage ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">Supprimer</button>
            </div>
        </div>
    </div>
</div>
@endsection