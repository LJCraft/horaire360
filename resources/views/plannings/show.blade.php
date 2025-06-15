@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Détails du planning</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('plannings.index') }}" class="btn btn-secondary me-2">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <a href="{{ route('plannings.edit', $planning) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Modifier
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-4">
            <!-- Informations du planning -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Informations du planning</h5>
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> #{{ $planning->id }}</p>
                    
                    <div class="mb-3">
                        <div class="text-muted small">Titre</div>
                        <div class="fw-bold">{{ $planning->titre ?: 'Planning sans titre' }}</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="text-muted small">Période</div>
                        <div class="fw-bold">
                            Du {{ $planning->date_debut ? $planning->date_debut->format('d/m/Y') : '' }} au {{ $planning->date_fin ? $planning->date_fin->format('d/m/Y') : '' }}
                        </div>
                        <div class="text-muted small">
                            {{ $planning->date_debut && $planning->date_fin ? $planning->date_debut->diffInDays($planning->date_fin) + 1 : '' }} jours
                        </div>
                    </div>
                    
                    @if($planning->description)
                    <div class="mb-3">
                        <div class="text-muted small">Description</div>
                        <div>{{ $planning->description }}</div>
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <div class="text-muted small">Statut</div>
                        <div>
                            @if($planning->statut === 'en_cours')
                                <span class="badge bg-success">En cours</span>
                            @elseif($planning->statut === 'a_venir')
                                <span class="badge bg-info">À venir</span>
                            @else
                                <span class="badge bg-secondary">Terminé</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="text-muted small">Heures totales</div>
                        <div class="fw-bold fs-5">{{ $planning->calculerHeuresTotales() }}</div>
                    </div>
                </div>
            </div>
            
            <!-- Informations de l'employé -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Employé</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-placeholder bg-light rounded-circle me-3" style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $planning->employe->prenom }} {{ $planning->employe->nom }}</h5>
                            <div class="text-muted">{{ $planning->employe->matricule }}</div>
                        </div>
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span>Poste</span>
                            <span class="fw-bold">{{ $planning->employe->poste->nom }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span>Département</span>
                            <span class="fw-bold">{{ $planning->employe->poste->departement }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span>Email</span>
                            <span>{{ $planning->employe->email }}</span>
                        </li>
                    </ul>
                    
                    <div class="mt-3">
                        <a href="{{ route('employes.show', $planning->employe) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-person-badge"></i> Voir le profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Détails du planning -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Horaires hebdomadaires</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Jour</th>
                                    <th>Type</th>
                                    <th>Heures</th>
                                    <th>Durée</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                                @endphp
                                
                                @foreach($jours as $index => $jour)
                                    @php 
                                        $jourIndex = $index + 1;
                                        $detail = $planning->details->firstWhere('jour', $jourIndex);
                                    @endphp
                                    <tr>
                                        <td class="align-middle fw-bold">{{ $jour }}</td>
                                        <td class="align-middle">
                                            @if(!$detail)
                                                <span class="text-muted">Non défini</span>
                                            @elseif($detail->jour_repos)
                                                <span class="badge bg-secondary">Repos</span>
                                            @elseif($detail->jour_entier)
                                                <span class="badge bg-success">Journée complète</span>
                                            @else
                                                <span class="badge bg-primary">Horaire spécifique</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            @if($detail && !$detail->jour_repos && !$detail->jour_entier && $detail->heure_debut && $detail->heure_fin)
                                                {{ substr($detail->heure_debut, 0, 5) }} - {{ substr($detail->heure_fin, 0, 5) }}
                                            @elseif($detail && $detail->jour_entier)
                                                09:00 - 17:00 (standard)
                                            @elseif($detail && $detail->jour_repos)
                                                -
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            @if($detail)
                                                {{ $detail->duree }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            @if($detail && $detail->note)
                                                {{ $detail->note }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total hebdomadaire:</td>
                                    <td colspan="2" class="fw-bold">{{ $planning->calculerHeuresTotales() }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Calendrier du planning -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Calendrier</h5>
                </div>
                <div class="card-body">
                    <div class="calendar-view">
                        @php
                            // Calculer les dates à afficher
                            $startDate = $planning->date_debut ? $planning->date_debut->copy()->startOfWeek() : null;
                            $endDate = $planning->date_fin ? $planning->date_fin->copy()->endOfWeek() : null;
                            $currentDate = $startDate ? $startDate->copy() : null;
                            
                            // Jours de la semaine
                            $weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
                        @endphp
                        
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        @foreach($weekDays as $day)
                                            <th class="text-center" style="width: 14.28%">{{ $day }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @while($currentDate && $currentDate <= $endDate)
                                        @if($currentDate->dayOfWeek == 1)
                                            <tr>
                                        @endif
                                        
                                        @php
                                            $isInPlanningPeriod = $currentDate && $planning->date_debut && $planning->date_fin && $currentDate->between($planning->date_debut, $planning->date_fin);
                                            $dayOfWeek = $currentDate->dayOfWeek == 0 ? 7 : $currentDate->dayOfWeek;
                                            $detail = $isInPlanningPeriod ? $planning->details->firstWhere('jour', $dayOfWeek) : null;
                                            
                                            $bgClass = '';
                                            if ($isInPlanningPeriod) {
                                                if ($detail && $detail->jour_repos) {
                                                    $bgClass = 'bg-light';
                                                } elseif ($detail && ($detail->jour_entier || ($detail->heure_debut && $detail->heure_fin))) {
                                                    $bgClass = 'bg-info bg-opacity-10';
                                                }
                                            }
                                            
                                            if ($currentDate && $currentDate->isToday()) {
                                                $bgClass = 'bg-primary bg-opacity-10 fw-bold';
                                            }
                                        @endphp
                                        
                                        <td class="text-center {{ $bgClass }}" style="height: 80px; position: relative;">
                                            <div class="position-absolute top-0 start-0 p-1">
                                                {{ $currentDate ? $currentDate->format('d') : '' }}
                                            </div>
                                            
                                            @if($isInPlanningPeriod && $detail)
                                                <div class="mt-4">
                                                    @if($detail->jour_repos)
                                                        <span class="badge bg-secondary">Repos</span>
                                                    @elseif($detail->jour_entier)
                                                        <span class="badge bg-success">Journée</span>
                                                    @elseif($detail->heure_debut && $detail->heure_fin)
                                                        <div class="small">{{ substr($detail->heure_debut, 0, 5) }}</div>
                                                        <div class="small">{{ substr($detail->heure_fin, 0, 5) }}</div>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        
                                        @if($currentDate && $currentDate->dayOfWeek == 0)
                                            </tr>
                                        @endif
                                        
                                        @php
                                            if ($currentDate) {
                                                $currentDate->addDay();
                                            }
                                        @endphp
                                    @endwhile
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <span class="badge bg-primary bg-opacity-10 me-2">Aujourd'hui</span>
                            <span class="badge bg-info bg-opacity-10 me-2">Jour travaillé</span>
                            <span class="badge bg-light me-2">Jour de repos</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection