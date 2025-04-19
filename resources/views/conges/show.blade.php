@extends('layouts.app')

@section('title', 'Détails de la demande de congé')

@section('page-title', 'Détails de la demande de congé')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="h3">Détails de la demande de congé</h1>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            @if($conge->statut === 'en_attente')
                @if(auth()->user()->is_admin || auth()->user()->employe && auth()->user()->employe->id === $conge->employe_id)
                    <a href="{{ route('conges.edit', $conge) }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                @endif
            @endif
            <a href="{{ route('conges.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Informations sur la demande</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tbody>
                        <tr>
                            <th style="width: 30%">Employé</th>
                            <td>
                                <a href="{{ route('employes.show', $conge->employe) }}">
                                    {{ $conge->employe->prenom }} {{ $conge->employe->nom }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Période</th>
                            <td>Du {{ $conge->date_debut->format('d/m/Y') }} au {{ $conge->date_fin->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Durée</th>
                            <td>{{ $conge->nombre_jours }} jour(s)</td>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td>{{ $conge->type_libelle }}</td>
                        </tr>
                        <tr>
                            <th>Motif</th>
                            <td>{{ $conge->motif ?: 'Aucun motif spécifié' }}</td>
                        </tr>
                        <tr>
                            <th>Statut</th>
                            <td>
                                @if($conge->statut === 'en_attente')
                                    <span class="badge bg-warning">En attente</span>
                                @elseif($conge->statut === 'approuve')
                                    <span class="badge bg-success">Approuvé</span>
                                @elseif($conge->statut === 'refuse')
                                    <span class="badge bg-danger">Refusé</span>
                                @endif
                            </td>
                        </tr>
                        @if($conge->statut !== 'en_attente')
                            <tr>
                                <th>Traité par</th>
                                <td>{{ $conge->traitePar->name ?? 'Non spécifié' }}</td>
                            </tr>
                            <tr>
                                <th>Commentaire</th>
                                <td>{{ $conge->commentaire_reponse ?: 'Aucun commentaire' }}</td>
                            </tr>
                        @endif
                        <tr>
                            <th>Date de demande</th>
                            <td>{{ $conge->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Dernière mise à jour</th>
                            <td>{{ $conge->updated_at->format('d/m/Y H:i') }}</td>
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
                    @if($conge->statut === 'en_attente')
                        @if(auth()->user()->is_admin || auth()->user()->employe && auth()->user()->employe->id === $conge->employe_id)
                            <a href="{{ route('conges.edit', $conge) }}" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Modifier cette demande
                            </a>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete('delete-conge-{{ $conge->id }}')">
                                <i class="bi bi-trash"></i> Supprimer cette demande
                            </button>
                            <form id="delete-conge-{{ $conge->id }}" action="{{ route('conges.destroy', $conge) }}" method="POST" class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                        
                        @if(auth()->user()->is_admin || auth()->user()->hasRole('Responsable RH') || auth()->user()->hasRole('Manager'))
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approuverModal">
                                <i class="bi bi-check"></i> Approuver cette demande
                            </button>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#refuserModal">
                                <i class="bi bi-x"></i> Refuser cette demande
                            </button>
                        @endif
                    @endif
                    
                    <a href="{{ route('conges.index', ['employe' => $conge->employe_id]) }}" class="btn btn-outline-primary">
                        <i class="bi bi-calendar-x"></i> Voir toutes les demandes de cet employé
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Informations sur l'employé</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($conge->employe->prenom . ' ' . $conge->employe->nom) }}&size=100&color=7F9CF5&background=EBF4FF" alt="{{ $conge->employe->prenom }} {{ $conge->employe->nom }}" class="rounded-circle">
                </div>
                <h5 class="text-center">{{ $conge->employe->prenom }} {{ $conge->employe->nom }}</h5>
                <p class="text-center text-muted">{{ $conge->employe->poste->titre }}</p>
                <hr>
                <div class="d-grid gap-2">
                    <a href="{{ route('employes.show', $conge->employe) }}" class="btn btn-outline-primary">
                        <i class="bi bi-person"></i> Voir la fiche employé
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Approuver -->
<div class="modal fade" id="approuverModal" tabindex="-1" aria-labelledby="approuverModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('conges.approuver', $conge) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="approuverModalLabel">Approuver la demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Vous êtes sur le point d'approuver la demande de congé de <strong>{{ $conge->employe->prenom }} {{ $conge->employe->nom }}</strong> pour la période du <strong>{{ $conge->date_debut->format('d/m/Y') }}</strong> au <strong>{{ $conge->date_fin->format('d/m/Y') }}</strong>.</p>
                    
                    <div class="mb-3">
                        <label for="commentaire_reponse" class="form-label">Commentaire (optionnel)</label>
                        <textarea class="form-control" id="commentaire_reponse" name="commentaire_reponse" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Approuver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Refuser -->
<div class="modal fade" id="refuserModal" tabindex="-1" aria-labelledby="refuserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('conges.refuser', $conge) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="refuserModalLabel">Refuser la demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Vous êtes sur le point de refuser la demande de congé de <strong>{{ $conge->employe->prenom }} {{ $conge->employe->nom }}</strong> pour la période du <strong>{{ $conge->date_debut->format('d/m/Y') }}</strong> au <strong>{{ $conge->date_fin->format('d/m/Y') }}</strong>.</p>
                    
                    <div class="mb-3">
                        <label for="commentaire_reponse" class="form-label">Motif du refus <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="commentaire_reponse" name="commentaire_reponse" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Refuser</button>
                </div>
            </form>
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
                Êtes-vous sûr de vouloir supprimer cette demande de congé ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">Supprimer</button>
            </div>
        </div>
    </div>
</div>
@endsection