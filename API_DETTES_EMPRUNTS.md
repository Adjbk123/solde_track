# API Dettes & Emprunts - SoldeTrack

## 💳 Gestion des Dettes et Emprunts

L'API des dettes permet de gérer tous les types de dettes et emprunts : dettes à payer, dettes à recevoir, prêts, crédits, etc.

## 🎯 Types de Dettes

### **1. Dettes à Payer**
- **Prêt bancaire** : Emprunt auprès d'une banque
- **Crédit consommation** : Achat à crédit
- **Dette personnelle** : Argent dû à un ami/famille
- **Facture impayée** : Facture en attente de paiement

### **2. Dettes à Recevoir**
- **Prêt accordé** : Argent prêté à quelqu'un
- **Vente à crédit** : Vente avec paiement différé
- **Créance** : Argent dû par un client

## 📋 Endpoints disponibles

### 1. Créer une dette (`POST /api/mouvements/dettes`)

#### **Requête :**
```http
POST /api/mouvements/dettes
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "500000.00",
    "categorie_id": 3,
    "description": "Prêt bancaire pour achat voiture",
    "echeance": "2024-12-31",
    "taux": "5.5",
    "projet_id": 1,
    "contact_id": 2,
    "compte_id": 1,
    "date": "2024-01-10"
}
```

#### **Paramètres :**
- `montantTotal` (requis) : Montant total de la dette
- `categorie_id` (requis) : ID de la catégorie
- `description` (optionnel) : Description de la dette
- `echeance` (optionnel) : Date d'échéance (YYYY-MM-DD)
- `taux` (optionnel) : Taux d'intérêt en pourcentage
- `projet_id` (optionnel) : ID du projet associé
- `contact_id` (optionnel) : ID du contact (créancier/débiteur)
- `compte_id` (optionnel) : ID du compte
- `date` (optionnel) : Date de création (défaut: aujourd'hui)

#### **Réponse (201 Created) :**
```json
{
    "message": "Dette créée avec succès",
    "mouvement": {
        "id": 1,
        "type": "dette_a_payer",
        "typeLabel": "Dette à payer",
        "montantTotal": "500000.00",
        "montantTotalFormatted": "500 000,00 XOF",
        "montantEffectif": "0.00",
        "montantEffectifFormatted": "0,00 XOF",
        "montantRestant": "500000.00",
        "montantRestantFormatted": "500 000,00 XOF",
        "statut": "non_paye",
        "statutLabel": "Non payé",
        "date": "2024-01-10 10:30:00",
        "description": "Prêt bancaire pour achat voiture",
        "echeance": "2024-12-31",
        "taux": "5.5",
        "montantRest": "500000.00",
        "montantInterets": "27500.00",
        "categorie": {
            "id": 3,
            "nom": "Prêt",
            "type": "dette"
        },
        "projet": {
            "id": 1,
            "nom": "Achat Voiture"
        },
        "contact": {
            "id": 2,
            "nom": "Banque ABC",
            "telephone": "+221 33 123 45 67"
        },
        "compte": {
            "id": 1,
            "nom": "Compte Principal",
            "type": "compte_principal"
        }
    }
}
```

### 2. Lister les dettes (`GET /api/mouvements?type=dette`)

#### **Requête :**
```http
GET /api/mouvements?type=dette_a_payer&statut=non_paye&page=1&limit=20
Authorization: Bearer {token}
```

#### **Paramètres de requête :**
- `type` (optionnel) : `dette_a_payer` ou `dette_a_recevoir`
- `statut` (optionnel) : `non_paye`, `partiellement_paye`, `paye`
- `projet_id` (optionnel) : ID du projet
- `contact_id` (optionnel) : ID du contact
- `page` (optionnel) : Numéro de page (défaut: 1)
- `limit` (optionnel) : Nombre d'éléments par page (défaut: 20)

#### **Réponse (200 OK) :**
```json
{
    "mouvements": [
        {
            "id": 1,
            "type": "dette_a_payer",
            "typeLabel": "Dette à payer",
            "montantTotal": "500000.00",
            "montantTotalFormatted": "500 000,00 XOF",
            "montantEffectif": "100000.00",
            "montantEffectifFormatted": "100 000,00 XOF",
            "montantRestant": "400000.00",
            "montantRestantFormatted": "400 000,00 XOF",
            "statut": "partiellement_paye",
            "statutLabel": "Partiellement payé",
            "date": "2024-01-10 10:30:00",
            "description": "Prêt bancaire pour achat voiture",
            "echeance": "2024-12-31",
            "taux": "5.5",
            "montantRest": "400000.00",
            "montantInterets": "22000.00",
            "categorie": {
                "id": 3,
                "nom": "Prêt",
                "type": "dette"
            },
            "projet": {
                "id": 1,
                "nom": "Achat Voiture"
            },
            "contact": {
                "id": 2,
                "nom": "Banque ABC",
                "telephone": "+221 33 123 45 67"
            }
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 20,
        "total": 1,
        "pages": 1
    }
}
```

### 3. Dettes en retard (`GET /api/dashboard/dettes/retard`)

#### **Requête :**
```http
GET /api/dashboard/dettes/retard
Authorization: Bearer {token}
```

#### **Réponse (200 OK) :**
```json
{
    "dettes": [
        {
            "id": 1,
            "montantTotal": "500000.00",
            "montantRest": "400000.00",
            "echeance": "2024-01-15",
            "taux": "5.5",
            "montantInterets": "22000.00",
            "description": "Prêt bancaire pour achat voiture",
            "contact": "Banque ABC",
            "joursRetard": 5
        }
    ],
    "total": 1
}
```

### 4. Modifier une dette (`PUT /api/mouvements/{id}`)

#### **Requête :**
```http
PUT /api/mouvements/1
Authorization: Bearer {token}
Content-Type: application/json

{
    "description": "Prêt bancaire pour achat voiture - Modifié",
    "echeance": "2025-01-31",
    "taux": "6.0"
}
```

#### **Réponse (200 OK) :**
```json
{
    "message": "Mouvement mis à jour avec succès",
    "mouvement": {
        "id": 1,
        "type": "dette_a_payer",
        "description": "Prêt bancaire pour achat voiture - Modifié",
        "echeance": "2025-01-31",
        "taux": "6.0",
        "montantInterets": "24000.00"
    }
}
```

### 5. Supprimer une dette (`DELETE /api/mouvements/{id}`)

#### **Requête :**
```http
DELETE /api/mouvements/1
Authorization: Bearer {token}
```

#### **Réponse (200 OK) :**
```json
{
    "message": "Mouvement supprimé avec succès"
}
```

## 💰 Gestion des Paiements Échelonnés

### 1. Créer un paiement (`POST /api/paiements`)

#### **Requête :**
```http
POST /api/paiements
Authorization: Bearer {token}
Content-Type: application/json

{
    "mouvement_id": 1,
    "montant": "50000.00",
    "date": "2024-01-20",
    "commentaire": "Premier paiement mensuel"
}
```

#### **Réponse (201 Created) :**
```json
{
    "message": "Paiement créé avec succès",
    "paiement": {
        "id": 1,
        "montant": "50000.00",
        "montantFormatted": "50 000,00 XOF",
        "date": "2024-01-20 14:30:00",
        "commentaire": "Premier paiement mensuel",
        "statut": "effectue",
        "statutLabel": "Effectué",
        "mouvement": {
            "id": 1,
            "type": "dette_a_payer",
            "montantRestant": "450000.00",
            "montantRestantFormatted": "450 000,00 XOF"
        }
    }
}
```

### 2. Marquer un paiement comme payé (`POST /api/paiements/{id}/marquer-paye`)

#### **Requête :**
```http
POST /api/paiements/1/marquer-paye
Authorization: Bearer {token}
```

#### **Réponse (200 OK) :**
```json
{
    "message": "Paiement marqué comme payé",
    "paiement": {
        "id": 1,
        "statut": "effectue",
        "statutLabel": "Effectué"
    }
}
```

## 📊 Statistiques des Dettes

### 1. Statistiques générales (`GET /api/mouvements/statistiques?type=dette`)

#### **Requête :**
```http
GET /api/mouvements/statistiques?type=dette_a_payer&debut=2024-01-01&fin=2024-12-31
Authorization: Bearer {token}
```

#### **Réponse (200 OK) :**
```json
{
    "periode": {
        "debut": "2024-01-01",
        "fin": "2024-12-31"
    },
    "statistiques": {
        "totalMontant": "1000000.00",
        "totalMontantFormatted": "1 000 000,00 XOF",
        "totalMontantPaye": "200000.00",
        "totalMontantPayeFormatted": "200 000,00 XOF",
        "totalMontantRestant": "800000.00",
        "totalMontantRestantFormatted": "800 000,00 XOF",
        "totalInterets": "44000.00",
        "totalInteretsFormatted": "44 000,00 XOF",
        "nombreDettes": 3,
        "nombreDettesPayees": 0,
        "nombreDettesEnRetard": 1
    }
}
```

## 🎯 Cas d'Usage Pratiques

### **Exemple 1 : Prêt Bancaire**
```json
{
    "montantTotal": "2000000.00",
    "categorie_id": 3,
    "description": "Prêt immobilier",
    "echeance": "2034-01-10",
    "taux": "4.5",
    "contact_id": 1,
    "compte_id": 1
}
```
- **Montant** : 2 000 000 XOF
- **Durée** : 10 ans
- **Taux** : 4.5% par an
- **Échéance** : 10 janvier 2034

### **Exemple 2 : Crédit Consommation**
```json
{
    "montantTotal": "500000.00",
    "categorie_id": 4,
    "description": "Achat électroménager à crédit",
    "echeance": "2024-12-31",
    "taux": "12.0",
    "contact_id": 2,
    "compte_id": 1
}
```
- **Montant** : 500 000 XOF
- **Durée** : 1 an
- **Taux** : 12% par an
- **Échéance** : 31 décembre 2024

### **Exemple 3 : Dette Personnelle**
```json
{
    "montantTotal": "100000.00",
    "categorie_id": 5,
    "description": "Argent prêté à un ami",
    "echeance": "2024-06-30",
    "taux": null,
    "contact_id": 3,
    "compte_id": 1
}
```
- **Montant** : 100 000 XOF
- **Sans intérêts** : taux = null
- **Échéance** : 30 juin 2024


## 🎨 Interface Suggérée pour le Frontend

### **Liste des Dettes**
```
┌─────────────────────────────────┐
│ 💳 Mes Dettes                   │
├─────────────────────────────────┤
│ 🏦 Prêt Bancaire                │
│ Montant: 500 000 XOF            │
│ Restant: 400 000 XOF (80%)      │
│ Échéance: 31/12/2024            │
│ Taux: 5.5% | Intérêts: 22k XOF  │
│                                 │
│ ⚠️ Crédit Consommation          │
│ Montant: 200 000 XOF            │
│ Restant: 200 000 XOF (100%)     │
│ Échéance: 15/01/2024 (RETARD)   │
│ Taux: 12% | Intérêts: 24k XOF   │
└─────────────────────────────────┘
```

### **Créer une Dette**
```
┌─────────────────────────────────┐
│ 💳 Nouvelle Dette               │
├─────────────────────────────────┤
│ Montant: [500000.00]            │
│ Catégorie: [Prêt ▼]             │
│ Description: [Prêt voiture]     │
│                                 │
│ 📅 Échéance: [31/12/2024]       │
│ 📊 Taux: [5.5] %                │
│                                 │
│ 👤 Contact: [Banque ABC ▼]      │
│ 🏗️ Projet: [Achat Voiture ▼]   │
│                                 │
│ [Créer la dette]                │
└─────────────────────────────────┘
```

## ⚠️ Codes d'erreur

### Erreurs générales :
- **401** : Non authentifié
- **403** : Accès refusé
- **404** : Dette non trouvée
- **500** : Erreur serveur

### Erreurs de validation :
- **400** : Données manquantes ou invalides
- **400** : Montant invalide
- **400** : Date d'échéance invalide
- **400** : Taux d'intérêt invalide

## 🎯 Fonctionnalités clés

1. **✅ Types de dettes** : Dettes à payer et à recevoir
2. **✅ Calculs automatiques** : Intérêts, montants restants
3. **✅ Gestion des échéances** : Alertes de retard
4. **✅ Paiements échelonnés** : Suivi des remboursements
5. **✅ Association projets** : Lier les dettes aux projets
6. **✅ Contacts** : Gérer créanciers et débiteurs
7. **✅ Statistiques** : Analyses complètes des dettes
8. **✅ Formatage** : Montants formatés avec devise

**L'API des dettes et emprunts est complètement documentée et prête pour le développement frontend !** 🚀💳
