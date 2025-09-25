# API Gestion des Transferts

## 📋 Vue d'ensemble

Le système de transferts permet aux utilisateurs de SoldeTrack de transférer des fonds entre leurs différents comptes. Cette fonctionnalité assure la traçabilité complète des mouvements financiers et maintient la cohérence des soldes des comptes.

## 🔗 Endpoints disponibles

### **💰 Transferts**
- `GET /api/transferts` - Liste des transferts
- `POST /api/transferts` - Créer un transfert
- `GET /api/transferts/statistiques` - Statistiques des transferts
- `GET /api/transferts/recents` - Transferts récents

## 🔐 Authentification

- **Type** : Bearer Token (JWT)
- **Obligatoire** : Oui
- **Header** : `Authorization: Bearer {token}`

## 💰 1. GESTION DES TRANSFERTS

### **1.1 LISTER LES TRANSFERTS**

#### **Description**
Récupère la liste paginée des transferts de l'utilisateur avec possibilité de filtrage.

#### **Endpoint**
```
GET /api/transferts
```

#### **Query Parameters**
| Paramètre | Type | Obligatoire | Description |
|-----------|------|--------------|-------------|
| `page` | integer | ❌ Non | Numéro de page (défaut: 1) |
| `limit` | integer | ❌ Non | Nombre d'éléments par page (défaut: 20) |
| `compte_id` | integer | ❌ Non | Filtrer par compte source ou destination |

#### **Exemple de requête**
```http
GET /api/transferts?page=1&limit=10&compte_id=1
Authorization: Bearer {token}
```

#### **Réponse**
```json
{
  "transferts": [
    {
      "id": 1,
      "montant": "50000.00",
      "montantFormatted": "50 000,00 XOF",
      "devise": {
        "id": 1,
        "code": "XOF",
        "nom": "Franc CFA"
      },
      "compteSource": {
        "id": 1,
        "nom": "Compte Principal",
        "description": "Compte bancaire principal",
        "type": "banque",
        "soldeActuel": "450000.00",
        "soldeActuelFormatted": "450 000,00 XOF",
        "devise": {
          "id": 1,
          "code": "XOF",
          "nom": "Franc CFA"
        }
      },
      "compteDestination": {
        "id": 2,
        "nom": "Épargne",
        "description": "Compte d'épargne",
        "type": "epargne",
        "soldeActuel": "150000.00",
        "soldeActuelFormatted": "150 000,00 XOF",
        "devise": {
          "id": 1,
          "code": "XOF",
          "nom": "Franc CFA"
        }
      },
      "date": "2024-01-15 14:30:00",
      "note": "Transfert mensuel",
      "description": "Transfert de 50 000 XOF du Compte Principal vers Épargne"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 25,
    "pages": 3
  }
}
```

### **1.2 CRÉER UN TRANSFERT**

#### **Description**
Effectue un transfert entre deux comptes de l'utilisateur avec mise à jour automatique des soldes.

#### **Endpoint**
```
POST /api/transferts
```

#### **Body Parameters**
| Paramètre | Type | Obligatoire | Description |
|-----------|------|--------------|-------------|
| `compte_source_id` | integer | ✅ Oui | ID du compte source |
| `compte_destination_id` | integer | ✅ Oui | ID du compte destination |
| `montant` | number | ✅ Oui | Montant à transférer |
| `note` | string | ❌ Non | Note descriptive du transfert |

#### **Exemple de requête**
```http
POST /api/transferts
Authorization: Bearer {token}
Content-Type: application/json

{
  "compte_source_id": 1,
  "compte_destination_id": 2,
  "montant": 50000,
  "note": "Transfert mensuel vers épargne"
}
```

#### **Réponse**
```json
{
  "message": "Transfert effectué avec succès",
  "transfert": {
    "id": 1,
    "montant": "50000.00",
    "montantFormatted": "50 000,00 XOF",
    "compteSource": {
      "id": 1,
      "nom": "Compte Principal",
      "nouveauSolde": "450000.00"
    },
    "compteDestination": {
      "id": 2,
      "nom": "Épargne",
      "nouveauSolde": "150000.00"
    },
    "description": "Transfert de 50 000 XOF du Compte Principal vers Épargne"
  }
}
```

### **1.3 STATISTIQUES DES TRANSFERTS**

#### **Description**
Récupère les statistiques globales des transferts de l'utilisateur.

#### **Endpoint**
```
GET /api/transferts/statistiques
```

#### **Exemple de requête**
```http
GET /api/transferts/statistiques
Authorization: Bearer {token}
```

#### **Réponse**
```json
{
  "totalTransferts": 15,
  "montantTotal": "750000.00",
  "montantTotalFormatted": "750 000,00 XOF",
  "moyenneTransfert": "50000.00",
  "moyenneTransfertFormatted": "50 000,00 XOF",
  "transfertMax": "200000.00",
  "transfertMaxFormatted": "200 000,00 XOF",
  "transfertMin": "5000.00",
  "transfertMinFormatted": "5 000,00 XOF",
  "devise": "XOF"
}
```

### **1.4 TRANSFERTS RÉCENTS**

#### **Description**
Récupère les transferts les plus récents de l'utilisateur.

#### **Endpoint**
```
GET /api/transferts/recents
```

#### **Query Parameters**
| Paramètre | Type | Obligatoire | Description |
|-----------|------|--------------|-------------|
| `limit` | integer | ❌ Non | Nombre de transferts à récupérer (défaut: 5) |

#### **Exemple de requête**
```http
GET /api/transferts/recents?limit=10
Authorization: Bearer {token}
```

#### **Réponse**
```json
{
  "transferts": [
    {
      "id": 3,
      "montant": "25000.00",
      "montantFormatted": "25 000,00 XOF",
      "compteSource": {
        "id": 1,
        "nom": "Compte Principal"
      },
      "compteDestination": {
        "id": 3,
        "nom": "Compte Business"
      },
      "date": "2024-01-20 10:15:00",
      "note": "Transfert business"
    }
  ],
  "total": 3
}
```

## ⚠️ Gestion des erreurs

### **Codes d'erreur courants**

| Code | Message | Description |
|------|---------|-------------|
| `400` | `Données manquantes` | Paramètres requis manquants |
| `400` | `Compte source non trouvé` | Compte source inexistant |
| `400` | `Compte destination non trouvé` | Compte destination inexistant |
| `400` | `Solde insuffisant` | Montant supérieur au solde disponible |
| `400` | `Comptes identiques` | Source et destination identiques |
| `401` | `Non authentifié` | Token manquant ou invalide |
| `404` | `Compte non trouvé` | Compte n'appartient pas à l'utilisateur |

### **Exemple d'erreur**
```json
{
  "error": "Solde insuffisant",
  "message": "Le solde du compte source (45 000 XOF) est insuffisant pour effectuer un transfert de 50 000 XOF"
}
```

## 🔒 Règles de sécurité

### **Validation des transferts**
- ✅ Vérification de la propriété des comptes
- ✅ Validation du solde suffisant
- ✅ Interdiction des transferts vers le même compte
- ✅ Montant strictement positif
- ✅ Authentification obligatoire

### **Mise à jour des soldes**
- ✅ Débit automatique du compte source
- ✅ Crédit automatique du compte destination
- ✅ Traçabilité complète des mouvements
- ✅ Cohérence des données garantie

## 📊 Cas d'usage

### **Transfert mensuel vers épargne**
```json
{
  "compte_source_id": 1,
  "compte_destination_id": 2,
  "montant": 100000,
  "note": "Épargne mensuelle"
}
```

### **Transfert entre comptes business**
```json
{
  "compte_source_id": 3,
  "compte_destination_id": 4,
  "montant": 250000,
  "note": "Réorganisation des fonds"
}
```


## 🚀 Intégration

### **Headers requis**
```http
Authorization: Bearer {jwt_token}
Content-Type: application/json
Accept: application/json
```

### **Exemple d'intégration JavaScript**
```javascript
// Créer un transfert
const createTransfert = async (compteSource, compteDestination, montant, note) => {
  const response = await fetch('/api/transferts', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      compte_source_id: compteSource,
      compte_destination_id: compteDestination,
      montant: montant,
      note: note
    })
  });
  
  return await response.json();
};

```

---

**📝 Note** : Tous les montants sont enregistrés avec 2 décimales et les devises sont gérées automatiquement selon les préférences de l'utilisateur.
