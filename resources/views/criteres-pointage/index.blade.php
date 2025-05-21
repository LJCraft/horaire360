@extends('layouts.app')

@section('title', 'Configuration des critères de pointage')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i> Configuration des critères de pointage
                    </h5>
                    <a href="{{ route('criteres-pointage.create') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-plus-circle"></i> Nouveau critère
                    </a>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Niveau</th>
                                    <th>Cible</th>
                                    <th>Période</th>
                                    <th>Dates</th>
                                    <th>Pointages</th>
                                    <th>Tolérance</th>
                                    <th>Pause</th>
                                    <th>Source</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($criteres as $critere)
                                    <tr>
                                        <td>
                                            @if ($critere->niveau === 'individuel')
                                                <span class="badge bg-info">Individuel</span>
                                            @else
                                                <span class="badge bg-primary">Départemental</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($critere->niveau === 'individuel')
                                                {{ $critere->employe->nom }} {{ $critere->employe->prenom }}
                                            @else
                                                {{ $critere->departement->nom }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($critere->periode === 'jour')
                                                <span class="badge bg-secondary">Jour</span>
                                            @elseif ($critere->periode === 'semaine')
                                                <span class="badge bg-secondary">Semaine</span>
                                            @else
                                                <span class="badge bg-secondary">Mois</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $critere->date_debut->format('d/m/Y') }} - {{ $critere->date_fin->format('d/m/Y') }}
                                        </td>
                                        <td class="text-center">
                                            {{ $critere->nombre_pointages }}
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                Avant: {{ $critere->tolerance_avant }} min
                                            </span>
                                            <span class="badge bg-light text-dark">
                                                Après: {{ $critere->tolerance_apres }} min
                                            </span>
                                        </td>
                                        <td>
                                            {{ $critere->duree_pause }} min
                                        </td>
                                        <td>
                                            @if ($critere->source_pointage === 'biometrique')
                                                <span class="badge bg-info">Biométrique</span>
                                            @elseif ($critere->source_pointage === 'manuel')
                                                <span class="badge bg-warning text-dark">Manuel</span>
                                            @else
                                                <span class="badge bg-secondary">Tous</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($critere->actif)
                                                <span class="badge bg-success">Actif</span>
                                            @else
                                                <span class="badge bg-danger">Inactif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('criteres-pointage.show', $critere) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('criteres-pointage.edit', $critere) }}" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $critere->id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Modal de suppression -->
                                            <div class="modal fade" id="deleteModal{{ $critere->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $critere->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title" id="deleteModalLabel{{ $critere->id }}">Confirmation de suppression</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Êtes-vous sûr de vouloir supprimer ce critère de pointage ?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <form action="{{ route('criteres-pointage.destroy', $critere) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Supprimer</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">Aucun critère de pointage configuré</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-center mt-3">
                        {{ $criteres->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
