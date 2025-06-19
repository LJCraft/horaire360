// OPTIMISATION COMPLÈTE - CONFIGURATION PAR DÉPARTEMENT
console.log('🚀 CHARGEMENT OPTIMISATION DÉPARTEMENT');

document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 INITIALISATION CONFIGURATION DÉPARTEMENT OPTIMISÉE');
    
    // ÉLÉMENTS DOM
    const elements = {
        departementFilter: document.getElementById('departement_filter'),
        posteFilter: document.getElementById('poste_filter'),
        gradeFilter: document.getElementById('grade_filter'),
        filterButton: document.getElementById('filterButton'),
        departementResults: document.getElementById('departement-results'),
        departementInstruction: document.getElementById('departement-instruction'),
        departementNom: document.getElementById('departement-nom'),
        totalEmployes: document.getElementById('total-employes'),
        avecCritereIndividuel: document.getElementById('avec-critere-individuel'),
        sansCritere: document.getElementById('sans-critere'),
        peutRecevoirDepartemental: document.getElementById('peut-recevoir-departemental'),
        critereExistant: document.getElementById('critere-departemental-existant'),
        detailsCritere: document.getElementById('details-critere-departemental'),
        postesContainer: document.getElementById('postes-container'),
        employesTbody: document.getElementById('employes-tbody'),
        afficherUniquementSansCritere: document.getElementById('afficher-uniquement-sans-critere'),
        creerCritereBtn: document.getElementById('creer-critere-departemental-btn')
    };

    let currentData = null;

    // FORCER LE DÉVERROUILLAGE DES DROPDOWNS
    function forceUnlockDropdowns() {
        if (elements.posteFilter) {
            elements.posteFilter.disabled = false;
            elements.posteFilter.style.backgroundColor = '#ffffff';
            elements.posteFilter.style.opacity = '1';
            elements.posteFilter.style.cursor = 'pointer';
        }
        
        if (elements.gradeFilter) {
            elements.gradeFilter.disabled = false;
            elements.gradeFilter.style.backgroundColor = '#ffffff';
            elements.gradeFilter.style.opacity = '1';
            elements.gradeFilter.style.cursor = 'pointer';
        }
    }

    // DÉVERROUILLAGE INITIAL ET CONTINU
    forceUnlockDropdowns();
    setInterval(forceUnlockDropdowns, 1000);

    // ÉVÉNEMENT CHANGEMENT DE DÉPARTEMENT
    if (elements.departementFilter) {
        elements.departementFilter.addEventListener('change', function() {
            const departementId = this.value;
            console.log('🏢 Département sélectionné:', departementId);
            
            forceUnlockDropdowns();
            
            if (departementId) {
                chargerPostesDepartement(departementId);
            } else {
                resetFilters();
            }
        });
    }

    // ÉVÉNEMENT CHANGEMENT DE POSTE
    if (elements.posteFilter) {
        elements.posteFilter.addEventListener('change', function() {
            const posteId = this.value;
            console.log('💼 Poste sélectionné:', posteId);
            
            forceUnlockDropdowns();
            
            if (posteId) {
                chargerGradesPoste(posteId);
            } else {
                if (elements.gradeFilter) {
                    elements.gradeFilter.innerHTML = '<option value="">Tous les grades</option>';
                    elements.gradeFilter.disabled = false;
                }
            }
        });
    }

    // ÉVÉNEMENT BOUTON FILTRER
    if (elements.filterButton) {
        elements.filterButton.addEventListener('click', function() {
            const departementId = elements.departementFilter?.value;
            
            if (!departementId) {
                showAlert('Veuillez sélectionner un département', 'warning');
                return;
            }
            
            chargerEmployesDepartement();
        });
    }

    // FONCTION : RÉINITIALISER LES FILTRES
    function resetFilters() {
        if (elements.posteFilter) {
            elements.posteFilter.innerHTML = '<option value="">Tous les postes</option>';
            elements.posteFilter.disabled = false;
        }
        if (elements.gradeFilter) {
            elements.gradeFilter.innerHTML = '<option value="">Tous les grades</option>';
            elements.gradeFilter.disabled = false;
        }
        if (elements.departementResults) {
            elements.departementResults.classList.add('d-none');
        }
        if (elements.departementInstruction) {
            elements.departementInstruction.classList.remove('d-none');
        }
        forceUnlockDropdowns();
    }

    // FONCTION : AFFICHER UNE ALERTE
    function showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insérer l'alerte au début du contenu
        const cardBody = document.querySelector('#departements .card-body');
        if (cardBody) {
            cardBody.insertBefore(alertDiv, cardBody.firstChild);
            
            // Auto-suppression après 5 secondes
            setTimeout(() => {
                if (alertDiv && alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    }

    // FONCTION : CHARGER LES POSTES D'UN DÉPARTEMENT
    function chargerPostesDepartement(departementId) {
        console.log('📡 Chargement postes pour département:', departementId);
        
        if (!elements.posteFilter) return;
        
        elements.posteFilter.innerHTML = '<option value="">Chargement...</option>';
        elements.posteFilter.disabled = false;
        
        fetch('/criteres-pointage/get-postes-departement', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                departement_id: departementId
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('📊 Postes reçus:', data);
            
            elements.posteFilter.innerHTML = '<option value="">Tous les postes</option>';
            
            if (data.postes && data.postes.length > 0) {
                data.postes.forEach(poste => {
                    const option = document.createElement('option');
                    option.value = poste.id;
                    option.textContent = poste.nom;
                    elements.posteFilter.appendChild(option);
                });
            }
            
            elements.posteFilter.disabled = false;
            forceUnlockDropdowns();
        })
        .catch(error => {
            console.error('❌ Erreur chargement postes:', error);
            elements.posteFilter.innerHTML = '<option value="">Erreur - Réessayez</option>';
            elements.posteFilter.disabled = false;
            showAlert('Erreur lors du chargement des postes', 'danger');
        });
    }

    // FONCTION : CHARGER LES GRADES D'UN POSTE
    function chargerGradesPoste(posteId) {
        console.log('📡 Chargement grades pour poste:', posteId);
        
        if (!elements.gradeFilter) return;
        
        elements.gradeFilter.innerHTML = '<option value="">Chargement...</option>';
        elements.gradeFilter.disabled = false;
        
        fetch('/criteres-pointage/get-grades-poste', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                poste_id: posteId
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('📊 Grades reçus:', data);
            
            elements.gradeFilter.innerHTML = '<option value="">Tous les grades</option>';
            
            if (data.grades && data.grades.length > 0) {
                data.grades.forEach(grade => {
                    const option = document.createElement('option');
                    option.value = grade.id;
                    option.textContent = grade.nom;
                    elements.gradeFilter.appendChild(option);
                });
            }
            
            elements.gradeFilter.disabled = false;
            forceUnlockDropdowns();
        })
        .catch(error => {
            console.error('❌ Erreur chargement grades:', error);
            elements.gradeFilter.innerHTML = '<option value="">Erreur - Réessayez</option>';
            elements.gradeFilter.disabled = false;
            showAlert('Erreur lors du chargement des grades', 'danger');
        });
    }

    // FONCTION : CHARGER LES EMPLOYÉS DU DÉPARTEMENT
    function chargerEmployesDepartement() {
        const departementId = elements.departementFilter?.value;
        const posteId = elements.posteFilter?.value || null;
        const gradeId = elements.gradeFilter?.value || null;
        
        console.log('📡 Chargement employés:', { departementId, posteId, gradeId });
        
        // Afficher un spinner de chargement
        if (elements.employesTbody) {
            elements.employesTbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <div class="text-muted">Chargement des employés du département...</div>
                    </td>
                </tr>
            `;
        }
        
        fetch('/criteres-pointage/get-employes-departement', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                departement_id: departementId,
                poste_id: posteId,
                grade_id: gradeId
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('📊 Données employés reçues:', data);
            
            if (data.error) {
                showAlert('Erreur: ' + data.error, 'danger');
                return;
            }
            
            currentData = data;
            afficherResultats(data);
        })
        .catch(error => {
            console.error('❌ Erreur chargement employés:', error);
            showAlert('Erreur lors du chargement des employés', 'danger');
            
            if (elements.employesTbody) {
                elements.employesTbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5 text-danger">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <div>Erreur lors du chargement des données</div>
                        </td>
                    </tr>
                `;
            }
        });
    }

    // FONCTION : AFFICHER LES RÉSULTATS
    function afficherResultats(data) {
        // MASQUER LE MESSAGE D'INSTRUCTION ET AFFICHER LES RÉSULTATS
        if (elements.departementInstruction) {
            elements.departementInstruction.classList.add('d-none');
        }
        if (elements.departementResults) {
            elements.departementResults.classList.remove('d-none');
        }
        
        // METTRE À JOUR LE NOM DU DÉPARTEMENT
        if (elements.departementNom) {
            elements.departementNom.textContent = data.departement;
        }
        
        // METTRE À JOUR LES STATISTIQUES
        if (elements.totalEmployes) {
            elements.totalEmployes.textContent = data.statistiques.total_employes;
        }
        if (elements.avecCritereIndividuel) {
            elements.avecCritereIndividuel.textContent = data.statistiques.avec_critere_individuel;
        }
        if (elements.sansCritere) {
            elements.sansCritere.textContent = data.statistiques.sans_critere;
        }
        if (elements.peutRecevoirDepartemental) {
            elements.peutRecevoirDepartemental.textContent = data.statistiques.peut_recevoir_departemental;
        }
        
        // AFFICHER CRITÈRE DÉPARTEMENTAL EXISTANT
        if (data.critere_departemental_existant && elements.critereExistant && elements.detailsCritere) {
            const critere = data.critere_departemental_existant;
            elements.detailsCritere.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Nombre de pointages:</strong> ${critere.nombre_pointages}</p>
                        <p class="mb-1"><strong>Source:</strong> ${critere.source_pointage}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Tolérance:</strong> ${critere.tolerance_avant} min avant / ${critere.tolerance_apres} min après</p>
                        <p class="mb-0"><strong>Employés concernés:</strong> <span class="badge bg-primary">${critere.employes_concernes}</span></p>
                    </div>
                </div>
            `;
            elements.critereExistant.classList.remove('d-none');
        } else if (elements.critereExistant) {
            elements.critereExistant.classList.add('d-none');
        }
        
        // AFFICHER LES POSTES
        afficherPostes(data.postes);
        
        // AFFICHER LES EMPLOYÉS
        afficherEmployes(data.employes);
    }

    // FONCTION : AFFICHER LES POSTES
    function afficherPostes(postes) {
        if (!elements.postesContainer) return;
        
        elements.postesContainer.innerHTML = '';
        
        if (postes.length === 0) {
            elements.postesContainer.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Aucun poste trouvé dans ce département
                    </div>
                </div>
            `;
            return;
        }
        
        postes.forEach(poste => {
            const posteCard = document.createElement('div');
            posteCard.className = 'col-md-4 col-lg-3 mb-3';
            posteCard.innerHTML = `
                <div class="card h-100 border-primary employe-card">
                    <div class="card-body text-center py-3">
                        <i class="fas fa-briefcase fa-2x text-primary mb-2"></i>
                        <h6 class="card-title text-primary mb-1">${poste.nom}</h6>
                        <small class="text-muted">ID: ${poste.id}</small>
                    </div>
                </div>
            `;
            elements.postesContainer.appendChild(posteCard);
        });
    }

    // FONCTION : AFFICHER LES EMPLOYÉS
    function afficherEmployes(employes) {
        if (!elements.employesTbody) return;
        
        elements.employesTbody.innerHTML = '';
        
        if (employes.length === 0) {
            elements.employesTbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-users fa-3x mb-3 d-block"></i>
                        Aucun employé trouvé avec les critères sélectionnés
                    </td>
                </tr>
            `;
            return;
        }
        
        employes.forEach(employe => {
            const row = document.createElement('tr');
            row.dataset.aCritereIndividuel = employe.a_critere_individuel;
            row.className = 'employe-row';
            
            // DÉTERMINER LE BADGE D'ÉTAT
            let badgeEtat = '';
            if (employe.a_critere_individuel) {
                badgeEtat = '<span class="badge bg-success"><i class="fas fa-user-check me-1"></i>Critère individuel</span>';
            } else if (employe.type_critere === 'departemental') {
                badgeEtat = '<span class="badge bg-info"><i class="fas fa-building me-1"></i>Critère départemental</span>';
            } else {
                badgeEtat = '<span class="badge bg-warning text-dark"><i class="fas fa-user-times me-1"></i>Sans critère</span>';
            }
            
            // NOMBRE DE POINTAGES
            const nombrePointages = employe.nombre_pointages || 'Non défini';
            
            // BOUTONS D'ACTION
            let boutons = '';
            if (!employe.a_critere_individuel) {
                boutons = `
                    <button class="btn btn-primary btn-sm" onclick="creerCritereIndividuel(${employe.id})" title="Créer un critère individuel">
                        <i class="fas fa-plus me-1"></i>Individuel
                    </button>
                `;
            } else {
                boutons = `<span class="text-muted small"><i class="fas fa-check me-1"></i>Configuré</span>`;
            }
            
            row.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${employe.photo}" alt="${employe.nom}" class="employe-avatar me-3" style="width: 40px; height: 40px; object-fit: cover;">
                        <div>
                            <div class="fw-bold text-primary">${employe.prenom} ${employe.nom}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge bg-light text-dark">${employe.poste}</span></td>
                <td><span class="badge bg-light text-dark">${employe.grade}</span></td>
                <td>${badgeEtat}</td>
                <td class="text-center">
                    ${nombrePointages !== 'Non défini' ? `<span class="badge bg-primary">${nombrePointages}</span>` : '<span class="text-muted small">Non défini</span>'}
                </td>
                <td class="text-center">${boutons}</td>
            `;
            
            elements.employesTbody.appendChild(row);
        });
    }

    // ÉVÉNEMENT : FILTRE AFFICHAGE EMPLOYÉS SANS CRITÈRE INDIVIDUEL
    if (elements.afficherUniquementSansCritere) {
        elements.afficherUniquementSansCritere.addEventListener('change', function() {
            if (!elements.employesTbody) return;
            
            const rows = elements.employesTbody.querySelectorAll('.employe-row');
            rows.forEach(row => {
                const aCritereIndividuel = row.dataset.aCritereIndividuel === 'true';
                
                if (this.checked && aCritereIndividuel) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        });
    }

    console.log('✅ CONFIGURATION DÉPARTEMENT OPTIMISÉE ACTIVÉE');
});

// FONCTION GLOBALE : RÉINITIALISER LES FILTRES DÉPARTEMENT
function resetDepartementFilters() {
    console.log('🔄 RESET FILTRES DÉPARTEMENT');
    
    const elements = {
        departementFilter: document.getElementById('departement_filter'),
        posteFilter: document.getElementById('poste_filter'),
        gradeFilter: document.getElementById('grade_filter'),
        departementResults: document.getElementById('departement-results'),
        departementInstruction: document.getElementById('departement-instruction'),
        afficherUniquementSansCritere: document.getElementById('afficher-uniquement-sans-critere')
    };
    
    // Réinitialiser tous les filtres
    if (elements.departementFilter) elements.departementFilter.value = '';
    if (elements.posteFilter) {
        elements.posteFilter.innerHTML = '<option value="">Tous les postes</option>';
        elements.posteFilter.disabled = false;
    }
    if (elements.gradeFilter) {
        elements.gradeFilter.innerHTML = '<option value="">Tous les grades</option>';
        elements.gradeFilter.disabled = false;
    }
    if (elements.afficherUniquementSansCritere) {
        elements.afficherUniquementSansCritere.checked = false;
    }
    
    // Afficher le message d'instruction et masquer les résultats
    if (elements.departementResults) {
        elements.departementResults.classList.add('d-none');
    }
    if (elements.departementInstruction) {
        elements.departementInstruction.classList.remove('d-none');
    }
}

// FONCTION GLOBALE : CRÉER CRITÈRE INDIVIDUEL
function creerCritereIndividuel(employeId) {
    console.log('👤 Création critère individuel pour employé:', employeId);
    
    const employeSelect = document.getElementById('employe_id');
    if (employeSelect) {
        employeSelect.value = employeId;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('individuellModal'));
    modal.show();
} 