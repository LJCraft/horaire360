<!-- @extends('layouts.app')

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
                
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                    @if(session('error'))
    <div class="alert alert-danger" role="alert">
        {{ session('error') }}
    </div>
@endif
                    <a href="{{ route('presences.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x"></i> Annuler
                    </a>
                </div>
                <button type="submit" class="btn btn-primary" id="submitBtn">
    <i class="bi bi-save"></i> Enregistrer
</button>
            </div>
        </form>
    </div>
</div>
@endsection
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('presenceForm');
        
        if (submitBtn && form) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Bouton soumis');
                
                // Récupérer les données du formulaire manuellement
                const formData = {
                    employe_id: form.querySelector('#employe_id').value,
                    date: form.querySelector('#date').value,
                    heure_arrivee: form.querySelector('#heure_arrivee').value,
                    heure_depart: form.querySelector('#heure_depart').value,
                    commentaire: form.querySelector('#commentaire').value
                };
                
                // Afficher les données dans un élément visible
                const debugDiv = document.createElement('div');
                debugDiv.style.position = 'fixed';
                debugDiv.style.top = '10px';
                debugDiv.style.right = '10px';
                debugDiv.style.backgroundColor = 'white';
                debugDiv.style.padding = '10px';
                debugDiv.style.border = '1px solid #ccc';
                debugDiv.style.zIndex = '9999';
                
                let debugText = 'Données du formulaire:\n';
                for (const [key, value] of Object.entries(formData)) {
                    debugText += `${key}: ${value}\n`;
                }
                
                debugDiv.textContent = debugText;
                document.body.appendChild(debugDiv);
                
                // Soumettre le formulaire après 5 secondes
                setTimeout(() => {
                    form.submit();
                }, 5000);
            });
        }
    });
</script> -->
@if(session('error'))
    <div class="alert alert-danger mt-4" role="alert">
        {{ session('error') }}
    </div>
@endif