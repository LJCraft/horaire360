/**
 * Dashboard charts initialization with AJAX
 * Horaire360 - Dashboard admin
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation des graphiques du tableau de bord...');
    
    // Fonction pour créer les graphiques
    function createCharts(data) {
        console.log('Données reçues pour les graphiques:', data);
        
        // 1. Graphique de répartition des employés par poste (donut)
        if (data.postes.labels.length > 0) {
            console.log('Création du graphique des postes...');
            try {
                var postesChart = new ApexCharts(document.querySelector("#employesChart"), {
                    series: data.postes.values,
                    chart: {
                        type: 'donut',
                        height: 350,
                        animations: {
                            enabled: true,
                            speed: 800
                        }
                    },
                    labels: data.postes.labels,
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '55%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total employés',
                                        formatter: function (w) {
                                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        }
                                    }
                                }
                            }
                        }
                    },
                    legend: {
                        position: 'bottom'
                    }
                });
                
                postesChart.render();
                console.log('Graphique des postes rendu avec succès');
                
                // Type change handlers
                document.querySelectorAll('.chart-type').forEach(function(item) {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        var type = this.getAttribute('data-type');
                        
                        if (type === 'donut' || type === 'pie') {
                            postesChart.updateOptions({
                                chart: { type: type },
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            size: type === 'donut' ? '55%' : '0%'
                                        }
                                    }
                                }
                            });
                        } else if (type === 'bar') {
                            postesChart.updateOptions({
                                chart: { type: 'bar' },
                                xaxis: { categories: data.postes.labels }
                            });
                        }
                    });
                });
                
                // Download handler
                var downloadEmployesChart = document.getElementById('downloadEmployesChart');
                if (downloadEmployesChart) {
                    downloadEmployesChart.addEventListener('click', function(e) {
                        e.preventDefault();
                        postesChart.dataURI().then(function(uri) {
                            var link = document.createElement('a');
                            link.href = uri.imgURI;
                            link.download = 'repartition-employes.png';
                            link.click();
                        });
                    });
                }
            } catch (error) {
                console.error('Erreur lors de la création du graphique des postes:', error);
                document.querySelector("#employesChart").innerHTML = 
                    '<div class="alert alert-danger">Erreur lors de la création du graphique: ' + error.message + '</div>';
            }
        } else {
            // Aucune donnée, affichage d'un message
            document.querySelector("#employesChart").innerHTML = 
                '<div class="text-center py-5 text-muted">' +
                '<i class="fas fa-users fa-3x mb-3"></i>' +
                '<p>Aucune donnée de répartition des employés disponible.</p>' +
                '</div>';
        }
        
        // 2. Graphique des présences des 30 derniers jours (area)
        if (data.presences.labels.length > 0 && data.presences.values.some(value => value > 0)) {
            console.log('Création du graphique des présences...');
            try {
                var presencesChart = new ApexCharts(document.querySelector("#presencesChart"), {
                    series: [{
                        name: 'Présences',
                        data: data.presences.values
                    }],
                    chart: {
                        height: 350,
                        type: 'area',
                        toolbar: {
                            show: true
                        },
                        zoom: {
                            enabled: true
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 3
                    },
                    colors: ['#4e73df'],
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.3,
                            stops: [0, 90, 100]
                        }
                    },
                    xaxis: {
                        categories: data.presences.labels,
                        labels: {
                            rotate: -45,
                            rotateAlways: data.presences.labels.length > 15
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'Nombre de présences'
                        },
                        min: 0
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val + " employés présents";
                            }
                        }
                    }
                });
                
                presencesChart.render();
                console.log('Graphique des présences rendu avec succès');
                
                // Type change handlers
                document.querySelectorAll('.presence-chart-type').forEach(function(item) {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        presencesChart.updateOptions({
                            chart: {
                                type: this.getAttribute('data-type')
                            }
                        });
                    });
                });
                
                // Download handler
                var downloadPresencesChart = document.getElementById('downloadPresencesChart');
                if (downloadPresencesChart) {
                    downloadPresencesChart.addEventListener('click', function(e) {
                        e.preventDefault();
                        presencesChart.dataURI().then(function(uri) {
                            var link = document.createElement('a');
                            link.href = uri.imgURI;
                            link.download = 'presences-30-jours.png';
                            link.click();
                        });
                    });
                }
            } catch (error) {
                console.error('Erreur lors de la création du graphique des présences:', error);
                document.querySelector("#presencesChart").innerHTML = 
                    '<div class="alert alert-danger">Erreur lors de la création du graphique: ' + error.message + '</div>';
            }
        } else {
            // Aucune donnée, affichage d'un message
            document.querySelector("#presencesChart").innerHTML = 
                '<div class="text-center py-5 text-muted">' +
                '<i class="fas fa-chart-line fa-3x mb-3"></i>' +
                '<p>Aucune donnée de présence disponible pour les 30 derniers jours.</p>' +
                '</div>';
        }
        
        // 3. Graphique de répartition des présences (radialBar)
        if (data.stats_presence.tauxPresence > 0 || data.stats_presence.tauxRetard > 0 || data.stats_presence.tauxAbsence > 0) {
            console.log('Création du graphique de répartition des présences...');
            try {
                var repartitionChart = new ApexCharts(document.querySelector("#presenceRepartitionChart"), {
                    series: [
                        data.stats_presence.tauxPresence, 
                        data.stats_presence.tauxRetard, 
                        data.stats_presence.tauxAbsence
                    ],
                    chart: {
                        height: 250,
                        type: 'radialBar'
                    },
                    plotOptions: {
                        radialBar: {
                            offsetY: 0,
                            startAngle: 0,
                            endAngle: 270,
                            hollow: {
                                margin: 5,
                                size: '30%',
                                background: 'transparent'
                            },
                            dataLabels: {
                                name: {
                                    show: true
                                },
                                value: {
                                    show: true,
                                    formatter: function(val) {
                                        return val + '%';
                                    }
                                },
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: function() {
                                        return '100%';
                                    }
                                }
                            }
                        }
                    },
                    colors: ['#1cc88a', '#f6c23e', '#e74a3b'],
                    labels: ['Présences', 'Retards', 'Absences'],
                    legend: {
                        show: true,
                        position: 'bottom'
                    }
                });
                
                repartitionChart.render();
                console.log('Graphique de répartition rendu avec succès');
            } catch (error) {
                console.error('Erreur lors de la création du graphique de répartition:', error);
                document.querySelector("#presenceRepartitionChart").innerHTML = 
                    '<div class="alert alert-danger">Erreur lors de la création du graphique: ' + error.message + '</div>';
            }
        } else {
            document.querySelector("#presenceRepartitionChart").innerHTML = 
                '<div class="text-center py-4 text-muted">' +
                '<i class="fas fa-chart-pie fa-3x mb-3"></i>' +
                '<p>Aucune donnée de répartition disponible.</p>' +
                '</div>';
        }
    }
    
    // Charger les données via AJAX
    function loadDashboardData() {
        // Afficher des indicateurs de chargement
        const loadingMessage = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Chargement des données...</p></div>';
        document.querySelector("#employesChart").innerHTML = loadingMessage;
        document.querySelector("#presencesChart").innerHTML = loadingMessage;
        document.querySelector("#presenceRepartitionChart").innerHTML = loadingMessage;
        
        // Faire la requête AJAX
        fetch('/api/dashboard-data')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                createCharts(data);
            })
            .catch(error => {
                console.error('Erreur lors du chargement des données:', error);
                const errorMessage = '<div class="alert alert-danger">Erreur lors du chargement des données: ' + error.message + '</div>';
                document.querySelector("#employesChart").innerHTML = errorMessage;
                document.querySelector("#presencesChart").innerHTML = errorMessage;
                document.querySelector("#presenceRepartitionChart").innerHTML = errorMessage;
            });
    }
    
    // Charger les données au chargement de la page
    loadDashboardData();
    
    // Ajouter un gestionnaire d'événement pour le bouton de rafraîchissement
    const refreshButton = document.getElementById('refreshDashboard');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            // Ajouter un effet de rotation à l'icône
            var icon = this.querySelector('i');
            if (icon) icon.classList.add('fa-spin');
            this.disabled = true;
            
            // Charger les données
            loadDashboardData();
            
            // Réactiver le bouton après un court délai
            setTimeout(function() {
                if (icon) icon.classList.remove('fa-spin');
                refreshButton.disabled = false;
            }, 1000);
        });
    }
    
    // Gestionnaire pour les boutons d'export
    var exportPDF = document.getElementById('exportPDF');
    var exportExcel = document.getElementById('exportExcel');
    
    if (exportPDF) {
        exportPDF.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Export PDF en cours de développement');
        });
    }
    
    if (exportExcel) {
        exportExcel.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Export Excel en cours de développement');
        });
    }
}); 