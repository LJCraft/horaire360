<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test des graphiques - Horaire360</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- ApexCharts CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.3/dist/apexcharts.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Test des graphiques</h1>
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <strong>Diagnostic:</strong> Cette page permet de tester si JavaScript et les graphiques fonctionnent correctement.
                </div>
                <div class="btn-group mb-3">
                    <button id="testButton" class="btn btn-primary">Cliquez ici pour tester JavaScript</button>
                    <button id="loadTestChartsBtn" class="btn btn-success">Charger les graphiques de test (données fixes)</button>
                    <button id="loadApiChartsBtn" class="btn btn-info">Charger les graphiques depuis l'API</button>
                </div>
                <div id="testResult" class="mt-3"></div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        Graphique de répartition des employés
                    </div>
                    <div class="card-body">
                        <div id="testChart1" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        Graphique des présences
                    </div>
                    <div class="card-body">
                        <div id="testChart2" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        Graphique de répartition des présences
                    </div>
                    <div class="card-body">
                        <div id="testChart3" style="height: 250px;"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        Canvas HTML5 de test
                    </div>
                    <div class="card-body">
                        <canvas id="canvasTest" width="400" height="200" style="border:1px solid #d3d3d3;"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        Données disponibles (debug)
                    </div>
                    <div class="card-body">
                        <pre id="debugData" style="max-height: 300px; overflow: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- ApexCharts JS -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.3/dist/apexcharts.min.js"></script>
    
    <script>
        // Test de base JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM chargé avec succès');
            
            // Test du bouton simple
            document.getElementById('testButton').addEventListener('click', function() {
                document.getElementById('testResult').innerHTML = 
                    '<div class="alert alert-success">JavaScript fonctionne correctement! Clic à ' + 
                    new Date().toLocaleTimeString() + '</div>';
                
                // Dessiner sur le canvas
                drawCanvas();
            });
            
            // Bouton pour charger les graphiques de test avec des données fixes
            document.getElementById('loadTestChartsBtn').addEventListener('click', function() {
                document.getElementById('testResult').innerHTML = 
                    '<div class="alert alert-success">Chargement des graphiques de test...</div>';
                
                // Données de test fixes
                const testData = {
                    postes: {
                        labels: ['Développeur', 'Designer', 'Manager', 'Commercial'],
                        values: [12, 8, 5, 3]
                    },
                    presences: {
                        labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                        values: [25, 22, 28, 24, 27, 10, 15]
                    },
                    stats_presence: {
                        tauxPresence: 75,
                        tauxRetard: 15,
                        tauxAbsence: 10
                    }
                };
                
                // Afficher les données pour le debug
                document.getElementById('debugData').textContent = JSON.stringify(testData, null, 2);
                
                // Créer les graphiques
                createTestCharts(testData);
            });
            
            // Bouton pour charger les graphiques depuis l'API
            document.getElementById('loadApiChartsBtn').addEventListener('click', function() {
                document.getElementById('testResult').innerHTML = 
                    '<div class="alert alert-warning">Chargement des données depuis l\'API...</div>';
                
                // Charger les données depuis l'API
                fetch('/api/test-charts-data')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erreur réseau: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        document.getElementById('testResult').innerHTML = 
                            '<div class="alert alert-success">Données chargées avec succès depuis l\'API!</div>';
                        
                        // Afficher les données pour le debug
                        document.getElementById('debugData').textContent = JSON.stringify(data, null, 2);
                        
                        // Créer les graphiques
                        createTestCharts(data);
                    })
                    .catch(error => {
                        document.getElementById('testResult').innerHTML = 
                            '<div class="alert alert-danger">Erreur lors du chargement des données: ' + error.message + '</div>';
                    });
            });
            
            // Canvas de test initial
            drawCanvas();
        });
        
        // Dessiner sur le canvas
        function drawCanvas() {
            var canvas = document.getElementById('canvasTest');
            var ctx = canvas.getContext('2d');
            
            // Effacer le canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Dessiner un rectangle
            ctx.fillStyle = "#3498db";
            ctx.fillRect(10, 10, 100, 100);
            
            // Dessiner un cercle
            ctx.beginPath();
            ctx.arc(300, 100, 50, 0, 2 * Math.PI);
            ctx.fillStyle = "#e74c3c";
            ctx.fill();
            
            // Ajouter du texte
            ctx.font = "16px Arial";
            ctx.fillStyle = "black";
            ctx.fillText("Canvas HTML5 fonctionne! " + new Date().toLocaleTimeString(), 20, 150);
        }
        
        // Créer les graphiques de test
        function createTestCharts(data) {
            console.log('Création des graphiques avec les données:', data);
            
            // 1. Graphique de répartition des employés par poste (donut)
            try {
                // Nettoyer le conteneur existant
                document.getElementById('testChart1').innerHTML = '';
                
                // Créer le graphique
                var chart1 = new ApexCharts(document.getElementById('testChart1'), {
                    series: data.postes.values,
                    chart: {
                        type: 'donut',
                        height: 300
                    },
                    labels: data.postes.labels,
                    colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
                });
                
                chart1.render();
            } catch (error) {
                console.error('Erreur lors de la création du graphique 1:', error);
                document.getElementById('testChart1').innerHTML = 
                    '<div class="alert alert-danger">Erreur: ' + error.message + '</div>';
            }
            
            // 2. Graphique des présences (area)
            try {
                // Nettoyer le conteneur existant
                document.getElementById('testChart2').innerHTML = '';
                
                // Créer le graphique
                var chart2 = new ApexCharts(document.getElementById('testChart2'), {
                    series: [{
                        name: 'Présences',
                        data: data.presences.values
                    }],
                    chart: {
                        height: 300,
                        type: 'area'
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth'
                    },
                    xaxis: {
                        categories: data.presences.labels
                    }
                });
                
                chart2.render();
            } catch (error) {
                console.error('Erreur lors de la création du graphique 2:', error);
                document.getElementById('testChart2').innerHTML = 
                    '<div class="alert alert-danger">Erreur: ' + error.message + '</div>';
            }
            
            // 3. Graphique de répartition des présences (radialBar)
            try {
                // Nettoyer le conteneur existant
                document.getElementById('testChart3').innerHTML = '';
                
                // Créer le graphique
                var chart3 = new ApexCharts(document.getElementById('testChart3'), {
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
                            dataLabels: {
                                name: {
                                    show: true
                                },
                                value: {
                                    show: true,
                                    formatter: function(val) {
                                        return val + '%';
                                    }
                                }
                            }
                        }
                    },
                    colors: ['#1cc88a', '#f6c23e', '#e74a3b'],
                    labels: ['Présences', 'Retards', 'Absences']
                });
                
                chart3.render();
            } catch (error) {
                console.error('Erreur lors de la création du graphique 3:', error);
                document.getElementById('testChart3').innerHTML = 
                    '<div class="alert alert-danger">Erreur: ' + error.message + '</div>';
            }
        }
    </script>
</body>
</html> 