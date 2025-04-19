@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Modifier le poste</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('postes.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('postes.update', $poste) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom du poste <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nom') is-invalid @enderror" id="nom" name="nom" value="{{ old('nom', $poste->nom) }}" required>
                    @error('nom')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="departement" class="form-label">Département <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('departement') is-invalid @enderror" id="departement" name="departement" value="{{ old('departement', $poste->departement) }}" required>
                    @error('departement')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $poste->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-outline-secondary me-md-2">Réinitialiser</button>
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>

    @if($poste->employes_count > 0)
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0">Employés associés</h5>
        </div>
        <div class="card-body">
            <p>
                <i class="bi bi-info-circle"></i> 
                Ce poste est actuellement associé à <strong>{{ $poste->employes_count }}</strong> employé(s).
            </p>
            <a href="{{ route('employes.index', ['poste_id' => $poste->id]) }}" class="btn btn-outline-primary">
                <i class="bi bi-person-badge"></i> Voir les employés
            </a>
        </div>
    </div>
    @endif
</div>
@endsection