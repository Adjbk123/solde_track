# API Gestion des Dettes et Prêts

## 📋 Vue d'ensemble

Le système de gestion des dettes et prêts couvre deux scénarios distincts selon le moment où vous enregistrez la transaction par rapport à l'utilisation réelle de l'argent.

## 🔗 Endpoints disponibles

### **💰 Dettes et Prêts**
- `POST /api/mouvements/dettes` - Créer une dette/prêt
- `GET /api/mouvements/debt-balance` - Solde des dettes
- `GET /api/mouvements/debt-categories/{type}` - Catégories compatibles

## 🔐 Authentification

- **Type** : Bearer Token (JWT)
- **Obligatoire** : Oui
- **Header** : `Authorization: Bearer {token}`

## 💰 1. GESTION DES DETTES ET PRÊTS

### **1.1 CRÉER UNE DETTE/PRÊT**

#### **Description**
Crée une dette ou un prêt avec gestion intelligente selon le scénario d'utilisation.

#### **Endpoint**
```
POST /api/mouvements/dettes
```

#### **Body Parameters**
| Paramètre | Type | Obligatoire | Description |
|-----------|------|--------------|-------------|
| `montantTotal` | number | ✅ Oui | Montant de la dette/prêt |
| `categorie_id` | integer | ✅ Oui | ID de la catégorie |
| `type_dette` | string | ❌ Non | Type: `dette_a_recevoir` (prêt) ou `dette_a_payer` (dette) |
| `deja_depense` | boolean | ❌ Non | Si l'argent est déjà traité (défaut: false) |
| `taux` | number | ❌ Non | Taux d'intérêt en % |
| `echeance` | string | ❌ Non | Date d'échéance (YYYY-MM-DD) |
| `contact_id` | integer | ❌ Non | ID du contact |
| `compte_id` | integer | ❌ Non | ID du compte |
| `description` | string | ❌ Non | Description |

## 📊 2. LES QUATRE SCÉNARIOS

### **🟢 SCÉNARIO 1 : Prêt immédiat (on vous doit)**

#### **Situation :** "Je viens de prêter 20 000 F à Jean, je l'encaisse maintenant"

#### **Utilisation :**
```json
{
  "montantTotal": "20000",
  "categorie_id": 1,
  "compte_id": 1,
  "contact_id": 2,
  "type_dette": "dette_a_recevoir",
  "taux": "5",
  "echeance": "2024-12-31",
  "description": "Prêt à Jean",
  "deja_depense": false
}
```

#### **Résultat :**
- ✅ **Compte crédité** de 20 000 F
- ✅ **Argent disponible** immédiatement
- ✅ **Peut être dépensé** normalement

---

### **🟡 SCÉNARIO 2 : Prêt rétroactif (on vous doit)**

#### **Situation :** "J'ai déjà prêté l'argent, je l'enregistre maintenant pour savoir qui me doit"

#### **Utilisation :**
```json
{
  "montantTotal": "20000",
  "categorie_id": 1,
  "compte_id": 1,
  "contact_id": 2,
  "type_dette": "dette_a_recevoir",
  "taux": "5",
  "echeance": "2024-12-31",
  "description": "Prêt à Jean (déjà fait)",
  "deja_depense": true
}
```

#### **Résultat :**
- ❌ **Compte NON crédité** (argent déjà dépensé)
- ✅ **Dette enregistrée** pour suivi

---

### **🔴 SCÉNARIO 3 : Emprunt immédiat (vous devez)**

#### **Situation :** "Je viens d'emprunter 20 000 F à la banque, je l'encaisse maintenant"

#### **Utilisation :**
```json
{
  "montantTotal": "20000",
  "categorie_id": 1,
  "compte_id": 1,
  "contact_id": 3,
  "type_dette": "dette_a_payer",
  "taux": "6",
  "echeance": "2024-12-31",
  "description": "Emprunt bancaire",
  "deja_depense": false
}
```

#### **Résultat :**
- ✅ **Compte débité** de 20 000 F
- ✅ **Argent disponible** immédiatement
- ✅ **Dette enregistrée** pour remboursement

---

### **🟠 SCÉNARIO 4 : Emprunt rétroactif (vous devez)**

#### **Situation :** "J'ai déjà emprunté l'argent, je l'enregistre maintenant pour savoir qui je dois"

#### **Utilisation :**
```json
{
  "montantTotal": "20000",
  "categorie_id": 1,
  "compte_id": 1,
  "contact_id": 3,
  "type_dette": "dette_a_payer",
  "taux": "6",
  "echeance": "2024-12-31",
  "description": "Emprunt bancaire (déjà fait)",
  "deja_depense": true
}
```

#### **Résultat :**
- ❌ **Compte NON débité** (argent déjà dépensé)
- ✅ **Dette enregistrée** pour remboursement

## 🔄 3. GESTION DES REMBOURSEMENTS

### **3.1 Remboursement partiel**

#### **Situation :** Le débiteur rembourse 10 000 F sur 20 000 F

#### **Processus automatique :**
1. **Créer un paiement** de 10 000 F
2. **Montant restant** : 10 000 F
3. **Statut** : "partiellement_paye"
4. **Intérêts** : Recalculés sur 10 000 F

### **3.2 Remboursement complet**

#### **Situation :** Le débiteur rembourse les 20 000 F restants

#### **Processus automatique :**
1. **Créer un paiement** de 20 000 F
2. **Montant restant** : 0 F
3. **Statut** : "paye"
4. **Dette** : Clôturée

## 📈 4. CALCUL DES INTÉRÊTS

### **Formule :**
```
Intérêts = (Montant restant × Taux) / 100
```

### **Exemple :**
- **Dette** : 50 000 F à 3%
- **Remboursement** : 20 000 F
- **Montant restant** : 30 000 F
- **Intérêts** : (30 000 × 3) / 100 = 900 F

## 🎯 5. CAS D'USAGE PRATIQUES

### **5.1 Prêt immédiat**
```json
{
  "montantTotal": "50000",
  "categorie_id": 1,
  "compte_id": 1,
  "contact_id": 3,
  "taux": "2",
  "echeance": "2024-06-30",
  "description": "Prêt urgent à Marie",
  "deja_depense": false
}
```

### **5.2 Enregistrement rétroactif**
```json
{
  "montantTotal": "30000",
  "categorie_id": 1,
  "compte_id": 1,
  "contact_id": 4,
  "taux": "4",
  "echeance": "2024-08-15",
  "description": "Prêt à Paul (déjà dépensé)",
  "deja_depense": true
}
```

### **5.3 Dette à payer**
```json
{
  "montantTotal": "15000",
  "categorie_id": 2,
  "compte_id": 1,
  "contact_id": 5,
  "taux": "6",
  "echeance": "2024-05-20",
  "description": "Emprunt à la banque",
  "deja_depense": false
}
```

## ⚠️ Gestion des erreurs

### **Codes d'erreur courants**

| Code | Message | Description |
|------|---------|-------------|
| `400` | `Données manquantes` | Paramètres requis manquants |
| `400` | `Catégorie non trouvée` | Catégorie inexistante |
| `400` | `Compte non trouvé` | Compte inexistant |
| `401` | `Non authentifié` | Token manquant ou invalide |

## 🔒 Règles de sécurité

### **Validation des dettes**
- ✅ Vérification de la propriété des comptes
- ✅ Validation du montant positif
- ✅ Gestion des taux d'intérêt
- ✅ Suivi des échéances
- ✅ Authentification obligatoire

### **Gestion des soldes**
- ✅ Crédit automatique si `deja_depense = false`
- ✅ Pas de crédit si `deja_depense = true`
- ✅ Suivi des remboursements
- ✅ Calcul automatique des intérêts

## 🚀 Intégration

### **Headers requis**
```http
Authorization: Bearer {jwt_token}
Content-Type: application/json
Accept: application/json
```

### **Exemple d'intégration JavaScript**
```javascript
// Créer un prêt avec encaissement immédiat
const creerPretImmediat = async (montant, contactId, compteId) => {
  const response = await fetch('/api/mouvements/dettes', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      montantTotal: montant,
      categorie_id: 1,
      compte_id: compteId,
      contact_id: contactId,
      taux: 5,
      echeance: '2024-12-31',
      description: 'Prêt immédiat',
      deja_depense: false
    })
  });
  
  return await response.json();
};

// Enregistrer un prêt déjà dépensé
const enregistrerPretRetroactif = async (montant, contactId, compteId) => {
  const response = await fetch('/api/mouvements/dettes', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      montantTotal: montant,
      categorie_id: 1,
      compte_id: compteId,
      contact_id: contactId,
      taux: 5,
      echeance: '2024-12-31',
      description: 'Prêt déjà dépensé',
      deja_depense: true
    })
  });
  
  return await response.json();
};
```

---

**📝 Note** : Le paramètre `deja_depense` est crucial pour distinguer les deux scénarios et éviter de fausser les soldes des comptes.
