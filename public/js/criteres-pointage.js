/**
 * Script pour gérer les formulaires de critères de pointage et le filtrage
 */
document.addEventListener('DOMContentLoaded', function() {
    // ===== GESTION DU FILTRAGE ET DES LISTES DÉROULANTES =====
    
    // Éléments du formulaire de filtrage
    const filterButton = document.getElementById('filterButton');
    const departementFilter = document.getElementById('departement_filter');
    const periodeFilter = document.getElementById('periode_filter');
    const posteFilter = document.getElementById('poste_filter');
    const gradeFilter = document.getElementById('grade_filter');
    const employesContainer = document.getElementById('employes-container');
    const departementStats = document.getElementById('departement-stats');
    const departementNom = document.getElementById('departement-nom');
    const employesCount = document.getElementById('employes-count');
    const employesConfiguredCount = document.getElementById('employes-configured-count');
    const employesTable = document.getElementById('employes-table')?.querySelector('tbody');
    const showOnlyUnconfigured = document.getElementById('show-only-unconfigured');
    const createDepartementCritere = document.getElementById('create-departement-critere');
    
    // Mise à jour des postes lors du changement de département
    if (departementFilter) {
        departementFilter.addEventListener('change', function() {
            const departementId = this.value;
            if (departementId) {
                updatePostesByDepartement(departementId);
            } else {
                // Réinitialiser le sélecteur de postes
                if (posteFilter) {
                    posteFilter.innerHTML = '<option value="">Tous les postes</option>';
                    posteFilter.disabled = true;
                }
                
                // Réinitialiser le sélecteur de grades
                if (gradeFilter) {
                    gradeFilter.innerHTML = '<option value="">Tous les grades</option>';
                    gradeFilter.disabled = true;
                }
            }
        });
    }
    
    // Mise à jour des grades lors du changement de poste
    if (posteFilter) {
        posteFilter.addEventListener('change', function() {
            const posteId = this.value;
            if (posteId) {
                updateGradesByPoste(posteId);
            } else {
                // Réinitialiser le sélecteur de grades
                if (gradeFilter) {
                    gradeFilter.innerHTML = '<option value="">Tous les grades</option>';
                    gradeFilter.disabled = true;
                }
            }
        });
    }
    
    // Fonction pour mettre à jour les postes en fonction du département
    function updatePostesByDepartement(departementId) {
        if (!posteFilter) return;
        
        // Obtenir le token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            console.error('CSRF token not found');
            return;
        }
        
        fetch('/criteres-pointage/get-postes-departement', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                departement_id: departementId
            })
        })
        .then(response => response.json())
        .then(data => {
            // Réinitialiser le sélecteur de postes
            posteFilter.innerHTML = '<option value="">Tous les postes</option>';
            
            // Ajouter les postes du département
            if (data.postes && data.postes.length > 0) {
                data.postes.forEach(poste => {
                    const option = document.createElement('option');
                    option.value = poste.id;
                    option.textContent = poste.nom;
                    posteFilter.appendChild(option);
                });
                
                // Activer le sélecteur de postes
                posteFilter.disabled = false;
            } else {
                posteFilter.disabled = true;
            }
            
            // Réinitialiser le sélecteur de grades
            if (gradeFilter) {
                gradeFilter.innerHTML = '<option value="">Tous les grades</option>';
                gradeFilter.disabled = true;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            posteFilter.innerHTML = '<option value="">Erreur de chargement</option>';
        });
    }
    
    // Fonction pour mettre à jour les grades en fonction du poste
    function updateGradesByPoste(posteId) {
        if (!gradeFilter) return;
        
        // Obtenir le token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            console.error('CSRF token not found');
            return;
        }
        
        fetch('/criteres-pointage/get-grades-poste', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                poste_id: posteId
            })
        })
        .then(response => response.json())
        .then(data => {
            // Réinitialiser le sélecteur de grades
            gradeFilter.innerHTML = '<option value="">Tous les grades</option>';
            
            // Ajouter les grades du poste
            if (data.grades && data.grades.length > 0) {
                data.grades.forEach(grade => {
                    const option = document.createElement('option');
                    option.value = grade.id;
                    option.textContent = grade.nom;
                    gradeFilter.appendChild(option);
                });
                
                // Activer le sélecteur de grades
                gradeFilter.disabled = false;
                
                // Mettre à jour le message d'information
                const gradeInfoDiv = gradeFilter.nextElementSibling;
                if (gradeInfoDiv && gradeInfoDiv.classList.contains('form-text')) {
                    gradeInfoDiv.innerHTML = `<i class="bi bi-info-circle"></i> ${data.grades.length} grade(s) disponible(s) pour ce poste`;
                    gradeInfoDiv.classList.remove('text-muted');
                    gradeInfoDiv.classList.add('text-primary');
                }
            } else {
                // Aucun grade disponible pour ce poste
                gradeFilter.disabled = true;
                
                // Mettre à jour le message d'information
                const gradeInfoDiv = gradeFilter.nextElementSibling;
                if (gradeInfoDiv && gradeInfoDiv.classList.contains('form-text')) {
                    gradeInfoDiv.innerHTML = `<i class="bi bi-exclamation-circle"></i> Aucun grade disponible pour ce poste`;
                    gradeInfoDiv.classList.remove('text-muted');
                    gradeInfoDiv.classList.add('text-warning');
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            gradeFilter.innerHTML = '<option value="">Erreur de chargement</option>';
            gradeFilter.disabled = true;
        });
    }
    
    // Filtrer les employés par département
    if (filterButton) {
        filterButton.addEventListener('click', function() {
            const departementId = departementFilter?.value;
            const periode = periodeFilter?.value;
            const posteId = posteFilter?.value;
            const gradeId = gradeFilter?.value;
            
            if (!departementId) {
                alert('Veuillez sélectionner un département');
                return;
            }
            
            // Obtenir le token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                console.error('CSRF token not found');
                return;
            }
            
            // Charger les employés du département
            fetch('/criteres-pointage/get-employes-departement', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    departement_id: departementId,
                    periode: periode,
                    poste_id: posteId,
                    grade_id: gradeId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher les statistiques du département
                    if (departementStats) departementStats.classList.remove('d-none');
                    if (departementNom) departementNom.textContent = data.departement;
                    
                    // Compter les employés configurés
                    const employesConfigured = data.employes.filter(employe => employe.a_critere).length;
                    if (employesCount) employesCount.textContent = data.employes.length;
                    if (employesConfiguredCount) employesConfiguredCount.textContent = employesConfigured;
                    
                    // Afficher la liste des employés
                    if (employesContainer) employesContainer.classList.remove('d-none');
                    renderEmployes(data.employes);
                    
                    // Mettre à jour le bouton de création de critère départemental
                    if (createDepartementCritere) {
                        createDepartementCritere.dataset.departementId = departementId;
                        createDepartementCritere.dataset.periode = periode;
                    }
                } else {
                    alert('Erreur lors du chargement des employés: ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors du chargement des employés');
            });
        });
    }
    
    // Filtrer les employés affichés (configurés/non configurés)
    if (showOnlyUnconfigured && employesTable) {
        showOnlyUnconfigured.addEventListener('change', function() {
            const rows = employesTable.querySelectorAll('tr');
            rows.forEach(row => {
                if (this.checked && row.dataset.hasCritere === 'true') {
                    row.classList.add('d-none');
                } else {
                    row.classList.remove('d-none');
                }
            });
        });
    }
    
    // Fonction pour afficher les employés dans le tableau
    function renderEmployes(employes) {
        if (!employesTable) return;
        
        employesTable.innerHTML = '';
        
        if (employes.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="5" class="text-center">Aucun employé trouvé dans ce département</td>`;
            employesTable.appendChild(row);
            return;
        }
        
        employes.forEach(employe => {
            const row = document.createElement('tr');
            row.dataset.hasCritere = employe.a_critere;
            
            if (showOnlyUnconfigured && showOnlyUnconfigured.checked && employe.a_critere) {
                row.classList.add('d-none');
            }
            
            row.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${employe.photo || '/assets/images/avatar-placeholder.png'}" alt="${employe.nom}" class="employe-avatar me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                        <div>
                            <div class="fw-bold">${employe.nom} ${employe.prenom}</div>
                        </div>
                    </div>
                </td>
                <td>${employe.poste || 'Non assigné'}</td>
                <td>${employe.grade || 'Non assigné'}</td>
                <td>
                    ${employe.a_critere ? 
                        '<span class="badge bg-success">Configuré</span>' : 
                        '<span class="badge bg-warning text-dark">Non configuré</span>'}
                </td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="#" class="configure-employe" data-employe-id="${employe.id}" data-bs-toggle="tooltip" title="${employe.a_critere ? 'Modifier le critère' : 'Configurer un critère'}">
                            <i class="bi ${employe.a_critere ? 'bi-pencil-square' : 'bi-gear-fill'} fs-5 ${employe.a_critere ? 'text-warning' : 'text-primary'}"></i>
                        </a>
                        ${employe.a_critere ? 
                            `<a href="#" class="view-critere" data-employe-id="${employe.id}" data-bs-toggle="tooltip" title="Voir le détail du critère">
                                <i class="bi bi-eye-fill fs-5 text-info"></i>
                            </a>` : ''
                        }
                        ${employe.a_critere ? 
                            `<a href="#" class="toggle-critere" data-employe-id="${employe.id}" data-bs-toggle="tooltip" title="Désactiver le critère">
                                <i class="bi bi-toggle-on fs-5 text-success"></i>
                            </a>` : ''
                        }
                    </div>
                </td>
            `;
            
            employesTable.appendChild(row);
        });
        
        // Initialiser les tooltips Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                placement: 'top',
                trigger: 'hover'
            });
        });
        
        // Ajouter des écouteurs d'événements pour les icônes de configuration
        const configButtons = document.querySelectorAll('.configure-employe');
        configButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const employeId = this.dataset.employeId;
                
                // Trouver l'employé correspondant dans les données
                const employe = employes.find(e => e.id == employeId);
                
                if (employe && employe.a_critere) {
                    // Si l'employé a déjà un critère, nous devons faire une requête pour obtenir l'ID du critère
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (!csrfToken) {
                        console.error('CSRF token not found');
                        return;
                    }
                    
                    fetch('/criteres-pointage/get-critere-employe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            employe_id: employeId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.critere_id) {
                            // Rediriger vers la page d'édition du critère en utilisant la nouvelle route personnalisée
                            const baseUrl = window.location.origin;
                            window.location.href = `${baseUrl}/criteres-pointage/edit/${data.critere_id}`;
                        } else {
                            // Ouvrir le modal de création si aucun critère n'est trouvé
                            openCreationModal(employeId);
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        // En cas d'erreur, ouvrir le modal de création
                        openCreationModal(employeId);
                    });
                } else {
                    // Si l'employé n'a pas de critère, ouvrir le modal de création
                    openCreationModal(employeId);
                }
            });
        });
        
        // Ajouter des écouteurs d'événements pour les icônes de visualisation
        const viewButtons = document.querySelectorAll('.view-critere');
        viewButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const employeId = this.dataset.employeId;
                
                // Récupérer l'ID du critère pour afficher les détails
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) {
                    console.error('CSRF token not found');
                    return;
                }
                
                fetch('/criteres-pointage/get-critere-employe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        employe_id: employeId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.critere_id) {
                        // Rediriger vers la page de détail du critère en utilisant un chemin absolu
                        const baseUrl = window.location.origin;
                        window.location.href = `${baseUrl}/criteres-pointage/${data.critere_id}`;
                    } else {
                        alert('Impossible de trouver le critère pour cet employé.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la récupération des détails du critère.');
                });
            });
        });
        
        // Ajouter des écouteurs d'événements pour les icônes de désactivation
        const toggleButtons = document.querySelectorAll('.toggle-critere');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const employeId = this.dataset.employeId;
                
                if (!confirm('Voulez-vous vraiment désactiver le critère pour cet employé ?')) {
                    return;
                }
                
                // Récupérer l'ID du critère pour le désactiver
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) {
                    console.error('CSRF token not found');
                    return;
                }
                
                fetch('/criteres-pointage/get-critere-employe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        employe_id: employeId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.critere_id) {
                        // Faire une requête pour désactiver le critère
                        // Note: Vous devrez créer cette route et cette méthode dans le contrôleur
                        alert('Fonctionnalité de désactivation à implémenter.');
                    } else {
                        alert('Impossible de trouver le critère pour cet employé.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la désactivation du critère.');
                });
            });
        });
        
        // Fonction pour ouvrir le modal de création de critère individuel
        function openCreationModal(employeId) {
            const individuellModal = document.getElementById('individuellModal');
            if (individuellModal) {
                const employeSelect = individuellModal.querySelector('#employe_id');
                if (employeSelect) {
                    employeSelect.value = employeId;
                }
                const modal = new bootstrap.Modal(individuellModal);
                modal.show();
            }
        }
    }
    
    // Initialiser la page si un département est déjà sélectionné
    if (departementFilter && departementFilter.value) {
        // Mettre à jour les postes d'abord
        updatePostesByDepartement(departementFilter.value);
        // Puis cliquer sur le bouton de filtrage
        if (filterButton) {
            setTimeout(() => {
                filterButton.click();
            }, 500);
        }
    }
    
    // ===== GESTION DES FORMULAIRES DE CRITÈRES DE POINTAGE =====
    // Formulaire de critère individuel
    const formIndividuel = document.getElementById('critere-individuel-form');
    if (formIndividuel) {
        formIndividuel.addEventListener('submit', function(e) {
            // Empêcher la soumission par défaut pour valider d'abord
            e.preventDefault();
            
            // Vérifier si le formulaire est valide
            if (formIndividuel.checkValidity()) {
                // Désactiver le bouton de soumission et afficher l'indicateur de chargement
                const submitButton = formIndividuel.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';
                }
                
                // Soumettre le formulaire
                formIndividuel.submit();
            } else {
                // Marquer le formulaire comme validé pour afficher les messages d'erreur
                formIndividuel.classList.add('was-validated');
            }
        });
    }
    
    // Formulaire de critère départemental
    const formDepartemental = document.getElementById('critere-departemental-form');
    if (formDepartemental) {
        formDepartemental.addEventListener('submit', function(e) {
            // Empêcher la soumission par défaut pour valider d'abord
            e.preventDefault();
            
            // Vérifier si des employés sont sélectionnés lorsque nécessaire
            const appliquerSelection = document.getElementById('appliquer_selection');
            if (appliquerSelection && appliquerSelection.checked) {
                const checkboxes = document.querySelectorAll('.employes-list input[type="checkbox"]:checked');
                if (checkboxes.length === 0) {
                    alert('Veuillez sélectionner au moins un employé');
                    return false;
                }
                
                // Ajouter les employés sélectionnés au formulaire
                const existingHiddenFields = formDepartemental.querySelectorAll('input[name="employes_selectionnes[]"]');
                existingHiddenFields.forEach(field => field.remove());
                
                checkboxes.forEach(checkbox => {
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = 'employes_selectionnes[]';
                    hiddenField.value = checkbox.value;
                    formDepartemental.appendChild(hiddenField);
                });
            }
            
            // Vérifier si le formulaire est valide
            if (formDepartemental.checkValidity()) {
                // Désactiver le bouton de soumission et afficher l'indicateur de chargement
                const submitButton = formDepartemental.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';
                }
                
                // Soumettre le formulaire
                formDepartemental.submit();
            } else {
                // Marquer le formulaire comme validé pour afficher les messages d'erreur
                formDepartemental.classList.add('was-validated');
            }
        });
    }
    
    // Gestion de la sélection des employés dans le modal départemental
    const appliquerTous = document.getElementById('appliquer_tous');
    const appliquerSelection = document.getElementById('appliquer_selection');
    const employesSelection = document.getElementById('employes-selection');
    
    if (appliquerSelection && employesSelection) {
        appliquerSelection.addEventListener('change', function() {
            if (this.checked) {
                employesSelection.classList.remove('d-none');
            } else {
                employesSelection.classList.add('d-none');
            }
        });
    }
    
    if (appliquerTous && employesSelection) {
        appliquerTous.addEventListener('change', function() {
            if (this.checked) {
                employesSelection.classList.add('d-none');
            }
        });
    }
    
    // Chargement des employés lors du changement de département dans le modal
    const departementSelect = document.getElementById('departement_id');
    if (departementSelect) {
        departementSelect.addEventListener('change', function() {
            const departementId = this.value;
            if (departementId) {
                // Afficher un indicateur de chargement
                const employesList = document.querySelector('.employes-list');
                if (employesList) {
                    employesList.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
                }
                
                // Charger les employés du département
                fetch('/criteres-pointage/get-employes-departement', {
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
                    if (data.success && employesList) {
                        // Générer la liste des employés
                        let html = '';
                        if (data.employes.length > 0) {
                            data.employes.forEach(employe => {
                                html += `
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="employe_${employe.id}" name="employes_selectionnes[]" value="${employe.id}">
                                    <label class="form-check-label" for="employe_${employe.id}">
                                        ${employe.nom} ${employe.prenom} - ${employe.poste ? employe.poste.nom : 'Sans poste'}
                                    </label>
                                </div>`;
                            });
                        } else {
                            html = '<div class="alert alert-info">Aucun employé trouvé dans ce département.</div>';
                        }
                        employesList.innerHTML = html;
                    } else {
                        employesList.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des employés.</div>';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    if (employesList) {
                        employesList.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des employés.</div>';
                    }
                });
            }
        });
    }
});
