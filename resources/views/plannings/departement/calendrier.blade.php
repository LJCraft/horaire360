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
            
            // Appel AJAX pour récupérer les événements
            fetch('{{ url("/api/plannings") }}?start=' + info.startStr + '&end=' + info.endStr + 
                  '&departement=' + departement)
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
                        <i class="bi bi-person me-1"></i> ${data.employe}<br>
                        <i class="bi bi-building me-1"></i> ${data.departement}<br>
                        <i class="bi bi-briefcase me-1"></i> ${data.poste}
                    </p>
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Date de début</small>
                            <div>${data.date_debut}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Date de fin</small>
                            <div>${data.date_fin}</div>
                        </div>
                    </div>
                `;
                
                if (data.description) {
                    content += `
                        <div class="mb-3">
                            <small class="text-muted">Description</small>
                            <div>${data.description}</div>
                        </div>
                    `;
                }
                
                if (data.details && data.details.length > 0) {
                    content += `
                        <h6 class="mt-3">Horaires hebdomadaires</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Jour</th>
                                    <th>Type</th>
                                    <th>Horaires</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    data.details.forEach(detail => {
                        let horaires = '';
                        if (detail.type === 'Horaire') {
                            horaires = `${detail.heure_debut} - ${detail.heure_fin}`;
                        } else if (detail.type === 'Journée entière') {
                            horaires = 'Toute la journée';
                        } else {
                            horaires = 'Repos';
                        }
                        
                        content += `
                            <tr>
                                <td>${detail.jour}</td>
                                <td>${detail.type}</td>
                                <td>${horaires}</td>
                            </tr>
                        `;
                        
                        if (detail.note) {
                            content += `
                                <tr>
                                    <td colspan="3" class="text-muted small">
                                        <i class="bi bi-info-circle me-1"></i> ${detail.note}
                                    </td>
                                </tr>
                            `;
                        }
                    });
                    
                    content += `
                            </tbody>
                        </table>
                    `;
                }
                
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