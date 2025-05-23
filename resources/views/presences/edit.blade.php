@extends('layouts.app')

@section('title', 'Modifier un pointage')

@section('page-title', 'Modifier un pointage')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="h3">Modifier le pointage</h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('presences.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
    </div>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Erreur :</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('presences.update', $presence) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="employe_id" class="form-label">Employé <span class="text-danger">*</span></label>
                    <select class="form-select @error('employe_id') is-invalid @enderror" id="employe_id" name="employe_id" required>
                        <option value="">Sélectionner un employé</option>
                        @foreach($employes as $employe)
                            <option value="{{ $employe->id }}" {{ old('employe_id', $presence->employe_id) == $employe->id ? 'selected' : '' }}>
                                {{ $employe->prenom }} {{ $employe->nom }}
                            </option>
                        @endforeach
                    </select>
                    @error('employe_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4">
                    <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', $presence->date->format('Y-m-d')) }}" required>
                    @error('date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4">
                    <label for="heure_arrivee" class="form-label">Heure d'arrivée <span class="text-danger">*</span></label>
                    <input type="time" class="form-control @error('heure_arrivee') is-invalid @enderror" id="heure_arrivee" name="heure_arrivee" value="{{ old('heure_arrivee', substr($presence->heure_arrivee, 0, 5)) }}" required>
                    @error('heure_arrivee')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4">
                    <label for="heure_depart" class="form-label">Heure de départ</label>
                    <input type="time" class="form-control @error('heure_depart') is-invalid @enderror" id="heure_depart" name="heure_depart" value="{{ old('heure_depart', $presence->heure_depart ? substr($presence->heure_depart, 0, 5) : '') }}">
                    @error('heure_depart')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label for="source_pointage" class="form-label">Source du pointage</label>
                    <div class="d-flex">
                        <div class="form-check me-4">
                            <input class="form-check-input" type="radio" name="source_pointage" id="source_manuel" value="manuel" {{ old('source_pointage', $presence->source_pointage ?? 'manuel') == 'manuel' ? 'checked' : '' }}>
                            <label class="form-check-label" for="source_manuel">
                                <i class="bi bi-person-fill"></i> Manuel
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="source_pointage" id="source_biometrique" value="biometrique" {{ old('source_pointage', $presence->source_pointage ?? 'manuel') == 'biometrique' ? 'checked' : '' }}>
                            <label class="form-check-label" for="source_biometrique">
                                <i class="bi bi-fingerprint"></i> Biométrique
                            </label>
                        </div>
                    </div>
                    <div class="form-text small">
                        La source du pointage détermine quels critères de pointage seront appliqués.
                    </div>
                    @error('source_pointage')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label for="commentaire" class="form-label">Commentaire</label>
                    <textarea class="form-control @error('commentaire') is-invalid @enderror" id="commentaire" name="commentaire" rows="3">{{ old('commentaire', $presence->commentaire) }}</textarea>
                    @error('commentaire')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-12 mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer les modifications
                    </button>
                    <a href="{{ route('presences.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x"></i> Annuler
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection