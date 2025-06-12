@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-week me-2"></i>Calendrier des plannings
                    </h5>
                    <div>
                        <a href="{{ route('plannings.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="bi bi-list me-1"></i>Liste des plannings
                        </a>
                        <a href="{{ route('plannings.create') }}" class="btn btn-primary btn-sm">
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
                            <form id="filterForm" class="row g-3">
                                <div class="col-md-4">
                                    <label for="departement" class="form-label">Département</label>
                                    <select id="departement" class="form-select form-select-sm">
                                        <option value="">Tous les départements</option>
                                        @foreach($departements ?? [] as $dept)
                                            <option value="{{ $dept }}">{{ $dept }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="employe" class="form-label">Employé</label>
                                    <select id="employe" class="form-select form-select-sm">
                                        <option value="">Tous les employés</option>
                                        @foreach($employes ?? [] as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->nom_complet }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" id="filtrer" class="btn btn-primary btn-sm w-100">
                                        <i class="bi bi-filter me-1"></i>Filtrer
                                    </button>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" id="resetFiltre" class="btn btn-outline-secondary btn-sm w-100">
                                        <i class="bi bi-x-circle me-1"></i>Réinitialiser
                                    </button>
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
                <a href="#" class="btn btn-primary" id="editPlanningBtn">
                    <i class="bi bi-pencil me-1"></i>Modifier
                </a>
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
    }
    
    /* Styles pour les différents types d'événements */
    .planning-event {
        background-color: #3788d8;
        border-color: #3788d8;
    }
    .repos-event {
        background-color: #28a745;
        border-color: #28a745;
    }
    .conge-event {
        background-color: #dc3545;
        border-color: #dc3545;
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
            showPlanningDetails(info.event.id);
        },
        events: function(info, successCallback, failureCallback) {
            // Récupération des données filtrées
            var departement = document.getElementById('departement').value;
            var employe = document.getElementById('employe').value;
            
            // Appel AJAX pour récupérer les événements
            fetch('{{ url("/api/plannings") }}?start=' + info.startStr + '&end=' + info.endStr + 
                  '&departement=' + departement + '&employe=' + employe)
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
    
    // Gestion des filtres
    document.getElementById('filtrer').addEventListener('click', function() {
        calendar.refetchEvents();
    });
    
    document.getElementById('resetFiltre').addEventListener('click', function() {
        document.getElementById('departement').value = '';
        document.getElementById('employe').value = '';
        calendar.refetchEvents();
    });
    
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
    
    // Fonction pour afficher les détails d'un planning
    function showPlanningDetails(planningId) {
        var modal = new bootstrap.Modal(document.getElementById('planningModal'));
        
        // Réinitialiser le contenu
        document.getElementById('planningModalBody').innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        `;
        
        // Mettre à jour le lien d'édition
        document.getElementById('editPlanningBtn').href = '/plannings/' + planningId + '/edit';
        
        // Afficher la modal
        modal.show();
        
        // Charger les détails
        fetch('{{ url("/api/plannings") }}/' + planningId)
            .then(response => response.json())
            .then(data => {
                var content = `
                    <h5>${data.titre}</h5>
                    <p class="text-muted">
                        <strong>Période:</strong> ${data.date_debut} - ${data.date_fin}
                    </p>
                    ${data.description ? '<p>' + data.description + '</p>' : ''}
                    <hr>
                    <p><strong>Employé:</strong> ${data.employe}</p>
                    <p><strong>Poste:</strong> ${data.poste}</p>
                    <p><strong>Département:</strong> ${data.departement}</p>
                    
                    <h6 class="mt-3">Horaires hebdomadaires</h6>
                    <ul class="list-group">
                `;
                
                data.details.forEach(detail => {
                    content += `
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">${detail.jour}</div>
                                ${detail.type === 'repos' ? 
                                    '<span class="badge bg-success">Repos</span>' : 
                                    (detail.type === 'jour_entier' ? 
                                        '<span class="badge bg-primary">Journée entière</span>' : 
                                        detail.heure_debut + ' - ' + detail.heure_fin)}
                                ${detail.note ? '<br><small class="text-muted">' + detail.note + '</small>' : ''}
                            </div>
                        </li>
                    `;
                });
                
                content += `</ul>`;
                
                document.getElementById('planningModalBody').innerHTML = content;
            })
            .catch(error => {
                document.getElementById('planningModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        Erreur lors du chargement des détails: ${error.message}
                    </div>
                `;
            });
    }
});
</script>
@endpush