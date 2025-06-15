@extends('layouts.app')

@section('title', 'Rapport des heures supplémentaires')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Rapport des heures supplémentaires</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="{{ route('rapports.index') }}">Rapports</a></li>
        <li class="breadcrumb-item active">Heures supplémentaires</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filtres
        </div>
        <div class="card-body">
            <form action="{{ route('rapports.heures-supplementaires') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date_debut" class="form-label">Date de début</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ $dateDebut->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label for="date_fin" class="form-label">Date de fin</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ $dateFin->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label for="employe_id" class="form-label">Employé</label>
                    <select class="form-select" id="employe_id" name="employe_id">
                        <option value="">Tous les employés</option>
                        @foreach($employes as $employe)
                            <option value="{{ $employe->id }}" {{ $employeId == $employe->id ? 'selected' : '' }}>
                                {{ $employe->nom }} {{ $employe->prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="departement_id" class="form-label">Département</label>
                    <select class="form-select" id="departement_id" name="departement_id">
                        <option value="">Tous les départements</option>
                        @foreach($departements as $departement)
                            <option value="{{ $departement->id }}" {{ $departementId == $departement->id ? 'selected' : '' }}>
                                {{ $departement->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="poste_id" class="form-label">Poste</label>
                    <select class="form-select" id="poste_id" name="poste_id">
                        <option value="">Tous les postes</option>
                        @foreach($postes as $poste)
                            <option value="{{ $poste->id }}" {{ $posteId == $poste->id ? 'selected' : '' }}>
                                {{ $poste->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="grade_id" class="form-label">Grade</label>
                    <select class="form-select" id="grade_id" name="grade_id">
                        <option value="">Tous les grades</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}" {{ $gradeId == $grade->id ? 'selected' : '' }}>
                                {{ $grade->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filtrer</button>
                    <a href="{{ route('rapports.heures-supplementaires') }}" class="btn btn-secondary">Réinitialiser</a>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="{{ route('rapports.heures-supplementaires.pdf', request()->query()) }}" class="btn btn-danger me-2">
                        <i class="fas fa-file-pdf me-1"></i> Exporter en PDF
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-clock me-1"></i>
                    Heures supplémentaires du {{ $dateDebut->format('d/m/Y') }} au {{ $dateFin->format('d/m/Y') }}
                </div>
                <div>
                    <span class="badge bg-info">Total: {{ $heuresSupplementaires->count() }} enregistrements</span>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($heuresSupplementaires->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Employé</th>
                                <th>Département</th>
                                <th>Poste</th>
                                <th>Date</th>
                                <th>Fin prévue</th>
                                <th>Départ réel</th>
                                <th>Heures supp.</th>
                                <th>Source</th>
                                <th>Commentaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($heuresSupplementaires as $hs)
                                <tr>
                                    <td>{{ $hs->employe->nom }} {{ $hs->employe->prenom }}</td>
                                    <td>{{ $hs->employe->poste->departement->nom ?? 'N/A' }}</td>
                                    <td>{{ $hs->employe->poste->nom ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($hs->date)->format('d/m/Y') }}</td>
                                    <td>{{ $hs->heure_fin_prevue }}</td>
                                    <td>{{ $hs->heure_depart_reelle }}</td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            {{ floor($hs->heures_supplementaires / 60) }}h{{ $hs->heures_supplementaires % 60 }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($hs->source == 'biometrique')
                                            <span class="badge bg-success">Biométrique</span>
                                        @elseif($hs->source == 'manuel')
                                            <span class="badge bg-info">Manuel</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $hs->source }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $hs->commentaire ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Résumé des heures supplémentaires -->
                <div class="mt-4">
                    <h5>Résumé des heures supplémentaires</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Total des heures supplémentaires</h6>
                                    <p class="card-text fs-4">
                                        @php
                                            $totalMinutes = $heuresSupplementaires->sum('heures_supplementaires');
                                            $heures = floor($totalMinutes / 60);
                                            $minutes = $totalMinutes % 60;
                                        @endphp
                                        {{ $heures }}h{{ $minutes }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Moyenne par employé</h6>
                                    <p class="card-text fs-4">
                                        @php
                                            $employesUniques = $heuresSupplementaires->pluck('employe.id')->unique()->count();
                                            if ($employesUniques > 0) {
                                                $moyenneMinutes = round($totalMinutes / $employesUniques);
                                                $heuresMoyenne = floor($moyenneMinutes / 60);
                                                $minutesMoyenne = $moyenneMinutes % 60;
                                                echo $heuresMoyenne . 'h' . $minutesMoyenne;
                                            } else {
                                                echo '0h00';
                                            }
                                        @endphp
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Nombre d'employés concernés</h6>
                                    <p class="card-text fs-4">{{ $employesUniques }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    Aucune heure supplémentaire trouvée pour la période sélectionnée.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialiser les sélecteurs avancés
        $('#employe_id, #departement_id, #poste_id, #grade_id').select2({
            placeholder: 'Sélectionner...',
            allowClear: true
        });
        
        // Mise à jour dynamique des postes en fonction du département
        $('#departement_id').change(function() {
            var departementId = $(this).val();
            if (departementId) {
                $.ajax({
                    url: '{{ route("postes.by-departement") }}',
                    type: 'GET',
                    data: { departement_id: departementId },
                    success: function(data) {
                        $('#poste_id').empty().append('<option value="">Tous les postes</option>');
                        $.each(data, function(key, value) {
                            $('#poste_id').append('<option value="' + value.id + '">' + value.nom + '</option>');
                        });
                        $('#poste_id').trigger('change');
                    }
                });
            } else {
                // Si aucun département n'est sélectionné, réinitialiser les postes
                $('#poste_id').empty().append('<option value="">Tous les postes</option>');
                @foreach($postes as $poste)
                    $('#poste_id').append('<option value="{{ $poste->id }}">{{ $poste->nom }}</option>');
                @endforeach
                $('#poste_id').trigger('change');
            }
        });
    });
</script>
@endsection
