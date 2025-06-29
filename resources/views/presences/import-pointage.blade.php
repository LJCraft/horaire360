@extends('layouts.app')

@section('title', 'Importer un template de pointage')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3">Importer un template de pointage</h1>
            <p class="text-muted">Importez les données de pointage depuis un template Excel rempli</p>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <a href="{{ route('presences.downloadPointageTemplate') }}" class="btn btn-info">
                    <i class="bi bi-file-earmark-excel"></i> Télécharger le template
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
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-upload me-2"></i>Importer le template de pointage</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('presences.importPointage') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="date" class="form-label">Date du pointage <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('date') is-invalid @enderror" 
                                           id="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                                    @error('date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Sélectionnez la date pour laquelle vous importez les pointages</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="fichier" class="form-label">Fichier Excel <span class="text-danger">*</span></label>
                                    <input class="form-control @error('fichier') is-invalid @enderror" 
                                           type="file" id="fichier" name="fichier" accept=".xlsx,.xls" required>
                                    @error('fichier')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Sélectionnez le template Excel rempli (.xlsx ou .xls)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Importer les pointages
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
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>Instructions</h5>
                </div>
                <div class="card-body">
                    <ol class="ps-3">
                        <li class="mb-2">Téléchargez le template en cliquant sur "Télécharger le template"</li>
                        <li class="mb-2">Ouvrez le fichier Excel et remplissez les colonnes :
                            <ul class="mt-1">
                                <li><strong>Arrivée Réelle (AR)</strong> : Format HH:MM</li>
                                <li><strong>Heure de Départ (HD)</strong> : Format HH:MM</li>
                            </ul>
                        </li>
                        <li class="mb-2">Laissez vides les lignes des employés absents</li>
                        <li class="mb-2">Enregistrez le fichier Excel</li>
                        <li class="mb-2">Sélectionnez la date correspondante</li>
                        <li class="mb-2">Importez le fichier rempli</li>
                    </ol>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h6 class="card-title mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Important</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-1">Le template contient <strong>tous les employés actifs</strong> classés par département</li>
                        <li class="mb-1">Seules les lignes avec des heures saisies seront importées</li>
                        <li class="mb-1">Les pointages existants pour la même date seront <strong>mis à jour</strong></li>
                        <li class="mb-1">Format des heures : <code>HH:MM</code> (ex: 08:30, 17:15)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .card-header.bg-primary {
        background-color: #0d6efd !important;
    }
    .card-header.bg-info {
        background-color: #0dcaf0 !important;
    }
    .card-header.bg-warning {
        background-color: #ffc107 !important;
    }
</style>
@endpush
@endsection
