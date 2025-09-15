# 💰 SoldeTrack - API de Gestion Financière Personnelle

## 🎯 **But de l'Application**

SoldeTrack est une **API REST complète** pour la gestion financière personnelle, conçue pour aider les utilisateurs à suivre leurs finances de manière simple et efficace. L'application permet de gérer les dépenses, les revenus, les dettes, les dons et les projets personnels avec un système de paiements échelonnés.

## 🌟 **Fonctionnalités Principales**

### 📊 **Gestion des Mouvements Financiers**
- **Dépenses** : Suivi des achats quotidiens avec lieu et méthode de paiement
- **Entrées** : Enregistrement des revenus (salaire, ventes, remboursements)
- **Dettes** : Gestion des emprunts à payer et à recevoir avec échéances
- **Dons** : Suivi des cadeaux et aides financières

### 💳 **Paiements Échelonnés**
- Remboursement par tranches des dettes
- Suivi des montants restants
- Calcul automatique des intérêts
- Historique complet des paiements

### 🏷️ **Catégorisation Intelligente**
- **Catégories par défaut** : Alimentation, Transport, Santé, Salaire, etc.
- **Personnalisation** : Ajout de catégories personnalisées
- **Types multiples** : Dépense, Entrée, Dette, Don

### 👥 **Gestion des Contacts**
- Import depuis le téléphone ou création manuelle
- Liaison avec les mouvements financiers
- Suivi des dettes par contact

### 🎯 **Projets Personnels**
- Création de projets avec budget prévu
- Suivi des dépenses et revenus par projet
- Calcul automatique du solde projet

### 💱 **Multi-Devises**
- Support de plusieurs devises (XOF, EUR, USD, GBP)
- Choix de la devise par défaut à l'inscription
- Formatage automatique des montants

### 📸 **Profils Utilisateurs**
- Upload de photos de profil sécurisé
- Gestion complète du profil utilisateur
- Authentification JWT sécurisée

## 🏗️ **Architecture Technique**

### **Backend**
- **Framework** : Symfony 7.3
- **Base de données** : MySQL avec Doctrine ORM
- **Authentification** : JWT (Lexik JWT Authentication Bundle)
- **API** : REST avec validation des données
- **Sécurité** : Upload sécurisé, validation stricte

### **Entités Principales**
```
User (Utilisateur)
├── Devise (Devise par défaut)
├── Categories (Catégories personnalisées)
├── Contacts (Contacts financiers)
├── Projets (Projets personnels)
└── Mouvements (Transactions financières)
    ├── Depense (Dépenses)
    ├── Entree (Revenus)
    ├── Dette (Dettes)
    └── Don (Dons)
    └── Paiements (Paiements échelonnés)
```

## 🚀 **Workflow Utilisateur**

### **1. Inscription**
- Création du compte avec devise par défaut
- Génération automatique des catégories par défaut
- Upload optionnel de photo de profil

### **2. Configuration**
- Import ou création de contacts
- Personnalisation des catégories
- Création de projets personnels

### **3. Utilisation Quotidienne**
- Enregistrement des mouvements financiers
- Suivi des paiements échelonnés
- Consultation du dashboard et statistiques

### **4. Suivi et Analyse**
- Visualisation des soldes par catégorie
- Suivi des projets et budgets
- Historique complet des transactions

## 📱 **Cas d'Usage Concrets**

### **Exemple : Gestion d'une Dette**
```
1. Créer un mouvement "Dette à payer" de 50,000 XOF
2. Lier à un contact "Tailleur"
3. Catégoriser comme "Habits"
4. Payer 20,000 XOF aujourd'hui
5. Payer 30,000 XOF la semaine prochaine
6. Dette soldée automatiquement
```

### **Exemple : Suivi d'un Projet**
```
1. Créer le projet "Élevage Volaille" (Budget: 100,000 XOF)
2. Enregistrer les dépenses (poussins, nourriture)
3. Enregistrer les revenus (vente de volailles)
4. Suivi automatique du solde projet
```

## 🔧 **Installation et Configuration**

### **Prérequis**
- PHP 8.2+
- MySQL 5.7+
- Composer
- Symfony CLI (optionnel)

### **Installation**
```bash
# Cloner le projet
git clone [repository-url]
cd solde-track-api

# Installer les dépendances
composer install

# Configurer la base de données
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Initialiser les données par défaut
php bin/console app:init-default-data

# Générer les clés JWT
php bin/console lexik:jwt:generate-keypair
```

### **Démarrage**
```bash
# Serveur de développement
symfony serve
# ou
php -S localhost:8000 -t public
```

## 📚 **Documentation API**

La documentation complète de l'API est disponible dans le fichier `API_DOCUMENTATION.md` avec :
- Tous les endpoints disponibles
- Exemples de requêtes et réponses
- Codes d'erreur et validation
- Authentification JWT

## 🛡️ **Sécurité**

- **Authentification JWT** pour tous les endpoints protégés
- **Validation stricte** des données d'entrée
- **Upload sécurisé** des photos de profil
- **Protection contre l'injection SQL** via Doctrine ORM
- **Headers de sécurité** pour les fichiers uploadés

## 🎨 **Interface Utilisateur**

L'API est conçue pour être utilisée avec :
- **Applications mobiles** (React Native, Flutter)
- **Applications web** (React, Vue.js, Angular)
- **Clients API** (Postman, Insomnia)

## 🔮 **Évolutions Futures**

- **Rapports PDF** automatiques
- **Notifications** de rappels de paiement
- **Graphiques** et visualisations avancées
- **Export/Import** de données
- **API de conversion** de devises en temps réel
- **Mode hors ligne** avec synchronisation

## 👥 **Équipe de Développement**

Développé avec ❤️ pour simplifier la gestion financière personnelle.

## 📄 **Licence**

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.

---

**SoldeTrack** - Votre compagnon financier personnel ! 💰✨
