# 📅 API Timestamps - Gestion des dates de création et modification

## 🎯 Vue d'ensemble

Toutes les entités principales de l'API incluent maintenant des timestamps automatiques pour le suivi des dates de création et de modification.

## 📊 Entités avec timestamps

### **Entités principales :**
- ✅ **Mouvement** (et ses sous-classes : Dette, Depense, Entree, Don)
- ✅ **User** (utilisateurs)
- ✅ **Projet** (projets)
- ✅ **Compte** (comptes bancaires)
- ✅ **Paiement** (paiements)

### **Champs automatiques :**
- `createdAt` : Date de création (automatique)
- `updatedAt` : Date de dernière modification (automatique)

## 🔧 Fonctionnement automatique

### **1. Création d'entité**
```php
// Les timestamps sont automatiquement définis
$mouvement = new Mouvement();
// createdAt = maintenant
// updatedAt = maintenant
```

### **2. Modification d'entité**
```php
// updatedAt est automatiquement mis à jour
$mouvement->setDescription("Nouvelle description");
$entityManager->flush();
// updatedAt = maintenant (automatique)
```

### **3. Trait automatique**
```php
use App\Entity\Traits\TimestampableTrait;

class MonEntite
{
    use TimestampableTrait;
    // Les timestamps sont automatiquement gérés
}
```

## 📋 Réponses API avec timestamps

### **Exemple de réponse Mouvement :**
```json
{
  "id": 1,
  "type": "sortie",
  "montantTotal": "1000.00",
  "date": "2024-01-15 10:30:00",
  "createdAt": "2024-01-15 10:30:00",
  "updatedAt": "2024-01-15 10:30:00",
  "description": "Achat alimentaire"
}
```

### **Exemple de réponse User :**
```json
{
  "id": 1,
  "nom": "Dupont",
  "email": "dupont@example.com",
  "createdAt": "2024-01-01 08:00:00",
  "updatedAt": "2024-01-15 14:30:00"
}
```

### **Exemple de réponse Projet :**
```json
{
  "id": 1,
  "nom": "Projet Web",
  "budgetPrevu": "50000.00",
  "createdAt": "2024-01-10 09:00:00",
  "updatedAt": "2024-01-15 16:45:00"
}
```

## 🚀 Avantages des timestamps

### **1. Audit trail**
- ✅ **Suivi complet** des modifications
- ✅ **Historique** des changements
- ✅ **Traçabilité** des données

### **2. Synchronisation**
- ✅ **Dernière modification** facilement identifiable
- ✅ **Cache invalidation** basée sur les dates
- ✅ **Synchronisation** entre clients

### **3. Analytics**
- ✅ **Métriques** de création/modification
- ✅ **Tendances** d'utilisation
- ✅ **Performance** des entités

## 🔄 Mise à jour automatique

### **Pré-Update Hook**
```php
#[ORM\PreUpdate]
public function setUpdatedAtValue(): void
{
    $this->updatedAt = new \DateTime();
}
```

### **Initialisation dans le constructeur**
```php
public function __construct()
{
    $this->createdAt = new \DateTime();
    $this->updatedAt = new \DateTime();
}
```

## 📈 Utilisation dans les requêtes

### **Filtrage par date de création :**
```php
// Mouvements créés aujourd'hui
$qb->andWhere('m.createdAt >= :today')
   ->setParameter('today', new \DateTime('today'));
```

### **Tri par dernière modification :**
```php
// Derniers mouvements modifiés
$qb->orderBy('m.updatedAt', 'DESC');
```

### **Recherche de modifications récentes :**
```php
// Modifications des dernières 24h
$qb->andWhere('m.updatedAt >= :yesterday')
   ->setParameter('yesterday', new \DateTime('-1 day'));
```

## 🛠️ Migration de la base de données

### **Colonnes ajoutées :**
```sql
ALTER TABLE mouvement ADD created_at DATETIME NOT NULL;
ALTER TABLE mouvement ADD updated_at DATETIME NOT NULL;
ALTER TABLE user ADD created_at DATETIME NOT NULL;
ALTER TABLE user ADD updated_at DATETIME NOT NULL;
ALTER TABLE projet ADD created_at DATETIME NOT NULL;
ALTER TABLE projet ADD updated_at DATETIME NOT NULL;
ALTER TABLE compte ADD created_at DATETIME NOT NULL;
ALTER TABLE compte ADD updated_at DATETIME NOT NULL;
ALTER TABLE paiement ADD created_at DATETIME NOT NULL;
ALTER TABLE paiement ADD updated_at DATETIME NOT NULL;
```

## ✅ Résumé

Les timestamps automatiques offrent :
- 🔄 **Mise à jour automatique** de `updatedAt`
- 📅 **Initialisation automatique** de `createdAt`
- 🎯 **Traçabilité complète** des entités
- 📊 **Données d'audit** intégrées
- 🚀 **Performance optimisée** avec les hooks Doctrine

Toutes les entités principales bénéficient maintenant d'un suivi temporel complet et automatique !
