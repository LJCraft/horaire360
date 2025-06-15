@extends('layouts.app')

@section('title', 'Gestion des congés')

@section('page-title', 'Gestion des congés')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="h3">Demandes de congés</h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('conges.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nouvelle demande
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Filtres</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('conges.index') }}" method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="employe" class="form-label">Employé</label>
                <select class="form-select" id="employe" name="employe">
                    <option value="">Tous les employés</option>
                    @foreach($employes as $emp)
                        <option value="{{ $emp->id }}" {{ (isset($employe) && $employe == $emp->id) ? 'selected' : '' }}>
                            {{ $emp->prenom }} {{ $emp->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="statut" class="form-label">Statut</label>
                <select class="form-select" id="statut" name="statut">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente" {{ (isset($statut) && $statut == 'en_attente') ? 'selected' : '' }}>En attente</option>
                    <option value="approuve" {{ (isset($statut) && $statut == 'approuve') ? 'selected' : '' }}>Approuvé</option>
                    <option value="refuse" {{ (isset($statut) && $statut == 'refuse') ? 'selected' : '' }}>Refusé</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Tous les types</option>
                    <option value="conge_paye" {{ (isset($type) && $type == 'conge_paye') ? 'selected' : '' }}>Congé payé</option>
                    <option value="maladie" {{ (isset($type) && $type == 'maladie') ? 'selected' : '' }}>Maladie</option>
                    <option value="sans_solde" {{ (isset($type) && $type == 'sans_solde') ? 'selected' : '' }}>Sans solde</option>
                    <option value="autre" {{ (isset($type) && $type == 'autre') ? 'selected' : '' }}>Autre</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filtrer
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Employé</th>
                        <th>Période</th>
                        <th>Durée</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Date de demande</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conges as $conge)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($conge->employe->prenom . ' ' . $conge->employe->nom) }}&size=32&color=7F9CF5&background=EBF4FF" alt="{{ $conge->employe->prenom }} {{ $conge->employe->nom }}" class="rounded-circle me-2">
                                    {{ $conge->employe->prenom }} {{ $conge->employe->nom }}
                                </div>
                            </td>
                            <td>{{ $conge->date_debut->format('d/m/Y') }} au {{ $conge->date_fin->format('d/m/Y') }}</td>
                            <td>{{ $conge->nombre_jours }} jour(s)</td>
                            <td>{{ $conge->type_libelle }}</td>
                            <td>
                                @if($conge->statut === 'en_attente')
                                    <span class="badge bg-warning">En attente</span>
                                @elseif($conge->statut === 'approuve')
                                    <span class="badge bg-success">Approuvé</span>
                                @elseif($conge->statut === 'refuse')
                                    <span class="badge bg-danger">Refusé</span>
                                @endif
                            </td>
                            <td>{{ $conge->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('conges.show', $conge) }}" class="btn btn-info" data-bs-toggle="tooltip" title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    @if($conge->statut === 'en_attente')
                                        @if(auth()->user()->is_admin || auth()->user()->employe && auth()->user()->employe->id === $conge->employe_id)
                                            <a href="{{ route('conges.edit', $conge) }}" class="btn btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endif
                                        
                                        @if(auth()->user()->is_admin || auth()->user()->hasRole('Responsable RH') || auth()->user()->hasRole('Manager'))
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approuverModal{{ $conge->id }}" title="Approuver">
                                                <i class="bi bi-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#refuserModal{{ $conge->id }}" title="Refuser">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        @endif
                                        
                                        @if(auth()->user()->is_admin || auth()->user()->employe && auth()->user()->employe->id === $conge->employe_id)
                                            <button type="button" class="btn btn-danger" onclick="confirmDelete('delete-conge-{{ $conge->id }}')" data-bs-toggle="tooltip" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <form id="delete-conge-{{ $conge->id }}" action="{{ route('conges.destroy', $conge) }}" method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endif
                                    @endif
                                </div>
                                
                                <!-- Modal Approuver -->
                                <div class="modal fade" id="approuverModal{{ $conge->id }}" tabindex="-1" aria-labelledby="approuverModalLabel{{ $conge->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('conges.approuver', $conge) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="approuverModalLabel{{ $conge->id }}">Approuver la demande</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Vous êtes sur le point d'approuver la demande de congé de <strong>{{ $conge->employe->prenom }} {{ $conge->employe->nom }}</strong> pour la période du <strong>{{ $conge->date_debut->format('d/m/Y') }}</strong> au <strong>{{ $conge->date_fin->format('d/m/Y') }}</strong>.</p>
                                                    
                                                    <div class="mb-3">
                                                        <label for="commentaire_reponse{{ $conge->id }}" class="form-label">Commentaire (optionnel)</label>
                                                        <textarea class="form-control" id="commentaire_reponse{{ $conge->id }}" name="commentaire_reponse" rows="3"></textarea>
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
                                <div class="modal fade" id="refuserModal{{ $conge->id }}" tabindex="-1" aria-labelledby="refuserModalLabel{{ $conge->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('conges.refuser', $conge) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="refuserModalLabel{{ $conge->id }}">Refuser la demande</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Vous êtes sur le point de refuser la demande de congé de <strong>{{ $conge->employe->prenom }} {{ $conge->employe->nom }}</strong> pour la période du <strong>{{ $conge->date_debut->format('d/m/Y') }}</strong> au <strong>{{ $conge->date_fin->format('d/m/Y') }}</strong>.</p>
                                                    
                                                    <div class="mb-3">
                                                        <label for="commentaire_reponse{{ $conge->id }}" class="form-label">Motif du refus <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" id="commentaire_reponse{{ $conge->id }}" name="commentaire_reponse" rows="3" required></textarea>
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-3">Aucune demande de congé trouvée</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-end mt-3 px-3 pb-3">
            {{ $conges->links() }}
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