@extends('layouts.app')

@section('title', 'Ajouter un pointage')

@section('page-title', 'Ajouter un pointage')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="h3">Nouveau pointage</h1>
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
        <form action="{{ route('presences.store') }}" method="POST" id="presenceForm">
            @csrf
            
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="employe_id" class="form-label">Employé <span class="text-danger">*</span></label>
                    <select class="form-select @error('employe_id') is-invalid @enderror" id="employe_id" name="employe_id" required>
                        <option value="">Sélectionner un employé</option>
                        @foreach($employes as $employe)
                            <option value="{{ $employe->id }}" {{ old('employe_id') == $employe->id ? 'selected' : '' }}>
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
                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date') ?? date('Y-m-d') }}" required>
                    @error('date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4">
                    <label for="heure_arrivee" class="form-label">Heure d'arrivée <span class="text-danger">*</span></label>
                    <input type="time" class="form-control @error('heure_arrivee') is-invalid @enderror" id="heure_arrivee" name="heure_arrivee" value="{{ old('heure_arrivee') }}" required>
                    @error('heure_arrivee')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-4">
                    <label for="heure_depart" class="form-label">Heure de départ</label>
                    <input type="time" class="form-control @error('heure_depart') is-invalid @enderror" id="heure_depart" name="heure_depart" value="{{ old('heure_depart') }}">
                    @error('heure_depart')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-12">
                    <label for="commentaire" class="form-label">Commentaire</label>
                    <textarea class="form-control @error('commentaire') is-invalid @enderror" id="commentaire" name="commentaire" rows="3">{{ old('commentaire') }}</textarea>
                    @error('commentaire')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-12 mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer
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