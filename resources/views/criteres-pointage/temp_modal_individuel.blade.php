<!-- Modal pour la création d'un critère individuel -->
<div class="modal fade" id="individuellModal" tabindex="-1" aria-labelledby="individuellModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="individuellModalLabel">Créer un critère individuel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="critere-individuel-form" action="{{ route('criteres-pointage.store') }}" method="POST">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="niveau" value="individuel">
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="employe_id" class="form-label">Employé <span class="text-danger">*</span></label>
                            <select class="form-select @error('employe_id') is-invalid @enderror" id="employe_id" name="employe_id" required>
                                <option value="">Sélectionner un employé</option>
                                @foreach($employes as $employe)
                                    <option value="{{ $employe->id }}" {{ old('employe_id') == $employe->id ? 'selected' : '' }}>
                                        {{ $employe->nom }} {{ $employe->prenom }} - {{ $employe->poste->nom ?? 'Sans poste' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employe_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date_debut') is-invalid @enderror" id="date_debut" name="date_debut" value="{{ old('date_debut', date('Y-m-d')) }}" required>
                            @error('date_debut')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="date_fin" class="form-label">Date de fin <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date_fin') is-invalid @enderror" id="date_fin" name="date_fin" value="{{ old('date_fin', date('Y-m-d', strtotime('+1 year'))) }}" required>
                            @error('date_fin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="periode" class="form-label">Période <span class="text-danger">*</span></label>
                            <select class="form-select @error('periode') is-invalid @enderror" id="periode" name="periode" required>
                                <option value="jour" {{ old('periode') == 'jour' ? 'selected' : '' }}>Jour</option>
                                <option value="semaine" {{ old('periode') == 'semaine' ? 'selected' : '' }}>Semaine</option>
                                <option value="mois" {{ old('periode', 'mois') == 'mois' ? 'selected' : '' }}>Mois</option>
                            </select>
                            @error('periode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="nombre_pointages" class="form-label">Nombre de pointages <span class="text-danger">*</span></label>
                            <select class="form-select @error('nombre_pointages') is-invalid @enderror" id="nombre_pointages" name="nombre_pointages" required>
                                <option value="1" {{ old('nombre_pointages') == '1' ? 'selected' : '' }}>1 (Présence uniquement)</option>
                                <option value="2" {{ old('nombre_pointages', '2') == '2' ? 'selected' : '' }}>2 (Arrivée et départ)</option>
                            </select>
                            @error('nombre_pointages')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tolerance_avant" class="form-label">Tolérance avant (minutes)</label>
                            <input type="number" class="form-control @error('tolerance_avant') is-invalid @enderror" id="tolerance_avant" name="tolerance_avant" value="{{ old('tolerance_avant', 10) }}" min="0" max="60" required>
                            @error('tolerance_avant')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Minutes de tolérance avant l'heure prévue (0 = pas de tolérance).
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="tolerance_apres" class="form-label">Tolérance après (minutes)</label>
                            <input type="number" class="form-control @error('tolerance_apres') is-invalid @enderror" id="tolerance_apres" name="tolerance_apres" value="{{ old('tolerance_apres', 10) }}" min="0" max="60" required>
                            @error('tolerance_apres')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Minutes de tolérance après l'heure prévue (0 = pas de tolérance).
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="duree_pause" class="form-label">Durée de pause (minutes)</label>
                            <input type="number" class="form-control @error('duree_pause') is-invalid @enderror" id="duree_pause" name="duree_pause" value="{{ old('duree_pause', 0) }}" min="0" max="240" required>
                            @error('duree_pause')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Durée de pause non décomptée du temps de travail (0 = pas de pause).
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="source_pointage" class="form-label">Source de pointage</label>
                            <select class="form-select @error('source_pointage') is-invalid @enderror" id="source_pointage" name="source_pointage" required>
                                <option value="tous" {{ old('source_pointage', 'tous') == 'tous' ? 'selected' : '' }}>Tous types de pointage</option>
                                <option value="biometrique" {{ old('source_pointage') == 'biometrique' ? 'selected' : '' }}>Biométrique uniquement</option>
                                <option value="manuel" {{ old('source_pointage') == 'manuel' ? 'selected' : '' }}>Manuel uniquement</option>
                            </select>
                            @error('source_pointage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Définit le type de pointage à prendre en compte pour les critères.
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="calcul_heures_sup_individuel" name="calcul_heures_sup" value="1" {{ old('calcul_heures_sup') ? 'checked' : '' }}>
                                <label class="form-check-label" for="calcul_heures_sup_individuel">Activer le calcul des heures supplémentaires</label>
                            </div>
                            <div class="form-text">
                                Permet de comptabiliser automatiquement les heures supplémentaires.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="seuil_heures_sup" class="form-label">Seuil des heures supplémentaires (minutes)</label>
                            <input type="number" class="form-control @error('seuil_heures_sup') is-invalid @enderror" id="seuil_heures_sup" name="seuil_heures_sup" value="{{ old('seuil_heures_sup', 15) }}" min="0" max="240" required>
                            @error('seuil_heures_sup')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Durée minimale au-delà de l'heure de fin prévue pour comptabiliser des heures supplémentaires.
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="priorite" class="form-label">Priorité du critère</label>
                            <select class="form-select @error('priorite') is-invalid @enderror" id="priorite" name="priorite" required>
                                <option value="1" {{ old('priorite', 1) == 1 ? 'selected' : '' }}>Haute (1)</option>
                                <option value="2" {{ old('priorite') == 2 ? 'selected' : '' }}>Normale (2)</option>
                                <option value="3" {{ old('priorite') == 3 ? 'selected' : '' }}>Basse (3)</option>
                            </select>
                            @error('priorite')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                En cas de chevauchement de critères, celui avec la priorité la plus haute sera appliqué.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1" {{ old('actif', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="actif">Critère actif</label>
                            </div>
                            <div class="form-text">
                                Décochez cette case pour désactiver ce critère sans le supprimer.
                            </div>
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
