# Base de Données - SoldeTrack

## Scripts de Devises par Défaut

### 📁 Fichiers disponibles :

1. **`devises_default.sql`** - Script SQL avec toutes les devises
2. **`../src/Command/InsertDefaultDevisesCommand.php`** - Commande Symfony
3. **`../scripts/insert_devises.php`** - Script PHP simple

### 🚀 Méthodes d'insertion :

#### **Méthode 1 : Commande Symfony (Recommandée)**
```bash
php bin/console app:insert-default-devises
```

#### **Méthode 2 : Script PHP simple**
```bash
php scripts/insert_devises.php
```

#### **Méthode 3 : SQL direct**
```bash
mysql -u root -p solde_track < database/devises_default.sql
```

### 📊 Devises incluses :

#### **Devises Africaines (20)**
- XOF, XAF, DZD, EGP, MAD, TND, NGN, ZAR, KES, GHS, etc.

#### **Devises Internationales (20)**
- USD, EUR, GBP, JPY, CHF, CAD, AUD, NZD, etc.

#### **Devises Asiatiques (20)**
- CNY, INR, KRW, SGD, HKD, TWD, THB, MYR, etc.

#### **Devises Moyen-Orient (15)**
- SAR, AED, QAR, KWD, BHD, OMR, JOD, etc.

#### **Devises Amériques (19)**
- BRL, ARS, CLP, COP, PEN, UYU, PYG, etc.

#### **Devises Océaniennes (6)**
- FJD, PGK, WST, TOP, VUV, SBD

#### **Cryptomonnaies (10)**
- BTC, ETH, USDT, BNB, ADA, SOL, XRP, etc.

#### **Devises Spéciales (5)**
- XDR, XAG, XAU, XPD, XPT

### ✅ **Total : 115 devises**

### 🔧 Prérequis :

1. Base de données créée
2. Table `devise` existante
3. Variables d'environnement configurées (`.env`)

### 📝 Structure de la table `devise` :

```sql
CREATE TABLE devise (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,
    nom VARCHAR(100) NOT NULL
);
```

### 🎯 Utilisation dans l'application :

Les devises sont automatiquement disponibles via :
- `GET /api/auth/devises` - Liste toutes les devises
- `GET /api/auth/devises/populaires` - Devises populaires
- Sélection lors de l'inscription
- Gestion des comptes et transferts
