# API de Synchronisation Biométrique Mobile

## 🎯 Vue d'ensemble

Cette API permet la synchronisation intelligente des pointages biométriques entre l'application mobile de reconnaissance faciale et le système RH.

## 🔗 Endpoints

### Base URL
```
/api/sync/
```

### 1. Test de connectivité
```http
GET /api/sync/test
```

**Réponse:**
```json
{
    "status": "ok",
    "message": "API de synchronisation biométrique fonctionnelle",
    "timestamp": "2025-01-21T10:30:00.000Z",
    "version": "1.0.0"
}
```

### 2. Synchronisation des pointages
```http
POST /api/sync/biometric
Content-Type: application/json
Authorization: Bearer <token> | Session Web
```

**Corps de la requête:**
```json
{
    "data": [
        {
            "userId": 123,
            "timestamp": "2025-01-21T08:05:30.000Z",
            "type": "entry"
        }
    ],
    "source_app": "mobile_facial_recognition",
    "version": "1.2.0"
}
```

## 🧠 Mapping Intelligent

L'API reconnaît automatiquement différents formats de données :

### Format 1: Timestamp ISO
```json
{
    "userId": 123,
    "timestamp": "2025-01-21T08:05:30.000Z",
    "type": "entry"
}
```

### Format 2: Date/Heure séparées
```json
{
    "emp_id": "123",
    "date": "2025-01-21",
    "hour": "08:05:30",
    "status": 1,
    "terminal_id": 1
}
```

### Format 3: Champs variés
```json
{
    "employee_id": 123,
    "action": "checkin",
    "time": "08:05:30",
    "date": "2025-01-21"
}
```

## 📥 Champs mappés automatiquement

| Champ de sortie | Champs d'entrée acceptés |
|-----------------|-------------------------|
| `employe_id` | userId, user_id, emp_id, employee_id, employe_id, id |
| `date` | date, day, Date |
| `heure` | time, hour, heure, Time |
| `type_pointage` | type, status, action |
| `terminal_id` | terminal_id, source, device |

## 🔄 Types de pointage

| Valeur | Description | Textes acceptés |
|--------|-------------|-----------------|
| 1 | Entrée | entry, entrée, in, checkin, arrivee |
| 0 | Sortie | exit, sortie, out, checkout, depart |

## ✅ Validation des données

- **ID employé** : Doit exister dans la base de données
- **Date** : Format YYYY-MM-DD
- **Heure** : Format HH:MM:SS
- **Type pointage** : 0 ou 1 uniquement

## 🚫 Gestion des doublons

L'API détecte automatiquement les doublons avec une tolérance de ±5 minutes entre :
- Pointages manuels
- Imports .dat 
- Synchronisations mobiles

## 📊 Réponse de synchronisation

```json
{
    "status": "success",
    "session_id": "sync_61f8b9c4e1234",
    "received": 10,
    "inserted": 8,
    "updated": 1,
    "ignored": 1,
    "conflicts": [
        {
            "line": 5,
            "reason": "Pointage déjà existant - Source: import_dat",
            "doublon_id": 123
        }
    ],
    "errors": [],
    "processing_time_ms": 245.67,
    "message": "8 pointage(s) inséré(s), 1 pointage(s) mis à jour, 1 pointage(s) ignoré(s)"
}
```

## 🔐 Authentification

L'API accepte deux modes d'authentification :

### 1. Session Web (depuis l'interface)
```javascript
fetch('/api/sync/biometric', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify(data)
})
```

### 2. Token Sanctum (depuis l'app mobile)
```javascript
fetch('/api/sync/biometric', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify(data)
})
```

## 🛠️ Intégration dans l'interface

### Bouton de synchronisation
```html
<button onclick="synchroniserMobile()">
    <i class="bi bi-arrow-repeat"></i> Synchroniser
</button>
```

### Gestion des états
- **Chargement** : Spinner + "Synchronisation en cours..."
- **Succès** : Message de confirmation + Modal détaillé
- **Erreur** : Message d'erreur avec détails

## 📈 Fonctionnalités automatiques

1. **Calcul des retards** : Basé sur les plannings définis
2. **Calcul des départs anticipés** : Selon les critères RH
3. **Heures supplémentaires** : Calcul automatique si applicable
4. **Traçabilité complète** : Logs détaillés de toutes les opérations

## 🗂️ Métadonnées stockées

Chaque pointage synchronisé contient :
```json
{
    "terminal_id": "1",
    "type": "biometric_sync",
    "source": "synchronisation_mobile",
    "sync_session": "sync_61f8b9c4e1234",
    "source_app": "mobile_facial_recognition",
    "app_version": "1.2.0",
    "donnee_brute": { /* données originales */ }
}
```

## 🔧 Configuration

### Limites par défaut
- **Max pointages par appel** : 1000
- **Tolérance de doublon** : ±5 minutes
- **Timeout** : 30 secondes

### Variables d'environnement
```env
SYNC_MAX_RECORDS_PER_CALL=1000
SYNC_DUPLICATE_TOLERANCE_MINUTES=5
SYNC_API_RATE_LIMIT=60
```

## 🚨 Gestion d'erreurs

### Erreurs courantes
- **401** : Non authentifié
- **400** : Données invalides
- **404** : Employé introuvable
- **422** : Format de données incorrect
- **500** : Erreur serveur

### Format des erreurs
```json
{
    "status": "error",
    "session_id": "sync_61f8b9c4e1234",
    "message": "Description de l'erreur",
    "errors": [
        {
            "line": 3,
            "message": "Format de date invalide"
        }
    ]
}
```

## 📋 Exemple d'utilisation complète

```javascript
async function synchroniserPointages() {
    const data = {
        data: [
            {
                "userId": 1,
                "timestamp": "2025-01-21T08:05:30.000Z",
                "type": "entry"
            },
            {
                "emp_id": "2",
                "date": "2025-01-21", 
                "hour": "17:30:15",
                "status": 0
            }
        ],
        source_app: "mobile_app_v2",
        version: "2.1.0"
    };

    try {
        const response = await fetch('/api/sync/biometric', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            console.log(`Synchronisation réussie: ${result.message}`);
            console.log(`Session: ${result.session_id}`);
            console.log(`Temps: ${result.processing_time_ms}ms`);
        } else {
            console.error(`Erreur: ${result.message}`);
        }
    } catch (error) {
        console.error('Erreur de connexion:', error);
    }
}
```

## 🔍 Logs et monitoring

Tous les appels sont logués avec :
- Session ID unique
- Utilisateur authentifié
- IP d'origine
- Données reçues
- Résultats de traitement
- Temps d'exécution

## 🚀 Déploiement

1. **Vérifier les routes** : `php artisan route:list --path=sync`
2. **Tester la connectivité** : `GET /api/sync/test`
3. **Configurer l'authentification** : Tokens Sanctum pour l'app mobile
4. **Activer les logs** : Configuration Laravel appropriée

---

## 📞 Support

Pour toute question technique :
- Consulter les logs Laravel
- Vérifier l'authentification
- Valider le format des données
- Tester avec des données simples d'abord 