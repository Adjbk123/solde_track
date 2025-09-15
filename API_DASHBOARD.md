# API Dashboard - SoldeTrack

## 📊 Tableau de Bord Financier

L'API Dashboard fournit une vue d'ensemble complète de la situation financière de l'utilisateur avec des statistiques, des soldes et des analyses détaillées.

## 🎯 Endpoints disponibles

### 1. Solde général

**Endpoint :** `GET /api/dashboard/solde`

**Exemple de requête :**
```http
GET /api/dashboard/solde
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "soldeTotal": "125000.00",
    "soldeTotalFormatted": "125 000,00 XOF",
    "statistiques": {
        "depense": {
            "label": "Dépense",
            "count": 15,
            "total": "75000.00"
        },
        "entree": {
            "label": "Entrée",
            "count": 8,
            "total": "200000.00"
        },
        "dette": {
            "label": "Dette",
            "count": 3,
            "total": "500000.00"
        },
        "don": {
            "label": "Don",
            "count": 2,
            "total": "10000.00"
        }
    },
    "dettesEnRetard": {
        "count": 1,
        "total": "50000.00",
        "totalFormatted": "50 000,00 XOF"
    },
    "devise": {
        "code": "XOF",
        "nom": "Franc CFA"
    }
}
```

### 2. Soldes des projets

**Endpoint :** `GET /api/dashboard/projets/soldes`

**Exemple de requête :**
```http
GET /api/dashboard/projets/soldes
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "projets": [
        {
            "id": 1,
            "nom": "Achat Maison",
            "description": "Projet d'achat d'une maison",
            "budgetPrevu": "5000000.00",
            "solde": "125000.00",
            "totalDepenses": "75000.00",
            "totalEntrees": "200000.00",
            "nombreMouvements": 8
        },
        {
            "id": 2,
            "nom": "Voyage",
            "description": "Voyage en Europe",
            "budgetPrevu": "1000000.00",
            "solde": "-25000.00",
            "totalDepenses": "50000.00",
            "totalEntrees": "25000.00",
            "nombreMouvements": 3
        }
    ]
}
```

### 3. Historique des mouvements

**Endpoint :** `GET /api/dashboard/historique`

**Paramètres de requête :**
- `debut` (optionnel) : Date de début (YYYY-MM-DD)
- `fin` (optionnel) : Date de fin (YYYY-MM-DD)
- `type` (optionnel) : Type de mouvement

**Exemple de requête :**
```http
GET /api/dashboard/historique?debut=2024-01-01&fin=2024-01-31&type=depense
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "historique": [
        {
            "id": 1,
            "type": "depense",
            "typeLabel": "Dépense",
            "montant": "25000.00",
            "date": "2024-01-20 14:30:00",
            "description": "Achat de nourriture",
            "categorie": "Alimentation",
            "projet": "Achat Maison",
            "contact": "Supermarché ABC"
        },
        {
            "id": 2,
            "type": "entree",
            "typeLabel": "Entrée",
            "montant": "150000.00",
            "date": "2024-01-15 10:00:00",
            "description": "Salaire mensuel",
            "categorie": "Salaire",
            "projet": null,
            "contact": null
        }
    ],
    "total": 2
}
```

### 4. Dettes en retard

**Endpoint :** `GET /api/dashboard/dettes/retard`

**Exemple de requête :**
```http
GET /api/dashboard/dettes/retard
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "dettes": [
        {
            "id": 1,
            "montantTotal": "200000.00",
            "montantRest": "50000.00",
            "echeance": "2024-01-15",
            "taux": "5.5",
            "montantInterets": "2750.00",
            "description": "Prêt bancaire",
            "contact": "Banque ABC",
            "joursRetard": 5
        }
    ],
    "total": 1
}
```

### 5. Soldes des comptes

**Endpoint :** `GET /api/dashboard/comptes/soldes`

**Exemple de requête :**
```http
GET /api/dashboard/comptes/soldes
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "comptes": [
        {
            "id": 1,
            "nom": "Compte Principal",
            "type": "compte_principal",
            "typeLabel": "Compte Principal",
            "soldeActuel": "125000.00",
            "soldeActuelFormatted": "125 000,00 XOF",
            "soldeInitial": "100000.00",
            "soldeInitialFormatted": "100 000,00 XOF",
            "devise": {
                "id": 1,
                "code": "XOF",
                "nom": "Franc CFA"
            },
            "dateCreation": "2024-01-01 10:00:00",
            "dateModification": "2024-01-20 15:30:00"
        },
        {
            "id": 2,
            "nom": "Compte Épargne",
            "type": "epargne",
            "typeLabel": "Épargne",
            "soldeActuel": "500000.00",
            "soldeActuelFormatted": "500 000,00 XOF",
            "soldeInitial": "500000.00",
            "soldeInitialFormatted": "500 000,00 XOF",
            "devise": {
                "id": 1,
                "code": "XOF",
                "nom": "Franc CFA"
            },
            "dateCreation": "2024-01-01 10:00:00",
            "dateModification": "2024-01-01 10:00:00"
        }
    ],
    "soldeTotal": "625000.00",
    "soldeTotalFormatted": "625 000,00 XOF",
    "nombreComptes": 2
}
```

### 6. Transferts récents

**Endpoint :** `GET /api/dashboard/transferts/recents`

**Paramètres de requête :**
- `limit` (optionnel) : Nombre de transferts (défaut: 5)

**Exemple de requête :**
```http
GET /api/dashboard/transferts/recents?limit=3
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "transferts": [
        {
            "id": 1,
            "montant": "50000.00",
            "montantFormatted": "50 000,00 XOF",
            "date": "2024-01-20 16:00:00",
            "note": "Transfert vers épargne",
            "annule": false,
            "compteSource": {
                "id": 1,
                "nom": "Compte Principal"
            },
            "compteDestination": {
                "id": 2,
                "nom": "Compte Épargne"
            },
            "devise": {
                "id": 1,
                "code": "XOF",
                "nom": "Franc CFA"
            }
        }
    ],
    "total": 1
}
```

### 7. Résumé complet

**Endpoint :** `GET /api/dashboard/resume`

**Exemple de requête :**
```http
GET /api/dashboard/resume
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "soldeTotal": "125000.00",
    "soldeTotalFormatted": "125 000,00 XOF",
    "soldeComptes": "625000.00",
    "soldeComptesFormatted": "625 000,00 XOF",
    "dettesEnRetard": {
        "count": 1,
        "total": "50000.00",
        "totalFormatted": "50 000,00 XOF"
    },
    "statistiques": {
        "depense": {
            "label": "Dépense",
            "count": 15,
            "total": "75000.00",
            "totalFormatted": "75 000,00 XOF"
        },
        "entree": {
            "label": "Entrée",
            "count": 8,
            "total": "200000.00",
            "totalFormatted": "200 000,00 XOF"
        },
        "dette": {
            "label": "Dette",
            "count": 3,
            "total": "500000.00",
            "totalFormatted": "500 000,00 XOF"
        },
        "don": {
            "label": "Don",
            "count": 2,
            "total": "10000.00",
            "totalFormatted": "10 000,00 XOF"
        }
    },
    "transfertsRecents": [
        {
            "id": 1,
            "montant": "50000.00",
            "montantFormatted": "50 000,00 XOF",
            "date": "2024-01-20 16:00:00",
            "compteSource": "Compte Principal",
            "compteDestination": "Compte Épargne"
        }
    ],
    "mouvementsRecents": [
        {
            "id": 1,
            "type": "depense",
            "typeLabel": "Dépense",
            "montant": "25000.00",
            "montantFormatted": "25 000,00 XOF",
            "date": "2024-01-20 14:30:00",
            "description": "Achat de nourriture",
            "categorie": "Alimentation"
        },
        {
            "id": 2,
            "type": "entree",
            "typeLabel": "Entrée",
            "montant": "150000.00",
            "montantFormatted": "150 000,00 XOF",
            "date": "2024-01-15 10:00:00",
            "description": "Salaire mensuel",
            "categorie": "Salaire"
        }
    ],
    "devise": {
        "code": "XOF",
        "nom": "Franc CFA"
    }
}
```

### 8. Statistiques par période

**Endpoint :** `GET /api/dashboard/statistiques/periodes`

**Paramètres de requête :**
- `debut` (requis) : Date de début (YYYY-MM-DD)
- `fin` (requis) : Date de fin (YYYY-MM-DD)

**Exemple de requête :**
```http
GET /api/dashboard/statistiques/periodes?debut=2024-01-01&fin=2024-01-31
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "periode": {
        "debut": "2024-01-01",
        "fin": "2024-01-31"
    },
    "statistiques": {
        "depense": {
            "count": 12,
            "total": 75000.00,
            "totalFormatted": "75 000,00 XOF"
        },
        "entree": {
            "count": 5,
            "total": 200000.00,
            "totalFormatted": "200 000,00 XOF"
        },
        "dette": {
            "count": 2,
            "total": 300000.00,
            "totalFormatted": "300 000,00 XOF"
        },
        "don": {
            "count": 1,
            "total": 5000.00,
            "totalFormatted": "5 000,00 XOF"
        }
    },
    "soldeNet": 430000.00,
    "soldeNetFormatted": "430 000,00 XOF",
    "totalMouvements": 20
}
```

## ⚠️ Codes d'erreur

### Erreurs générales :
- **401** : Non authentifié
- **403** : Accès refusé
- **400** : Paramètres manquants ou invalides
- **500** : Erreur serveur

## 🎯 Fonctionnalités clés

1. **✅ Vue d'ensemble** : Solde total et statistiques générales
2. **✅ Gestion des projets** : Soldes et budgets par projet
3. **✅ Historique complet** : Tous les mouvements avec filtrage
4. **✅ Alertes dettes** : Dettes en retard avec calcul des jours
5. **✅ Gestion des comptes** : Soldes de tous les comptes
6. **✅ Transferts récents** : Derniers transferts entre comptes
7. **✅ Résumé complet** : Vue d'ensemble de toute la situation financière
8. **✅ Analyses par période** : Statistiques sur des périodes spécifiques
9. **✅ Formatage** : Tous les montants formatés avec la devise de l'utilisateur
10. **✅ Sécurité** : Chaque utilisateur ne voit que ses données

## 📊 Cas d'usage typiques

1. **Page d'accueil** : Utiliser `/resume` pour afficher un tableau de bord complet
2. **Suivi des projets** : Utiliser `/projets/soldes` pour voir l'état des budgets
3. **Alertes** : Utiliser `/dettes/retard` pour identifier les dettes en retard
4. **Historique** : Utiliser `/historique` avec filtres pour analyser les mouvements
5. **Analyses** : Utiliser `/statistiques/periodes` pour des rapports personnalisés
