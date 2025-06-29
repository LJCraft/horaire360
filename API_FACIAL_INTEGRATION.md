# 📱 API-FACIAL - Intégration Application Mobile

## 🎯 Vue d'ensemble

Le driver **API-FACIAL** permet d'intégrer votre application mobile de reconnaissance faciale directement avec Horaire360 comme un véritable appareil biométrique distant, avec **synchronisation automatique des pointages**.

---

## 🚀 Configuration

### 1️⃣ Créer un appareil API-FACIAL

1. Aller dans **Configuration > Appareils Biométriques**
2. Cliquer sur **"Ajouter un Appareil"**
3. Sélectionner la marque **"API-FACIAL"**
4. Remplir les informations :

| Champ | Exemple | Description |
|-------|---------|-------------|
| **Nom** | `App Mobile - Entrée` | Nom descriptif de l'appareil |
| **URL API** | `https://votre-api.com/pointages?nameEntreprise=VotreEntreprise` | URL de votre API de pointages |
| **Token d'auth** | `Bearer abc123...` | Token d'authentification (optionnel) |
| **Format** | `JSON` | Format de réponse (JSON/XML) |

### 2️⃣ Format attendu de l'API

Votre API doit retourner les pointages au format :

```json
{
  "pointages": [
    {
      "employee_id": 123,
      "date": "2025-01-15",
      "time": "08:30:00",
      "type": "in",
      "confidence": 0.95,
      "photo_path": "/photos/capture_123.jpg",
      "location": "Entrée principale"
    }
  ]
}
```

**Champs supportés :**
- `employee_id` / `employe_id` / `user_id` : ID de l'employé
- `employee_name` / `email` : Nom ou email (si pas d'ID)
- `date` / `datetime` / `timestamp` : Date/heure du pointage
- `type` / `in_out` / `action` : Type (`in`/`out`, `1`/`0`, `entree`/`sortie`)
- `confidence` : Score de confiance (optionnel)
- `photo_path` : Chemin de la photo (optionnel)
- `location` : Géolocalisation (optionnel)

---

## 🔧 Utilisation

### Interface Web

1. **Test de connexion** : Bouton 📶 pour vérifier la connectivité dans la liste des appareils
2. **Synchronisation centralisée** : Dans "Rapports > Pointages Biométriques" → Section "Synchronisation des appareils"
3. **Statut en temps réel** : Badge de statut (Connecté/Déconnecté/Erreur)

### Ligne de commande

```bash
# Synchroniser tous les appareils API-FACIAL
php artisan sync:api-facial

# Synchroniser un appareil spécifique
php artisan sync:api-facial --device=1

# Forcer la synchronisation (même si récente)
php artisan sync:api-facial --force

# Afficher les statistiques
php artisan sync:api-facial --stats
```

### Automatisation CRON

```bash
# Synchronisation toutes les 5 minutes
*/5 * * * * cd /path/to/horaire360 && php artisan sync:api-facial >> /dev/null 2>&1

# Synchronisation plus espacée (toutes les 30 min)
*/30 * * * * cd /path/to/horaire360 && php artisan sync:api-facial >> /dev/null 2>&1
```

---

## 📊 Fonctionnalités

### ✅ Capacités supportées

| Fonctionnalité | Support | Description |
|----------------|---------|-------------|
| 🔗 **Test de connexion** | ✅ | Vérification HTTP/HTTPS |
| 🔄 **Sync automatique** | ✅ | Récupération programmée |
| 📱 **Sync manuelle** | ✅ | Déclenchement à la demande |
| ⏱️ **Temps réel** | ✅ | Synchronisation fréquente |
| 👤 **Reconnaissance faciale** | ✅ | Score de confiance |
| 📸 **Capture photo** | ✅ | Stockage des captures |
| 📍 **Géolocalisation** | ✅ | Position du pointage |
| 📈 **Score de confiance** | ✅ | Qualité de la reconnaissance |
| 🔐 **Authentification** | ✅ | Bearer token/API Key |
| 📄 **Formats multiples** | ✅ | JSON et XML |

### ❌ Limitations

- Pas de gestion des utilisateurs à distance
- Pas d'envoi de commandes vers l'app mobile
- Import par lots uniquement (pas de streaming)

---

## 🛠️ Développement

### Structure du Driver

```php
// Driver principal
app/Services/BiometricSync/Drivers/ApiFacialDriver.php

// Service de synchronisation
app/Services/ApiFacialSyncService.php

// Commande Artisan
app/Console/Commands/SyncApiFacialDevices.php

// Contrôleur web
app/Http/Controllers/BiometricDeviceController.php
```

### Logs de développement

Les logs sont disponibles dans `storage/logs/laravel.log` :

```log
[2025-01-15 10:30:00] API-FACIAL: Début synchronisation appareil {"device_id":1}
[2025-01-15 10:30:01] API-FACIAL: Récupération réussie {"records_count":5}
[2025-01-15 10:30:01] API-FACIAL: Synchronisation réussie {"success_count":4,"duplicates":1}
```

---

## 🔍 Debugging

### Vérifications de base

1. **URL accessible** : Testez votre API dans un navigateur
2. **Token valide** : Vérifiez l'authentification
3. **Format correct** : Validez la structure JSON
4. **Employés existants** : Vérifiez les IDs dans Horaire360

### Outils de diagnostic

```bash
# Afficher les statistiques détaillées
php artisan sync:api-facial --stats

# Forcer une synchronisation avec logs détaillés
php artisan sync:api-facial --device=1 --force

# Consulter les logs Laravel
tail -f storage/logs/laravel.log | grep "API-FACIAL"
```

### API de test

Créez une API de test simple :

```php
// routes/api.php
Route::get('/test-pointages', function() {
    return [
        'pointages' => [
            [
                'employee_id' => 1,
                'datetime' => now()->toISOString(),
                'type' => 'in',
                'confidence' => 0.95
            ]
        ]
    ];
});
```

---

## 📞 Support

### Messages d'erreur courants

| Erreur | Cause | Solution |
|--------|-------|----------|
| `Configuration API-FACIAL manquante` | URL vide | Vérifier l'URL API |
| `Erreur API: 401` | Token invalide | Vérifier le token d'auth |
| `Erreur API: 404` | URL incorrecte | Vérifier l'endpoint |
| `Employé non trouvé` | ID inexistant | Créer l'employé ou corriger l'ID |
| `Impossible de se connecter` | Réseau/DNS | Vérifier la connectivité |

### Ressources utiles

- 📋 **Logs système** : `storage/logs/laravel.log`
- 🔍 **Interface debug** : `/biometric-devices/debug-data`
- 📊 **Statistiques** : Commande `--stats`
- 🌐 **Test réseau** : Bouton de test de connexion

---

## 🔄 Exemple d'implémentation côté mobile

### Endpoint requis dans votre app

```javascript
// Express.js exemple
app.get('/pointages', (req, res) => {
    const { since, nameEntreprise } = req.query;
    
    // Filtrer par entreprise et date
    const pointages = getPointagesSince(since, nameEntreprise);
    
    res.json({
        pointages: pointages.map(p => ({
            employee_id: p.employee_id,
            datetime: p.created_at,
            type: p.is_entry ? 'in' : 'out',
            confidence: p.face_confidence,
            photo_path: p.photo_url,
            location: p.gps_location
        }))
    });
});
```

### Format de réponse recommandé

```json
{
  "status": "success",
  "count": 2,
  "pointages": [
    {
      "id": 123,
      "employee_id": 45,
      "employee_name": "Jean Dupont",
      "datetime": "2025-01-15T08:30:15Z",
      "type": "in",
      "confidence": 0.97,
      "photo_path": "/storage/captures/2025/01/15/capture_123.jpg",
      "location": "Lat: 48.8566, Lng: 2.3522",
      "device_info": {
        "model": "iPhone 12",
        "app_version": "1.2.3"
      }
    }
  ]
}
```

---

**🎉 Votre application mobile est maintenant intégrée comme un véritable appareil biométrique dans Horaire360 !** 