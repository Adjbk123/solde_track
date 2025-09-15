# API Paiements - SoldeTrack

## 💳 Gestion des Paiements Échelonnés

L'API des paiements permet de gérer les paiements échelonnés pour les mouvements financiers (principalement les dettes et dépenses importantes).

## 📋 Types de paiements

- **Payé** : Paiement effectué
- **En attente** : Paiement en cours
- **Annulé** : Paiement annulé

## 🎯 Endpoints disponibles

### 1. Lister les paiements d'un mouvement

**Endpoint :** `GET /api/paiements/mouvement/{mouvementId}`

**Exemple de requête :**
```http
GET /api/paiements/mouvement/1
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "paiements": [
        {
            "id": 1,
            "montant": "50000.00",
            "montantFormatted": "50 000,00 XOF",
            "date": "2024-01-15 10:30:00",
            "commentaire": "Premier paiement",
            "statut": "paye",
            "statutLabel": "Payé",
            "mouvement": {
                "id": 1,
                "type": "dette",
                "typeLabel": "Dette",
                "montantTotal": "200000.00",
                "montantTotalFormatted": "200 000,00 XOF",
                "description": "Prêt bancaire",
                "categorie": {
                    "id": 3,
                    "nom": "Prêt"
                }
            }
        },
        {
            "id": 2,
            "montant": "50000.00",
            "montantFormatted": "50 000,00 XOF",
            "date": "2024-02-15 10:30:00",
            "commentaire": "Deuxième paiement",
            "statut": "en_attente",
            "statutLabel": "En attente",
            "mouvement": {
                "id": 1,
                "type": "dette",
                "typeLabel": "Dette",
                "montantTotal": "200000.00",
                "montantTotalFormatted": "200 000,00 XOF",
                "description": "Prêt bancaire",
                "categorie": {
                    "id": 3,
                    "nom": "Prêt"
                }
            }
        }
    ],
    "totalPaiements": "100000.00"
}
```

### 2. Voir un paiement

**Endpoint :** `GET /api/paiements/{id}`

**Exemple de requête :**
```http
GET /api/paiements/1
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "paiement": {
        "id": 1,
        "montant": "50000.00",
        "montantFormatted": "50 000,00 XOF",
        "date": "2024-01-15 10:30:00",
        "commentaire": "Premier paiement",
        "statut": "paye",
        "statutLabel": "Payé",
        "mouvement": {
            "id": 1,
            "type": "dette",
            "typeLabel": "Dette",
            "montantTotal": "200000.00",
            "montantTotalFormatted": "200 000,00 XOF",
            "description": "Prêt bancaire",
            "categorie": {
                "id": 3,
                "nom": "Prêt"
            }
        }
    }
}
```

### 3. Créer un paiement

**Endpoint :** `POST /api/paiements`

**Corps de la requête :**
```json
{
    "mouvement_id": 1,
    "montant": "50000.00",
    "date": "2024-01-15",
    "commentaire": "Premier paiement",
    "statut": "paye"
}
```

**Champs requis :**
- `mouvement_id` : ID du mouvement associé
- `montant` : Montant du paiement

**Champs optionnels :**
- `date` : Date du paiement (YYYY-MM-DD)
- `commentaire` : Commentaire sur le paiement
- `statut` : Statut du paiement (paye, en_attente, annule)

**Exemple de requête :**
```http
POST /api/paiements
Authorization: Bearer {token}
Content-Type: application/json

{
    "mouvement_id": 1,
    "montant": "50000.00",
    "date": "2024-01-15",
    "commentaire": "Premier paiement",
    "statut": "paye"
}
```

**Réponse (201 Created) :**
```json
{
    "message": "Paiement créé avec succès",
    "paiement": {
        "id": 1,
        "montant": "50000.00",
        "montantFormatted": "50 000,00 XOF",
        "date": "2024-01-15 00:00:00",
        "commentaire": "Premier paiement",
        "statut": "paye",
        "statutLabel": "Payé"
    },
    "mouvement": {
        "montantEffectif": "50000.00",
        "montantRestant": "150000.00",
        "statut": "en_attente",
        "statutLabel": "En attente"
    }
}
```

### 4. Modifier un paiement

**Endpoint :** `PUT /api/paiements/{id}`

**Corps de la requête :**
```json
{
    "montant": "60000.00",
    "date": "2024-01-20",
    "commentaire": "Paiement modifié",
    "statut": "paye"
}
```

**Champs modifiables :**
- `montant` : Montant du paiement
- `date` : Date du paiement (YYYY-MM-DD)
- `commentaire` : Commentaire sur le paiement
- `statut` : Statut du paiement

**Exemple de requête :**
```http
PUT /api/paiements/1
Authorization: Bearer {token}
Content-Type: application/json

{
    "montant": "60000.00",
    "date": "2024-01-20",
    "commentaire": "Paiement modifié",
    "statut": "paye"
}
```

**Réponse (200 OK) :**
```json
{
    "message": "Paiement mis à jour avec succès",
    "paiement": {
        "id": 1,
        "montant": "60000.00",
        "montantFormatted": "60 000,00 XOF",
        "date": "2024-01-20 00:00:00",
        "commentaire": "Paiement modifié",
        "statut": "paye",
        "statutLabel": "Payé"
    }
}
```

### 5. Supprimer un paiement

**Endpoint :** `DELETE /api/paiements/{id}`

**Exemple de requête :**
```http
DELETE /api/paiements/1
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "message": "Paiement supprimé avec succès"
}
```

### 6. Marquer un paiement comme payé

**Endpoint :** `POST /api/paiements/{id}/marquer-paye`

**Exemple de requête :**
```http
POST /api/paiements/1/marquer-paye
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "message": "Paiement marqué comme payé",
    "paiement": {
        "id": 1,
        "montant": "50000.00",
        "montantFormatted": "50 000,00 XOF",
        "date": "2024-01-15 10:30:00",
        "commentaire": "Premier paiement",
        "statut": "paye",
        "statutLabel": "Payé",
        "mouvement": {
            "id": 1,
            "type": "dette",
            "typeLabel": "Dette",
            "montantTotal": "200000.00",
            "montantTotalFormatted": "200 000,00 XOF",
            "description": "Prêt bancaire",
            "categorie": {
                "id": 3,
                "nom": "Prêt"
            }
        }
    }
}
```

### 7. Marquer un paiement comme en attente

**Endpoint :** `POST /api/paiements/{id}/marquer-en-attente`

**Exemple de requête :**
```http
POST /api/paiements/1/marquer-en-attente
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "message": "Paiement marqué comme en attente",
    "paiement": {
        "id": 1,
        "montant": "50000.00",
        "montantFormatted": "50 000,00 XOF",
        "date": "2024-01-15 10:30:00",
        "commentaire": "Premier paiement",
        "statut": "en_attente",
        "statutLabel": "En attente",
        "mouvement": {
            "id": 1,
            "type": "dette",
            "typeLabel": "Dette",
            "montantTotal": "200000.00",
            "montantTotalFormatted": "200 000,00 XOF",
            "description": "Prêt bancaire",
            "categorie": {
                "id": 3,
                "nom": "Prêt"
            }
        }
    }
}
```

### 8. Statistiques des paiements

**Endpoint :** `GET /api/paiements/statistiques`

**Paramètres de requête :**
- `debut` (optionnel) : Date de début (YYYY-MM-DD)
- `fin` (optionnel) : Date de fin (YYYY-MM-DD)
- `statut` (optionnel) : Statut du paiement

**Exemple de requête :**
```http
GET /api/paiements/statistiques?debut=2024-01-01&fin=2024-01-31&statut=paye
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "statistiques": {
        "totalPaiements": 150000.00,
        "totalPaiementsFormatted": "150 000,00 XOF",
        "nombreTotal": 3
    },
    "parStatut": {
        "paye": {
            "count": 2,
            "total": 100000.00
        },
        "en_attente": {
            "count": 1,
            "total": 50000.00
        }
    },
    "parMouvement": {
        "dette": {
            "count": 3,
            "total": 150000.00
        }
    }
}
```

### 9. Paiements récents

**Endpoint :** `GET /api/paiements/recents`

**Paramètres de requête :**
- `limit` (optionnel) : Nombre de paiements (défaut: 10)
- `statut` (optionnel) : Statut du paiement

**Exemple de requête :**
```http
GET /api/paiements/recents?limit=5&statut=paye
Authorization: Bearer {token}
```

**Réponse (200 OK) :**
```json
{
    "paiements": [
        {
            "id": 3,
            "montant": "50000.00",
            "montantFormatted": "50 000,00 XOF",
            "date": "2024-01-20 14:30:00",
            "commentaire": "Troisième paiement",
            "statut": "paye",
            "statutLabel": "Payé",
            "mouvement": {
                "id": 1,
                "type": "dette",
                "typeLabel": "Dette",
                "montantTotal": "200000.00",
                "montantTotalFormatted": "200 000,00 XOF",
                "description": "Prêt bancaire",
                "categorie": {
                    "id": 3,
                    "nom": "Prêt"
                }
            }
        }
    ]
}
```

## ⚠️ Codes d'erreur

### Erreurs générales :
- **401** : Non authentifié
- **403** : Accès refusé
- **404** : Paiement ou mouvement non trouvé
- **500** : Erreur serveur

### Erreurs de validation :
- **400** : Données manquantes ou invalides
- **404** : Mouvement non trouvé

## 🎯 Fonctionnalités clés

1. **✅ CRUD complet** : Créer, lire, modifier, supprimer
2. **✅ Gestion des statuts** : Payé, en attente, annulé
3. **✅ Mise à jour automatique** : Le mouvement est mis à jour automatiquement
4. **✅ Statistiques** : Analyses détaillées par statut et type de mouvement
5. **✅ Formatage** : Montants formatés avec la devise de l'utilisateur
6. **✅ Relations** : Liens avec les mouvements et leurs détails
7. **✅ Sécurité** : Chaque utilisateur ne voit que ses paiements
8. **✅ Actions rapides** : Marquer comme payé/en attente en un clic
