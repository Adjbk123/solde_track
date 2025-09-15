# API Mouvements - SoldeTrack

## 💰 Gestion des Mouvements Financiers

L'API des mouvements permet de gérer tous les types de transactions financières : dépenses, entrées, dettes et dons.

## 📋 Types de mouvements

- **Dépense** : Sortie d'argent (achats, factures, etc.)
- **Entrée** : Entrée d'argent (salaire, vente, etc.)
- **Dette** : Argent dû (prêt, crédit, etc.)
- **Don** : Don d'argent (cadeau, charité, etc.)

## 🎯 Endpoints disponibles

### 1. Lister les mouvements

**Endpoint :** `GET /api/mouvements`

**Paramètres de requête :**
- `type` (optionnel) : Type de mouvement (depense, entree, dette, don)
- `projet_id` (optionnel) : ID du projet
- `categorie_id` (optionnel) : ID de la catégorie
- `statut` (optionnel) : Statut (en_attente, effectue, annule)
- `page` (optionnel) : Numéro de page (défaut: 1)
- `limit` (optionnel) : Nombre d'éléments par page (défaut: 20)

**Exemple de requête :**
```http
GET /api/mouvements?type=depense&projet_id=1&categorie_id=2&statut=effectue&page=1&limit=20
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "mouvements": [
        {
            "id": 1,
            "type": "depense",
            "typeLabel": "Dépense",
            "montantTotal": "50000.00",
            "montantTotalFormatted": "50 000,00 XOF",
            "montantEffectif": "50000.00",
            "montantEffectifFormatted": "50 000,00 XOF",
            "montantRestant": "0.00",
            "montantRestantFormatted": "0,00 XOF",
            "statut": "effectue",
            "statutLabel": "Effectué",
            "date": "2024-01-20 14:30:00",
            "description": "Achat de matériaux",
            "categorie": {
                "id": 1,
                "nom": "Matériaux",
                "type": "depense"
            },
            "projet": {
                "id": 1,
                "nom": "Achat Maison"
            },
            "compte": {
                "id": 1,
                "nom": "Compte Principal",
                "type": "compte_principal"
            },
            "lieu": "Magasin Brico",
            "methodePaiement": "carte",
            "methodePaiementLabel": "Carte bancaire",
            "recu": "REC-2024-001"
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 20,
        "total": 1
    }
}
```

### 2. Voir un mouvement

**Endpoint :** `GET /api/mouvements/{id}`

**Exemple de requête :**
```http
GET /api/mouvements/1
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "mouvement": {
        "id": 1,
        "type": "depense",
        "typeLabel": "Dépense",
        "montantTotal": "50000.00",
        "montantTotalFormatted": "50 000,00 XOF",
        "montantEffectif": "50000.00",
        "montantEffectifFormatted": "50 000,00 XOF",
        "montantRestant": "0.00",
        "montantRestantFormatted": "0,00 XOF",
        "statut": "effectue",
        "statutLabel": "Effectué",
        "date": "2024-01-20 14:30:00",
        "description": "Achat de matériaux",
        "categorie": {
            "id": 1,
            "nom": "Matériaux",
            "type": "depense"
        },
        "projet": {
            "id": 1,
            "nom": "Achat Maison"
        },
        "compte": {
            "id": 1,
            "nom": "Compte Principal",
            "type": "compte_principal"
        },
        "lieu": "Magasin Brico",
        "methodePaiement": "carte",
        "methodePaiementLabel": "Carte bancaire",
        "recu": "REC-2024-001"
    }
}
```

### 3. Créer une dépense

**Endpoint :** `POST /api/mouvements/depenses`

**Corps de la requête :**
```json
{
    "montantTotal": "25000.00",
    "categorie_id": 1,
    "description": "Achat de nourriture",
    "lieu": "Supermarché",
    "methodePaiement": "especes",
    "recu": "REC-2024-002",
    "projet_id": 1,
    "contact_id": 1,
    "compte_id": 1,
    "date": "2024-01-20"
}
```

**Champs requis :**
- `montantTotal` : Montant de la dépense
- `categorie_id` : ID de la catégorie

**Champs optionnels :**
- `description` : Description de la dépense
- `lieu` : Lieu de l'achat
- `methodePaiement` : Méthode de paiement (especes, carte, virement, cheque, momo, autre)
- `recu` : Numéro de reçu
- `projet_id` : ID du projet associé
- `contact_id` : ID du contact associé
- `compte_id` : ID du compte associé
- `date` : Date de la dépense (YYYY-MM-DD)

**Exemple de requête :**
```http
POST /api/mouvements/depenses
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "25000.00",
    "categorie_id": 1,
    "description": "Achat de nourriture",
    "lieu": "Supermarché",
    "methodePaiement": "especes",
    "recu": "REC-2024-002",
    "projet_id": 1,
    "compte_id": 1,
    "date": "2024-01-20"
}
```

**Réponse (201 Created) :**
```json
{
    "message": "Dépense créée avec succès",
    "mouvement": {
        "id": 2,
        "type": "depense",
        "typeLabel": "Dépense",
        "montantTotal": "25000.00",
        "montantTotalFormatted": "25 000,00 XOF",
        "montantEffectif": "25000.00",
        "montantEffectifFormatted": "25 000,00 XOF",
        "statut": "effectue",
        "statutLabel": "Effectué",
        "date": "2024-01-20 00:00:00",
        "description": "Achat de nourriture",
        "categorie": {
            "id": 1,
            "nom": "Alimentation",
            "type": "depense"
        },
        "lieu": "Supermarché",
        "methodePaiement": "especes",
        "methodePaiementLabel": "Espèces",
        "recu": "REC-2024-002"
    }
}
```

### 4. Créer une entrée

**Endpoint :** `POST /api/mouvements/entrees`

**Corps de la requête :**
```json
{
    "montantTotal": "150000.00",
    "categorie_id": 2,
    "description": "Salaire mensuel",
    "source": "Employeur",
    "methode": "virement",
    "projet_id": 1,
    "compte_id": 1,
    "date": "2024-01-15"
}
```

**Champs requis :**
- `montantTotal` : Montant de l'entrée
- `categorie_id` : ID de la catégorie

**Champs optionnels :**
- `description` : Description de l'entrée
- `source` : Source de l'argent
- `methode` : Méthode de réception (especes, virement, cheque, momo, autre)
- `projet_id` : ID du projet associé
- `contact_id` : ID du contact associé
- `compte_id` : ID du compte associé
- `date` : Date de l'entrée (YYYY-MM-DD)

**Exemple de requête :**
```http
POST /api/mouvements/entrees
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "150000.00",
    "categorie_id": 2,
    "description": "Salaire mensuel",
    "source": "Employeur",
    "methode": "virement",
    "projet_id": 1,
    "compte_id": 1,
    "date": "2024-01-15"
}
```

**Réponse (201 Created) :**
```json
{
    "message": "Entrée créée avec succès",
    "mouvement": {
        "id": 3,
        "type": "entree",
        "typeLabel": "Entrée",
        "montantTotal": "150000.00",
        "montantTotalFormatted": "150 000,00 XOF",
        "montantEffectif": "150000.00",
        "montantEffectifFormatted": "150 000,00 XOF",
        "statut": "effectue",
        "statutLabel": "Effectué",
        "date": "2024-01-15 00:00:00",
        "description": "Salaire mensuel",
        "categorie": {
            "id": 2,
            "nom": "Salaire",
            "type": "entree"
        },
        "source": "Employeur",
        "methode": "virement",
        "methodeLabel": "Virement bancaire"
    }
}
```

### 5. Créer une dette

**Endpoint :** `POST /api/mouvements/dettes`

**Corps de la requête :**
```json
{
    "montantTotal": "500000.00",
    "categorie_id": 3,
    "description": "Prêt bancaire",
    "echeance": "2024-12-31",
    "taux": "5.5",
    "projet_id": 1,
    "contact_id": 2,
    "compte_id": 1,
    "date": "2024-01-10"
}
```

**Champs requis :**
- `montantTotal` : Montant de la dette
- `categorie_id` : ID de la catégorie

**Champs optionnels :**
- `description` : Description de la dette
- `echeance` : Date d'échéance (YYYY-MM-DD)
- `taux` : Taux d'intérêt (en pourcentage)
- `projet_id` : ID du projet associé
- `contact_id` : ID du contact associé
- `compte_id` : ID du compte associé
- `date` : Date de la dette (YYYY-MM-DD)

**Exemple de requête :**
```http
POST /api/mouvements/dettes
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "500000.00",
    "categorie_id": 3,
    "description": "Prêt bancaire",
    "echeance": "2024-12-31",
    "taux": "5.5",
    "projet_id": 1,
    "contact_id": 2,
    "compte_id": 1,
    "date": "2024-01-10"
}
```

**Réponse (201 Created) :**
```json
{
    "message": "Dette créée avec succès",
    "mouvement": {
        "id": 4,
        "type": "dette",
        "typeLabel": "Dette",
        "montantTotal": "500000.00",
        "montantTotalFormatted": "500 000,00 XOF",
        "montantEffectif": "500000.00",
        "montantEffectifFormatted": "500 000,00 XOF",
        "montantRestant": "500000.00",
        "montantRestantFormatted": "500 000,00 XOF",
        "statut": "en_attente",
        "statutLabel": "En attente",
        "date": "2024-01-10 00:00:00",
        "description": "Prêt bancaire",
        "categorie": {
            "id": 3,
            "nom": "Prêt",
            "type": "dette"
        },
        "echeance": "2024-12-31",
        "taux": "5.5",
        "montantRest": "500000.00",
        "montantRestFormatted": "500 000,00 XOF",
        "montantInterets": "27500.00",
        "montantInteretsFormatted": "27 500,00 XOF",
        "enRetard": false
    }
}
```

### 6. Créer un don

**Endpoint :** `POST /api/mouvements/dons`

**Corps de la requête :**
```json
{
    "montantTotal": "10000.00",
    "categorie_id": 4,
    "description": "Don pour l'éducation",
    "occasion": "Anniversaire",
    "contact_id": 3,
    "compte_id": 1,
    "date": "2024-01-18"
}
```

**Champs requis :**
- `montantTotal` : Montant du don
- `categorie_id` : ID de la catégorie

**Champs optionnels :**
- `description` : Description du don
- `occasion` : Occasion du don
- `projet_id` : ID du projet associé
- `contact_id` : ID du contact associé
- `compte_id` : ID du compte associé
- `date` : Date du don (YYYY-MM-DD)

**Exemple de requête :**
```http
POST /api/mouvements/dons
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "10000.00",
    "categorie_id": 4,
    "description": "Don pour l'éducation",
    "occasion": "Anniversaire",
    "contact_id": 3,
    "compte_id": 1,
    "date": "2024-01-18"
}
```

**Réponse (201 Created) :**
```json
{
    "message": "Don créé avec succès",
    "mouvement": {
        "id": 5,
        "type": "don",
        "typeLabel": "Don",
        "montantTotal": "10000.00",
        "montantTotalFormatted": "10 000,00 XOF",
        "montantEffectif": "10000.00",
        "montantEffectifFormatted": "10 000,00 XOF",
        "statut": "effectue",
        "statutLabel": "Effectué",
        "date": "2024-01-18 00:00:00",
        "description": "Don pour l'éducation",
        "categorie": {
            "id": 4,
            "nom": "Charité",
            "type": "don"
        },
        "occasion": "Anniversaire"
    }
}
```

### 7. Modifier un mouvement

**Endpoint :** `PUT /api/mouvements/{id}`

**Corps de la requête :**
```json
{
    "montantTotal": "55000.00",
    "description": "Achat de matériaux - mis à jour",
    "date": "2024-01-21"
}
```

**Champs modifiables :**
- `montantTotal` : Montant du mouvement
- `description` : Description du mouvement
- `date` : Date du mouvement (YYYY-MM-DD)

**Exemple de requête :**
```http
PUT /api/mouvements/1
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "55000.00",
    "description": "Achat de matériaux - mis à jour",
    "date": "2024-01-21"
}
```

**Réponse (200 OK) :**
```json
{
    "message": "Mouvement mis à jour avec succès",
    "mouvement": {
        "id": 1,
        "type": "depense",
        "typeLabel": "Dépense",
        "montantTotal": "55000.00",
        "montantTotalFormatted": "55 000,00 XOF",
        "montantEffectif": "55000.00",
        "montantEffectifFormatted": "55 000,00 XOF",
        "statut": "effectue",
        "statutLabel": "Effectué",
        "date": "2024-01-21 00:00:00",
        "description": "Achat de matériaux - mis à jour"
    }
}
```

### 8. Supprimer un mouvement

**Endpoint :** `DELETE /api/mouvements/{id}`

**Exemple de requête :**
```http
DELETE /api/mouvements/1
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "message": "Mouvement supprimé avec succès"
}
```

### 9. Statistiques des mouvements

**Endpoint :** `GET /api/mouvements/statistiques`

**Paramètres de requête :**
- `debut` (optionnel) : Date de début (YYYY-MM-DD)
- `fin` (optionnel) : Date de fin (YYYY-MM-DD)
- `type` (optionnel) : Type de mouvement

**Exemple de requête :**
```http
GET /api/mouvements/statistiques?debut=2024-01-01&fin=2024-01-31&type=depense
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "statistiques": {
        "totalDepenses": 75000.00,
        "totalDepensesFormatted": "75 000,00 XOF",
        "totalEntrees": 150000.00,
        "totalEntreesFormatted": "150 000,00 XOF",
        "totalDettes": 500000.00,
        "totalDettesFormatted": "500 000,00 XOF",
        "totalDons": 10000.00,
        "totalDonsFormatted": "10 000,00 XOF",
        "soldeNet": 75000.00,
        "soldeNetFormatted": "75 000,00 XOF",
        "nombreTotal": 4
    },
    "parType": {
        "depense": {
            "count": 2,
            "total": 75000.00
        },
        "entree": {
            "count": 1,
            "total": 150000.00
        },
        "dette": {
            "count": 1,
            "total": 500000.00
        },
        "don": {
            "count": 1,
            "total": 10000.00
        }
    },
    "parCategorie": {
        "Matériaux": {
            "count": 1,
            "total": 50000.00
        },
        "Alimentation": {
            "count": 1,
            "total": 25000.00
        },
        "Salaire": {
            "count": 1,
            "total": 150000.00
        },
        "Prêt": {
            "count": 1,
            "total": 500000.00
        },
        "Charité": {
            "count": 1,
            "total": 10000.00
        }
    }
}
```

### 10. Mouvements récents

**Endpoint :** `GET /api/mouvements/recents`

**Paramètres de requête :**
- `limit` (optionnel) : Nombre de mouvements (défaut: 10)
- `type` (optionnel) : Type de mouvement

**Exemple de requête :**
```http
GET /api/mouvements/recents?limit=5&type=depense
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "mouvements": [
        {
            "id": 2,
            "type": "depense",
            "typeLabel": "Dépense",
            "montantTotal": "25000.00",
            "montantTotalFormatted": "25 000,00 XOF",
            "montantEffectif": "25000.00",
            "montantEffectifFormatted": "25 000,00 XOF",
            "statut": "effectue",
            "statutLabel": "Effectué",
            "date": "2024-01-20 00:00:00",
            "description": "Achat de nourriture",
            "categorie": {
                "id": 1,
                "nom": "Alimentation",
                "type": "depense"
            }
        }
    ]
}
```

## ⚠️ Codes d'erreur

### Erreurs générales :
- **401** : Non authentifié
- **403** : Accès refusé
- **404** : Mouvement non trouvé
- **500** : Erreur serveur

### Erreurs de validation :
- **400** : Données manquantes ou invalides
- **404** : Catégorie, projet ou contact non trouvé

## 🎯 Fonctionnalités clés

1. **✅ CRUD complet** : Créer, lire, modifier, supprimer
2. **✅ 4 types de mouvements** : Dépenses, Entrées, Dettes, Dons
3. **✅ Filtrage avancé** : Par type, projet, catégorie, statut
4. **✅ Pagination** : Liste paginée des mouvements
5. **✅ Statistiques** : Analyses détaillées par type et catégorie
6. **✅ Formatage** : Montants formatés avec la devise de l'utilisateur
7. **✅ Relations** : Liens avec projets, catégories, contacts, comptes
8. **✅ Sécurité** : Chaque utilisateur ne voit que ses mouvements
