<!-- Modal pour la création d'un critère départemental -->
<div class="modal fade" id="departementModal" tabindex="-1" aria-labelledby="departementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="departementModalLabel">Créer un critère départemental</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="critere-departemental-form" action="{{ route('criteres-pointage.store') }}" method="POST">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="niveau" value="departemental">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="departement_id" class="form-label">Département</label>
                            <select class="form-select" id="departement_id" name="departement_id" required>
                                <option value="">Sélectionner un département</option>
                                @foreach ($departements as $departement)
                                    <option value="{{ $departement->departement }}">
                                        {{ $departement->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="periode" class="form-label">Période</label>
                            <select class="form-select" id="periode" name="periode" required>
                                <option value="jour">Jour</option>
                                <option value="semaine">Semaine</option>
                                <option value="mois" selected>Mois</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="{{ date('Y-m-d', strtotime('+1 month')) }}" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nombre_pointages" class="form-label">Nombre de pointages</label>
                            <select class="form-select" id="nombre_pointages" name="nombre_pointages" required>
                                <option value="1">1 pointage (présence uniquement)</option>
                                <option value="2" selected>2 pointages (arrivée et départ)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="source_pointage" class="form-label">Source de pointage</label>
                            <select class="form-select" id="source_pointage" name="source_pointage" required>
                                <option value="biometrique">Biométrique</option>
                                <option value="manuel">Manuel</option>
                                <option value="tous" selected>Tous</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="tolerance_avant" class="form-label">Tolérance avant (minutes)</label>
                            <input type="number" class="form-control" id="tolerance_avant" name="tolerance_avant" value="5" min="0" max="60" required>
                        </div>
                        <div class="col-md-4">
                            <label for="tolerance_apres" class="form-label">Tolérance après (minutes)</label>
                            <input type="number" class="form-control" id="tolerance_apres" name="tolerance_apres" value="5" min="0" max="60" required>
                        </div>
                        <div class="col-md-4">
                            <label for="duree_pause" class="form-label">Durée de pause (minutes)</label>
                            <input type="number" class="form-control" id="duree_pause" name="duree_pause" value="60" min="0" max="120" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="calcul_heures_sup" name="calcul_heures_sup" value="1">
                                <label class="form-check-label" for="calcul_heures_sup">Activer le calcul des heures supplémentaires</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="seuil_heures_sup" class="form-label">Seuil des heures supplémentaires (minutes)</label>
                            <input type="number" class="form-control" id="seuil_heures_sup" name="seuil_heures_sup" value="30" min="0" max="240">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Appliquer à:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="appliquer_a" id="appliquer_tous" value="tous" checked>
                            <label class="form-check-label" for="appliquer_tous">
                                Tous les employés sans critère individuel
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="appliquer_a" id="appliquer_selection" value="selection">
                            <label class="form-check-label" for="appliquer_selection">
                                Sélection d'employés spécifiques
                            </label>
                        </div>
                    </div>
                    
                    <div id="employes-selection" class="d-none">
                        <label class="form-label">Sélectionner les employés:</label>
                        <div class="employes-list border p-3 rounded" style="max-height: 200px; overflow-y: auto;">
                            <!-- Les employés seront chargés dynamiquement ici -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
