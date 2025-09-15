# SoldeTrack API - Documentation

## 🔐 Authentification JWT

L'API utilise l'authentification JWT pour sécuriser tous les endpoints.

### Inscription (JSON) - Connexion automatique
```http
POST /api/auth/register
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "motdepasse123",
    "nom": "Dupont",
    "prenoms": "Jean",
    "devise_id": 1,
    "dateNaissance": "1990-01-01" // optionnel
}
```

**Réponse (201 Created) :**
```json
{
    "message": "Inscription réussie et connexion automatique",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "nom": "Dupont",
        "prenoms": "Jean",
        "photo": null,
        "dateNaissance": "1990-01-01",
        "dateCreation": "2024-01-15 10:30:00",
        "devise": {
            "id": 1,
            "code": "XOF",
            "nom": "Franc CFA Ouest-Africain"
        }
    },
    "setup": {
        "categories_created": true,
        "comptes_created": true,
        "message": "Catégories et comptes par défaut créés automatiquement"
    }
}
```

### Inscription avec photo (FormData)
```http
POST /api/auth/register-with-photo
Content-Type: multipart/form-data

FormData:
- email: "user@example.com"
- password: "motdepasse123"
- nom: "Dupont"
- prenoms: "Jean"
- devise_id: 1
- dateNaissance: "1990-01-01" (optionnel)
- photo: [fichier image] (optionnel)
```

### Récupérer les devises disponibles
```http
GET /api/auth/devises
```

**Réponse :**
```json
{
    "devises": [
        {
            "id": 1,
            "code": "XOF",
            "nom": "Franc CFA"
        },
        {
            "id": 2,
            "code": "EUR",
            "nom": "Euro"
        },
        {
            "id": 3,
            "code": "USD",
            "nom": "Dollar Américain"
        }
    ]
}
```

### Récupérer les devises populaires
```http
GET /api/auth/devises/popular
```

### Connexion
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "motdepasse123"
}
```

**Réponse (200 OK) :**
```json
{
    "message": "Connexion réussie",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "nom": "Dupont",
        "prenoms": "Jean",
        "photo": "http://localhost:8000/uploads/profils/1_photo-abc123.jpg",
        "dateNaissance": "1990-01-01",
        "dateCreation": "2024-01-15 10:30:00",
        "devise": {
            "id": 1,
            "code": "XOF",
            "nom": "Franc CFA Ouest-Africain"
        }
    }
}
```

### Changer le mot de passe
```http
POST /api/auth/change-password
Authorization: Bearer {token}
Content-Type: application/json

{
    "currentPassword": "ancien_motdepasse",
    "newPassword": "nouveau_motdepasse123"
}
```

### Mot de passe oublié
```http
POST /api/auth/forgot-password
Content-Type: application/json

{
    "email": "user@example.com"
}
```

### Réinitialiser le mot de passe
```http
POST /api/auth/reset-password
Content-Type: application/json

{
    "email": "user@example.com",
    "token": "token_de_reinitialisation",
    "newPassword": "nouveau_motdepasse123"
}
```

### Utilisation du token
```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

## 👤 Gestion du Profil

### Voir le profil
```http
GET /api/profile
Authorization: Bearer {token}
```

### Modifier le profil
```http
PUT /api/profile
Authorization: Bearer {token}
Content-Type: application/json

{
    "nom": "Nouveau nom",
    "prenoms": "Nouveaux prénoms",
    "photo": "chemin/vers/nouvelle/photo.jpg",
    "dateNaissance": "1990-01-01",
    "devise_id": 1
}
```

### Changer l'email
```http
POST /api/profile/change-email
Authorization: Bearer {token}
Content-Type: application/json

{
    "newEmail": "nouveau@email.com",
    "password": "motdepasse_actuel"
}
```

### Changer la devise
```http
POST /api/profile/change-devise
Authorization: Bearer {token}
Content-Type: application/json

{
    "devise_id": 2
}
```

## 📸 Gestion des Photos de Profil

### Uploader une photo
```http
POST /api/photo/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

FormData:
- photo: [fichier image]
```

**Formats acceptés :** JPEG, PNG, GIF, WebP  
**Taille max :** 5MB  
**Redimensionnement automatique :** 300x300px

### Supprimer la photo
```http
DELETE /api/photo/delete
Authorization: Bearer {token}
```

### Informations sur la photo
```http
GET /api/photo/info
Authorization: Bearer {token}
```

**Réponse :**
```json
{
    "hasPhoto": true,
    "photo": {
        "filename": "1_photo-abc123.jpg",
        "url": "/uploads/profils/1_photo-abc123.jpg",
        "size": 45678,
        "lastModified": 1694567890
    }
}
```

### Supprimer le compte
```http
DELETE /api/profile/delete-account
Authorization: Bearer {token}
Content-Type: application/json

{
    "password": "motdepasse_actuel",
    "confirmation": "SUPPRIMER MON COMPTE"
}
```

## 📊 Endpoints API

### 1. Gestion des Catégories

#### Lister les catégories
```http
GET /api/categories?type=depense
Authorization: Bearer {token}
```

#### Créer une catégorie
```http
POST /api/categories
Authorization: Bearer {token}
Content-Type: application/json

{
    "nom": "Alimentation",
    "type": "depense"
}
```

**Types disponibles :** `depense`, `entree`, `dette`, `don`

### 2. Gestion des Devises

#### Lister les devises
```http
GET /api/devises
Authorization: Bearer {token}
```

#### Voir une devise
```http
GET /api/devises/{code}
Authorization: Bearer {token}
```

### 3. Gestion des Comptes

#### Lister les comptes
```http
GET /api/comptes
Authorization: Bearer {token}
```

#### Créer un compte
```http
POST /api/comptes
Authorization: Bearer {token}
Content-Type: application/json

{
    "nom": "Compte Épargne",
    "description": "Compte pour mes économies",
    "type": "epargne",
    "solde_initial": "10000.00",
    "numero": "1234567890",
    "institution": "UBA"
}
```

#### Voir un compte
```http
GET /api/comptes/{id}
Authorization: Bearer {token}
```

#### Modifier un compte
```http
PUT /api/comptes/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "nom": "Nouveau nom",
    "description": "Nouvelle description"
}
```

#### Supprimer un compte
```http
DELETE /api/comptes/{id}
Authorization: Bearer {token}
```

#### Désactiver/Réactiver un compte
```http
POST /api/comptes/{id}/desactiver
POST /api/comptes/{id}/reactiver
Authorization: Bearer {token}
```

#### Types de comptes disponibles
```http
GET /api/comptes/types
Authorization: Bearer {token}
```

**Types disponibles :**
- `compte_principal` : Compte Principal
- `epargne` : Épargne
- `momo` : Mobile Money
- `carte` : Carte Bancaire
- `especes` : Espèces
- `banque` : Compte Bancaire
- `crypto` : Cryptomonnaie
- `autre` : Autre

#### Statistiques des comptes
```http
GET /api/comptes/statistiques
Authorization: Bearer {token}
```

### 4. Gestion des Transferts

#### Lister les transferts
```http
GET /api/transferts?page=1&limit=20&compte_id=1
Authorization: Bearer {token}
```

#### Créer un transfert
```http
POST /api/transferts
Authorization: Bearer {token}
Content-Type: application/json

{
    "compte_source_id": 1,
    "compte_destination_id": 2,
    "montant": "5000.00",
    "note": "Transfert vers épargne"
}
```

#### Voir un transfert
```http
GET /api/transferts/{id}
Authorization: Bearer {token}
```

#### Annuler un transfert
```http
POST /api/transferts/{id}/annuler
Authorization: Bearer {token}
```

#### Simuler un transfert
```http
POST /api/transferts/simuler
Authorization: Bearer {token}
Content-Type: application/json

{
    "compte_source_id": 1,
    "compte_destination_id": 2,
    "montant": "5000.00"
}
```

#### Transferts récents
```http
GET /api/transferts/recents?limit=10
Authorization: Bearer {token}
```

#### Statistiques des transferts
```http
GET /api/transferts/statistiques
Authorization: Bearer {token}
```

### 5. Gestion des Mouvements

#### Lister les mouvements
```http
GET /api/mouvements?type=depense&projet_id=1&page=1&limit=20
Authorization: Bearer {token}
```

#### Créer une dépense
```http
POST /api/mouvements/depenses
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "50.00",
    "categorie_id": 1,
    "description": "Courses au supermarché",
    "lieu": "Carrefour",
    "methodePaiement": "carte",
    "projet_id": 1, // optionnel
    "contact_id": 1, // optionnel
    "date": "2024-01-15 14:30:00" // optionnel
}
```

#### Créer une entrée
```http
POST /api/mouvements/entrees
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "2000.00",
    "categorie_id": 2,
    "description": "Salaire mensuel",
    "source": "Entreprise ABC",
    "methode": "virement",
    "date": "2024-01-01 00:00:00"
}
```

### 4. Paiements Échelonnés

#### Lister les paiements d'un mouvement
```http
GET /api/paiements/mouvement/{mouvementId}
Authorization: Bearer {token}
```

#### Créer un paiement
```http
POST /api/paiements
Authorization: Bearer {token}
Content-Type: application/json

{
    "mouvement_id": 1,
    "montant": "2000.00",
    "commentaire": "Premier acompte",
    "date": "2024-01-15 14:30:00"
}
```

#### Modifier un paiement
```http
PUT /api/paiements/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "montant": "2500.00",
    "commentaire": "Paiement modifié"
}
```

#### Supprimer un paiement
```http
DELETE /api/paiements/{id}
Authorization: Bearer {token}
```

### 5. Dashboard et Statistiques

#### Solde global
```http
GET /api/dashboard/solde
Authorization: Bearer {token}
```

**Réponse :**
```json
{
    "soldeTotal": "1250.50",
    "statistiques": {
        "depense": {
            "label": "Dépense",
            "count": 15,
            "total": "750.00"
        },
        "entree": {
            "label": "Entrée",
            "count": 3,
            "total": "2000.00"
        }
    },
    "dettesEnRetard": {
        "count": 2,
        "total": "500.00"
    }
}
```

#### Soldes par projet
```http
GET /api/dashboard/projets/soldes
Authorization: Bearer {token}
```

#### Historique des mouvements
```http
GET /api/dashboard/historique?debut=2024-01-01&fin=2024-01-31&type=depense
Authorization: Bearer {token}
```

#### Dettes en retard
```http
GET /api/dashboard/dettes/retard
Authorization: Bearer {token}
```

## 🔒 Sécurité

### Fonctionnalités de sécurité implémentées :

1. **Authentification JWT** : Tokens sécurisés avec expiration
2. **Isolation des données** : Chaque utilisateur ne voit que ses propres données
3. **Validation des entrées** : Toutes les données sont validées
4. **Hashage des mots de passe** : Utilisation de l'algorithme de hashage sécurisé de Symfony
5. **Protection CSRF** : Protection contre les attaques CSRF
6. **Rate limiting** : Limitation du nombre de requêtes (à configurer)

### Bonnes pratiques :

- ✅ Toujours utiliser HTTPS en production
- ✅ Stocker les tokens JWT de manière sécurisée côté client
- ✅ Implémenter le refresh token pour renouveler les tokens
- ✅ Logs de sécurité pour tracer les tentatives d'accès
- ✅ Validation stricte des données d'entrée

## 🚀 Installation et Configuration

### 1. Installation des dépendances
```bash
composer install
```

### 2. Configuration de la base de données
```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate
```

### 3. Génération des clés JWT
```bash
php bin/console lexik:jwt:generate-keypair
```

### 4. Configuration des variables d'environnement
Créer un fichier `.env.local` :
```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/solde_track?serverVersion=8.0.32&charset=utf8mb4"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_secure_passphrase_here
```

## 📱 Utilisation côté client

### Exemple avec JavaScript/Fetch
```javascript
// Connexion
const login = async (email, password) => {
    const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password })
    });
    
    const data = await response.json();
    if (data.token) {
        localStorage.setItem('jwt_token', data.token);
    }
    return data;
};

// Utilisation du token pour les requêtes
const getMouvements = async () => {
    const token = localStorage.getItem('jwt_token');
    const response = await fetch('/api/mouvements', {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    return response.json();
};
```

## 🎯 Fonctionnalités principales

- ✅ **Gestion complète des finances** : Dépenses, entrées, dettes, dons
- ✅ **Suivi par projet** : Budget et dépenses par projet
- ✅ **Gestion des contacts** : Suivi des transactions avec des personnes
- ✅ **Catégorisation** : Organisation des mouvements par catégories
- ✅ **Dettes échelonnées** : Paiements partiels avec calculs automatiques
- ✅ **Statistiques en temps réel** : Soldes, historiques, alertes
- ✅ **Sécurité robuste** : JWT, validation, isolation des données

Cette API est prête pour être utilisée avec n'importe quelle application frontend (React, Vue, Angular, mobile, etc.).
