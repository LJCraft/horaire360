@extends('layouts.app')

@section('title', 'Importer des pointages')

@section('page-title', 'Importer des pointages')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="h3">Importer des pointages</h1>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="{{ route('presences.template') }}" class="btn btn-info">
                <i class="bi bi-file-earmark-excel"></i> Télécharger le modèle
            </a>
            <a href="{{ route('presences.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('presences.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="fichier" class="form-label">Fichier Excel (.xlsx, .xls, .csv)</label>
                        <input class="form-control @error('fichier') is-invalid @enderror" type="file" id="fichier" name="fichier" accept=".xlsx,.xls,.csv" required>
                        @error('fichier')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Importer
                        </button>
                        <a href="{{ route('presences.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Instructions</h5>
            </div>
            <div class="card-body">
                <ol class="ps-3">
                    <li class="mb-2">Téléchargez le modèle Excel en cliquant sur le bouton "Télécharger le modèle".</li>
                    <li class="mb-2">Remplissez les données des pointages selon le format du modèle.</li>
                    <li class="mb-2">Les champs marqués d'un astérisque (*) sont obligatoires.</li>
                    <li class="mb-2">Consultez la feuille "Employés" pour connaître les IDs des employés disponibles.</li>
                    <li class="mb-2">Enregistrez le fichier au format .xlsx, .xls ou .csv.</li>
                    <li class="mb-2">Utilisez le formulaire ci-contre pour téléverser votre fichier.</li>
                </ol>
                
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Note :</strong> Le système détectera automatiquement les retards et départs anticipés en fonction des plannings existants.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection