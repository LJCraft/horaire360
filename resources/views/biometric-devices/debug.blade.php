@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Debug Synchronisation Mobile</h1>
                    <p class="text-muted mb-0">Outils de test et débogage pour l'application mobile de reconnaissance faciale</p>
                </div>
                <a href="{{ route('biometric-devices.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <!-- Section de test de connectivité -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Test de Connectivité API</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-primary w-100 mb-3" onclick="testConnectivity()">
                                <i class="bi bi-wifi"></i> Tester la Connectivité
                            </button>
                            <div id="connectivity-result" class="alert d-none"></div>
                        </div>
                        <div class="col-md-6">
                            <h6>Endpoints disponibles :</h6>
                            <ul class="list-unstyled">
                                <li><code>GET /api/sync/test-public</code> - Test basique</li>
                                <li><code>POST /api/sync/mobile/test-public</code> - Test données mobile</li>
                                <li><code>POST /api/sync/mobile/sync-firebase</code> - Sync Firebase (auth required)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section de test de données -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Test de Données Mobile</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Format de données Firebase :</h6>
                            <textarea id="test-data" class="form-control mb-3" rows="15" placeholder="Collez ici les données JSON de votre application mobile">{
  "data": [
    {
      "employeeId": "1",
      "employeeName": "Tchoutezo",
      "timestamp": "2025-01-21T08:05:30.000Z",
      "latitude": 3.8800975,
      "longitude": 11.4979914,
      "matchPercentage": 89.7822967518043,
      "type": "entry"
    }
  ],
  "source_app": "mobile_facial_recognition",
  "version": "1.0.0"
}</textarea>
                            <button class="btn btn-success w-100" onclick="testMobileData()">
                                <i class="bi bi-play"></i> Tester ces Données
                            </button>
                        </div>
                        <div class="col-md-6">
                            <h6>Résultat du test :</h6>
                            <div id="mobile-test-result" class="border rounded p-3 bg-light" style="min-height: 400px;">
                                <em class="text-muted">Cliquez sur "Tester ces Données" pour voir le résultat...</em>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section de synchronisation réelle -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">Synchronisation Réelle</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Cette section effectue une vraie synchronisation qui va insérer des données dans la base.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Instructions pour votre application mobile :</h6>
                            <ol>
                                <li>Configurez l'URL de base : <code>{{ url('/') }}</code></li>
                                <li>Endpoint de synchronisation : <code>/api/sync/mobile/sync-firebase</code></li>
                                <li>Méthode : <code>POST</code></li>
                                <li>Headers requis :
                                    <ul>
                                        <li><code>Content-Type: application/json</code></li>
                                        <li><code>Authorization: Bearer YOUR_TOKEN</code></li>
                                    </ul>
                                </li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6>Format JSON attendu :</h6>
                            <pre class="bg-dark text-light p-3 rounded"><code>{
  "data": [
    {
      "employeeId": "1",
      "employeeName": "Nom Employé",
      "timestamp": "2025-01-21T08:05:30.000Z",
      "latitude": 3.8800975,
      "longitude": 11.4979914,
      "matchPercentage": 89.78,
      "type": "entry"
    }
  ]
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section de logs -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Logs de Debug</h6>
                </div>
                <div class="card-body">
                    <div id="debug-logs" class="border rounded p-3 bg-dark text-light" style="height: 300px; overflow-y: auto; font-family: monospace;">
                        <div class="text-muted">Les logs apparaîtront ici...</div>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary mt-2" onclick="clearLogs()">
                        <i class="bi bi-trash"></i> Effacer les logs
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Configuration CSRF
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Fonction pour ajouter des logs
function addLog(message, type = 'info') {
    const logsContainer = document.getElementById('debug-logs');
    const timestamp = new Date().toLocaleTimeString();
    const colorClass = {
        'info': 'text-info',
        'success': 'text-success', 
        'error': 'text-danger',
        'warning': 'text-warning'
    }[type] || 'text-light';
    
    const logEntry = document.createElement('div');
    logEntry.className = colorClass;
    logEntry.innerHTML = `[${timestamp}] ${message}`;
    
    logsContainer.appendChild(logEntry);
    logsContainer.scrollTop = logsContainer.scrollHeight;
}

// Tester la connectivité de base
async function testConnectivity() {
    addLog('🔗 Test de connectivité en cours...', 'info');
    
    try {
        const response = await fetch('/api/sync/test-public', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        const resultDiv = document.getElementById('connectivity-result');
        resultDiv.classList.remove('d-none', 'alert-success', 'alert-danger');
        
        if (response.ok) {
            resultDiv.classList.add('alert-success');
            resultDiv.innerHTML = `
                <i class="bi bi-check-circle me-2"></i>
                <strong>Connectivité OK !</strong><br>
                <small>Message: ${result.message}</small><br>
                <small>Version: ${result.version}</small>
            `;
            addLog('✅ Connectivité réussie', 'success');
        } else {
            resultDiv.classList.add('alert-danger');
            resultDiv.innerHTML = `
                <i class="bi bi-x-circle me-2"></i>
                <strong>Erreur de connectivité</strong><br>
                <small>${result.message || 'Erreur inconnue'}</small>
            `;
            addLog('❌ Erreur de connectivité', 'error');
        }
    } catch (error) {
        addLog(`💥 Erreur réseau: ${error.message}`, 'error');
        
        const resultDiv = document.getElementById('connectivity-result');
        resultDiv.classList.remove('d-none', 'alert-success');
        resultDiv.classList.add('alert-danger');
        resultDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Erreur réseau</strong><br>
            <small>${error.message}</small>
        `;
    }
}

// Tester les données mobile
async function testMobileData() {
    const testDataTextarea = document.getElementById('test-data');
    const resultDiv = document.getElementById('mobile-test-result');
    
    addLog('📱 Test des données mobile en cours...', 'info');
    
    try {
        // Valider le JSON
        const jsonData = JSON.parse(testDataTextarea.value);
        addLog('✅ JSON valide', 'success');
        
        // Envoyer au endpoint de test
        const response = await fetch('/api/sync/mobile/test-public', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(jsonData)
        });
        
        const result = await response.json();
        
        // Afficher le résultat formaté
        resultDiv.innerHTML = `
            <div class="mb-3">
                <span class="badge ${response.ok ? 'bg-success' : 'bg-danger'}">
                    ${response.ok ? 'SUCCESS' : 'ERROR'}
                </span>
                <span class="badge bg-secondary">${response.status}</span>
            </div>
            
            <h6>Informations de debug :</h6>
            <ul class="mb-3">
                <li><strong>Méthode :</strong> ${result.debug_info?.method || 'N/A'}</li>
                <li><strong>Content-Type :</strong> ${result.debug_info?.content_type || 'N/A'}</li>
                <li><strong>Taille :</strong> ${result.debug_info?.content_length || 0} bytes</li>
                <li><strong>Authentifié :</strong> ${result.debug_info?.has_auth ? 'Oui' : 'Non'}</li>
            </ul>
            
            ${result.analysis ? `
                <h6>Analyse des données :</h6>
                <ul class="mb-3">
                    <li><strong>Type :</strong> ${result.analysis.data_type}</li>
                    <li><strong>Nombre d'éléments :</strong> ${result.analysis.count}</li>
                    <li><strong>Clés :</strong> ${Array.isArray(result.analysis.keys) ? result.analysis.keys.join(', ') : 'N/A'}</li>
                </ul>
            ` : ''}
            
            ${result.mapping_test ? `
                <h6>Test de mapping :</h6>
                <div class="alert ${result.mapping_test.mapping_success ? 'alert-success' : 'alert-warning'} alert-sm">
                    ${result.mapping_test.mapping_success ? 
                        '✅ Mapping réussi - Les données peuvent être traitées' : 
                        '⚠️ Problème de mapping - Vérifiez le format des données'
                    }
                </div>
                
                <details class="mb-3">
                    <summary class="btn btn-sm btn-outline-secondary">Voir détails du mapping</summary>
                    <pre class="mt-2 p-2 bg-light border rounded" style="font-size: 0.8em; max-height: 200px; overflow-y: auto;">${JSON.stringify(result.mapping_test, null, 2)}</pre>
                </details>
            ` : ''}
            
            <details>
                <summary class="btn btn-sm btn-outline-primary">Voir réponse complète</summary>
                <pre class="mt-2 p-2 bg-dark text-light rounded" style="font-size: 0.8em; max-height: 300px; overflow-y: auto;">${JSON.stringify(result, null, 2)}</pre>
            </details>
        `;
        
        if (response.ok) {
            addLog('✅ Test des données réussi', 'success');
            if (result.mapping_test?.mapping_success) {
                addLog('✅ Mapping des données réussi', 'success');
            } else {
                addLog('⚠️ Problème de mapping détecté', 'warning');
            }
        } else {
            addLog(`❌ Erreur lors du test: ${result.message}`, 'error');
        }
        
    } catch (error) {
        addLog(`💥 Erreur: ${error.message}`, 'error');
        
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Erreur :</strong> ${error.message}
            </div>
        `;
    }
}

// Effacer les logs
function clearLogs() {
    document.getElementById('debug-logs').innerHTML = '<div class="text-muted">Les logs apparaîtront ici...</div>';
}

// Test initial au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    addLog('🚀 Page de debug chargée', 'info');
    addLog('💡 Utilisez les boutons ci-dessus pour tester la synchronisation', 'info');
});
</script>
@endpush 