# API Projets - SoldeTrack

## 🎯 Gestion des Projets

L'API des projets permet de gérer les projets financiers personnels avec suivi des budgets et des mouvements associés.

## 📋 Endpoints disponibles

### 1. Lister les projets (`GET /api/projets`)

#### **Requête :**
```http
GET /api/projets?page=1&limit=20&search=maison
Authorization: Bearer {token}
```

#### **Paramètres de requête :**
- `page` (optionnel) : Numéro de page (défaut: 1)
- `limit` (optionnel) : Nombre d'éléments par page (défaut: 20)
- `search` (optionnel) : Recherche dans le nom et la description

#### **Réponse (200 OK) :**
```json
{
    "projets": [
        {
            "id": 1,
            "nom": "Achat Maison",
            "description": "Projet d'achat d'une maison familiale",
            "budgetPrevu": "50000000.00",
            "budgetPrevuFormatted": "50 000 000,00 XOF",
            "dateCreation": "2024-01-15 10:30:00",
            "nombreMouvements": 15
        },
        {
            "id": 2,
            "nom": "Voyage Europe",
            "description": "Voyage de 2 semaines en Europe",
            "budgetPrevu": "2000000.00",
            "budgetPrevuFormatted": "2 000 000,00 XOF",
            "dateCreation": "2024-01-10 14:20:00",
            "nombreMouvements": 8
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 20,
        "total": 2,
        "pages": 1
    }
}
```

### 2. Voir un projet (`GET /api/projets/{id}`)

#### **Requête :**
```http
GET /api/projets/1
Authorization: Bearer {token}
```

#### **Réponse (200 OK) :**
```json
{
    "projet": {
        "id": 1,
        "nom": "Achat Maison",
        "description": "Projet d'achat d'une maison familiale",
        "budgetPrevu": "50000000.00",
        "budgetPrevuFormatted": "50 000 000,00 XOF",
        "dateCreation": "2024-01-15 10:30:00",
        "nombreMouvements": 15,
        "statistiques": {
            "totalDepenses": "5000000.00",
            "totalDepensesFormatted": "5 000 000,00 XOF",
            "totalEntrees": "8000000.00",
            "totalEntreesFormatted": "8 000 000,00 XOF",
            "soldeActuel": "3000000.00",
            "soldeActuelFormatted": "3 000 000,00 XOF"
        }
    }
}
```

### 3. Créer un projet (`POST /api/projets`)

#### **Requête :**
```http
POST /api/projets
Authorization: Bearer {token}
Content-Type: application/json

{
    "nom": "Achat Voiture",
    "description": "Achat d'une voiture d'occasion",
    "budgetPrevu": "3000000.00"
}
```

#### **Réponse (201 Created) :**
```json
{
    "message": "Projet créé avec succès",
    "projet": {
        "id": 3,
        "nom": "Achat Voiture",
        "description": "Achat d'une voiture d'occasion",
        "budgetPrevu": "3000000.00",
        "budgetPrevuFormatted": "3 000 000,00 XOF",
        "dateCreation": "2024-01-20 16:45:00"
    }
}
```

### 4. Modifier un projet (`PUT /api/projets/{id}`)

#### **Requête :**
```http
PUT /api/projets/3
Authorization: Bearer {token}
Content-Type: application/json

{
    "nom": "Achat Voiture Neuve",
    "description": "Achat d'une voiture neuve",
    "budgetPrevu": "5000000.00"
}
```

#### **Réponse (200 OK) :**
```json
{
    "message": "Projet mis à jour avec succès",
    "projet": {
        "id": 3,
        "nom": "Achat Voiture Neuve",
        "description": "Achat d'une voiture neuve",
        "budgetPrevu": "5000000.00",
        "budgetPrevuFormatted": "5 000 000,00 XOF",
        "dateCreation": "2024-01-20 16:45:00"
    }
}
```

### 5. Supprimer un projet (`DELETE /api/projets/{id}`)

#### **Requête :**
```http
DELETE /api/projets/3
Authorization: Bearer {token}
```

#### **Réponse (200 OK) :**
```json
{
    "message": "Projet supprimé avec succès"
}
```

#### **Erreur si des mouvements existent (400 Bad Request) :**
```json
{
    "error": "Impossible de supprimer",
    "message": "Ce projet contient des mouvements"
}
```

### 6. Statistiques d'un projet (`GET /api/projets/{id}/statistiques`)

#### **Requête :**
```http
GET /api/projets/1/statistiques
Authorization: Bearer {token}
```

#### **Réponse (200 OK) :**
```json
{
    "projet": {
        "id": 1,
        "nom": "Achat Maison",
        "description": "Projet d'achat d'une maison familiale",
        "budgetPrevu": "50000000.00",
        "budgetPrevuFormatted": "50 000 000,00 XOF",
        "dateCreation": "2024-01-15 10:30:00",
        "nombreMouvements": 15
    },
    "statistiques": {
        "budgetPrevu": 50000000.00,
        "budgetPrevuFormatted": "50 000 000,00 XOF",
        "totalDepenses": 5000000.00,
        "totalDepensesFormatted": "5 000 000,00 XOF",
        "totalEntrees": 8000000.00,
        "totalEntreesFormatted": "8 000 000,00 XOF",
        "totalDettes": 2000000.00,
        "totalDettesFormatted": "2 000 000,00 XOF",
        "totalDons": 500000.00,
        "totalDonsFormatted": "500 000,00 XOF",
        "soldeActuel": 3000000.00,
        "soldeActuelFormatted": "3 000 000,00 XOF",
        "pourcentageUtilise": 10.0,
        "nombreMouvements": 15,
        "derniereActivite": "2024-01-20 14:30:00"
    }
}
```

### 7. Mouvements d'un projet (`GET /api/projets/{id}/mouvements`)

#### **Requête :**
```http
GET /api/projets/1/mouvements?page=1&limit=10&type=depense
Authorization: Bearer {token}
```

#### **Paramètres de requête :**
- `page` (optionnel) : Numéro de page (défaut: 1)
- `limit` (optionnel) : Nombre d'éléments par page (défaut: 20)
- `type` (optionnel) : Filtrer par type (depense, entree, dette, don)

#### **Réponse (200 OK) :**
```json
{
    "mouvements": [
        {
            "id": 1,
            "type": "depense",
            "montant": "500000.00",
            "montantFormatted": "500 000,00 XOF",
            "description": "Achat de matériaux",
            "date": "2024-01-20 14:30:00",
            "statut": "effectue",
            "categorie": {
                "id": 1,
                "nom": "Matériaux"
            }
        },
        {
            "id": 2,
            "type": "entree",
            "montant": "1000000.00",
            "montantFormatted": "1 000 000,00 XOF",
            "description": "Épargne mensuelle",
            "date": "2024-01-15 09:00:00",
            "statut": "effectue",
            "categorie": {
                "id": 2,
                "nom": "Épargne"
            }
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 10,
        "total": 15,
        "pages": 2
    }
}
```


## ⚠️ Codes d'erreur

### Erreurs générales :
- **401** : Non authentifié
- **403** : Accès refusé
- **404** : Projet non trouvé
- **500** : Erreur serveur

### Erreurs de validation :
- **400** : Données manquantes ou invalides
- **400** : Impossible de supprimer (mouvements associés)

## 🎯 Fonctionnalités clés

1. **✅ CRUD complet** : Créer, lire, modifier, supprimer
2. **✅ Recherche** : Recherche dans le nom et la description
3. **✅ Pagination** : Liste paginée des projets
4. **✅ Statistiques** : Calculs automatiques des budgets et soldes
5. **✅ Mouvements** : Liste des mouvements associés au projet
6. **✅ Formatage** : Montants formatés avec la devise de l'utilisateur
7. **✅ Sécurité** : Chaque utilisateur ne voit que ses projets

## 🔄 Workflow des Projets

### 1. **Création d'un Projet**
```http
POST /api/projets
{
    "nom": "Rénovation Maison",
    "description": "Rénovation complète de la cuisine et salle de bain",
    "budgetPrevu": "15000.00"
}
```

### 2. **Association des Mouvements**
Quand vous créez un mouvement, vous pouvez l'associer à un projet :

```http
POST /api/mouvements/depenses
{
    "montantTotal": "500.00",
    "description": "Achat carrelage cuisine",
    "categorie_id": 1,
    "projet_id": 1,  // ← Association au projet
    "lieu": "Magasin Brico",
    "methodePaiement": "carte"
}
```

### 3. **Suivi et Analyse**
Le système calcule automatiquement :
- **Total des dépenses** du projet
- **Total des entrées** du projet  
- **Solde actuel** (entrées - dépenses)
- **Pourcentage du budget utilisé**
- **Nombre de mouvements**

## 📊 Cas d'Usage Pratiques

### **Exemple 1 : Projet "Vacances"**
```json
{
    "nom": "Vacances Été 2024",
    "description": "Voyage en famille au Sénégal",
    "budgetPrevu": "5000.00"
}
```
- **Dépenses** : Billets d'avion, hôtel, restaurants, souvenirs
- **Entrées** : Épargne mensuelle, aide familiale
- **Suivi** : Pourcentage du budget utilisé, solde restant

### **Exemple 2 : Projet "Business"**
```json
{
    "nom": "Lancement E-commerce",
    "description": "Création d'une boutique en ligne",
    "budgetPrevu": "10000.00"
}
```
- **Dépenses** : Développement, marketing, stock
- **Entrées** : Ventes, investissements
- **Suivi** : ROI, rentabilité

## 🎨 Interface Suggérée pour le Frontend

### **Liste des Projets**
```
┌─────────────────────────────────┐
│ 📋 Mes Projets                  │
├─────────────────────────────────┤
│ 🏠 Rénovation Maison            │
│ Budget: 15 000 XOF              │
│ Utilisé: 56.7% (8 500 XOF)     │
│ Solde: -6 500 XOF               │
│                                 │
│ ✈️ Vacances Été 2024           │
│ Budget: 5 000 XOF               │
│ Utilisé: 30% (1 500 XOF)       │
│ Solde: 3 500 XOF                │
└─────────────────────────────────┘
```

### **Détails d'un Projet**
```
┌─────────────────────────────────┐
│ 🏠 Rénovation Maison            │
├─────────────────────────────────┤
│ Budget prévu: 15 000 XOF       │
│ Dépenses: 8 500 XOF (56.7%)    │
│ Entrées: 2 000 XOF             │
│ Solde: -6 500 XOF              │
│                                 │
│ 📊 Mouvements récents           │
│ • Achat carrelage: -500 XOF    │
│ • Épargne mensuelle: +1000 XOF │
│ • Peinture: -300 XOF           │
└─────────────────────────────────┘
```

## 🔗 Intégration avec les Mouvements

### **Créer un mouvement avec projet**
```http
POST /api/mouvements/depenses
{
    "montantTotal": "500.00",
    "description": "Achat matériaux",
    "categorie_id": 1,
    "projet_id": 1,  // ← Lier au projet
    "date": "2024-01-20",
    "lieu": "Magasin Brico",
    "methodePaiement": "carte"
}
```

### **Créer un mouvement sans projet**
```http
POST /api/mouvements/depenses
{
    "montantTotal": "100.00",
    "description": "Courses quotidiennes",
    "categorie_id": 2,
    // Pas de projet_id = mouvement général
    "date": "2024-01-20",
    "lieu": "Supermarché",
    "methodePaiement": "espèces"
}
```

## 📱 Exemples d'Utilisation Frontend

### **1. Page Liste des Projets**
```dart
// Récupérer tous les projets
final projets = await ProjetService.getProjets();

// Rechercher des projets
final projetsRecherche = await ProjetService.getProjets(
  search: "maison"
);
```

### **2. Page Détails d'un Projet**
```dart
// Récupérer les détails d'un projet
final projet = await ProjetService.getProjet(projetId);

// Récupérer les statistiques
final stats = await ProjetService.getStatistiques(projetId);

// Récupérer les mouvements
final mouvements = await ProjetService.getMouvements(projetId);
```

### **3. Créer un Nouveau Projet**
```dart
final nouveauProjet = await ProjetService.createProjet(
  nom: "Nouveau Projet",
  description: "Description du projet",
  budgetPrevu: "10000.00"
);
```

### **4. Modifier un Projet**
```dart
await ProjetService.updateProjet(
  projetId: 1,
  nom: "Nom modifié",
  budgetPrevu: "15000.00"
);
```

## 🚨 Règles de Sécurité

- ✅ **Isolation** : Chaque utilisateur ne voit que ses propres projets
- ✅ **Validation** : Impossible de supprimer un projet avec des mouvements
- ✅ **Authentification** : Tous les endpoints nécessitent un token JWT
- ✅ **Autorisation** : Vérification que le projet appartient à l'utilisateur

## 📋 Checklist Frontend

### **Pages à créer :**
- [ ] **Liste des projets** (avec recherche et pagination)
- [ ] **Détails d'un projet** (avec statistiques)
- [ ] **Création d'un projet** (formulaire)
- [ ] **Modification d'un projet** (formulaire)
- [ ] **Mouvements d'un projet** (liste avec filtres)

### **Fonctionnalités à implémenter :**
- [ ] **Recherche** dans les projets
- [ ] **Pagination** pour les listes
- [ ] **Calculs automatiques** des pourcentages
- [ ] **Formatage des montants** avec devise
- [ ] **Gestion des erreurs** (projet non trouvé, etc.)
- [ ] **Validation** des formulaires
- [ ] **Loading states** pendant les requêtes

### **Intégrations nécessaires :**
- [ ] **Service ProjetService** pour les appels API
- [ ] **Modèle Projet** pour la sérialisation
- [ ] **Gestion des tokens** d'authentification
- [ ] **Gestion des erreurs** réseau
- [ ] **Cache local** pour les données

**L'API des projets est maintenant complètement documentée et prête pour le développement frontend !** 🚀📱
