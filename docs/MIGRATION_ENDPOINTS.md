# 🔄 MIGRATION DES ENDPOINTS - NOUVELLE ARCHITECTURE

## 📋 Vue d'ensemble

L'ancien `MouvementController.php` a été supprimé et remplacé par une architecture modulaire avec des contrôleurs spécialisés.

## 🚀 NOUVEAUX ENDPOINTS

### **1. 💰 GESTION DES DETTES - `/api/dettes`**

| Méthode | Endpoint | Description | Ancien Endpoint |
|---------|----------|-------------|-----------------|
| `POST` | `/api/dettes` | Créer une dette | `POST /api/mouvements/dettes` |
| `GET` | `/api/dettes` | Lister les dettes | `GET /api/mouvements?type=dette` |
| `GET` | `/api/dettes/{id}` | Détails d'une dette | `GET /api/mouvements/{id}` |
| `PUT` | `/api/dettes/{id}` | Modifier une dette | `PUT /api/mouvements/{id}` |
| `DELETE` | `/api/dettes/{id}` | Supprimer une dette | `DELETE /api/mouvements/{id}` |
| `POST` | `/api/dettes/{id}/paiements` | Enregistrer un paiement | `POST /api/paiements` |
| `GET` | `/api/dettes/{id}/paiements` | Historique des paiements | `GET /api/paiements/mouvement/{id}` |
| `GET` | `/api/dettes/en-retard` | Dettes en retard | `GET /api/mouvements?statut=en_retard` |
| `GET` | `/api/dettes/echeance-proche` | Échéances proches | `GET /api/mouvements?echeance_proche=true` |
| `GET` | `/api/dettes/resume` | Résumé des dettes | `GET /api/mouvements/debt-balance` |

### **2. 📊 MOUVEMENTS UNIFIÉS - `/api/mouvements-unifies`**

| Méthode | Endpoint | Description | Ancien Endpoint |
|---------|----------|-------------|-----------------|
| `POST` | `/api/mouvements-unifies/sortie` | Créer une dépense | `POST /api/mouvements/sorties` |
| `POST` | `/api/mouvements-unifies/entree` | Créer une entrée | `POST /api/mouvements/entrees` |
| `POST` | `/api/mouvements-unifies/don` | Créer un don | `POST /api/mouvements/dons` |
| `GET` | `/api/mouvements-unifies` | Lister les mouvements | `GET /api/mouvements` |
| `GET` | `/api/mouvements-unifies/{id}` | Détails d'un mouvement | `GET /api/mouvements/{id}` |
| `PUT` | `/api/mouvements-unifies/{id}` | Modifier un mouvement | `PUT /api/mouvements/{id}` |
| `DELETE` | `/api/mouvements-unifies/{id}` | Supprimer un mouvement | `DELETE /api/mouvements/{id}` |
| `GET` | `/api/mouvements-unifies/recents` | Mouvements récents | `GET /api/mouvements/recents` |

## 🔄 MIGRATION DES DONNÉES

### **Changements dans les paramètres :**

#### **Création de dettes :**
```json
// ANCIEN (MouvementController)
{
  "montantTotal": "20000",
  "categorie_id": 1,
  "type_dette": "dette_a_recevoir",
  "deja_depense": false,
  "echeance": "2024-12-31",
  "taux": "5"
}

// NOUVEAU (DetteController)
{
  "montantPrincipal": "20000",
  "categorie_id": 1,
  "typeDette": "pret",
  "tauxInteret": "5",
  "dateEcheance": "2024-12-31",
  "typeCalculInteret": "simple"
}
```

#### **Création de mouvements :**
```json
// ANCIEN (MouvementController)
{
  "montantTotal": "5000",
  "categorie_id": 1,
  "description": "Courses",
  "lieu": "Supermarché"
}

// NOUVEAU (MouvementUnifieController)
{
  "montantTotal": "5000",
  "categorie_id": 1,
  "description": "Courses",
  "lieu": "Supermarché"
}
```

## 📝 CHANGEMENTS DANS LES RÉPONSES

### **Format de réponse standardisé :**
```json
// NOUVEAU FORMAT
{
  "success": true,
  "message": "Opération réussie",
  "data": {
    // Données de la réponse
  }
}

// EN CAS D'ERREUR
{
  "success": false,
  "erreur": "Message d'erreur",
  "details": {
    // Détails de l'erreur
  }
}
```

## 🛠️ ACTIONS REQUISES POUR LE FRONTEND

### **1. Mise à jour des URLs :**
- Remplacer `/api/mouvements/dettes` par `/api/dettes`
- Remplacer `/api/mouvements` par `/api/mouvements-unifies`
- Mettre à jour les endpoints de paiements

### **2. Mise à jour des modèles :**
- Adapter les modèles pour les nouveaux champs des dettes
- Utiliser les nouveaux types de dettes (`pret`, `emprunt`, `creance`)

### **3. Mise à jour des services :**
- Modifier `DetteService` pour utiliser les nouveaux endpoints
- Adapter `MouvementService` pour les mouvements unifiés

## ⚠️ POINTS D'ATTENTION

1. **Compatibilité** : Les anciens endpoints ne fonctionnent plus
2. **Validation** : Nouvelle validation plus stricte
3. **Messages** : Tous les messages sont maintenant en français
4. **Format** : Réponses standardisées avec `success` et `erreur`

## 🚀 AVANTAGES DE LA NOUVELLE ARCHITECTURE

- ✅ **Code plus propre** et maintenable
- ✅ **Validation robuste** des données
- ✅ **Messages d'erreur cohérents** en français
- ✅ **Séparation des responsabilités** claire
- ✅ **API RESTful** standardisée
- ✅ **Gestion d'erreurs améliorée**
- ✅ **Performance optimisée**

## 📞 SUPPORT

En cas de problème avec la migration, vérifiez :
1. Les nouveaux endpoints sont bien utilisés
2. Les paramètres sont au bon format
3. Les réponses sont gérées avec le nouveau format
4. Les services frontend sont mis à jour
