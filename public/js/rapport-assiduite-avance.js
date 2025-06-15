/**
 * Script avancé pour le rapport de ponctualité et d'assiduité
 * Optimisé pour gérer un grand nombre d'employés
 */
document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const ponctualiteChart = document.getElementById('ponctualiteChart');
    const assiduiteChart = document.getElementById('assiduiteChart');
    const departementSelect = document.getElementById('departement_id');
    const posteSelect = document.getElementById('poste_id');
    const performanceSelect = document.getElementById('performance');
    const tableBody = document.querySelector('.rapport-assiduite-table tbody');
    
    // Vérifier si les éléments nécessaires existent
    if (!ponctualiteChart || !assiduiteChart || !window.rapportData) {
        console.warn('Éléments nécessaires non trouvés pour les graphiques avancés');
        return;
    }
    
    // Initialisation
    initAdvancedCharts();
    
    /**
     * Initialise les graphiques avancés pour la ponctualité et l'assiduité
     * Optimisé pour gérer un grand nombre d'employés
     */
    function initAdvancedCharts() {
        // Récupérer les données
        const { employes, tauxPonctualite, tauxAssiduite, departements, postes } = window.rapportData;
        
        // Paramètres de visualisation
        const MAX_VISIBLE_ITEMS = 20; // Nombre maximum d'éléments à afficher en même temps
        const tooManyEmployees = employes.length > MAX_VISIBLE_ITEMS;
        
        console.log(`Initialisation des graphiques avancés pour ${employes.length} employés`);
        
        // Préparer les données pour les graphiques
        const employeesData = prepareDataForCharts(employes, tauxPonctualite, tauxAssiduite);
        
        // Initialiser les graphiques
        initPonctualiteChart(employeesData, tooManyEmployees);
        initAssiduiteChart(employeesData, tooManyEmployees);
        
        // Ajouter les contrôles pour filtrer les données
        setupAdvancedFilters(employeesData);
    }
    
    /**
     * Prépare les données pour les graphiques
     * @param {Array} employes - Liste des noms d'employés
     * @param {Array} tauxPonctualite - Liste des taux de ponctualité
     * @param {Array} tauxAssiduite - Liste des taux d'assiduité
     * @returns {Array} - Données structurées pour les graphiques
     */
    function prepareDataForCharts(employes, tauxPonctualite, tauxAssiduite) {
        // Créer un tableau d'objets avec les données de chaque employé
        const employeesData = employes.map((nom, index) => ({
            nom: nom,
            ponctualite: tauxPonctualite[index] || 0,
            assiduite: tauxAssiduite[index] || 0,
            performance: calculatePerformance(tauxPonctualite[index], tauxAssiduite[index])
        }));
        
        // Trier par taux d'assiduité décroissant
        employeesData.sort((a, b) => b.assiduite - a.assiduite);
        
        return employeesData;
    }
    
    /**
     * Calcule la performance globale d'un employé
     * @param {Number} ponctualite - Taux de ponctualité
     * @param {Number} assiduite - Taux d'assiduité
     * @returns {String} - Niveau de performance (excellent, bon, moyen, faible)
     */
    function calculatePerformance(ponctualite, assiduite) {
        const moyenne = (ponctualite + assiduite) / 2;
        
        if (moyenne >= 95) return 'excellent';
        if (moyenne >= 80) return 'bon';
        if (moyenne >= 60) return 'moyen';
        return 'faible';
    }
    
    /**
     * Initialise le graphique de ponctualité
     * @param {Array} employeesData - Données des employés
     * @param {Boolean} tooManyEmployees - Indique s'il y a trop d'employés pour un affichage standard
     */
    function initPonctualiteChart(employeesData, tooManyEmployees) {
        // Trier les données par taux de ponctualité
        const sortedData = [...employeesData].sort((a, b) => b.ponctualite - a.ponctualite);
        
        // Limiter le nombre d'employés affichés si nécessaire
        const displayData = tooManyEmployees ? sortedData.slice(0, MAX_VISIBLE_ITEMS) : sortedData;
        
        // Préparer les données pour le graphique
        const categories = displayData.map(e => e.nom);
        const series = [{
            name: 'Taux de ponctualité',
            data: displayData.map(e => e.ponctualite)
        }];
        
        // Configuration du graphique
        const options = {
            series: series,
            chart: {
                type: 'bar',
                height: tooManyEmployees ? 600 : 350,
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        const employeIndex = config.dataPointIndex;
                        const employeName = categories[employeIndex];
                        highlightEmployeeInTable(employeName);
                    }
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    dataLabels: {
                        position: 'top',
                    },
                    borderRadius: 4,
                    barHeight: '70%',
                    distributed: tooManyEmployees
                }
            },
            colors: tooManyEmployees ? 
                ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#5a5c69', '#6610f2', '#fd7e14', '#20c9a6', '#858796'] : 
                ['#4e73df'],
            dataLabels: {
                enabled: !tooManyEmployees,
                formatter: function (val) {
                    return val + "%";
                },
                offsetX: 20,
                style: {
                    fontSize: '12px',
                    fontWeight: 'bold',
                    colors: ['#000']
                }
            },
            xaxis: {
                categories: categories,
                labels: {
                    formatter: function (val) {
                        return val + "%";
                    },
                    style: {
                        fontSize: tooManyEmployees ? '10px' : '12px'
                    }
                },
                max: 100
            },
            yaxis: {
                labels: {
                    show: true,
                    style: {
                        fontWeight: 'medium'
                    },
                    formatter: function(val) {
                        if (typeof val === 'string' && val.length > 15) {
                            return val.substring(0, 15) + '...';
                        }
                        return val;
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + "%";
                    }
                },
                theme: 'light',
                marker: {
                    show: true
                }
            },
            grid: {
                borderColor: '#e0e0e0',
                strokeDashArray: 4,
                xaxis: {
                    lines: {
                        show: true
                    }
                }
            }
        };
        
        // Créer le graphique
        const ponctualiteChartObj = new ApexCharts(ponctualiteChart, options);
        ponctualiteChartObj.render();
        
        // Ajouter les contrôles pour changer le type de graphique
        addChartTypeControls(ponctualiteChart, ponctualiteChartObj, ['bar', 'line', 'radar']);
        
        // Ajouter un bouton pour afficher tous les employés
        if (tooManyEmployees) {
            addViewAllButton(ponctualiteChart, 'ponctualite', sortedData, ponctualiteChartObj);
        }
    }
    
    /**
     * Initialise le graphique d'assiduité
     * @param {Array} employeesData - Données des employés
     * @param {Boolean} tooManyEmployees - Indique s'il y a trop d'employés pour un affichage standard
     */
    function initAssiduiteChart(employeesData, tooManyEmployees) {
        // Trier les données par taux d'assiduité
        const sortedData = [...employeesData].sort((a, b) => b.assiduite - a.assiduite);
        
        // Limiter le nombre d'employés affichés si nécessaire
        const displayData = tooManyEmployees ? sortedData.slice(0, MAX_VISIBLE_ITEMS) : sortedData;
        
        // Déterminer le type de graphique optimal
        const chartType = tooManyEmployees ? 'treemap' : 'bar';
        
        // Configuration du graphique
        let options;
        
        if (chartType === 'treemap') {
            // Configuration pour treemap (optimisé pour de nombreux employés)
            options = {
                series: [{
                    data: sortedData.map(e => ({
                        x: e.nom,
                        y: e.assiduite
                    }))
                }],
                chart: {
                    height: 600,
                    type: 'treemap',
                    toolbar: {
                        show: true
                    }
                },
                title: {
                    text: 'Taux d\'assiduité par employé',
                    align: 'center'
                },
                colors: [
                    '#e74a3b',   // Rouge pour faible
                    '#f6c23e',   // Jaune pour moyen
                    '#4e73df',   // Bleu pour bon
                    '#1cc88a'    // Vert pour excellent
                ],
                plotOptions: {
                    treemap: {
                        distributed: true,
                        enableShades: true,
                        colorScale: {
                            ranges: [
                                { from: 0, to: 60, color: '#e74a3b' },   // Rouge pour faible
                                { from: 60, to: 80, color: '#f6c23e' },  // Jaune pour moyen
                                { from: 80, to: 95, color: '#4e73df' },  // Bleu pour bon
                                { from: 95, to: 100, color: '#1cc88a' }  // Vert pour excellent
                            ]
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val + "%";
                        }
                    }
                }
            };
        } else {
            // Configuration pour graphique en barres (moins d'employés)
            options = {
                series: [{
                    name: 'Taux d\'assiduité',
                    data: displayData.map(e => e.assiduite)
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: {
                        show: true
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    },
                    events: {
                        dataPointSelection: function(event, chartContext, config) {
                            const employeIndex = config.dataPointIndex;
                            const employeName = displayData[employeIndex].nom;
                            highlightEmployeeInTable(employeName);
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '70%',
                        borderRadius: 5,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val + '%';
                    },
                    offsetY: -20,
                    style: {
                        fontSize: '12px',
                        colors: ["#304758"]
                    }
                },
                xaxis: {
                    categories: displayData.map(e => e.nom),
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    max: 100,
                    title: {
                        text: 'Taux d\'assiduité (%)'
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: "vertical",
                        shadeIntensity: 0.25,
                        gradientToColors: undefined,
                        inverseColors: true,
                        opacityFrom: 1,
                        opacityTo: 0.85,
                        stops: [50, 100]
                    }
                },
                colors: ['#1cc88a']
            };
        }
        
        // Créer le graphique
        const assiduiteChartObj = new ApexCharts(assiduiteChart, options);
        assiduiteChartObj.render();
        
        // Ajouter les contrôles pour changer le type de graphique (sauf pour treemap)
        if (chartType !== 'treemap') {
            addChartTypeControls(assiduiteChart, assiduiteChartObj, ['bar', 'line', 'area']);
        }
        
        // Ajouter un bouton pour afficher tous les employés
        if (tooManyEmployees && chartType !== 'treemap') {
            addViewAllButton(assiduiteChart, 'assiduite', sortedData, assiduiteChartObj);
        }
    }
    
    /**
     * Ajoute des contrôles pour changer le type de graphique
     * @param {HTMLElement} chartElement - Élément DOM du graphique
     * @param {Object} chartObj - Instance ApexCharts
     * @param {Array} chartTypes - Types de graphiques disponibles
     */
    function addChartTypeControls(chartElement, chartObj, chartTypes) {
        // Créer les contrôles
        const chartControls = document.createElement('div');
        chartControls.className = 'chart-controls mt-2 mb-3 text-center';
        
        // Créer le groupe de boutons
        let buttonsHtml = '<div class="btn-group" role="group">';
        
        chartTypes.forEach((type, index) => {
            const isActive = index === 0 ? 'active' : '';
            buttonsHtml += `<button type="button" class="btn btn-sm btn-outline-primary ${isActive}" data-chart-type="${type}">${capitalizeFirstLetter(type)}</button>`;
        });
        
        buttonsHtml += '</div>';
        chartControls.innerHTML = buttonsHtml;
        
        // Ajouter les contrôles avant le graphique
        chartElement.parentNode.insertBefore(chartControls, chartElement);
        
        // Ajouter les événements pour changer le type de graphique
        chartControls.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', function() {
                const chartType = this.getAttribute('data-chart-type');
                chartControls.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                chartObj.updateOptions({
                    chart: {
                        type: chartType
                    }
                }, false, true);
            });
        });
    }
    
    /**
     * Ajoute un bouton pour afficher tous les employés dans un modal
     * @param {HTMLElement} chartElement - Élément DOM du graphique
     * @param {String} dataType - Type de données ('ponctualite' ou 'assiduite')
     * @param {Array} allData - Toutes les données des employés
     * @param {Object} chartObj - Instance ApexCharts
     */
    function addViewAllButton(chartElement, dataType, allData, chartObj) {
        // Créer le bouton
        const viewAllButton = document.createElement('button');
        viewAllButton.className = 'btn btn-sm btn-outline-primary mt-2';
        viewAllButton.innerHTML = '<i class="bi bi-list"></i> Voir tous les employés';
        
        // Ajouter le bouton après le graphique
        chartElement.parentNode.appendChild(viewAllButton);
        
        // Ajouter l'événement pour afficher tous les employés
        viewAllButton.addEventListener('click', function() {
            // Créer un modal pour afficher toutes les données
            const modalId = `modal-${dataType}-all`;
            
            // Supprimer un modal existant s'il y en a un
            const existingModal = document.getElementById(modalId);
            if (existingModal) {
                existingModal.remove();
            }
            
            // Créer le modal
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = modalId;
            modal.tabIndex = -1;
            modal.setAttribute('aria-labelledby', `${modalId}-label`);
            modal.setAttribute('aria-hidden', 'true');
            
            // Contenu du modal
            modal.innerHTML = `
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="${modalId}-label">Tous les taux de ${dataType === 'ponctualite' ? 'ponctualité' : 'assiduité'}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Employé</th>
                                            <th>Taux de ${dataType === 'ponctualite' ? 'ponctualité' : 'assiduité'} (%)</th>
                                            <th>Performance</th>
                                        </tr>
                                    </thead>
                                    <tbody id="${modalId}-body">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Ajouter le modal au document
            document.body.appendChild(modal);
            
            // Remplir le tableau avec les données
            const modalBody = document.getElementById(`${modalId}-body`);
            
            allData.forEach(employee => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${employee.nom}</td>
                    <td>${employee[dataType]}%</td>
                    <td>
                        <span class="badge bg-${getPerformanceColor(employee.performance)}">${capitalizeFirstLetter(employee.performance)}</span>
                    </td>
                `;
                
                // Ajouter un événement pour mettre en évidence l'employé dans le tableau principal
                tr.addEventListener('click', function() {
                    highlightEmployeeInTable(employee.nom);
                    
                    // Fermer le modal
                    const modalInstance = bootstrap.Modal.getInstance(document.getElementById(modalId));
                    modalInstance.hide();
                });
                
                modalBody.appendChild(tr);
            });
            
            // Afficher le modal
            const modalInstance = new bootstrap.Modal(document.getElementById(modalId));
            modalInstance.show();
        });
    }
    
    /**
     * Configure les filtres avancés pour les graphiques
     * @param {Array} employeesData - Données des employés
     */
    function setupAdvancedFilters(employeesData) {
        // Vérifier si les éléments de filtre existent
        if (!departementSelect || !posteSelect || !performanceSelect) return;
        
        // Ajouter un événement pour filtrer par performance
        if (performanceSelect) {
            performanceSelect.addEventListener('change', function() {
                const selectedPerformance = this.value;
                
                // Filtrer les lignes du tableau
                if (tableBody) {
                    const rows = tableBody.querySelectorAll('tr');
                    
                    rows.forEach(row => {
                        // Récupérer les taux de ponctualité et d'assiduité
                        const ponctualiteCell = row.querySelector('td:nth-child(10)');
                        const assiduiteCell = row.querySelector('td:nth-child(11)');
                        
                        if (ponctualiteCell && assiduiteCell) {
                            const ponctualite = parseFloat(ponctualiteCell.textContent);
                            const assiduite = parseFloat(assiduiteCell.textContent);
                            const moyenne = (ponctualite + assiduite) / 2;
                            let performance = '';
                            
                            if (moyenne >= 95) performance = 'excellent';
                            else if (moyenne >= 80) performance = 'bon';
                            else if (moyenne >= 60) performance = 'moyen';
                            else performance = 'faible';
                            
                            // Afficher ou masquer la ligne en fonction de la performance
                            if (!selectedPerformance || performance === selectedPerformance) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        }
                    });
                }
            });
        }
    }
    
    /**
     * Met en évidence un employé dans le tableau
     * @param {String} employeeName - Nom de l'employé à mettre en évidence
     */
    function highlightEmployeeInTable(employeeName) {
        if (!tableBody) return;
        
        // Récupérer toutes les lignes du tableau
        const rows = tableBody.querySelectorAll('tr');
        
        // Supprimer la mise en évidence précédente
        rows.forEach(row => {
            row.classList.remove('table-primary');
            row.classList.remove('highlight-row');
        });
        
        // Trouver la ligne correspondant à l'employé et la mettre en évidence
        rows.forEach(row => {
            const nameCell = row.querySelector('td:first-child');
            if (nameCell && nameCell.textContent.includes(employeeName)) {
                row.classList.add('table-primary');
                row.classList.add('highlight-row');
                
                // Faire défiler jusqu'à la ligne
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
    
    /**
     * Retourne la couleur Bootstrap correspondant à un niveau de performance
     * @param {String} performance - Niveau de performance
     * @returns {String} - Classe de couleur Bootstrap
     */
    function getPerformanceColor(performance) {
        switch (performance) {
            case 'excellent': return 'success';
            case 'bon': return 'primary';
            case 'moyen': return 'warning';
            case 'faible': return 'danger';
            default: return 'secondary';
        }
    }
    
    /**
     * Met en majuscule la première lettre d'une chaîne
     * @param {String} string - Chaîne à transformer
     * @returns {String} - Chaîne avec la première lettre en majuscule
     */
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
});

// Ajouter des styles CSS pour la mise en évidence des lignes
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        .highlight-row {
            transition: background-color 0.3s ease;
            animation: highlight-pulse 2s ease-in-out;
        }
        
        @keyframes highlight-pulse {
            0% { background-color: rgba(78, 115, 223, 0.1); }
            50% { background-color: rgba(78, 115, 223, 0.3); }
            100% { background-color: rgba(78, 115, 223, 0.1); }
        }
    `;
    document.head.appendChild(style);
});
