// Script de test pour la configuration départementale
console.log('========== SCRIPT TEST CHARGÉ ==========');

// Test de base
window.testBoutonFiltrer = function() {
    console.log('TEST FONCTION APPELÉE');
    alert('La fonction testBoutonFiltrer fonctionne !');
};

// Fonction principale de test
function initTestDepartement() {
    console.log('========== INIT TEST ==========');
    
    const boutonCharger = document.getElementById('charger_departement_btn');
    const selectDepartement = document.getElementById('departement_selector');
    
    console.log('Bouton filtrer trouvé:', !!boutonCharger);
    console.log('Select département trouvé:', !!selectDepartement);
    
    if (boutonCharger) {
        console.log('AJOUT EVENT LISTENER SUR BOUTON');
        boutonCharger.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('========== BOUTON CLIQUÉ ==========');
            
            const departementId = selectDepartement ? selectDepartement.value : null;
            console.log('Département sélectionné:', departementId);
            
            if (!departementId) {
                alert('Veuillez sélectionner un département');
                return;
            }
            
            // Test de requête simple
            console.log('Envoi requête AJAX...');
            
            fetch('/criteres-pointage/get-employes-departement', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    departement_id: departementId
                })
            })
            .then(response => {
                console.log('Réponse reçue:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Données reçues:', data);
                alert('Données reçues ! Voir console pour détails.');
                
                // Test simple d'affichage
                if (data.success) {
                    document.getElementById('message_instruction').classList.add('d-none');
                    document.getElementById('resultats_departement').classList.remove('d-none');
                    document.getElementById('nom_departement_actuel').textContent = departementId;
                }
            })
            .catch(error => {
                console.error('Erreur AJAX:', error);
                alert('Erreur: ' + error.message);
            });
        });
    } else {
        console.error('BOUTON NON TROUVÉ !');
        
        // Essayer après délai
        setTimeout(() => {
            const boutonRetard = document.getElementById('charger_departement_btn');
            if (boutonRetard) {
                console.log('Bouton trouvé après délai');
                boutonRetard.click();
            } else {
                console.error('Bouton toujours non trouvé après délai');
            }
        }, 2000);
    }
}

// Initialiser quand le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTestDepartement);
} else {
    initTestDepartement();
} 