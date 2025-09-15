# 🚀 Guide d'Installation - SoldeTrack API

## 📋 **Prérequis**

### **Système**
- **PHP** : 8.2 ou supérieur
- **MySQL** : 5.7 ou supérieur
- **Composer** : 2.0 ou supérieur
- **Symfony CLI** : 5.0 ou supérieur (optionnel)

### **Extensions PHP Requises**
```bash
# Vérifier les extensions
php -m | grep -E "(pdo|mysql|json|mbstring|openssl|curl|zip|gd)"
```

## 🔧 **Installation Rapide**

### **1. Cloner le Projet**
```bash
git clone [votre-repository]
cd solde-track-api
```

### **2. Installer les Dépendances**
```bash
composer install
```

### **3. Configuration de l'Environnement**
```bash
# Copier le fichier d'environnement
cp .env .env.local

# Éditer la configuration
nano .env.local
```

**Configuration minimale :**
```env
# Base de données
DATABASE_URL="mysql://username:password@127.0.0.1:3306/solde_track?serverVersion=8.0.32&charset=utf8mb4"

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here
```

### **4. Créer la Base de Données**
```bash
# Créer la base de données
php bin/console doctrine:database:create

# Vérifier la connexion
php bin/console doctrine:database:create --if-not-exists
```

### **5. Générer les Migrations**
```bash
# Créer les migrations
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate --no-interaction
```

### **6. Générer les Clés JWT**
```bash
# Générer les clés JWT
php bin/console lexik:jwt:generate-keypair
```

### **7. Initialiser les Données par Défaut**
```bash
# Créer les devises par défaut
php bin/console app:init-default-data
```

### **8. Démarrer le Serveur**
```bash
# Avec Symfony CLI (recommandé)
symfony serve

# Ou avec PHP intégré
php -S localhost:8000 -t public
```

## ✅ **Vérification de l'Installation**

### **Test de l'API**
```bash
# Tester l'endpoint des devises
curl http://localhost:8000/api/auth/devises

# Réponse attendue
{
    "devises": [
        {
            "id": 1,
            "code": "XOF",
            "nom": "Franc CFA"
        },
        {
            "id": 2,
            "code": "EUR",
            "nom": "Euro"
        }
    ]
}
```

### **Test d'Inscription**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "motdepasse123",
    "nom": "Dupont",
    "prenoms": "Jean",
    "devise_id": 1
  }'
```

## 🐛 **Dépannage**

### **Erreur de Base de Données**
```bash
# Vérifier la connexion
php bin/console doctrine:database:create --if-not-exists

# Réinitialiser la base
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

### **Erreur JWT**
```bash
# Régénérer les clés
rm -rf config/jwt/
php bin/console lexik:jwt:generate-keypair
```

### **Erreur de Permissions**
```bash
# Donner les permissions d'écriture
chmod -R 755 var/
chmod -R 755 public/uploads/
```

### **Erreur de Cache**
```bash
# Vider le cache
php bin/console cache:clear
php bin/console cache:clear --env=prod
```

## 🔧 **Configuration Avancée**

### **Serveur Web (Apache/Nginx)**

#### **Apache (.htaccess)**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

#### **Nginx**
```nginx
server {
    listen 80;
    server_name solde-track.local;
    root /path/to/solde-track-api/public;
    
    location / {
        try_files $uri /index.php$is_args$args;
    }
    
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }
}
```

### **Variables d'Environnement**
```env
# Production
APP_ENV=prod
APP_SECRET=your_secret_key_here

# Base de données
DATABASE_URL="mysql://user:pass@localhost:3306/solde_track"

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase

# Upload
UPLOAD_MAX_SIZE=5M
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,webp
```

## 📊 **Monitoring et Logs**

### **Logs de l'Application**
```bash
# Voir les logs en temps réel
tail -f var/log/dev.log

# Logs de production
tail -f var/log/prod.log
```

### **Monitoring des Performances**
```bash
# Profiler Symfony
# Accéder à http://localhost:8000/_profiler

# Vérifier les requêtes SQL
php bin/console doctrine:query:sql "SHOW PROCESSLIST"
```

## 🔒 **Sécurité**

### **Configuration de Production**
```env
# Désactiver le debug
APP_ENV=prod
APP_DEBUG=false

# Changer le secret
APP_SECRET=your_very_long_secret_key_here

# HTTPS uniquement
TRUSTED_PROXIES=127.0.0.1
TRUSTED_HOSTS=localhost
```

### **Permissions de Fichiers**
```bash
# Permissions sécurisées
chmod 644 config/jwt/private.pem
chmod 644 config/jwt/public.pem
chmod 755 public/uploads/
chmod 755 var/
```

## 🚀 **Déploiement**

### **Déploiement Automatique**
```bash
# Script de déploiement
#!/bin/bash
git pull origin main
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
```

### **Docker (Optionnel)**
```dockerfile
FROM php:8.2-fpm
RUN docker-php-ext-install pdo pdo_mysql
COPY . /var/www/html
WORKDIR /var/www/html
RUN composer install --no-dev
```

## 📞 **Support**

### **En Cas de Problème**
1. Vérifier les logs : `var/log/dev.log`
2. Tester la base de données : `php bin/console doctrine:database:create --if-not-exists`
3. Vérifier les permissions : `ls -la var/ public/uploads/`
4. Redémarrer le serveur : `symfony serve`

### **Commandes Utiles**
```bash
# Vérifier la configuration
php bin/console debug:config

# Vérifier les routes
php bin/console debug:router

# Vérifier les services
php bin/console debug:container

# Vérifier la base de données
php bin/console doctrine:schema:validate
```

---

**Installation terminée !** 🎉 Votre API SoldeTrack est prête à être utilisée !
