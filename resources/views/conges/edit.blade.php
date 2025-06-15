@extends('layouts.app')

@section('title', 'Modifier une demande de congé')

@section('page-title', 'Modifier une demande de congé')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="h3">Modifier la demande de congé</h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('conges.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('conges.update', $conge) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="employe_id" class="form-label">Employé <span class="text-danger">*</span></label>
                    <select class="form-select @error('employe_id') is-invalid @enderror" id="employe_id" name="employe_id" required>
                        <option value="">Sélectionner un employé</option>
                        @foreach($employes as $employe)
                            <option value="{{ $employe->id }}" {{ old('employe_id', $conge->employe_id) == $employe->id ? 'selected' : '' }}>
                                {{ $employe->prenom }} {{ $employe->nom }}
                            </option>
                        @endforeach
                    </select>
                    @error('employe_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label for="date_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('date_debut') is-invalid @enderror" id="date_debut" name="date_debut" value="{{ old('date_debut', $conge->date_debut->format('Y-m-d')) }}" required>
                    @error('date_debut')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label for="date_fin" class="form-label">Date de fin <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('date_fin') is-invalid @enderror" id="date_fin" name="date_fin" value="{{ old('date_fin', $conge->date_fin->format('Y-m-d')) }}" required>
                    @error('date_fin')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-12">
                    <label for="type" class="form-label">Type de congé <span class="text-danger">*</span></label>
                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                        <option value="">Sélectionner un type</option>
                        <option value="conge_paye" {{ old('type', $conge->type) == 'conge_paye' ? 'selected' : '' }}>Congé payé</option>
                        <option value="maladie" {{ old('type', $conge->type) == 'maladie' ? 'selected' : '' }}>Maladie</option>
                        <option value="sans_solde" {{ old('type', $conge->type) == 'sans_solde' ? 'selected' : '' }}>Sans solde</option>
                        <option value="autre" {{ old('type', $conge->type) == 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-12">
                    <label for="motif" class="form-label">Motif</label>
                    <textarea class="form-control @error('motif') is-invalid @enderror" id="motif" name="motif" rows="3">{{ old('motif', $conge->motif) }}</textarea>
                    @error('motif')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer les modifications
                    </button>
                    <a href="{{ route('conges.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x"></i> Annuler
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Calculer automatiquement la durée
        const dateDebutInput = document.getElementById('date_debut');
        const dateFinInput = document.getElementById('date_fin');
        
        function updateDateFin() {
            if (dateDebutInput.value && !dateFinInput.value) {
                dateFinInput.value = dateDebutInput.value;
            }
            
            if (dateDebutInput.value > dateFinInput.value) {
                dateFinInput.value = dateDebutInput.value;
            }
        }
        
        dateDebutInput.addEventListener('change', updateDateFin);
    });
</script>
@endpush
@endsection