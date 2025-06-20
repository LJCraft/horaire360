@extends('layouts.app')

@section('title', 'Modifier un critère de pointage')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i> Modifier un critère de pointage
                    </h5>
                    <a href="{{ route('criteres-pointage.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="/criteres-pointage/update/{{ $criterePointage->id }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Informations générales</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Niveau de configuration</label>
                                            <div class="form-control-plaintext">
                                                @if ($criterePointage->niveau === 'individuel')
                                                    <span class="badge bg-info">Individuel</span>
                                                    @if ($criterePointage->employe)
                                                        <p class="mb-0 mt-2">Employé : {{ $criterePointage->employe->nom }} {{ $criterePointage->employe->prenom }}</p>
                                                    @else
                                                        <p class="mb-0 mt-2">Employé : Non défini</p>
                                                    @endif
                                                @else
                                                    <span class="badge bg-primary">Départemental</span>
                                                    @if ($criterePointage->departement)
                                                        <p class="mb-0 mt-2">Département : {{ $criterePointage->departement->nom }}</p>
                                                    @else
                                                        <p class="mb-0 mt-2">Département : Non défini</p>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Période calculée</label>
                                            <div class="form-control-plaintext">
                                                <span class="badge bg-info">{{ $criterePointage->periode_calculee }}</span>
                                                <div class="form-text text-info mt-1">
                                                    <i class="fas fa-info-circle me-1"></i>La période est calculée automatiquement à partir des dates
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Dates d'application</label>
                                            <div class="form-control-plaintext">
                                                {{ $criterePointage->date_debut->format('d/m/Y') }} - {{ $criterePointage->date_fin->format('d/m/Y') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Configuration des critères</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="nombre_pointages" class="form-label">Nombre de pointages requis</label>
                                            <select class="form-select @error('nombre_pointages') is-invalid @enderror" id="nombre_pointages" name="nombre_pointages">
                                                <option value="1" {{ $criterePointage->nombre_pointages == 1 ? 'selected' : '' }}>1 pointage (présence uniquement)</option>
                                                <option value="2" {{ $criterePointage->nombre_pointages == 2 ? 'selected' : '' }}>2 pointages (arrivée et départ)</option>
                                            </select>
                                            @error('nombre_pointages')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text" id="pointage_info">
                                                @if ($criterePointage->nombre_pointages == 1)
                                                    Avec 1 pointage, l'employé doit pointer une seule fois dans la journée, dans la plage [heure début - tolérance] → [heure fin + tolérance].
                                                @else
                                                    Avec 2 pointages, l'employé doit pointer à l'arrivée et au départ dans les plages de tolérance.
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="tolerance_avant" class="form-label">Tolérance avant (minutes)</label>
                                            <input type="number" class="form-control @error('tolerance_avant') is-invalid @enderror" id="tolerance_avant" name="tolerance_avant" value="{{ old('tolerance_avant', $criterePointage->tolerance_avant) }}" min="0" max="60">
                                            @error('tolerance_avant')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="tolerance_apres" class="form-label">Tolérance après (minutes)</label>
                                            <input type="number" class="form-control @error('tolerance_apres') is-invalid @enderror" id="tolerance_apres" name="tolerance_apres" value="{{ old('tolerance_apres', $criterePointage->tolerance_apres) }}" min="0" max="60">
                                            @error('tolerance_apres')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="duree_pause" class="form-label">Durée de pause (minutes)</label>
                                            <input type="number" class="form-control @error('duree_pause') is-invalid @enderror" id="duree_pause" name="duree_pause" value="{{ old('duree_pause', $criterePointage->duree_pause) }}" min="0" max="240">
                                            @error('duree_pause')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Durée de pause non décomptée du temps de travail (0 = pas de pause).
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="source_pointage" class="form-label">Source de pointage</label>
                                            <select class="form-select @error('source_pointage') is-invalid @enderror" id="source_pointage" name="source_pointage">
                                                <option value="tous" {{ old('source_pointage', $criterePointage->source_pointage) == 'tous' ? 'selected' : '' }}>Tous types de pointage</option>
                                                <option value="biometrique" {{ old('source_pointage', $criterePointage->source_pointage) == 'biometrique' ? 'selected' : '' }}>Biométrique uniquement</option>
                                                <option value="manuel" {{ old('source_pointage', $criterePointage->source_pointage) == 'manuel' ? 'selected' : '' }}>Manuel uniquement</option>
                                            </select>
                                            @error('source_pointage')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Définit le type de pointage à prendre en compte pour les critères.
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="calcul_heures_sup" name="calcul_heures_sup" value="1" {{ $criterePointage->calcul_heures_sup ? 'checked' : '' }}>
                                                <label class="form-check-label" for="calcul_heures_sup">Activer le calcul des heures supplémentaires</label>
                                            </div>
                                            <div class="form-text">
                                                Permet de comptabiliser automatiquement les heures supplémentaires.
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="seuil_heures_sup" class="form-label">Seuil des heures supplémentaires (minutes)</label>
                                            <input type="number" class="form-control @error('seuil_heures_sup') is-invalid @enderror" id="seuil_heures_sup" name="seuil_heures_sup" value="{{ old('seuil_heures_sup', $criterePointage->seuil_heures_sup) }}" min="0" max="240">
                                            @error('seuil_heures_sup')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Durée minimale au-delà de l'heure de fin prévue pour comptabiliser des heures supplémentaires.
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="priorite" class="form-label">Priorité du critère</label>
                                            <select class="form-select @error('priorite') is-invalid @enderror" id="priorite" name="priorite">
                                                <option value="1" {{ old('priorite', $criterePointage->priorite) == 1 ? 'selected' : '' }}>Haute (1)</option>
                                                <option value="2" {{ old('priorite', $criterePointage->priorite) == 2 ? 'selected' : '' }}>Normale (2)</option>
                                                <option value="3" {{ old('priorite', $criterePointage->priorite) == 3 ? 'selected' : '' }}>Basse (3)</option>
                                            </select>
                                            @error('priorite')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                En cas de chevauchement de critères, celui avec la priorité la plus haute sera appliqué.
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="actif" name="actif" {{ $criterePointage->actif ? 'checked' : '' }}>
                                                <label class="form-check-label" for="actif">Critère actif</label>
                                            </div>
                                            <div class="form-text">
                                                Décochez cette case pour désactiver ce critère sans le supprimer.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if ($criterePointage->niveau === 'departemental')
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Options avancées</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="appliquer_aux_individuels" name="appliquer_aux_individuels" value="1">
                                    <label class="form-check-label" for="appliquer_aux_individuels">
                                        Appliquer ces modifications aux critères individuels du département
                                    </label>
                                </div>
                                <div class="form-text">
                                    Si cette option est activée, les modifications seront appliquées à tous les critères individuels des employés du département pour la même période.
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='{{ route('criteres-pointage.index') }}'">Annuler</button>
                            <button type="submit" class="btn btn-primary">Mettre à jour</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mise à jour du message d'information sur le pointage
        const nombrePointages = document.getElementById('nombre_pointages');
        const pointageInfo = document.getElementById('pointage_info');
        
        nombrePointages.addEventListener('change', function() {
            if (nombrePointages.value === '1') {
                pointageInfo.innerHTML = 'Avec 1 pointage, l\'employé doit pointer une seule fois dans la journée, dans la plage [heure début - tolérance] → [heure fin + tolérance].';
            } else {
                pointageInfo.innerHTML = 'Avec 2 pointages, l\'employé doit pointer à l\'arrivée et au départ dans les plages de tolérance.';
            }
        });
    });
</script>
@endsection
