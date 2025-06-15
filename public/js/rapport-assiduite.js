/**
 * Script pour le rapport de ponctualité et d'assiduité
 * Gère le sélecteur de période, le filtrage département-poste et les graphiques
 */

// Fonction d'initialisation globale pour permettre son appel explicite depuis la vue
function initRapportAssiduite() {
    console.log('Initialisation du rapport d\'assiduité depuis la fonction globale');
    initRapportAssiduiteInternal();
}

// Fonction interne d'initialisation
function initRapportAssiduiteInternal() {
    // Éléments DOM
    const periodeBtns = document.querySelectorAll('.periode-btn');
    const periodePrec = document.getElementById('periode-precedente');
    const periodeSuiv = document.getElementById('periode-suivante');
    const aujourdhuiBtn = document.getElementById('aujourdhui');
    const departementSelect = document.getElementById('departement_id');
    const posteSelect = document.getElementById('poste_id');
    const posteOptions = document.querySelectorAll('.poste-option');
    const ponctualiteChart = document.getElementById('ponctualiteChart');
    const assiduiteChart = document.getElementById('assiduiteChart');
    const container = document.querySelector('.container-fluid');
    
    // Variables globales
    let periode = '';
    let dateDebut = '';
    
    // Initialisation
    function init() {
        // Récupérer les valeurs initiales depuis les attributs data
        if (document.body.dataset.periode) {
            periode = document.body.dataset.periode;
        }
        
        if (document.body.dataset.dateDebut) {
            dateDebut = document.body.dataset.dateDebut;
        }
        
        // Initialiser le filtrage département-poste
        setupDepartementPosteFilter();
        
        // Initialiser les boutons de période
        setupPeriodeButtons();
        
        // Initialiser les graphiques si nécessaires
        if (ponctualiteChart && assiduiteChart && window.rapportData) {
            initCharts();
        }
    }
    
    // Appeler la fonction d'initialisation
    init();
    
    // Filtrage département-poste
    function setupDepartementPosteFilter() {
        if (!departementSelect || !posteSelect) return;
        
        // Fonction pour filtrer les postes en fonction du département sélectionné
        function filtrerPostes() {
            const departementId = departementSelect.value;
            let postesVisibles = 0;
            
            console.log('Filtrage des postes pour le département ID:', departementId);
            
            // Supprimer d'abord tout message existant
            const messageOption = posteSelect.querySelector('option[disabled]:not([value=""])');
            if (messageOption) {
                posteSelect.removeChild(messageOption);
            }
            
            // Filtrer les options de poste
            posteOptions.forEach(option => {
                if (option.value === "") return; // Ignorer l'option "Tous les postes"
                
                const posteDepartement = option.getAttribute('data-departement');
                
                console.log(`Poste: ${option.textContent}, Département du poste: ${posteDepartement}, Département sélectionné: ${departementId}`);
                
                // Vérifier si le département correspond (en tenant compte des types de données)
                // Le departementId peut être numérique alors que posteDepartement est une chaîne
                if (!departementId || String(posteDepartement) === String(departementId)) {
                    option.style.display = '';
                    postesVisibles++;
                    console.log(`Poste visible: ${option.textContent}`);
                } else {
                    option.style.display = 'none';
                    if (option.selected) {
                        posteSelect.value = ""; // Réinitialiser à "Tous les postes"
                    }
                }
            });
            
            console.log(`Total des postes visibles: ${postesVisibles}`);
            
            // Afficher un message si aucun poste n'est disponible
            if (postesVisibles === 0 && departementId) {
                const messageOption = document.createElement('option');
                messageOption.textContent = 'Aucun poste disponible pour ce département';
                messageOption.disabled = true;
                posteSelect.appendChild(messageOption);
            }
        }
        
        // Appliquer le filtre au chargement
        filtrerPostes();
        
        // Appliquer le filtre lors du changement de département
        departementSelect.addEventListener('change', filtrerPostes);
    }
    
    // Configuration des boutons de période
    function setupPeriodeButtons() {
        if (!periodeBtns.length) return;
        
        // Gestion des boutons de période
        periodeBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                periode = this.dataset.periode;
                
                // Vérifier si l'option de mise à jour AJAX est activée
                const useAjax = document.getElementById('use_ajax');
                if (useAjax && useAjax.checked) {
                    updateGraphsWithAjax();
                } else {
                    chargerRapport();
                }
            });
        });
        
        // Gestion des boutons de navigation
        if (periodePrec) {
            periodePrec.addEventListener('click', function(e) {
                e.preventDefault();
                naviguerPeriode('precedente');
            });
        }
        
        if (periodeSuiv) {
            periodeSuiv.addEventListener('click', function(e) {
                e.preventDefault();
                naviguerPeriode('suivante');
            });
        }
        
        if (aujourdhuiBtn) {
            aujourdhuiBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const now = new Date();
                dateDebut = now.toISOString().split('T')[0];
                
                // Vérifier si l'option de mise à jour AJAX est activée
                const useAjax = document.getElementById('use_ajax');
                if (useAjax && useAjax.checked) {
                    updateGraphsWithAjax();
                } else {
                    chargerRapport();
                }
            });
        }
    }
    
    // Navigation entre les périodes
    function naviguerPeriode(direction) {
        try {
            const date = new Date(dateDebut);
            
            if (isNaN(date.getTime())) {
                // Si la date n'est pas valide, utiliser la date actuelle
                console.warn('Date de début invalide, utilisation de la date actuelle');
                const now = new Date();
                date.setTime(now.getTime());
            }
            
            if (periode === 'jour') {
                date.setDate(date.getDate() + (direction === 'precedente' ? -1 : 1));
            } else if (periode === 'semaine') {
                date.setDate(date.getDate() + (direction === 'precedente' ? -7 : 7));
            } else if (periode === 'mois') {
                date.setMonth(date.getMonth() + (direction === 'precedente' ? -1 : 1));
            } else if (periode === 'annee') {
                date.setFullYear(date.getFullYear() + (direction === 'precedente' ? -1 : 1));
            }
            
            dateDebut = date.toISOString().split('T')[0];
            
            // Vérifier si l'option de mise à jour AJAX est activée
            const useAjax = document.getElementById('use_ajax');
            if (useAjax && useAjax.checked) {
                updateGraphsWithAjax();
            } else {
                chargerRapport();
            }
        } catch (error) {
            console.error('Erreur lors de la navigation entre périodes:', error);
        }
    }
    
    /**
     * Charge le rapport avec les paramètres sélectionnés
     */
    function chargerRapport() {
        try {
            // Récupérer les valeurs des filtres
            const departementId = departementSelect ? departementSelect.value : '';
            const posteId = posteSelect ? posteSelect.value : '';
            
            // Construire l'URL avec les paramètres
            const url = new URL(window.location.pathname, window.location.origin);
            url.searchParams.set('periode', periode);
            url.searchParams.set('date_debut', dateDebut);
            
            // Ajouter les filtres s'ils existent
            if (departementId) {
                url.searchParams.set('departement_id', departementId);
            }
            
            if (posteSelect && posteSelect.value) {
                url.searchParams.set('poste_id', posteSelect.value);
            }
            
            const performanceSelect = document.getElementById('performance');
            if (performanceSelect && performanceSelect.value) {
                url.searchParams.set('performance', performanceSelect.value);
            }
            
            const graphiquesCheckbox = document.getElementById('afficher_graphiques');
            if (graphiquesCheckbox && graphiquesCheckbox.checked) {
                url.searchParams.set('afficher_graphiques', '1');
            }
            
            // Rediriger vers la nouvelle URL
            console.log('Redirection vers:', url.toString());
            window.location.href = url.toString();
        } catch (error) {
            console.error('Erreur lors du chargement du rapport:', error);
            alert('Une erreur est survenue lors du chargement du rapport. Veuillez réessayer.');
        }
    }
    
    // Initialisation
    init();
    
    /**
     * Met à jour les graphiques via AJAX en fonction de la période sélectionnée
     * sans recharger toute la page
     */
    function updateGraphsWithAjax() {
        try {
            // Afficher un indicateur de chargement
            const loadingMessage = '<div class="d-flex justify-content-center align-items-center" style="height: 300px"><div class="spinner-border text-primary" role="status"></div><span class="ms-2">Chargement des données...</span></div>';
            
            if (document.querySelector("#ponctualiteChart")) {
                document.querySelector("#ponctualiteChart").innerHTML = loadingMessage;
            }
            
            if (document.querySelector("#assiduiteChart")) {
                document.querySelector("#assiduiteChart").innerHTML = loadingMessage;
            }
            
            // Récupérer les valeurs des filtres
            const departementId = departementSelect ? departementSelect.value : '';
            const posteId = posteSelect ? posteSelect.value : '';
            
            // Construire l'URL pour la requête AJAX
            const url = new URL('/api/rapport-data', window.location.origin);
            url.searchParams.set('periode', periode);
            url.searchParams.set('date_debut', dateDebut);
            
            if (departementId) {
                url.searchParams.set('departement_id', departementId);
            }
            
            if (posteId) {
                url.searchParams.set('poste_id', posteId);
            }
            
            // Faire la requête AJAX
            fetch(url.toString())
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Données reçues:', data);
                    
                    // Mettre à jour les données du rapport
                    window.rapportData = {
                        employes: data.employes,
                        tauxPonctualite: data.tauxPonctualite,
                        tauxAssiduite: data.tauxAssiduite,
                        periode: periode,
                        dateDebut: dateDebut,
                        periodeLabel: data.periodeLabel
                    };
                    
                    // Mettre à jour les éléments de l'interface
                    const periodeLabel = document.querySelector('.periode-label');
                    if (periodeLabel) {
                        periodeLabel.textContent = data.periodeLabel;
                    }
                    
                    // Réinitialiser et recréer les graphiques
                    if (document.querySelector("#ponctualiteChart")) {
                        document.querySelector("#ponctualiteChart").innerHTML = '';
                    }
                    
                    if (document.querySelector("#assiduiteChart")) {
                        document.querySelector("#assiduiteChart").innerHTML = '';
                    }
                    
                    // Initialiser les graphiques avec les nouvelles données
                    initCharts();
                    
                    // Mettre à jour le tableau des données si nécessaire
                    updateDataTable(data.tableData);
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des données:', error);
                    const errorMessage = '<div class="alert alert-danger">Erreur lors du chargement des données: ' + error.message + '</div>';
                    
                    if (document.querySelector("#ponctualiteChart")) {
                        document.querySelector("#ponctualiteChart").innerHTML = errorMessage;
                    }
                    
                    if (document.querySelector("#assiduiteChart")) {
                        document.querySelector("#assiduiteChart").innerHTML = errorMessage;
                    }
                });
        } catch (error) {
            console.error('Erreur lors de la mise à jour des graphiques:', error);
        }
    }
    
    /**
     * Met à jour le tableau de données avec les nouvelles valeurs
     */
    function updateDataTable(tableData) {
        if (!tableData) return;
        
        const tableBody = document.querySelector('.table-rapport tbody');
        if (!tableBody) return;
        
        // Vider le tableau existant
        tableBody.innerHTML = '';
        
        // Ajouter les nouvelles lignes
        tableData.forEach(row => {
            const tr = document.createElement('tr');
            
            // Ajouter les cellules
            Object.values(row).forEach(value => {
                const td = document.createElement('td');
                td.innerHTML = value;
                tr.appendChild(td);
            });
            
            tableBody.appendChild(tr);
        });
    }
    
    /**
     * Initialise les graphiques de ponctualité et d'assiduité
     * Taux de ponctualité (%) = (fréquence réalisée / fréquence prévue) × 100
     * Taux d'assiduité (%) = (heures faites / heures prévues) × 100
     */
    function initCharts() {
        if (!window.rapportData) {
            console.error('Données du rapport non disponibles');
            return;
        }
        
        const { employes, tauxPonctualite, tauxAssiduite } = window.rapportData;
        
        // Optimisation pour un grand nombre d'employés
        const MAX_VISIBLE_ITEMS = 20; // Nombre maximum d'éléments à afficher en même temps
        let employesOptimized = [...employes];
        let tauxPonctualiteOptimized = [...tauxPonctualite];
        let tauxAssiduiteOptimized = [...tauxAssiduite];
        
        // Si nous avons trop d'employés, préparons les données pour une visualisation optimisée
        const tooManyEmployees = employes.length > MAX_VISIBLE_ITEMS;
        
        if (tooManyEmployees) {
            console.log(`Optimisation des graphiques pour ${employes.length} employés`);
            
            // Trier les données par taux d'assiduité pour le graphique d'assiduité
            const assiduiteData = employes.map((nom, index) => ({ 
                nom, 
                taux: tauxAssiduite[index] 
            }));
            assiduiteData.sort((a, b) => b.taux - a.taux);
            
            // Trier les données par taux de ponctualité pour le graphique de ponctualité
            const ponctualiteData = employes.map((nom, index) => ({ 
                nom, 
                taux: tauxPonctualite[index] 
            }));
            ponctualiteData.sort((a, b) => b.taux - a.taux);
            
            // Créer des tableaux optimisés pour chaque graphique
            employesOptimized = assiduiteData.map(d => d.nom);
            tauxAssiduiteOptimized = assiduiteData.map(d => d.taux);
            
            const employesPonctualite = ponctualiteData.map(d => d.nom);
            const tauxPonctualiteOptimized = ponctualiteData.map(d => d.taux);
        }
        
        // Configuration du graphique de ponctualité
        const ponctualiteOptions = {
            series: [{
                name: 'Taux de ponctualité',
                data: tauxPonctualiteOptimized || tauxPonctualite
            }],
            chart: {
                type: 'bar',
                height: tooManyEmployees ? 600 : 300, // Augmenter la hauteur si beaucoup d'employés
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
                        // Permettre l'interaction avec les points de données
                        const employeIndex = config.dataPointIndex;
                        const employeName = tooManyEmployees ? employesPonctualite[employeIndex] : employes[employeIndex];
                        console.log('Employé sélectionné:', employeName);
                        
                        // Mettre en évidence l'employé dans le tableau
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
                    barHeight: tooManyEmployees ? '90%' : '70%',
                    distributed: tooManyEmployees, // Distribuer les couleurs si nombreux employés
                    rangeBarOverlap: false
                }
            },
            colors: tooManyEmployees ? 
                ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#5a5c69', '#6610f2', '#fd7e14', '#20c9a6', '#858796'] : 
                ['#4e73df'],
            dataLabels: {
                enabled: !tooManyEmployees, // Désactiver les étiquettes si trop d'employés
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
                categories: tooManyEmployees ? employesPonctualite : employes,
                labels: {
                    formatter: function (val) {
                        return val + "%";
                    },
                    rotate: 0,
                    trim: true,
                    style: {
                        fontSize: tooManyEmployees ? '10px' : '12px'
                    }
                },
                max: 100,
                tickAmount: 10
            },
            yaxis: {
                labels: {
                    show: true,
                    style: {
                        fontWeight: 'medium'
                    },
                    // Tronquer les noms trop longs
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
                },
                fixed: {
                    enabled: true,
                    position: 'topRight'
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
            },
            // Ajouter un scrollbar pour les grands ensembles de données
            scrollbar: {
                enabled: tooManyEmployees,
                offsetY: -10
            }
        };
        
        // Configuration du graphique d'assiduité
        const assiduiteOptions = {
            series: [{
                name: 'Taux d\'assiduité',
                data: tauxAssiduiteOptimized
            }],
            chart: {
                type: tooManyEmployees ? 'treemap' : 'bar', // Utiliser un treemap pour de nombreux employés
                height: tooManyEmployees ? 600 : 300,
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
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        // Permettre l'interaction avec les points de données
                        if (!tooManyEmployees) {
                            const employeIndex = config.dataPointIndex;
                            const employeName = employes[employeIndex];
                            console.log('Employé sélectionné:', employeName);
                            
                            // Mettre en évidence l'employé dans le tableau
                            highlightEmployeeInTable(employeName);
                        }
                    }
                }
            },
            plotOptions: tooManyEmployees ? {
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
            } : {
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
                enabled: !tooManyEmployees, // Désactiver pour treemap
                formatter: function(val) {
                    return val + '%';
                },
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ["#304758"]
                }
            },
            // Configuration spécifique pour treemap
            ...(tooManyEmployees ? {
                legend: {
                    show: false
                },
                tooltip: {
                    x: {
                        show: true,
                        formatter: function(val, opts) {
                            const index = opts.dataPointIndex;
                            return employesOptimized[index];
                        }
                    },
                    y: {
                        formatter: function(val) {
                            return val + '%';
                        }
                    }
                }
            } : {}),
            // Configuration standard pour les barres
            ...(!tooManyEmployees ? {
                xaxis: {
                    categories: employes,
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
            
            // Faire défiler jusqu'à la ligne
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
}

// Configuration du graphique de ponctualité
const ponctualiteOptions = {
    series: [{
        name: 'Taux de ponctualité',
        data: tauxPonctualiteOptimized
    }],
    chart: {
        type: tooManyEmployees ? 'treemap' : 'bar', // Utiliser un treemap pour de nombreux employés
        height: tooManyEmployees ? 600 : 300,
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
                        mode: currentTheme === 'dark' ? 'dark' : 'light',
                        palette: 'palette1'
                    },
                    grid: {
                        borderColor: currentTheme === 'dark' ? '#2a3c61' : '#e0e0e0'
                    },
                    xaxis: {
                        labels: {
                            style: {
                                colors: currentTheme === 'dark' ? '#a3b8d9' : '#718096'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: currentTheme === 'dark' ? '#a3b8d9' : '#718096'
                            }
                        }
                    }
                });
                
                assiduiteChartObj.updateOptions({
                    theme: {
                        mode: currentTheme === 'dark' ? 'dark' : 'light',
                        palette: 'palette1'
                    },
                    grid: {
                        borderColor: currentTheme === 'dark' ? '#2a3c61' : '#e0e0e0'
                    },
                    xaxis: {
                        labels: {
                            style: {
                                colors: currentTheme === 'dark' ? '#a3b8d9' : '#718096'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: currentTheme === 'dark' ? '#a3b8d9' : '#718096'
                            }
                        }
                    }
                });
            }
            
            // Observer les changements de thème
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'data-bs-theme') {
                        updateChartTheme();
                    }
                });
            });
            
            // Observer les changements d'attribut sur l'élément HTML
            observer.observe(document.documentElement, { attributes: true });
            
            // Appliquer le thème initial aux graphiques
            updateChartTheme();
        } catch (error) {
            console.error('Erreur lors de l\'initialisation des graphiques:', error);
            if (document.querySelector("#ponctualiteChart")) {
                document.querySelector("#ponctualiteChart").innerHTML = 
                    '<div class="alert alert-danger">Erreur lors de l\'initialisation du graphique: ' + error.message + '</div>';
            }
            if (document.querySelector("#assiduiteChart")) {
                document.querySelector("#assiduiteChart").innerHTML = 
                    '<div class="alert alert-danger">Erreur lors de l\'initialisation du graphique: ' + error.message + '</div>';
            }
        }
    }
});
