# Guide de Synchronisation Mobile - Application de Reconnaissance Faciale

## 🎯 Vue d'ensemble

Ce guide explique comment synchroniser les données de pointage de votre application mobile de reconnaissance faciale avec le système web Horaire360.

## 🔗 Configuration de l'Application Mobile

### URL de Base
```
Production: https://votre-domaine.com
Développement: http://localhost:8000
```

### Endpoints Disponibles

| Endpoint | Méthode | Description | Authentification |
|----------|---------|-------------|------------------|
| `/api/sync/test-public` | GET | Test de connectivité | Non |
| `/api/sync/mobile/test-public` | POST | Test de données (debug) | Non |
| `/api/sync/mobile/sync-firebase` | POST | Synchronisation réelle | Oui (Bearer Token) |

## 📱 Format des Données Attendues

### Structure JSON
```json
{
  "data": [
    {
      "employeeId": "1",
      "employeeName": "Nom Employé",
      "timestamp": "2025-01-21T08:05:30.000Z",
      "latitude": 3.8800975,
      "longitude": 11.4979914,
      "matchPercentage": 89.7822967518043,
      "type": "entry"
    }
  ],
  "source_app": "mobile_facial_recognition",
  "version": "1.0.0"
}
```

### Description des Champs

| Champ | Type | Obligatoire | Description |
|-------|------|-------------|-------------|
| `employeeId` | string/number | ✅ | ID de l'employé dans le système |
| `employeeName` | string | ❌ | Nom de l'employé (pour debug) |
| `timestamp` | string (ISO 8601) | ✅ | Date et heure du pointage |
| `latitude` | number | ❌ | Latitude GPS |
| `longitude` | number | ❌ | Longitude GPS |
| `matchPercentage` | number | ❌ | Score de confiance de la reconnaissance |
| `type` | string | ✅ | Type de pointage: "entry" ou "exit" |

## 🔐 Authentification

### 1. Obtenir un Token
L'application mobile doit d'abord s'authentifier pour obtenir un token Sanctum :

```javascript
const response = await fetch('/api/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password'
    })
});

const result = await response.json();
const token = result.token; // À stocker pour les requêtes suivantes
```

### 2. Utiliser le Token
```javascript
const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`
};
```

## 📤 Exemple de Synchronisation

### Code JavaScript/React Native
```javascript
async function synchroniserPointages(pointagesData) {
    try {
        const response = await fetch('/api/sync/mobile/sync-firebase', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                data: pointagesData,
                source_app: 'mobile_facial_recognition',
                version: '1.0.0'
            })
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            console.log(`✅ Synchronisation réussie: ${result.message}`);
            console.log(`📊 Statistiques:`, {
                reçus: result.received,
                insérés: result.inserted,
                mis_à_jour: result.updated,
                ignorés: result.ignored
            });
        } else {
            console.error(`❌ Erreur: ${result.message}`);
        }
        
        return result;
    } catch (error) {
        console.error('💥 Erreur de synchronisation:', error);
        throw error;
    }
}
```

### Code Flutter/Dart
```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

Future<Map<String, dynamic>> synchroniserPointages(List<Map<String, dynamic>> pointages) async {
  try {
    final response = await http.post(
      Uri.parse('${baseUrl}/api/sync/mobile/sync-firebase'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({
        'data': pointages,
        'source_app': 'mobile_facial_recognition',
        'version': '1.0.0',
      }),
    );

    final result = jsonDecode(response.body);
    
    if (response.statusCode == 200 && result['status'] == 'success') {
      print('✅ Synchronisation réussie: ${result['message']}');
      return result;
    } else {
      print('❌ Erreur: ${result['message']}');
      throw Exception(result['message']);
    }
  } catch (e) {
    print('💥 Erreur de synchronisation: $e');
    rethrow;
  }
}
```

## 🧪 Tests et Debug

### 1. Page de Debug Web
Accédez à `/biometric-devices/debug-mobile` dans l'interface web pour :
- Tester la connectivité
- Valider le format des données
- Déboguer les problèmes de mapping

### 2. Test de Connectivité
```javascript
// Test basique
fetch('/api/sync/test-public')
  .then(response => response.json())
  .then(data => console.log('Connectivité:', data));
```

### 3. Test de Données
```javascript
// Test du format des données sans authentification
fetch('/api/sync/mobile/test-public', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        data: [/* vos données de test */]
    })
})
.then(response => response.json())
.then(data => console.log('Test données:', data));
```

## 📊 Réponses de l'API

### Succès
```json
{
  "status": "success",
  "message": "3 pointage(s) inséré(s), 1 pointage(s) mis à jour",
  "received": 4,
  "inserted": 3,
  "updated": 1,
  "ignored": 0,
  "conflicts": 0,
  "errors": 0,
  "processing_time_ms": 245,
  "session_id": "mobile_sync_61f8b9c4e1234"
}
```

### Erreur
```json
{
  "status": "error",
  "message": "Erreur lors de la synchronisation mobile",
  "error": "Employé ID 999 introuvable",
  "errors": [
    {
      "line": 2,
      "reason": "Employé ID 999 introuvable",
      "data": { /* données originales */ }
    }
  ]
}
```

## 🔧 Gestion des Erreurs

### Erreurs Communes

| Code | Erreur | Solution |
|------|--------|----------|
| 401 | Non authentifié | Vérifier le token Bearer |
| 422 | Données invalides | Vérifier le format JSON |
| 404 | Employé introuvable | Vérifier l'ID employé |
| 500 | Erreur serveur | Contacter l'administrateur |

### Stratégie de Retry
```javascript
async function synchroniserAvecRetry(data, maxRetries = 3) {
    for (let i = 0; i < maxRetries; i++) {
        try {
            return await synchroniserPointages(data);
        } catch (error) {
            if (i === maxRetries - 1) throw error;
            
            // Attendre avant de réessayer
            await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
        }
    }
}
```

## 📋 Checklist d'Intégration

### Côté Application Mobile
- [ ] Configuration de l'URL de base
- [ ] Implémentation de l'authentification
- [ ] Format JSON correct des données
- [ ] Gestion des erreurs réseau
- [ ] Tests avec l'endpoint de debug
- [ ] Stratégie de retry en cas d'échec
- [ ] Stockage local en cas de perte de connexion

### Côté Serveur Web
- [ ] Configuration des CORS si nécessaire
- [ ] Vérification des logs de synchronisation
- [ ] Test des endpoints via la page de debug
- [ ] Configuration des tokens Sanctum
- [ ] Surveillance des performances

## 🚀 Mise en Production

### Variables d'Environnement
```env
# Dans votre application mobile
API_BASE_URL=https://votre-domaine.com
API_SYNC_ENDPOINT=/api/sync/mobile/sync-firebase
API_TIMEOUT=30000
```

### Monitoring
- Surveillez les logs Laravel dans `storage/logs/`
- Utilisez la page de debug pour tester régulièrement
- Configurez des alertes pour les échecs de synchronisation

## 📞 Support

### Logs Utiles
```bash
# Voir les logs de synchronisation
tail -f storage/logs/laravel.log | grep "SYNCHRONISATION"

# Voir les erreurs API
tail -f storage/logs/laravel.log | grep "ERROR"
```

### Contact
- **Développeur Backend :** [Votre nom]
- **Documentation :** `/biometric-devices/debug-mobile`
- **Logs en temps réel :** Consultez la page de debug

---

## 📝 Notes de Version

### v1.0.0
- Support initial Firebase/Google Cloud
- Mapping intelligent des champs
- Gestion des géolocalisations
- Scores de confiance de reconnaissance
- Page de debug intégrée 