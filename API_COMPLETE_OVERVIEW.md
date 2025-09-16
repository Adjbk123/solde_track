# 📚 API SoldeTrack - Vue d'Ensemble Complète

## 🎯 **APIs Disponibles pour le Frontend**

Voici la liste complète de toutes les APIs disponibles dans SoldeTrack pour le développement de votre application mobile Flutter.

### 📋 **1. Authentification & Utilisateurs**
- **Fichier** : `API_RESPONSES.md`
- **Endpoints** :
  - `POST /api/auth/register` - Inscription avec connexion automatique
  - `POST /api/auth/login` - Connexion
  - `POST /api/auth/change-password` - Changement de mot de passe
  - `GET /api/auth/devises` - Liste des devises disponibles

### 📊 **2. Dashboard & Statistiques**
- **Fichier** : `API_DASHBOARD.md`
- **Endpoints** :
  - `GET /api/dashboard/solde` - Solde général
  - `GET /api/dashboard/projets-soldes` - Soldes des projets
  - `GET /api/dashboard/mouvements` - Historique des mouvements
  - `GET /api/dashboard/dettes-en-retard` - Dettes en retard
  - `GET /api/dashboard/comptes-soldes` - Soldes des comptes
  - `GET /api/dashboard/transferts-recents` - Transferts récents
  - `GET /api/dashboard/resume` - Résumé complet
  - `GET /api/dashboard/statistiques-periodes` - Statistiques par période

### 📈 **3. Statistiques Avancées**
- **Fichier** : `API_STATISTIQUES.md`
- **Endpoints** :
  - `GET /api/statistiques/resume` - Résumé des statistiques
  - `GET /api/statistiques/evolution-depenses` - Évolution des dépenses
  - `GET /api/statistiques/depenses-par-categorie` - Dépenses par catégorie
  - `GET /api/statistiques/entrees-par-categorie` - Entrées par catégorie
  - `GET /api/statistiques/comparaison-periodes` - Comparaison entre périodes
  - `GET /api/statistiques/tendances` - Tendances sur plusieurs périodes
  - `GET /api/statistiques/categories` - Liste des catégories utilisateur

### 🏗️ **4. Projets**
- **Fichier** : `API_PROJETS.md`
- **Endpoints** :
  - `GET /api/projets` - Liste des projets (avec recherche et pagination)
  - `GET /api/projets/{id}` - Détails d'un projet
  - `POST /api/projets` - Créer un projet
  - `PUT /api/projets/{id}` - Modifier un projet
  - `DELETE /api/projets/{id}` - Supprimer un projet
  - `GET /api/projets/{id}/statistiques` - Statistiques d'un projet
  - `GET /api/projets/{id}/mouvements` - Mouvements d'un projet

### 💰 **5. Mouvements Financiers**
- **Fichier** : `API_MOUVEMENTS.md`
- **Endpoints** :
  - `GET /api/mouvements` - Liste des mouvements (avec filtres)
  - `GET /api/mouvements/{id}` - Détails d'un mouvement
  - `POST /api/mouvements/depenses` - Créer une dépense
  - `POST /api/mouvements/entrees` - Créer une entrée
  - `POST /api/mouvements/dettes` - Créer une dette
  - `POST /api/mouvements/dons` - Créer un don
  - `PUT /api/mouvements/{id}` - Modifier un mouvement
  - `DELETE /api/mouvements/{id}` - Supprimer un mouvement
  - `GET /api/mouvements/statistiques` - Statistiques des mouvements
  - `GET /api/mouvements/recents` - Mouvements récents

### 💳 **6. Paiements**
- **Fichier** : `API_PAIEMENTS.md`
- **Endpoints** :
  - `GET /api/paiements` - Liste des paiements
  - `GET /api/paiements/{id}` - Détails d'un paiement
  - `POST /api/paiements` - Créer un paiement
  - `PUT /api/paiements/{id}` - Modifier un paiement
  - `DELETE /api/paiements/{id}` - Supprimer un paiement
  - `POST /api/paiements/{id}/marquer-paye` - Marquer comme payé
  - `POST /api/paiements/{id}/marquer-en-attente` - Marquer en attente
  - `GET /api/paiements/statistiques` - Statistiques des paiements
  - `GET /api/paiements/recents` - Paiements récents

### 🏦 **7. Dettes & Emprunts**
- **Fichier** : `API_DETTES_EMPRUNTS.md`
- **Endpoints** :
  - `POST /api/mouvements/dettes` - Créer une dette
  - `GET /api/mouvements?type=dette_a_payer` - Dettes à payer
  - `GET /api/mouvements?type=dette_a_recevoir` - Dettes à recevoir
  - `GET /api/dashboard/dettes/retard` - Dettes en retard
  - `PUT /api/mouvements/{id}` - Modifier une dette
  - `DELETE /api/mouvements/{id}` - Supprimer une dette
  - `GET /api/mouvements/statistiques?type=dette` - Statistiques des dettes
  - `POST /api/paiements` - Créer un paiement échelonné
  - `POST /api/paiements/{id}/marquer-paye` - Marquer un paiement comme effectué

### 👤 **8. Profil Utilisateur**
- **Fichier** : `API_PROFILE.md`
- **Endpoints** :
  - `GET /api/profile` - Informations du profil
  - `PUT /api/profile` - Modifier le profil
  - `POST /api/profile/change-email` - Changer l'email
  - `POST /api/profile/change-password` - Changer le mot de passe
  - `POST /api/profile/change-devise` - Changer la devise
  - `DELETE /api/profile` - Supprimer le compte
  - `GET /api/profile/statistiques` - Statistiques du profil
  - `GET /api/profile/preferences` - Préférences utilisateur
  - `PUT /api/profile/preferences` - Modifier les préférences
  - `GET /api/profile/export-data` - Exporter les données

### 🏦 **9. Comptes & Transferts**
- **Fichier** : `API_DOCUMENTATION.md` (sections Compte et Transfert)
- **Endpoints Comptes** :
  - `GET /api/comptes` - Liste des comptes
  - `GET /api/comptes/{id}` - Détails d'un compte
  - `POST /api/comptes` - Créer un compte
  - `PUT /api/comptes/{id}` - Modifier un compte
  - `DELETE /api/comptes/{id}` - Supprimer un compte
  - `GET /api/comptes/types` - Types de comptes disponibles
  - `GET /api/comptes/statistiques` - Statistiques des comptes

- **Endpoints Transferts** :
  - `GET /api/transferts` - Liste des transferts
  - `GET /api/transferts/{id}` - Détails d'un transfert
  - `POST /api/transferts` - Créer un transfert
  - `POST /api/transferts/{id}/annuler` - Annuler un transfert
  - `POST /api/transferts/simuler` - Simuler un transfert
  - `GET /api/transferts/recents` - Transferts récents
  - `GET /api/transferts/statistiques` - Statistiques des transferts

### 📂 **10. Catégories**
- **Fichier** : `API_DOCUMENTATION.md` (section Categorie)
- **Endpoints** :
  - `GET /api/categories` - Liste des catégories
  - `GET /api/categories/{id}` - Détails d'une catégorie
  - `POST /api/categories` - Créer une catégorie
  - `PUT /api/categories/{id}` - Modifier une catégorie
  - `DELETE /api/categories/{id}` - Supprimer une catégorie

### 👥 **11. Contacts**
- **Fichier** : `API_DOCUMENTATION.md` (section Contact)
- **Endpoints** :
  - `GET /api/contacts` - Liste des contacts
  - `GET /api/contacts/{id}` - Détails d'un contact
  - `POST /api/contacts` - Créer un contact
  - `PUT /api/contacts/{id}` - Modifier un contact
  - `DELETE /api/contacts/{id}` - Supprimer un contact

### 📸 **12. Photos de Profil**
- **Fichier** : `API_DOCUMENTATION.md` (section Photo)
- **Endpoints** :
  - `POST /api/photos/upload` - Uploader une photo
  - `DELETE /api/photos` - Supprimer la photo
  - `GET /api/photos/info` - Informations sur la photo

## 🔧 **Configuration Technique**

### **Base URL**
```
http://localhost:8000/api
```

### **Authentification**
Tous les endpoints (sauf `/api/auth/register`, `/api/auth/login`, `/api/auth/devises`) nécessitent :
```http
Authorization: Bearer {token}
```

### **Format des Réponses**
Toutes les réponses sont au format JSON avec :
- **Succès** : Code 200/201 avec les données
- **Erreur** : Code 400/401/404/500 avec message d'erreur

### **Pagination**
Les listes supportent la pagination :
```http
GET /api/endpoint?page=1&limit=20
```

### **Recherche**
Les listes supportent la recherche :
```http
GET /api/endpoint?search=terme
```

## 📱 **Structure Frontend Recommandée**

### **Services Flutter**
```
    lib/
    ├── services/
    │   ├── auth_service.dart
    │   ├── dashboard_service.dart
    │   ├── statistiques_service.dart
    │   ├── projet_service.dart
    │   ├── mouvement_service.dart
    │   ├── paiement_service.dart
    │   ├── dette_service.dart
    │   ├── profile_service.dart
    │   ├── compte_service.dart
    │   ├── transfert_service.dart
    │   ├── categorie_service.dart
    │   ├── contact_service.dart
    │   └── photo_service.dart
```

### **Modèles Dart**
```
    lib/
    ├── models/
    │   ├── user.dart
    │   ├── projet.dart
    │   ├── mouvement.dart
    │   ├── paiement.dart
    │   ├── dette.dart
    │   ├── compte.dart
    │   ├── transfert.dart
    │   ├── categorie.dart
    │   ├── contact.dart
    │   └── statistiques.dart
```

### **Pages Principales**
```
    lib/
    ├── pages/
    │   ├── auth/
    │   ├── dashboard/
    │   ├── statistiques/
    │   ├── projets/
    │   ├── mouvements/
    │   ├── paiements/
    │   ├── dettes/
    │   ├── comptes/
    │   ├── transferts/
    │   ├── categories/
    │   ├── contacts/
    │   └── profile/
```

## 🚀 **Prochaines Étapes**

1. **✅ Backend** : Complètement terminé et documenté
2. **🔄 Frontend** : À développer avec les APIs documentées
3. **📱 Tests** : Tester chaque endpoint avec Postman/Insomnia
4. **🔒 Sécurité** : Vérifier l'authentification JWT
5. **📊 Monitoring** : Ajouter des logs et métriques

**Toutes les APIs sont prêtes pour le développement frontend !** 🎉📱
