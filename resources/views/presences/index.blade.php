@extends('layouts.app')

@section('title', 'Gestion des présences')

@section('page-title', 'Gestion des pointages')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="h3">Pointages</h1>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="{{ route('presences.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Ajouter un pointage
            </a>
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a href="{{ route('presences.importForm') }}" class="dropdown-item">
                    <i class="bi bi-upload"></i> Importer des pointages
                </a></li>
                <li><a href="{{ route('presences.template') }}" class="dropdown-item">
                    <i class="bi bi-file-earmark-excel"></i> Télécharger le modèle
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a href="{{ route('presences.downloadPointageTemplate') }}" class="dropdown-item">
                    <i class="bi bi-file-earmark-excel-fill"></i> Télécharger le template de pointage
                </a></li>
                <li><a href="{{ route('presences.importPointageForm') }}" class="dropdown-item">
                    <i class="bi bi-upload"></i> Importer un template de pointage
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a href="{{ route('presences.export') }}" class="dropdown-item">
                    <i class="bi bi-download"></i> Exporter les pointages
                </a></li>
                <li><a href="{{ route('presences.export.excel') }}" class="dropdown-item">
                    <i class="bi bi-file-earmark-excel"></i> Exporter en Excel
                </a></li>
                <li><a href="{{ route('presences.export.pdf') }}" class="dropdown-item">
                    <i class="bi bi-file-earmark-pdf"></i> Exporter en PDF
                </a></li>
            </ul>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Filtres</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('presences.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
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
            <div class="col-md-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" value="{{ $date }}">
            </div>
            <div class="col-md-2">
                <label for="retard" class="form-label">Retard</label>
                <select class="form-select" id="retard" name="retard">
                    <option value="">Tous</option>
                    <option value="1" {{ (isset($retard) && $retard == '1') ? 'selected' : '' }}>Oui</option>
                    <option value="0" {{ (isset($retard) && $retard == '0') ? 'selected' : '' }}>Non</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="depart_anticipe" class="form-label">Départ anticipé</label>
                <select class="form-select" id="depart_anticipe" name="depart_anticipe">
                    <option value="">Tous</option>
                    <option value="1" {{ (isset($departAnticipe) && $departAnticipe == '1') ? 'selected' : '' }}>Oui</option>
                    <option value="0" {{ (isset($departAnticipe) && $departAnticipe == '0') ? 'selected' : '' }}>Non</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="statut" class="form-label">Statut</label>
                <select class="form-select" id="statut" name="statut">
                    <option value="">Tous</option>
                    <option value="present" {{ (isset($statut) && $statut == 'present') ? 'selected' : '' }}>Présent</option>
                    <option value="retard" {{ (isset($statut) && $statut == 'retard') ? 'selected' : '' }}>Retard</option>
                    <option value="absent" {{ (isset($statut) && $statut == 'absent') ? 'selected' : '' }}>Absent</option>
                    <option value="depart_anticipe" {{ (isset($statut) && $statut == 'depart_anticipe') ? 'selected' : '' }}>Départ anticipé</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="source_pointage" class="form-label">Source</label>
                <select class="form-select" id="source_pointage" name="source_pointage">
                    <option value="">Toutes</option>
                    <option value="manuel" {{ (isset($sourcePointage) && $sourcePointage == 'manuel') ? 'selected' : '' }}>Saisie manuelle</option>
                    <option value="biometrique" {{ (isset($sourcePointage) && $sourcePointage == 'biometrique') ? 'selected' : '' }}>Import .dat</option>
                    <option value="synchronisation" {{ (isset($sourcePointage) && $sourcePointage == 'synchronisation') ? 'selected' : '' }}>Sync mobile</option>
                    <option value="import_template" {{ (isset($sourcePointage) && $sourcePointage == 'import_template') ? 'selected' : '' }}>Template pointage</option>
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
                        <th>Date</th>
                        <th>Heure d'arrivée</th>
                        <th>Heure de départ</th>
                        <th>Durée</th>
                        <th>Statut</th>
                        <th>Source</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($presences as $presence)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($presence->employe->photo_profil && file_exists(public_path('storage/photos/' . $presence->employe->photo_profil)))
                                        <img src="{{ asset('storage/photos/' . $presence->employe->photo_profil) }}" 
                                            alt="Photo de {{ $presence->employe->prenom }}" 
                                            class="rounded-circle me-2" 
                                            style="width: 32px; height: 32px; object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" 
                                            style="width: 32px; height: 32px; font-size: 0.8rem;">
                                            {{ strtoupper(substr($presence->employe->prenom, 0, 1)) }}{{ strtoupper(substr($presence->employe->nom, 0, 1)) }}
                                        </div>
                                    @endif
                                    {{ $presence->employe->prenom }} {{ $presence->employe->nom }}
                                </div>
                            </td>
                            <td>{{ $presence->date->format('d/m/Y') }}</td>
                            <td>{{ $presence->heure_arrivee }}</td>
                            <td>{{ $presence->heure_depart ?: '-' }}</td>
                            <td>{{ $presence->duree ? number_format($presence->duree, 2) . ' h' : '-' }}</td>
                            <td>
                                @php
                                    $statut = $presence->statut ?? 'present';
                                    $statutLabels = [
                                        'present' => ['label' => 'Présent', 'class' => 'success'],
                                        'retard' => ['label' => 'Retard', 'class' => 'warning'],
                                        'depart_anticipe' => ['label' => 'Départ anticipé', 'class' => 'warning'],
                                        'retard_et_depart_anticipe' => ['label' => 'Retard + Départ anticipé', 'class' => 'danger'],
                                        'absent' => ['label' => 'Absent', 'class' => 'danger'],
                                        'present_sans_planning' => ['label' => 'Présent (sans planning)', 'class' => 'info'],
                                    ];
                                    $statutInfo = $statutLabels[$statut] ?? ['label' => ucfirst($statut), 'class' => 'secondary'];
                                @endphp
                                <span class="badge bg-{{ $statutInfo['class'] }}">
                                    {{ $statutInfo['label'] }}
                                </span>
                                
                                @if($presence->heures_supplementaires && $presence->heures_supplementaires > 0)
                                    <span class="badge bg-primary">
                                        +{{ number_format($presence->heures_supplementaires, 1) }}h sup.
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $source = $presence->source_pointage ?? 'manuel';
                                    $sourceLabels = [
                                        'manuel' => ['label' => 'Saisie manuelle', 'class' => 'secondary', 'icon' => 'bi-person-fill'],
                                        'biometrique' => ['label' => 'Import .dat', 'class' => 'primary', 'icon' => 'bi-file-earmark-text'],
                                        'synchronisation' => ['label' => 'Sync mobile', 'class' => 'success', 'icon' => 'bi-phone'],
                                    ];
                                    $sourceInfo = $sourceLabels[$source] ?? ['label' => ucfirst($source), 'class' => 'secondary', 'icon' => 'bi-question'];
                                @endphp
                                <span class="badge bg-{{ $sourceInfo['class'] }}">
                                    <i class="{{ $sourceInfo['icon'] }} me-1"></i> {{ $sourceInfo['label'] }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('presences.show', $presence) }}" class="btn btn-info" data-bs-toggle="tooltip" title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('presences.edit', $presence) }}" class="btn btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger" onclick="confirmDelete('delete-presence-{{ $presence->id }}')" data-bs-toggle="tooltip" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form id="delete-presence-{{ $presence->id }}" action="{{ route('presences.destroy', $presence) }}" method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-3">Aucun pointage trouvé</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($presences->hasPages())
        <div class="d-flex justify-content-center align-items-center gap-3 mt-3 px-3 pb-3">
            <!-- Bouton Précédent -->
            @if($presences->onFirstPage())
                <button class="btn btn-outline-secondary btn-sm" disabled>
                    <i class="bi bi-chevron-left"></i> Précédent
                </button>
            @else
                <a href="{{ $presences->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-chevron-left"></i> Précédent
                </a>
            @endif
            
            <!-- Indicateur de page compact -->
            <span class="px-3 py-2 bg-light rounded text-muted small">
                Page {{ $presences->currentPage() }} sur {{ $presences->lastPage() }}
            </span>
            
            <!-- Bouton Suivant -->
            @if($presences->hasMorePages())
                <a href="{{ $presences->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                    Suivant <i class="bi bi-chevron-right"></i>
                </a>
            @else
                <button class="btn btn-outline-secondary btn-sm" disabled>
                    Suivant <i class="bi bi-chevron-right"></i>
                </button>
            @endif
        </div>
        @endif
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