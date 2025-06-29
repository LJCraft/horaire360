# Guide d'utilisation - Template de Pointage

## 🎯 Fonctionnalités implémentées

### 1. Template Excel de pointage
- **Tous les employés actifs** classés par département puis par ordre alphabétique
- **Fusion des cellules** pour les noms de départements (affichage vertical)
- **Colonnes à remplir** : Arrivée Réelle (AR) et Heure de Départ (HD)
- **Date du jour** en en-tête
- **Format professionnel** avec bordures et couleurs

### 2. Import intelligent
- **Calcul automatique** des retards et absences
- **Comparaison avec les plannings** existants
- **Gestion des critères de pointage** personnalisés (tolérances)
- **Calcul des heures supplémentaires**
- **Mise à jour** des pointages existants

### 3. Statuts détaillés
- **Présent** : Pointage normal
- **Retard** : Arrivée après l'heure prévue (+ tolérance)
- **Départ anticipé** : Départ avant l'heure prévue (+ tolérance)
- **Absent** : Aucun pointage malgré un planning
- **Retard + Départ anticipé** : Combinaison des deux
- **Présent (sans planning)** : Pointage sans planning défini

## 📋 Mode d'emploi

### Étape 1 : Télécharger le template
1. Aller dans **Présences** > Menu déroulant
2. Cliquer sur **"Télécharger le template de pointage"**
3. Le fichier Excel est généré avec la date du jour

### Étape 2 : Remplir le template
1. Ouvrir le fichier Excel téléchargé
2. Remplir les colonnes **AR** (Arrivée Réelle) et **HD** (Heure de Départ)
3. Format des heures : **HH:MM** (ex: 08:30, 17:15)
4. Laisser vides les lignes des employés absents
5. Enregistrer le fichier

### Étape 3 : Importer le template rempli
1. Aller dans **Présences** > Menu déroulant
2. Cliquer sur **"Importer un template de pointage"**
3. Sélectionner la **date** correspondante
4. Choisir le **fichier Excel** rempli
5. Cliquer sur **"Importer les pointages"**

## 🔧 Fonctionnalités avancées

### Critères de pointage
Le système utilise automatiquement les critères de pointage configurés :
- **Tolérance avant** : Retard accepté (défaut: 10 min)
- **Tolérance après** : Départ anticipé accepté (défaut: 10 min)
- **Seuil heures supplémentaires** : Calcul personnalisé

### Filtres disponibles
Dans la liste des pointages :
- **Employé** : Filtrer par employé spécifique
- **Date** : Filtrer par date
- **Statut** : Présent, Retard, Absent, Départ anticipé
- **Source** : Template pointage, Saisie manuelle, etc.

### Rapports
Les données importées apparaissent automatiquement dans :
- **Liste des pointages**
- **Rapports globaux**
- **Rapports par employé**
- **Rapports par département**

## 📊 Exemple de template généré

```
FEUILLE DE POINTAGE - SAMEDI 29 JUIN 2025

Département    | Matricule | Nom      | Prénom   | Poste        | AR    | HD
---------------|-----------|----------|----------|--------------|-------|-------
Administration | EMP001    | MARTIN   | Pierre   | Directeur    |       |
Commercial     | EMP002    | DURAND   | Sophie   | Commercial   | 08:30 | 17:15
Commercial     | EMP003    | BERNARD  | Jean     | Commercial   | 08:25 | 17:30
...
```

## ⚠️ Points importants

1. **Format des heures** : Respecter le format HH:MM
2. **Employés absents** : Laisser les cellules vides (sera marqué absent si planning existant)
3. **Date d'import** : Bien sélectionner la date correspondante
4. **Mise à jour** : Les pointages existants sont mis à jour, pas écrasés
5. **Plannings requis** : Pour le calcul des retards, un planning doit exister

## 🚀 Avantages

- **Gain de temps** : Saisie en lot de tous les employés
- **Précision** : Calcul automatique des statuts
- **Flexibilité** : Mise à jour possible des pointages
- **Traçabilité** : Source "Template pointage" identifiable
- **Conformité** : Respect des critères de pointage configurés 