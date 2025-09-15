# API Statistiques - SoldeTrack

## 📊 Gestion des Statistiques Financières

L'API Statistiques fournit tous les endpoints nécessaires pour alimenter l'écran de statistiques de votre application mobile, incluant les résumés, évolutions, et analyses par catégorie.

## 🎯 Endpoints disponibles

### 1. Résumé des statistiques

**Endpoint :** `GET /api/statistiques/resume`

**Paramètres de requête :**
- `periode` (optionnel) : Période d'analyse (semaine, mois, trimestre, annee) - défaut: semaine

**Exemple de requête :**
```http
GET /api/statistiques/resume?periode=semaine
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "periode": "semaine",
    "entrees": {
        "montant": "150000.00",
        "montantFormatted": "150 000,00 XOF",
        "variation": 12.5,
        "variationFormatted": "+12.5%"
    },
    "depenses": {
        "montant": "75000.00",
        "montantFormatted": "75 000,00 XOF",
        "variation": -5.2,
        "variationFormatted": "-5.2%"
    },
    "soldeTotal": {
        "montant": "75000.00",
        "montantFormatted": "75 000,00 XOF"
    },
    "ceMois": {
        "montant": "125000.00",
        "montantFormatted": "125 000,00 XOF"
    }
}
```

### 2. Évolution des dépenses

**Endpoint :** `GET /api/statistiques/evolution-depenses`

**Paramètres de requête :**
- `periode` (optionnel) : Période d'analyse (semaine, mois, trimestre) - défaut: semaine
- `type` (optionnel) : Type de données (depenses, entrees, solde) - défaut: depenses

**Exemple de requête :**
```http
GET /api/statistiques/evolution-depenses?periode=semaine&type=depenses
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "periode": "semaine",
    "type": "depenses",
    "donnees": [],
    "message": "Graphique en cours de développement"
}
```

### 3. Dépenses par catégorie

**Endpoint :** `GET /api/statistiques/depenses-par-categorie`

**Paramètres de requête :**
- `periode` (optionnel) : Période d'analyse (semaine, mois, trimestre, annee) - défaut: mois

**Exemple de requête :**
```http
GET /api/statistiques/depenses-par-categorie?periode=mois
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "periode": "mois",
    "totalDepenses": "63000.00",
    "totalDepensesFormatted": "63 000,00 XOF",
    "categories": [
        {
            "id": 1,
            "nom": "Alimentation",
            "montant": "25000.00",
            "montantFormatted": "25 000,00 XOF",
            "pourcentage": 39.7
        },
        {
            "id": 2,
            "nom": "Transport",
            "montant": "15000.00",
            "montantFormatted": "15 000,00 XOF",
            "pourcentage": 23.8
        },
        {
            "id": 3,
            "nom": "Santé",
            "montant": "10000.00",
            "montantFormatted": "10 000,00 XOF",
            "pourcentage": 15.9
        },
        {
            "id": 4,
            "nom": "Loisirs",
            "montant": "8000.00",
            "montantFormatted": "8 000,00 XOF",
            "pourcentage": 12.7
        },
        {
            "id": 5,
            "nom": "Autres",
            "montant": "5000.00",
            "montantFormatted": "5 000,00 XOF",
            "pourcentage": 7.9
        }
    ]
}
```

### 4. Entrées par catégorie

**Endpoint :** `GET /api/statistiques/entrees-par-categorie`

**Paramètres de requête :**
- `periode` (optionnel) : Période d'analyse (semaine, mois, trimestre, annee) - défaut: mois

**Exemple de requête :**
```http
GET /api/statistiques/entrees-par-categorie?periode=mois
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "periode": "mois",
    "totalEntrees": "200000.00",
    "totalEntreesFormatted": "200 000,00 XOF",
    "categories": [
        {
            "id": 6,
            "nom": "Salaire",
            "montant": "150000.00",
            "montantFormatted": "150 000,00 XOF",
            "pourcentage": 75.0
        },
        {
            "id": 7,
            "nom": "Vente",
            "montant": "30000.00",
            "montantFormatted": "30 000,00 XOF",
            "pourcentage": 15.0
        },
        {
            "id": 8,
            "nom": "Autres",
            "montant": "20000.00",
            "montantFormatted": "20 000,00 XOF",
            "pourcentage": 10.0
        }
    ]
}
```

### 5. Comparaison entre périodes

**Endpoint :** `GET /api/statistiques/comparaison-periodes`

**Paramètres de requête :**
- `periode` (optionnel) : Période d'analyse (semaine, mois, trimestre) - défaut: mois

**Exemple de requête :**
```http
GET /api/statistiques/comparaison-periodes?periode=mois
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "periode": "mois",
    "actuelle": {
        "debut": "2024-01-01",
        "fin": "2024-01-31",
        "entrees": {
            "montant": "200000.00",
            "montantFormatted": "200 000,00 XOF"
        },
        "depenses": {
            "montant": "75000.00",
            "montantFormatted": "75 000,00 XOF"
        }
    },
    "precedente": {
        "debut": "2023-12-01",
        "fin": "2023-12-31",
        "entrees": {
            "montant": "180000.00",
            "montantFormatted": "180 000,00 XOF"
        },
        "depenses": {
            "montant": "80000.00",
            "montantFormatted": "80 000,00 XOF"
        }
    },
    "variations": {
        "entrees": 11.1,
        "depenses": -6.25
    }
}
```

### 6. Tendances sur plusieurs périodes

**Endpoint :** `GET /api/statistiques/tendances`

**Paramètres de requête :**
- `periode` (optionnel) : Période d'analyse (semaine, mois, trimestre) - défaut: mois
- `nb_periodes` (optionnel) : Nombre de périodes à analyser - défaut: 6

**Exemple de requête :**
```http
GET /api/statistiques/tendances?periode=mois&nb_periodes=6
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "periode": "mois",
    "nbPeriodes": 6,
    "tendances": [
        {
            "periode": "2024-01-01 / 2024-01-31",
            "entrees": "200000.00",
            "depenses": "75000.00",
            "solde": "125000.00"
        },
        {
            "periode": "2023-12-01 / 2023-12-31",
            "entrees": "180000.00",
            "depenses": "80000.00",
            "solde": "100000.00"
        },
        {
            "periode": "2023-11-01 / 2023-11-30",
            "entrees": "190000.00",
            "depenses": "85000.00",
            "solde": "105000.00"
        },
        {
            "periode": "2023-10-01 / 2023-10-31",
            "entrees": "175000.00",
            "depenses": "90000.00",
            "solde": "85000.00"
        },
        {
            "periode": "2023-09-01 / 2023-09-30",
            "entrees": "185000.00",
            "depenses": "78000.00",
            "solde": "107000.00"
        },
        {
            "periode": "2023-08-01 / 2023-08-31",
            "entrees": "195000.00",
            "depenses": "82000.00",
            "solde": "113000.00"
        }
    ]
}
```

### 7. Liste des catégories de l'utilisateur

**Endpoint :** `GET /api/statistiques/categories`

**Exemple de requête :**
```http
GET /api/statistiques/categories
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "categories": [
        {
            "id": 1,
            "nom": "Alimentation",
            "type": "depense",
            "dateCreation": "2024-01-15 10:30:00"
        },
        {
            "id": 2,
            "nom": "Transport",
            "type": "depense",
            "dateCreation": "2024-01-15 10:30:00"
        },
        {
            "id": 3,
            "nom": "Santé",
            "type": "depense",
            "dateCreation": "2024-01-15 10:30:00"
        },
        {
            "id": 4,
            "nom": "Salaire",
            "type": "entree",
            "dateCreation": "2024-01-15 10:30:00"
        },
        {
            "id": 5,
            "nom": "Vente",
            "type": "entree",
            "dateCreation": "2024-01-15 10:30:00"
        }
    ]
}
```

## ⚠️ Codes d'erreur

### Erreurs générales :
- **401** : Non authentifié
- **403** : Accès refusé
- **400** : Paramètres invalides
- **500** : Erreur serveur

## 🎯 Fonctionnalités clés

1. **✅ Résumé complet** : Entrées, dépenses, solde total avec variations
2. **✅ Périodes flexibles** : Semaine, mois, trimestre, année
3. **✅ Analyses par catégorie** : Dépenses et entrées détaillées avec vraies catégories utilisateur
4. **✅ Comparaisons** : Évolution entre périodes
5. **✅ Tendances** : Historique sur plusieurs périodes
6. **✅ Formatage** : Tous les montants formatés avec la devise
7. **✅ Catégories réelles** : Récupération des vraies catégories de l'utilisateur depuis la BDD
8. **✅ Pourcentages** : Calculs automatiques des proportions
9. **✅ Liste des catégories** : Endpoint pour récupérer toutes les catégories de l'utilisateur

## 📊 Cas d'usage pour l'interface mobile

### 1. **Écran principal des statistiques** :
- Utiliser `/resume` pour les 4 cartes principales (Entrées, Dépenses, Solde Total, Ce Mois)
- Utiliser `/depenses-par-categorie` pour la section "Dépenses par catégorie"

### 2. **Sélecteur de période** :
- Passer le paramètre `periode` (semaine, mois, trimestre) à tous les endpoints

### 3. **Graphiques d'évolution** :
- Utiliser `/evolution-depenses` pour les graphiques (en développement)
- Utiliser `/tendances` pour les graphiques de tendance

### 4. **Analyses détaillées** :
- Utiliser `/comparaison-periodes` pour les comparaisons
- Utiliser `/entrees-par-categorie` pour les analyses d'entrées

### 5. **Gestion des couleurs** :
- Utiliser `/categories` pour récupérer la liste des catégories de l'utilisateur
- Le frontend peut gérer les couleurs selon ses propres règles
- Les statistiques retournent les vraies catégories de l'utilisateur depuis la BDD

## 🎨 Gestion des couleurs

**Le frontend gère les couleurs** selon ses propres règles. L'API retourne uniquement les données des catégories réelles de l'utilisateur depuis la base de données, sans imposer de couleurs spécifiques.

**L'API Statistiques est maintenant complètement prête pour alimenter votre interface mobile !** 🚀📊
