# API Profile - SoldeTrack

## 👤 Gestion du Profil Utilisateur

L'API Profile permet de gérer toutes les informations personnelles de l'utilisateur, ses préférences, et les fonctionnalités de sécurité de son compte.

## 🎯 Endpoints disponibles

### 1. Voir le profil

**Endpoint :** `GET /api/profile`

**Exemple de requête :**
```http
GET /api/profile
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "user": {
        "id": 1,
        "email": "john.doe@example.com",
        "nom": "Doe",
        "prenoms": "John",
        "photo": "https://api.soldetrack.com/uploads/profils/user_1_photo.jpg",
        "dateNaissance": "1990-05-15",
        "devise": {
            "id": 1,
            "code": "XOF",
            "nom": "Franc CFA"
        },
        "dateCreation": "2024-01-01 10:00:00"
    }
}
```

### 2. Modifier le profil

**Endpoint :** `PUT /api/profile`

**Corps de la requête :**
```json
{
    "nom": "Doe",
    "prenoms": "John",
    "photo": "user_1_photo.jpg",
    "dateNaissance": "1990-05-15",
    "devise_id": 1
}
```

**Champs modifiables :**
- `nom` : Nom de famille
- `prenoms` : Prénoms
- `photo` : Nom du fichier photo
- `dateNaissance` : Date de naissance (YYYY-MM-DD)
- `devise_id` : ID de la devise

**Exemple de requête :**
```http
PUT /api/profile
Authorization: Bearer {token}
Content-Type: application/json

{
    "nom": "Doe",
    "prenoms": "John",
    "dateNaissance": "1990-05-15",
    "devise_id": 1
}
```

**Réponse (200 OK) :**
```json
{
    "message": "Profil mis à jour avec succès",
    "user": {
        "id": 1,
        "email": "john.doe@example.com",
        "nom": "Doe",
        "prenoms": "John",
        "photo": "https://api.soldetrack.com/uploads/profils/user_1_photo.jpg",
        "dateNaissance": "1990-05-15",
        "devise": {
            "id": 1,
            "code": "XOF",
            "nom": "Franc CFA"
        },
        "dateCreation": "2024-01-01 10:00:00"
    }
}
```

### 3. Changer l'email

**Endpoint :** `POST /api/profile/change-email`

**Corps de la requête :**
```json
{
    "newEmail": "john.doe.new@example.com",
    "password": "motdepasse123"
}
```

**Champs requis :**
- `newEmail` : Nouvelle adresse email
- `password` : Mot de passe actuel

**Exemple de requête :**
```http
POST /api/profile/change-email
Authorization: Bearer {token}
Content-Type: application/json

{
    "newEmail": "john.doe.new@example.com",
    "password": "motdepasse123"
}
```

**Réponse (200 OK) :**
```json
{
    "message": "Email modifié avec succès",
    "user": {
        "id": 1,
        "email": "john.doe.new@example.com",
        "nom": "Doe",
        "prenoms": "John"
    }
}
```

### 4. Changer le mot de passe

**Endpoint :** `POST /api/profile/change-password`

**Corps de la requête :**
```json
{
    "currentPassword": "ancienmotdepasse",
    "newPassword": "nouveaumotdepasse123"
}
```

**Champs requis :**
- `currentPassword` : Mot de passe actuel
- `newPassword` : Nouveau mot de passe (minimum 6 caractères)

**Exemple de requête :**
```http
POST /api/profile/change-password
Authorization: Bearer {token}
Content-Type: application/json

{
    "currentPassword": "ancienmotdepasse",
    "newPassword": "nouveaumotdepasse123"
}
```

**Réponse (200 OK) :**
```json
{
    "message": "Mot de passe modifié avec succès"
}
```

### 5. Changer la devise

**Endpoint :** `POST /api/profile/change-devise`

**Corps de la requête :**
```json
{
    "devise_id": 2
}
```

**Champs requis :**
- `devise_id` : ID de la nouvelle devise

**Exemple de requête :**
```http
POST /api/profile/change-devise
Authorization: Bearer {token}
Content-Type: application/json

{
    "devise_id": 2
}
```

**Réponse (200 OK) :**
```json
{
    "message": "Devise modifiée avec succès",
    "devise": {
        "id": 2,
        "code": "EUR",
        "nom": "Euro"
    }
}
```

### 6. Supprimer le compte

**Endpoint :** `DELETE /api/profile/delete-account`

**Corps de la requête :**
```json
{
    "password": "motdepasse123",
    "confirmation": "SUPPRIMER MON COMPTE"
}
```

**Champs requis :**
- `password` : Mot de passe actuel
- `confirmation` : Texte exact "SUPPRIMER MON COMPTE"

**Exemple de requête :**
```http
DELETE /api/profile/delete-account
Authorization: Bearer {token}
Content-Type: application/json

{
    "password": "motdepasse123",
    "confirmation": "SUPPRIMER MON COMPTE"
}
```

**Réponse (200 OK) :**
```json
{
    "message": "Compte supprimé avec succès"
}
```

### 7. Statistiques du profil

**Endpoint :** `GET /api/profile/statistiques`

**Exemple de requête :**
```http
GET /api/profile/statistiques
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "statistiques": {
        "totalMouvements": 45,
        "totalComptes": 3,
        "totalProjets": 2,
        "totalCategories": 8,
        "totalContacts": 12,
        "soldeTotal": "125000.00",
        "soldeTotalFormatted": "125 000,00 XOF"
    },
    "derniereActivite": {
        "id": 45,
        "type": "depense",
        "typeLabel": "Dépense",
        "montant": "25000.00",
        "montantFormatted": "25 000,00 XOF",
        "date": "2024-01-20 14:30:00",
        "description": "Achat de nourriture"
    },
    "compte": {
        "dateCreation": "2024-01-01 10:00:00",
        "joursActif": 19
    }
}
```

### 8. Voir les préférences

**Endpoint :** `GET /api/profile/preferences`

**Exemple de requête :**
```http
GET /api/profile/preferences
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "preferences": {
        "devise": {
            "id": 1,
            "code": "XOF",
            "nom": "Franc CFA"
        },
        "notifications": {
            "dettesEnRetard": true,
            "nouveauxMouvements": true,
            "rapportsMensuels": false
        },
        "affichage": {
            "formatDate": "DD/MM/YYYY",
            "formatMontant": "avecSeparateurs",
            "theme": "clair"
        }
    }
}
```

### 9. Modifier les préférences

**Endpoint :** `PUT /api/profile/preferences`

**Corps de la requête :**
```json
{
    "devise_id": 2
}
```

**Champs modifiables :**
- `devise_id` : ID de la devise

**Exemple de requête :**
```http
PUT /api/profile/preferences
Authorization: Bearer {token}
Content-Type: application/json

{
    "devise_id": 2
}
```

**Réponse (200 OK) :**
```json
{
    "message": "Préférences mises à jour avec succès",
    "preferences": {
        "devise": {
            "id": 2,
            "code": "EUR",
            "nom": "Euro"
        }
    }
}
```

### 10. Exporter les données

**Endpoint :** `GET /api/profile/export-data`

**Exemple de requête :**
```http
GET /api/profile/export-data
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "message": "Données exportées avec succès",
    "data": {
        "utilisateur": {
            "id": 1,
            "email": "john.doe@example.com",
            "nom": "Doe",
            "prenoms": "John",
            "dateCreation": "2024-01-01 10:00:00",
            "devise": {
                "code": "XOF",
                "nom": "Franc CFA"
            }
        },
        "mouvements": [
            {
                "id": 1,
                "type": "depense",
                "montantTotal": "25000.00",
                "montantEffectif": "25000.00",
                "statut": "effectue",
                "date": "2024-01-20 14:30:00",
                "description": "Achat de nourriture",
                "categorie": "Alimentation",
                "projet": "Achat Maison",
                "contact": "Supermarché ABC"
            }
        ],
        "comptes": [
            {
                "id": 1,
                "nom": "Compte Principal",
                "type": "compte_principal",
                "soldeActuel": "125000.00",
                "soldeInitial": "100000.00",
                "dateCreation": "2024-01-01 10:00:00"
            }
        ],
        "projets": [
            {
                "id": 1,
                "nom": "Achat Maison",
                "description": "Projet d'achat d'une maison",
                "budgetPrevu": "5000000.00",
                "dateCreation": "2024-01-01 10:00:00"
            }
        ],
        "categories": [
            {
                "id": 1,
                "nom": "Alimentation",
                "type": "depense",
                "dateCreation": "2024-01-01 10:00:00"
            }
        ],
        "contacts": [
            {
                "id": 1,
                "nom": "Supermarché ABC",
                "telephone": "+225 20 30 40 50",
                "email": "contact@supermarché-abc.com",
                "source": "commercial",
                "dateCreation": "2024-01-01 10:00:00"
            }
        ],
        "export": {
            "date": "2024-01-20 16:00:00",
            "totalMouvements": 45,
            "totalComptes": 3,
            "totalProjets": 2,
            "totalCategories": 8,
            "totalContacts": 12
        }
    }
}
```

## ⚠️ Codes d'erreur

### Erreurs générales :
- **401** : Non authentifié
- **403** : Accès refusé
- **400** : Données manquantes ou invalides
- **404** : Ressource non trouvée
- **409** : Conflit (email déjà utilisé)
- **500** : Erreur serveur

### Erreurs de validation :
- **400** : Format d'email invalide
- **400** : Mot de passe trop court
- **400** : Mot de passe incorrect
- **400** : Confirmation invalide pour suppression

## 🎯 Fonctionnalités clés

1. **✅ Gestion du profil** : Modifier nom, prénoms, photo, date de naissance
2. **✅ Sécurité** : Changement d'email et mot de passe avec vérification
3. **✅ Préférences** : Gestion de la devise et des préférences d'affichage
4. **✅ Statistiques** : Vue d'ensemble de l'activité de l'utilisateur
5. **✅ Export de données** : Export complet de toutes les données
6. **✅ Suppression de compte** : Suppression sécurisée avec confirmation
7. **✅ Validation** : Validation complète des données et mots de passe
8. **✅ Sécurité** : Vérification du mot de passe pour les actions sensibles

## 📊 Cas d'usage typiques

1. **Modification du profil** : Utiliser `PUT /api/profile` pour mettre à jour les informations
2. **Changement de mot de passe** : Utiliser `POST /api/profile/change-password` pour la sécurité
3. **Changement de devise** : Utiliser `POST /api/profile/change-devise` pour l'internationalisation
4. **Statistiques personnelles** : Utiliser `GET /api/profile/statistiques` pour un aperçu
5. **Export de données** : Utiliser `GET /api/profile/export-data` pour la portabilité
6. **Suppression de compte** : Utiliser `DELETE /api/profile/delete-account` pour supprimer définitivement
