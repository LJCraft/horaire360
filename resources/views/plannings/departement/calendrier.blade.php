@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-week me-2"></i>Calendrier des plannings par département
                    </h5>
                    <div>
                        <a href="{{ route('plannings.departement.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="bi bi-list me-1"></i>Liste des plannings
                        </a>
                        <a href="{{ route('plannings.departement.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>Nouveau planning
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <!-- Filtres -->
                    <div class="row mb-4">
                        <div class="col-md-9">
                            <form id="filterForm" class="row g-3" method="GET" action="{{ route('plannings.departement.calendrier') }}">
                                <div class="col-md-4">
                                    <label for="departement" class="form-label">Département</label>
                                    <select id="departement" name="departement" class="form-select form-select-sm">
                                        <option value="">Tous les départements</option>
                                        @foreach($departements ?? [] as $dept)
                                            <option value="{{ $dept }}" {{ $departementSelectionne == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="poste_id" class="form-label">Poste</label>
                                    <select id="poste_id" name="poste_id" class="form-select form-select-sm">
                                        <option value="">Tous les postes</option>
                                        @foreach($postes ?? [] as $poste)
                                            <option value="{{ $poste->id }}" {{ request('poste_id') == $poste->id ? 'selected' : '' }}>
                                                {{ $poste->departement }} - {{ $poste->nom }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="bi bi-filter me-1"></i>Filtrer
                                    </button>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <a href="{{ route('plannings.departement.calendrier') }}" class="btn btn-outline-secondary btn-sm w-100">
                                        <i class="bi bi-x-circle me-1"></i>Réinitialiser
                                    </a>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-3 d-flex justify-content-end align-items-end">
                            <div class="btn-group" role="group">
                                <button type="button" id="view-month" class="btn btn-outline-primary btn-sm active">
                                    <i class="bi bi-calendar-month me-1"></i>Mois
                                </button>
                                <button type="button" id="view-week" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-calendar-week me-1"></i>Semaine
                                </button>
                                <button type="button" id="view-day" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-calendar-day me-1"></i>Jour
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Légende -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body py-2">
                                    <h6 class="mb-2">Légende</h6>
                                    <div class="d-flex flex-wrap">
                                        <div class="me-3 mb-1">
                                            <span class="badge bg-primary me-1">&nbsp;</span>
                                            <small>Horaire normal</small>
                                        </div>
                                        <div class="me-3 mb-1">
                                            <span class="badge bg-success me-1">&nbsp;</span>
                                            <small>Repos</small>
                                        </div>
                                        <div class="me-3 mb-1">
                                            <span class="badge bg-warning text-dark me-1">&nbsp;</span>
                                            <small>Journée entière</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calendrier -->
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal détails planning -->
<div class="modal fade" id="planningModal" tabindex="-1" aria-labelledby="planningModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="planningModalLabel">Détails du planning</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="planningModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
<style>
    .fc-event {
        cursor: pointer;
    }
    .fc-event-title {
        white-space: normal;
        font-weight: 500;
    }
    
    /* Styles pour les différents types d'événements */
    .poste-event {
        border-left-width: 5px;
    }
    .repos-event {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }
    .jour-entier-event {
        background-image: linear-gradient(45deg, rgba(255,255,255,.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.15) 50%, rgba(255,255,255,.15) 75%, transparent 75%, transparent) !important;
        background-size: 1rem 1rem !important;
    }
    .horaire-event {
        border-style: solid;
    }
    
    /* Tooltip personnalisé */
    .calendar-tooltip {
        position: absolute;
        z-index: 1070;
        display: block;
        margin: 0;
        padding: 8px;
        max-width: 300px;
        background-color: white;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        font-size: 0.875rem;
        border: 1px solid rgba(0,0,0,0.1);
    }
    .calendar-tooltip h6 {
        margin-bottom: 0.5rem;
        border-bottom: 1px solid #eee;
        padding-bottom: 0.5rem;
    }
    .calendar-tooltip ul {
        margin: 0;
        padding-left: 1.5rem;
    }
</style>
@endpush

@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales/fr.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation du calendrier
    var calendarEl = document.getElementById('calendar');
    var tooltipEl = null;
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'fr',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: "Aujourd'hui",
            month: 'Mois',
            week: 'Semaine',
            day: 'Jour'
        },
        allDayText: 'Journée',
        firstDay: 1, // Lundi comme premier jour
        height: 'auto',
        navLinks: true,
        selectable: false,
        selectMirror: true,
        dayMaxEvents: true,
        eventClick: function(info) {
            showGroupDetails(info.event);
        },
        eventMouseEnter: function(info) {
            showTooltip(info.event, info.jsEvent);
        },
        eventMouseLeave: function() {
            hideTooltip();
        },
        events: function(info, successCallback, failureCallback) {
            // Récupération des données filtrées
            var departement = document.getElementById('departement').value;
            var posteId = document.getElementById('poste_id').value;
            
            // Appel AJAX pour récupérer les événements
            fetch('{{ url("/api/plannings/departement") }}?start=' + info.startStr + '&end=' + info.endStr + 
                  '&departement=' + departement + '&poste_id=' + posteId)
                .then(response => response.json())
                .then(data => {
                    successCallback(data);
                })
                .catch(error => {
                    console.error('Erreur de chargement des événements:', error);
                    failureCallback(error);
                });
        }
    });
    
    calendar.render();
    
    // Gestion des vues
    document.getElementById('view-month').addEventListener('click', function() {
        calendar.changeView('dayGridMonth');
        setActiveViewButton(this);
    });
    
    document.getElementById('view-week').addEventListener('click', function() {
        calendar.changeView('timeGridWeek');
        setActiveViewButton(this);
    });
    
    document.getElementById('view-day').addEventListener('click', function() {
        calendar.changeView('timeGridDay');
        setActiveViewButton(this);
    });
    
    function setActiveViewButton(button) {
        document.querySelectorAll('.btn-group .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        button.classList.add('active');
    }
    
    // Fonction pour afficher un tooltip au survol
    function showTooltip(event, mouseEvent) {
        // Créer le tooltip s'il n'existe pas
        if (!tooltipEl) {
            tooltipEl = document.createElement('div');
            tooltipEl.className = 'calendar-tooltip';
            document.body.appendChild(tooltipEl);
        }
        
        // Récupérer les données de l'événement
        var props = event.extendedProps;
        var employes = props.employes || [];
        var type = props.type;
        var heureDebut = props.heure_debut;
        var heureFin = props.heure_fin;
        
        // Construire le contenu du tooltip
        var content = `<h6>${props.poste} - ${props.departement}</h6>`;
        
        if (type === 'repos') {
            content += `<p class="mb-1"><span class="badge bg-success">Repos</span></p>`;
        } else if (type === 'jour_entier') {
            content += `<p class="mb-1"><span class="badge bg-warning text-dark">Journée entière</span></p>`;
        } else {
            content += `<p class="mb-1"><span class="badge bg-primary">Horaire: ${heureDebut.substring(0, 5)} - ${heureFin.substring(0, 5)}</span></p>`;
        }
        
        content += `<p class="mb-1"><strong>${employes.length} employé(s):</strong></p>`;
        content += '<ul class="mb-0">';
        
        // Limiter à 5 employés pour éviter un tooltip trop grand
        var displayCount = Math.min(employes.length, 5);
        for (var i = 0; i < displayCount; i++) {
            content += `<li>${employes[i].nom}</li>`;
        }
        
        // S'il y a plus d'employés, indiquer le nombre restant
        if (employes.length > 5) {
            content += `<li>+ ${employes.length - 5} autres</li>`;
        }
        
        content += '</ul>';
        
        // Mettre à jour le contenu et positionner le tooltip
        tooltipEl.innerHTML = content;
        tooltipEl.style.left = mouseEvent.pageX + 10 + 'px';
        tooltipEl.style.top = mouseEvent.pageY + 10 + 'px';
        tooltipEl.style.display = 'block';
    }
    
    function hideTooltip() {
        if (tooltipEl) {
            tooltipEl.style.display = 'none';
        }
    }
    
    // Fonction pour afficher les détails d'un groupe
    function showGroupDetails(event) {
        var modal = new bootstrap.Modal(document.getElementById('planningModal'));
        var props = event.extendedProps;
        var employes = props.employes || [];
        
        // Titre de la modal
        document.getElementById('planningModalLabel').textContent = 
            props.poste + ' - ' + event.start.toLocaleDateString('fr-FR');
        
        // Construire le contenu
        var content = `
            <div class="mb-3">
                <h6 class="mb-2">${props.departement} - ${props.poste}</h6>
                <p class="mb-1">
                    <strong>Date:</strong> ${event.start.toLocaleDateString('fr-FR')}
                </p>
        `;
        
        if (props.type === 'repos') {
            content += `<p class="mb-2"><span class="badge bg-success">Repos</span></p>`;
        } else if (props.type === 'jour_entier') {
            content += `<p class="mb-2"><span class="badge bg-warning text-dark">Journée entière</span></p>`;
        } else {
            content += `<p class="mb-2"><span class="badge bg-primary">Horaire: ${props.heure_debut.substring(0, 5)} - ${props.heure_fin.substring(0, 5)}</span></p>`;
        }
        
        content += `
            </div>
            <div class="mb-3">
                <h6 class="border-bottom pb-2 mb-2">Employés (${employes.length})</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        employes.forEach(function(employe) {
            content += `
                <tr>
                    <td>${employe.nom}</td>
                    <td class="text-end">
                        <a href="/plannings/${employe.planning_id}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="/plannings/${employe.planning_id}/edit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
            `;
        });
        
        content += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        document.getElementById('planningModalBody').innerHTML = content;
        
        // Afficher la modal
        modal.show();
    }
    
    // Mettre à jour les postes disponibles lorsque le département change
    document.getElementById('departement').addEventListener('change', function() {
        var departement = this.value;
        var posteSelect = document.getElementById('poste_id');
        
        // Réinitialiser la sélection de poste
        posteSelect.innerHTML = '<option value="">Tous les postes</option>';
        
        // Si un département est sélectionné, charger les postes correspondants
        if (departement) {
            fetch('{{ url("/api/postes") }}?departement=' + departement)
                .then(response => response.json())
                .then(postes => {
                    postes.forEach(function(poste) {
                        var option = document.createElement('option');
                        option.value = poste.id;
                        option.textContent = poste.nom;
                        posteSelect.appendChild(option);
                    });
                });
        }
    });
});
</script>
@endpush 