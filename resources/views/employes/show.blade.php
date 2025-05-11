@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Détails de l'employé</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('employes.index') }}" class="btn btn-secondary me-2">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <a href="{{ route('employes.edit', $employe) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Modifier
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Informations personnelles</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="mx-auto mb-3 position-relative">
                            @if($employe->photo_profil && file_exists(public_path('storage/photos/' . $employe->photo_profil)))
                                <div class="photo-container position-relative" style="width: 120px; height: 120px; margin: 0 auto;">
                                    <img src="{{ asset('storage/photos/' . $employe->photo_profil) }}" alt="Photo de {{ $employe->prenom }} {{ $employe->nom }}" 
                                        class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                                    <a href="{{ route('employes.edit', $employe) }}" class="change-photo-btn">
                                        <div class="photo-overlay rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-camera-fill text-white"></i>
                                        </div>
                                    </a>
                                </div>
                            @else
                                <div class="photo-container position-relative" style="width: 120px; height: 120px; margin: 0 auto;">
                                    <div class="avatar-placeholder bg-light rounded-circle mx-auto" 
                                        style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                        <span>{{ strtoupper(substr($employe->prenom, 0, 1)) }}{{ strtoupper(substr($employe->nom, 0, 1)) }}</span>
                                    </div>
                                    <a href="{{ route('employes.edit', $employe) }}" class="change-photo-btn">
                                        <div class="photo-overlay rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-camera-fill text-white"></i>
                                        </div>
                                    </a>
                                </div>
                            @endif
                        </div>
                        <h3 class="mb-1">{{ $employe->prenom }} {{ $employe->nom }}</h3>
                        <p class="text-muted mb-0">{{ $employe->poste->nom }}</p>
                        <span class="badge bg-{{ $employe->statut === 'actif' ? 'success' : 'danger' }} mt-2">
                            {{ $employe->statut }}
                        </span>
                    </div>
                    
                    <hr>
                    
                    <div class="info-item mb-3">
                        <div class="text-muted small">Matricule</div>
                        <div class="fw-bold">{{ $employe->matricule }}</div>
                    </div>
                    
                    <div class="info-item mb-3">
                        <div class="text-muted small">Email</div>
                        <div>
                            <a href="mailto:{{ $employe->email }}" class="text-decoration-none">
                                <i class="bi bi-envelope me-1"></i> {{ $employe->email }}
                            </a>
                        </div>
                    </div>
                    
                    <div class="info-item mb-3">
                        <div class="text-muted small">Téléphone</div>
                        <div>
                            @if($employe->telephone)
                                <a href="tel:{{ $employe->telephone }}" class="text-decoration-none">
                                    <i class="bi bi-telephone me-1"></i> {{ $employe->telephone }}
                                </a>
                            @else
                                <span class="text-muted"><i>Non renseigné</i></span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="info-item mb-3">
                        <div class="text-muted small">Date de naissance</div>
                        <div>
                            @if($employe->date_naissance)
                                {{ $employe->date_naissance instanceof \Carbon\Carbon ? $employe->date_naissance->format('d/m/Y') : (is_string($employe->date_naissance) ? \Carbon\Carbon::parse($employe->date_naissance)->format('d/m/Y') : '') }}
                                <span class="text-muted">(
                                    {{ $employe->date_naissance instanceof \Carbon\Carbon ? $employe->date_naissance->age : (is_string($employe->date_naissance) ? \Carbon\Carbon::parse($employe->date_naissance)->age : '') }} ans)
                                </span>
                            @else
                                <span class="text-muted"><i>Non renseignée</i></span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Compte utilisateur</h5>
                </div>
                <div class="card-body">
                    @if($employe->utilisateur)
                        <div class="d-flex align-items-center">
                            <div class="bg-success rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Compte actif</div>
                                <div class="text-muted small">Rôle: {{ $employe->utilisateur->role->nom }}</div>
                            </div>
                        </div>
                    @else
                        <div class="d-flex align-items-center">
                            <div class="bg-secondary rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="bi bi-x-lg"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">Aucun compte associé</div>
                                <div class="text-muted small">Cet employé n'a pas accès à l'application</div>
                            </div>
                        </div>
                        
                        <form action="{{ route('users.create-from-employee', $employe->id) }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-person-plus"></i> Créer un compte
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations professionnelles</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <div class="text-muted small">Poste</div>
                                <div class="fw-bold">{{ $employe->poste->nom }}</div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <div class="text-muted small">Département</div>
                                <div class="fw-bold">{{ $employe->poste->departement }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <div class="text-muted small">Date d'embauche</div>
                                <div class="fw-bold">
                                    {{ $employe->date_embauche instanceof \Carbon\Carbon ? $employe->date_embauche->format('d/m/Y') : (is_string($employe->date_embauche) ? \Carbon\Carbon::parse($employe->date_embauche)->format('d/m/Y') : '') }}
                                </div>
                                <div class="text-muted small">
                                    {{ $employe->date_embauche instanceof \Carbon\Carbon ? $employe->date_embauche->diffForHumans() : (is_string($employe->date_embauche) ? \Carbon\Carbon::parse($employe->date_embauche)->diffForHumans() : '') }}
                                </div>
                            </div>
                            
                            <div class="info-item mb-3">
                                <div class="text-muted small">Ancienneté</div>
                                <div class="fw-bold">
                                    @php
                                        $years = $employe->date_embauche instanceof \Carbon\Carbon ? $employe->date_embauche->diffInYears(now()) : (is_string($employe->date_embauche) ? \Carbon\Carbon::parse($employe->date_embauche)->diffInYears(now()) : 0);
                                        $months = $employe->date_embauche instanceof \Carbon\Carbon ? $employe->date_embauche->diffInMonths(now()) % 12 : (is_string($employe->date_embauche) ? \Carbon\Carbon::parse($employe->date_embauche)->diffInMonths(now()) % 12 : 0);
                                    @endphp
                                    
                                    @if($years > 0)
                                        {{ $years }} an{{ $years > 1 ? 's' : '' }}
                                        @if($months > 0)
                                            et {{ $months }} mois
                                        @endif
                                    @else
                                        {{ $months }} mois
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historique des affectations</h5>
                </div>
                <div class="card-body">
                    @if($employe->affectations && $employe->affectations->isNotEmpty())
                        <ul class="list-group">
                            @foreach($employe->affectations as $affectation)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $affectation->poste->nom }}</strong> - {{ $affectation->departement->nom }}
                                        <div class="text-muted small">
                                            Du {{ $affectation->date_debut instanceof \Carbon\Carbon ? $affectation->date_debut->format('d/m/Y') : (is_string($affectation->date_debut) ? \Carbon\Carbon::parse($affectation->date_debut)->format('d/m/Y') : '') }}
                                            @if($affectation->date_fin)
                                                au {{ $affectation->date_fin instanceof \Carbon\Carbon ? $affectation->date_fin->format('d/m/Y') : (is_string($affectation->date_fin) ? \Carbon\Carbon::parse($affectation->date_fin)->format('d/m/Y') : '') }}
                                            @else
                                                (Actuel)
                                            @endif
                                        </div>
                                    </div>
                                    @if(!$affectation->date_fin)
                                        <span class="badge bg-success">En cours</span>
                                    @else
                                        <span class="badge bg-secondary">Terminé</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">Aucune affectation enregistrée.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Planning du mois</h5>
                                <a href="{{ route('plannings.create', ['employe_id' => $employe->id]) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Ajouter au planning
                                </a>
                            </div>
                            
                            @php
                                $planningSemaine = \App\Models\Planning::where('employe_id', $employe->id)
                                    ->where('date_debut', '<=', \Carbon\Carbon::today())
                                    ->where('date_fin', '>=', \Carbon\Carbon::today())
                                    ->first();
                            @endphp
                            
                            @if($planningSemaine)
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Jour</th>
                                                <th>Horaire</th>
                                                <th>Durée</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                                            @endphp
                                            
                                            @foreach($jours as $index => $jour)
                                                @php 
                                                    $jourIndex = $index + 1;
                                                    $detail = $planningSemaine->details->firstWhere('jour', $jourIndex);
                                                @endphp
                                                <tr>
                                                    <td>{{ $jour }}</td>
                                                    <td>
                                                        @if(!$detail)
                                                            <span class="text-muted">Non défini</span>
                                                        @elseif($detail->jour_repos)
                                                            <span class="badge bg-secondary">Repos</span>
                                                        @elseif($detail->jour_entier)
                                                            <span class="badge bg-success">Journée</span>
                                                        @else
                                                            {{ substr($detail->heure_debut, 0, 5) }} - {{ substr($detail->heure_fin, 0, 5) }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($detail)
                                                            {{ $detail->duree }}
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="2" class="text-end">Total hebdomadaire:</th>
                                                <th>{{ $planningSemaine->calculerHeuresTotales() }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="text-end mt-2">
                                    <a href="{{ route('plannings.show', $planningSemaine) }}" class="text-decoration-none">
                                        Voir le planning complet <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="bi bi-calendar-week text-muted" style="font-size: 2rem;"></i>
                                    <p class="mt-3 text-muted">Aucun planning en cours pour cet employé</p>
                                    <a href="{{ route('plannings.create', ['employe_id' => $employe->id]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-plus-circle"></i> Créer un planning
                                    </a>
                                </div>
                            @endif
                            
</div>
@endsection
