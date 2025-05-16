@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Options d'exportation du rapport</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('rapports.export-pdf') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        
                        <div class="mb-4">
                            <h5>Type de rapport : <span class="text-primary">{{ $typeLabel }}</span></h5>
                        </div>

                        <div class="form-group mb-3">
                            <label for="periode" class="form-label">Période</label>
                            <select name="periode" id="periode" class="form-select">
                                <option value="jour" {{ $periode == 'jour' ? 'selected' : '' }}>Jour</option>
                                <option value="semaine" {{ $periode == 'semaine' ? 'selected' : '' }}>Semaine</option>
                                <option value="mois" {{ $periode == 'mois' ? 'selected' : '' }}>Mois</option>
                                <option value="annee" {{ $periode == 'annee' ? 'selected' : '' }}>Année</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $dateDebut ?? now()->format('Y-m-d') }}">
                        </div>

                        @if(isset($employes) && count($employes) > 0)
                        <div class="form-group mb-3">
                            <label for="employe_id" class="form-label">Employé (optionnel)</label>
                            <select name="employe_id" id="employe_id" class="form-select">
                                <option value="">Tous les employés</option>
                                @foreach($employes as $employe)
                                    <option value="{{ $employe->id }}" {{ $employeId == $employe->id ? 'selected' : '' }}>
                                        {{ $employe->prenom }} {{ $employe->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if(isset($departements) && count($departements) > 0)
                        <div class="form-group mb-3">
                            <label for="departement" class="form-label">Département (optionnel)</label>
                            <select name="departement" id="departement" class="form-select">
                                <option value="">Tous les départements</option>
                                @foreach($departements as $departement)
                                    <option value="{{ $departement->departement }}" {{ $departementId == $departement->departement ? 'selected' : '' }}>
                                        {{ $departement->departement }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if(isset($services) && count($services) > 0)
                        <div class="form-group mb-3">
                            <label for="service" class="form-label">Service (optionnel)</label>
                            <select name="service" id="service" class="form-select">
                                <option value="">Tous les services</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->departement }}" {{ $serviceId == $service->departement ? 'selected' : '' }}>
                                        {{ $service->departement }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="form-group mb-3">
                            <label for="format" class="form-label">Format</label>
                            <select name="format" id="format" class="form-select">
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-export me-1"></i> Exporter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodeSelect = document.getElementById('periode');
    const dateDebutInput = document.getElementById('date_debut');
    
    // Mettre à jour la date en fonction de la période sélectionnée
    periodeSelect.addEventListener('change', function() {
        const today = new Date();
        let dateDebut = new Date();
        
        switch(this.value) {
            case 'jour':
                // Aujourd'hui
                break;
            case 'semaine':
                // Début de la semaine
                dateDebut.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1));
                break;
            case 'mois':
                // Début du mois
                dateDebut.setDate(1);
                break;
            case 'annee':
                // Début de l'année
                dateDebut = new Date(today.getFullYear(), 0, 1);
                break;
        }
        
        // Formater la date au format YYYY-MM-DD
        const year = dateDebut.getFullYear();
        const month = String(dateDebut.getMonth() + 1).padStart(2, '0');
        const day = String(dateDebut.getDate()).padStart(2, '0');
        dateDebutInput.value = `${year}-${month}-${day}`;
    });
});
</script>
@endsection
